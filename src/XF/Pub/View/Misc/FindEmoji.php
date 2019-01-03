<?php

namespace XF\Pub\View\Misc;

use XF\Mvc\View;

class FindEmoji extends View
{
	public function renderJson()
	{
		$results = [];
		foreach ($this->params['results'] AS $_result)
		{
			$result = [
				'id' => $_result['shortname'],
				'iconHtml' => $_result['html'],
				'text' => $_result['shortname'],
				'desc' => isset($_result['name']) ? $_result['name'] : '',
				'q' => $this->params['q']
			];

			// concatenate the text/desc as results are filtered on this matching
			$result['text_desc'] = $result['text'] . $result['desc'];

			$results[] = $result;
		}

		return [
			'results' => $results,
			'q' => $this->params['q']
		];
	}
}