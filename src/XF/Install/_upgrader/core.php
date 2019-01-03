<?php

// Note: much of the code in this file is intentionally independent and possibly repeated. This is to reduce
// dependencies which may cause problems when upgrading.

/**
 * Class XFUpgrader
 *
 * Provides the primary logic for handling the upgrade.
 */
class XFUpgrader
{
	// set this to true to make debugging the upgrader simpler
	const DEBUGGING = false;

	protected $upgradeKey;

	/**
	 * @var ZipArchive|null
	 */
	protected $zip;
	
	protected $zipVersionId;

	public function canAttempt(&$error = null)
	{
		if (!class_exists('ZipArchive'))
		{
			$error = 'ZipArchive class does not exist.';
			return false;
		}

		$config = \XF::app()->config();
		if (!$config['enableOneClickUpgrade'])
		{
			$error = 'One-click upgrades have not been enabled.';
			return false;
		}

		if (!self::DEBUGGING)
		{
			if ($config['development']['enabled'])
			{
				$error = 'This is a development install (via dev mode).';
				return false;
			}

			$xfHashes = \XF::getAddOnDirectory() . '/XF/hashes.json';
			if (!file_exists($xfHashes))
			{
				$error = 'This is a development install (via missing hashes).';
				return false;
			}
		}

		if (!is_writable(__FILE__))
		{
			$error = 'The files are not writable.';
			return false;
		}

		$isWindows = (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN');
		if (!$isWindows)
		{
			// Only allow the upgrade to run if we're not likely to cause mixed file ownership.
			// This is possibly over restrictive. (If relaxed in the future, we should special case
			// to prevent using root unless the files are owned by root.)

			$uid = function_exists('posix_getuid') ? posix_getuid() : fileowner(\XF\Util\File::getTempFile());
			if ($uid !== fileowner(__FILE__))
			{
				$error = 'The files are owned by a different user than the upgrade is running as.';
				return false;
			}
		}

		return true;
	}

	public function setUpgradeKey($upgradeKey, &$error = null, $fullVerification = true)
	{
		$error = null;

		if (!class_exists('ZipArchive'))
		{
			$error = 'ZipArchive class does not exist. Please upgrade manually.';
			return false;
		}
		
		$upgradeKey = preg_replace('#[^a-z0-9_\-]#i', '', $upgradeKey);
		if (!$upgradeKey)
		{
			return false;
		}

		$upgradeFile = $this->getUpgradeFile($upgradeKey);
		if (!file_exists($upgradeFile))
		{
			return false;
		}

		$zip = new ZipArchive();
		if ($zip->open($upgradeFile) !== true)
		{
			return false;
		}
		
		if (!$this->validateZipFile($zip, $error, $zipVersionId, $fullVerification))
		{
			return false;
		}

		$this->upgradeKey = $upgradeKey;
		$this->zip = $zip;
		$this->zipVersionId = $zipVersionId;

		return true;
	}

	public function validateFile($file, &$error = null, $fullVerification = true)
	{
		$zip = new ZipArchive();
		if ($zip->open($file) !== true)
		{
			$error = 'Error opening file.';
			return false;
		}

		return $this->validateZipFile($zip, $error, $zipVersionid, $fullVerification);
	}

	protected function validateZipFile(ZipArchive $zip, &$error = null, &$zipVersionId = null, $fullVerification = true)
	{
		$xfClass = $zip->getFromName('upload/src/XF.php');
		if (!$xfClass)
		{
			$error = 'The zip file does not appear to be a valid XenForo release.';
			return false;
		}

		if (!self::DEBUGGING)
		{
			if ($zip->locateName('upload/src/addons/XF/hashes.json') === false)
			{
				$error = 'The zip file does not appear to be a valid XenForo release.';
				return false;
			}
		}
		
		if (!preg_match('#public\s+static\s+\$versionId\s*=\s*(\d+)\s*;#', $xfClass, $match))
		{
			$error = 'The zip file does not appear to contain the expected contents.';
			return false;
		}
		
		$zipVersionId = intval($match[1]);

		if ($zipVersionId < \XF::$versionId)
		{
			$error = 'The zip file contains a version older than the version currently in use. Cannot continue.';
			return false;
		}

		if ($zipVersionId >= 3000000)
		{
			// assume that 3.0 won't be supported with this unless we decide otherwise
			$error = 'The zip file contains a version that cannot be upgraded to automatically.';
			return false;
		}

		if ($fullVerification)
		{
			$requirements = $zip->getFromName('upload/src/XF/Install/_upgrader/requirements.json');
			if ($requirements)
			{
				$reqJson = json_decode($requirements, true);
				if (!$this->checkRequirements($reqJson, $errors))
				{
					$error = 'The following requirements were not met for this upgrade: ' . implode(' ', $errors);
					return false;
				}
			}
		}
		
		return true;
	}

	public function checkRequirements(array $requirements, &$errors = [])
	{
		$errors = [];

		foreach ($requirements AS $productKey => $requirement)
		{
			if (is_array($requirement))
			{
				list($version, $printable) = $requirement;
			}
			else
			{
				$version = $requirement;
				$printable = null;
			}

			$enabled = false;
			$versionValid = false;

			if (strpos($productKey, 'php-ext/') === 0)
			{
				$parts = explode('/', $productKey, 2);
				$enabled = extension_loaded($parts[1]);

				if ($version === '*')
				{
					$versionValid = true;
				}
				else
				{
					$versionValid = (version_compare(phpversion($parts[1]), $version) === 1);
				}

				if ($printable === null)
				{
					$printable = "PHP extension $parts[1]";
					if ($version !== '*')
					{
						$printable .= " $version+";
					}
				}
			}
			else if ($productKey === 'php')
			{
				$enabled = true;
				$versionValid = (version_compare(phpversion(), $version) === 1);

				if ($printable === null)
				{
					$printable = "PHP $version+";
				}
			}
			else if ($productKey === 'mysql')
			{
				$mySqlVersion = \XF::db()->getServerVersion();
				if ($mySqlVersion)
				{
					$enabled = true;
					$versionValid = (version_compare(strtolower($mySqlVersion), $version) === 1);
				}

				if ($printable === null)
				{
					$printable = "MySQL $version+";
				}
			}
			else
			{
				throw new \LogicException("Unknown requirement check $productKey");
			}
			// TODO: expand to PHP function checks?

			if (!$enabled || !$versionValid)
			{
				$errors[] = "$printable is required.";
			}
		}

		return $errors ? false : true;
	}

	public function getHashes()
	{
		$file = \XF::getAddOnDirectory() . '/XF/hashes.json';
		if (!file_exists($file))
		{
			return null;
		}

		return json_decode(file_get_contents($file), true);
	}
	
	public function compareHashes()
	{
		$existingHashes = $this->getHashes();
		if ($existingHashes)
		{
			return $this->getExtractor()->compareHashes($existingHashes);
		}
		else
		{
			return null;
		}
	}
	
	public function checkWritable(array $changeset = null, &$failures = [])
	{
		return $this->getExtractor()->checkWritable($changeset, $failures);
	}

	public function copyFiles(array $changeset = null, $start = 0, $maxTime = null)
	{
		return $this->getExtractor()->copyFiles($changeset, $start, $maxTime);
	}

	public function copyNamedFiles(array $files, &$error)
	{
		return $this->getExtractor()->copyNamedFiles($files, $error);
	}

	/**
	 * @return XFUpgraderExtractor
	 */
	protected function getExtractor()
	{
		if (!$this->zip)
		{
			throw new \LogicException("Zip not opened yet");
		}

		return new XFUpgraderExtractor($this->zip);
	}

	public function cleanUp()
	{
		if (!$this->upgradeKey)
		{
			return;
		}

		if ($this->zip)
		{
			$this->zip->close();
			$this->zip = null;
		}

		if (!self::DEBUGGING)
		{
			@unlink($this->getUpgradeFile());
		}
	}

	protected function getUpgradeFile($upgradeKey = null)
	{
		$upgradeKey = $upgradeKey ?: $this->upgradeKey;

		$dir = \XF\Util\File::getTempDir();
		return "$dir/upgrade-{$upgradeKey}.zip";
	}

	public function getZipVersionId()
	{
		return $this->zipVersionId;
	}
}

/**
 * Class XFUpgraderExtractor
 *
 * Manages extracting files from the upgrade zip.
 *
 * Striking similarity to src/XF/Service/AddOnArchive/Extractor.php. Changes should be mirrored as necessary.
 */
class XFUpgraderExtractor
{
	/**
	 * @var ZipArchive
	 */
	protected $zip;

