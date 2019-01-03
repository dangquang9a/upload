<?php

namespace XF\Http;

use XF\App;

class MetadataFetcher
{
	/**
	 * @var App
	 */
	protected $app;

	/**
	 * @var Reader
	 */
	protected $reader;

	protected $limits = [
		'time' => 5,
		'bytes' => 1.5 * 1024 * 1024
	];

	public function __construct(App $app, Reader $reader)
	{
		$this->app = $app;
		$this->reader = $reader;
	}

	public function setLimits(array $limits)
	{
		$this->limits = array_replace($this->limits, $limits);
	}

	/**
	 * @param      $requestUrl
	 * @param null $error
	 * @param null $startTime
	 * @param null $timeLimit
	 *
	 * @return bool|\XF\Http\Metadata
	 * @throws \Exception
	 */
	public function fetch($requestUrl, &$error = null, $startTime = null, $timeLimit = null)
	{
		$requestUrl = $this->getValidRequestUrl($requestUrl, $startTime, $timeLimit);
		if (!$requestUrl)
		{
			$error = 'Could not get a valid request URL from: ' . htmlspecialchars($requestUrl);
			return false;
		}

		$response = $this->reader->getUntrusted(
			$requestUrl,
			$this->limits,
			null,
			[],
			$error
		);
		if (!$response)
		{
			return false;
		}

		if ($response->getStatusCode() != 200)
		{
			$error = 'Response returned a non-successful status code: ' . $response->getStatusCode();
			return false;
		}

		$body = $response->getBody()->read(100 * 1024);
		$headers = $response->getHeaders();

		$class = 'XF\Http\Metadata';
		$class = $this->app->extendClass($class);

		return new $class($this->app, $body, $headers, $requestUrl);
	}

	public function getValidRequestUrl($requestUrl, $startTime = null, $timeLimit = null)
	{
		$requestUrl = preg_replace('/#.*$/', '', $requestUrl);
		if (preg_match_all('/[^A-Za-z0-9._~:\/?#\[\]@!$&\'()*+,;=%-]/', $requestUrl, $matches))
		{
			foreach ($matches[0] AS $match)
			{
				$requestUrl = str_replace($match[0], '%' . strtoupper(dechex(ord($match[0]))), $requestUrl);
			}
		}

		if ($this->canFetchUrlHtml($requestUrl, $startTime, $timeLimit))
		{
			return $requestUrl;
		}

		return false;
	}

	protected function canFetchUrlHtml($requestUrl, $startTime = null, $timeLimit = null)
	{
		if ($requestUrl != $this->app->stringFormatter()->censorText($requestUrl))
		{
			return false;
		}

		if ($startTime && $timeLimit)
		{
			if (microtime(true) - $startTime > $timeLimit)
			{
				return false;
			}
		}

		return true;
	}
}