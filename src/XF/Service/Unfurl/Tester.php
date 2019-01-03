<?php

namespace XF\Service\Unfurl;

use XF\Service\AbstractService;

class Tester extends AbstractService
{
	protected $url;

	public function __construct(\XF\App $app, $url)
	{
		parent::__construct($app);
		$this->url = $url;
	}

	public function test(&$error = null)
	{
		$metadata = $this->app->http()->metadataFetcher()->fetch($this->url, $error);

		if (!$metadata)
		{
			$error = 'Could not fetch metadata from URL with error: ' . ($error ?: 'N/A');
			return false;
		}

		$title = $metadata->getTitle();

		if (!$title)
		{
			// if no title, might as well bail out
			$error = 'Could not fetch title metadata from URL.';
			return false;
		}

		$unfurlData = [
			'title' => $title,
			'description' => $metadata->getDescription(),
			'image_url' => $metadata->getImage(),
			'favicon_url' => $metadata->getFavicon(),
			'body' => $metadata->getBody()
		];

		return $unfurlData;
	}
}