	public function __construct(ZipArchive $zip)
	{
		$this->zip = $zip;
	}

	public function compareHashes(array $existingHashes)
	{
		$newHashes = $this->getNewHashes();
		if (!$newHashes)
		{
			return null;
		}

		$changes = [];
		foreach ($newHashes AS $file => $newHash)
		{
			if (!isset($existingHashes[$file]))
			{
				$changes[$file] = 'create';
			}
			else if ($newHash !== $existingHashes[$file])
			{
				$changes[$file] = 'update';
			}
		}

		$changes[preg_replace('#^upload/#', '', $this->getHashFileName())] = 'update';

		foreach ($existingHashes AS $oldFile => $null)
		{
			if (!isset($newHashes[$oldFile]))
			{
				$changes[$oldFile] = 'delete';
			}
		}

		return $changes;
	}

	public function checkWritable(array $changeset = null, &$failures = [])
	{
		$zip = $this->zip;
		$failures = [];

		for ($i = 0; $i < $zip->numFiles; $i++)
		{
			$zipFileName = $zip->getNameIndex($i);
			$fsFileName = $this->getFsFileNameFromZipName($zipFileName);
			if ($fsFileName === null)
			{
				continue;
			}

			if (is_array($changeset) && !isset($changeset[$fsFileName]))
			{
				// we're not changing this file
				continue;
			}

			if (!\XF\Util\File::isWritable($this->getFinalFsFileName($fsFileName)))
			{
				$failures[] = $fsFileName;
			}
		}

		return $failures ? false : true;
	}

	public function copyFiles(array $changeset = null, $start = 0, $maxTime = null)
	{
		$zip = $this->zip;
		$lastComplete = $start;

		$s = microtime(true);

		for ($i = $start; $i < $zip->numFiles; $i++)
		{
			$lastComplete = $i;

			$zipFileName = $zip->getNameIndex($i);
			$targetWritten = $this->writeFileFromZip(
				$zipFileName,
				function($fsFileName) use ($changeset)
				{
					return (
						!is_array($changeset)
						|| isset($changeset[$fsFileName])
					);
				}
			);

			if (!$targetWritten)
			{
				return [
					'status' => 'error',
					'error' => "Failed write to {$zipFileName}"
				];
			}

			if ($maxTime !== null && (microtime(true) - $s) > $maxTime)
			{
				break;
			}
		}

		$complete = ($i >= $zip->numFiles);

		if ($complete)
		{
			// if we don't have a new hashes file, we need to remove the old one if it exists as it will be wrong
			$hashZipFileName = $this->getHashFileName();
			if ($zip->locateName($hashZipFileName) === false)
			{
				$fsFileName = $this->getFsFileNameFromZipName($hashZipFileName);
				$finalFileName = $this->getFinalFsFileName($fsFileName);
				if (file_exists($finalFileName) && !@unlink($finalFileName))
				{
					return [
						'status' => 'error',
						'error' => "Failed write to {$fsFileName}"
					];
				}
			}
		}

		return [
			'status' => ($complete ? 'complete' : 'incomplete'),
			'last' => $lastComplete,
			'percent' => ($complete || !$zip->numFiles) ? 100 : 100 * ($lastComplete / $zip->numFiles)
		];
	}

	public function copyNamedFiles(array $files, &$error)
	{
		if (!$files)
		{
			return true;
		}

		$zip = $this->zip;

		$regexParts = [];
		foreach ($files AS $file)
		{
			$part = preg_quote($file, '#');
			$part = str_replace('\\*', '.*', $part);
			$regexParts[] = $part;
		}
		$filesRegex = '#^(' . implode('|', $regexParts) . ')$#';

		for ($i = 0; $i < $zip->numFiles; $i++)
		{
			$zipFileName = $zip->getNameIndex($i);
			$targetWritten = $this->writeFileFromZip(
				$zipFileName,
				function($fsFileName) use ($filesRegex)
				{
					return preg_match($filesRegex, $fsFileName);
				}
			);

			if (!$targetWritten)
			{
				$error = "Failed write to {$zipFileName}";
				return false;
			}
		}

		return true;
	}

	protected function writeFileFromZip($zipFileName, \Closure $checkWriteNeeded = null)
	{
		$fsFileName = $this->getFsFileNameFromZipName($zipFileName);
		if ($fsFileName === null)
		{
			// not a writable file - consider fine
			return true;
		}

		$finalFileName = $this->getFinalFsFileName($fsFileName);

		if ($checkWriteNeeded)
		{
			$isWriteNeeded = $checkWriteNeeded($fsFileName, $finalFileName, $zipFileName);
			if (!$isWriteNeeded)
			{
				// no action required, so consider fine
				return true;
			}
		}

		$dataStream = $this->zip->getStream($zipFileName);
		return @\XF\Util\File::writeFile($finalFileName, $dataStream, false);
	}

	protected function getNewHashes()
	{
		$newHashesJson = $this->zip->getFromName($this->getHashFileName());
		if (!$newHashesJson)
		{
			return null;
		}

		return json_decode($newHashesJson, true);
	}

	protected function getFsFileNameFromZipName($fileName)
	{
		if (substr($fileName, -1) === '/')
		{
			// this is a directory we can just skip this
			return null;
		}

		if (!preg_match("#^upload/.#", $fileName))
		{
			// file outside of "upload" so we can just skip this
			return null;
		}

		return substr($fileName, 7); // remove "upload/"
	}

	protected function getFinalFsFileName($fileName)
	{
		return \XF::getRootDirectory() . \XF::$DS . $fileName;
	}

	protected function getHashFileName()
	{
		return "upload/src/addons/XF/hashes.json";
	}
}

/**
 * Class XFUpgraderWeb
 *
 * Provides the logic for triggering the upgrade via the web (with page refreshes, etc).
 */
class XFUpgraderWeb
{
	/**
	 * @var \XF\Http\Request
	 */
	protected $request;

	/**
	 * @var \XF\Template\Templater
	 */
	protected $templater;

	/**
	 * @var XFUpgrader
	 */
	protected $upgrader;

	protected $key;
	protected $state = [];

	public function __construct(\XF\App $app)
	{
		$this->request = $app->request();
		$this->templater = $app->templater();
		$this->upgrader = new XFUpgrader();
	}

	public function run()
	{
		$request = $this->request;
		if (!$request->isPost())
		{
			header('Location: index.php?upgrade/');
			return;
		}

		$key = $request->filter('key', 'string');
		if (!$this->upgrader->setUpgradeKey($key, $error))
		{
			if ($error)
			{
				$this->outputError($error . ' Please upgrade manually.');
			}
			else
			{
				$this->outputError('Invalid key. Please upgrade manually.');
			}
			return;
		}

		$this->key = $key;

		\XF::app()->error()->setForceShowTrace(true);

		if (!$this->upgrader->canAttempt($error))
		{
			$this->outputError("Cannot attempt: $error Please upgrade manually.");
			$this->cleanUp();
			return;
		}

		$step = $request->filter('step', 'string');
		if (!$step)
		{
			$step = 'init';
		}
		$stepMethod = 'step' . $step;

		if (!method_exists($this, $stepMethod))
		{
			$this->outputError("Failed to find step $step. Please upgrade manually.");
			return;
		}

		$this->state = $request->filter('state', 'json-array');
		$params = $request->filter('params', 'json-array');

		$result = $this->$stepMethod($params);

		if (is_array($result))
		{
			$newStep = $step;
			$newParams = $result;
		}
		else if (is_string($result))
		{
			$newStep = $result;
			$newParams = [];
		}
		else if ($result === false)
		{
			// indicates we're already generated the page so stop
			return;
		}
		else
		{
			throw new \LogicException("{$stepMethod} didn't return the expected data");
		}

		$ticks = $this->request->filter('ticks', 'uint');

		$this->outputResult($newStep, $newParams, $ticks);
	}

	protected function stepInit(array $params)
	{
		// note that this is redone after the self-update
		$hashChanges = $this->upgrader->compareHashes();
		if (!$this->upgrader->checkWritable($hashChanges, $failures))
		{
			$this->outputError('Not all files are writable. Please upgrade manually.');
			$this->cleanUp();
			return false;
		}

		return 'selfupdate';
	}

	protected function stepSelfUpdate(array $params)
	{
		$files = [
			'src/XF/Install/_upgrader/*',
			'install/oc-upgrader.php'
		];

		if (!$this->upgrader->copyNamedFiles($files, $error))
		{
			$this->outputError('One or more files failed to copy. Please upgrade manually.');
			$this->cleanUp();
			return false;
		}

		\XF\Util\Php::resetOpcache();

		return 'reinit';
	}

	protected function stepReInit(array $params)
	{
		// redo this after the self update to avoid bugs
		$this->state['changes'] = $this->upgrader->compareHashes();

		return 'copy';
	}

	protected function stepCopy(array $params)
	{
		$params = array_replace([
			'start' => 0
		], $params);
		$start = max(0, intval($params['start']));

		if (isset($this->state['changes']) && is_array($this->state['changes']))
		{
			$hashChanges = $this->state['changes'];
		}
		else
		{
			$hashChanges = null;
		}

		$result = $this->upgrader->copyFiles($hashChanges, $start, \XF::app()->config('jobMaxRunTime'));

		switch ($result['status'])
		{
			case 'error':
				$this->outputError('One or more files failed to copy. Please upgrade manually.');
				$this->cleanUp();
				return false;

			case 'incomplete':
				$params['start'] = $result['last'] + 1;
				$params['percent'] = $result['percent'];
				return $params;

			case 'complete':
				\XF\Util\Php::resetOpcache();
				return 'complete';

			default:
				throw new \LogicException("Unknown result from copy '$result[status]'");
		}
	}

	protected function stepComplete(array $params)
	{
		$this->cleanUp();

		$app = \XF::app();
		$basicUpgradeRedirect = true;

		if ($app instanceof \XF\Install\App)
		{
			$app->setupUpgradeSession();
			if (\XF::visitor()->is_admin)
			{
				// we have an install session
				$basicUpgradeRedirect = false;
			}
		}

		$upgrader = new \XF\Install\Upgrader($app);
		if ($upgrader->isCliRecommended())
		{
			// if we recommend CLI upgrades, force this
			$basicUpgradeRedirect = true;
		}

		if ($basicUpgradeRedirect)
		{
			header('Location: index.php?upgrade/');
		}
		else
		{
			// output a page to post into the upgrade system
			$content = $this->templater->renderTemplate('upgrade_oc_complete');
			$this->outputContainer($content);
		}

		return false;
	}

	protected function outputResult($step, array $params, $lastTicks)
	{
		$state = $this->state;
		$ticks = max(0, intval($lastTicks)) + 1;

		$content = $this->templater->renderTemplate('upgrade_oc_step', [
			'key' => $this->key,
			'step' => $step,
			'ticks' => $ticks,
			'state' => $state,
			'params' => $params
		]);
		$this->outputContainer($content);
	}

	protected function outputError($message, $code = 400)
	{
		$content = $this->templater->renderTemplate('error', [
			'error' => $message
		]);
		$this->outputContainer($content, $code);
	}

	protected function outputContainer($content, $code = 200)
	{
		$pageParams = $this->templater->pageParams;
		$pageParams['content'] = $content;

		header('Content-type: text/html; charset=utf-8', true, $code);
		echo $this->templater->renderTemplate('PAGE_CONTAINER', $pageParams);
	}

	protected function cleanUp()
	{
		$this->upgrader->cleanUp();
	}

	/**
	 * Factory creation method. This is to move as much code into this file as possible to allow flexibility
	 * with future changes.
	 *
	 * @param string $rootDir Root of XF install
	 *
	 * @return XFUpgraderWeb
	 */
	public static function create($rootDir)
	{
		require($rootDir . '/src/XF.php');
		XF::start($rootDir);

		$app = XF::setupApp('XF\Install\App');
		$app->start();

		return new XFUpgraderWeb($app);
	}
}