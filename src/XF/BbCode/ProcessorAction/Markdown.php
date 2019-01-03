<?php

namespace XF\BbCode\ProcessorAction;

use XF\BbCode\Parser;
use XF\BbCode\RuleSet;

class Markdown implements FiltererInterface, ProcessorAwareInterface
{
	protected $tokens;

	/**
	 * @var \XF\BbCode\Processor
	 */
	protected $processor;

	/**
	 * @var array
	 */
	protected $tags;

	public function setProcessor(\XF\BbCode\Processor $processor)
	{
		$this->processor = $processor;
	}

	public function addFiltererHooks(FiltererHooks $hooks)
	{
		$hooks->addParseHook('filterInput');
	}

	public function filterInput($string, Parser $parser, RuleSet $rules, array &$options)
	{
		$this->tags = $rules->getTags();

		return $this->parseMarkdown($string);
	}

	protected function addToken($string)
	{
		$tokenId = count($this->tokens);
		$token = "\x1A" . $tokenId . "\x1A";

		$this->tokens[$tokenId] = $string;

		return $token;
	}

	protected function replaceTokens($string, $isTopLevel = false)
	{
		if ($this->tokens)
		{
			$string = preg_replace_callback(
				"#\x1A(\d+)\x1A#",
				function($match)
				{
					$tokenId = $match[1];
					if (isset($this->tokens[$tokenId]))
					{
						return $this->replaceTokens($this->tokens[$tokenId]);
					}
					else
					{
						return '';
					}
				},
				$string
			);

			if ($isTopLevel)
			{
				$string = str_replace("\x1A", '', $string);
			}
		}

		return $string;
	}

	protected function parseMarkdown($string)
	{
		$this->tokens = [];

		// tokenize stuff we don't want parsed for markdown
		$string = $this->stashBbCodeListItems($string);
		$string = $this->stashBbCodePlainItems($string);
		$string = $this->stashBbCodeMarkUp($string);

		// parse markdown
		$string = $this->parseFencedCode($string);
		$string = $this->parseUnorderedLists($string);
		$string = $this->parseOrderedLists($string);
		$string = $this->parseInlineCode($string);
		$string = $this->parseBlockQuote($string);
		$string = $this->parseLinks($string);
		$string = $this->parseInlineTags($string);

		// restore stuff that was stashed or tokenized
		$string = $this->replaceTokens($string, true);

		return $string;
	}

	/**
	 * Replace BB Code [*] items with tokens so they are note parsed for tokens
	 *
	 * @param $string
	 *
	 * @return string
	 */
	protected function stashBbCodeListItems($string)
	{
		return preg_replace_callback('/\[\*\]/', function($match)
		{
			return $this->addToken($match[0]);
		}, $string);
	}

	/**
	 * Replace BB Code "plain" items with tokens so they are not parsed for markdown
	 *
	 * @param $string
	 *
	 * @return string
	 */
	protected function stashBbCodePlainItems($string)
	{
		return preg_replace_callback(
			'#\[(code|icode|php|html|plain|media|img|user|attach)(=[^\]]*)?](.*)\[/\\1]#siU',
			function ($match)
			{
				return $this->addToken($match[0]);
			},
			$string
		);
	}

	/**
	 * Stashes actual BB code markup so Markdown is not matched within it.
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	protected function stashBbCodeMarkUp($string)
	{
		if ($this->tags)
		{
			$tagRegex = '(' . implode('|', array_keys($this->tags)) . ')';
		}
		else
		{
			$tagRegex = '[a-z0-9_]+';
		}

		return preg_replace_callback(
			'#(\[' . $tagRegex . '(?:=[^\]]*)?+\]|\[[a-z0-9_]+(?:\s?[a-z0-9_]+="[^"]*")+\]|\[/' . $tagRegex . '\])#si',
			function ($match)
			{
				return $this->addToken($match[0]);
			},
			$string
		);
	}

	/**
	 * Parse markdown fenced code into [CODE] BB code
	 *
	 * @param $string
	 *
	 * @return string
	 */
	protected function parseFencedCode($string)
	{
		return preg_replace_callback('/(?<=\s|^)(`{3}|~{3})([^\n]+)?\n(.+\n)\1(?=\s|$)/siU', function($match)
		{
			if (empty($match[2]))
			{
				return '[CODE]' . $this->addToken($match[3]) . '[/CODE]';
			}
			else if (preg_match('/^[a-z0-9]+$/i', $match[2]))
			{
				if (strtoupper($match[2]) == 'RICH')
				{
					return "[CODE=\"rich\"]{$match[3]}[/CODE]";
				}
				else
				{
					return "[CODE=\"{$match[2]}\"]" . $this->addToken($match[3]) . '[/CODE]';
				}
			}
			else
			{
				return '[CODE]' . $this->addToken("{$match[2]}\n{$match[3]}") . '[/CODE]';
			}
		}, $string);
	}

	/**
	 * Parse markdown inline code into [ICODE] BB code
	 *
	 * @param $string
	 *
	 * @return string
	 */
	protected function parseInlineCode($string)
	{
		return preg_replace_callback('/(?<=\s|^)(`{3}|`{2}|`)([^`]+)\1/U', function($match)
		{
			return '[ICODE]' . $this->addToken($match[2]) . '[/ICODE]';
		}, $string);
	}

	/**
	 * Parse markdown links and images to [URL] and [IMG]
	 *
	 * @param $string
	 *
	 * @return string
	 */
	protected function parseLinks($string)
	{
		return preg_replace_callback('/(\!)?\[([^\]]+)\]\((http[^\)]+)\)/iU', function($match)
		{
			if (!empty($match[1]))
			{
				return "[IMG]{$match[3]}[/IMG]";
			}
			else
			{
				return "[URL=\"{$match[3]}\"]{$match[2]}[/URL]";
			}
		}, $string);
	}

	/**
	 * Parse markdown blockquote bits and pieces into [QUOTE]
	 *
	 * @param $string
	 *
	 * @return string
	 */
	protected function parseBlockQuote($string)
	{
		return preg_replace_callback('/(?<=\n|^)([ \t]*)>([^\r\n]*)(\r?\n\1>[^\r\n]*)*/', function($match)
		{
			return '[QUOTE]' . preg_replace('/(?<=\n|^)[ \t]*>([^\r\n]*)/', '$1', $match[0]) . '[/QUOTE]';
		}, $string);
	}

	/**
	 * Parse inline markdown tags into [B], [I], [S] and [ICODE]
	 *
	 * @param $string
	 *
	 * @return string
	 */
	protected function parseInlineTags($string)
	{
		// __ delimiters require non-word characters on either side
		$string = preg_replace_callback(
			'#(?<=\W|^)(__|_(?!_)|~+?(?!~))(?!\s)(.+)(?<!\s)\\1(?!_)(?=\W|$)#U',
			function ($match) {
				$innerText = $this->parseInlineTags($match[2]);

				if ($match[1] == '__')
				{
					return "[B]{$innerText}[/B]";
				}
				else if ($match[1][0] == '~')
				{
					return "[S]{$innerText}[/S]";
				}
				else
				{
					return "[I]{$innerText}[/I]";
				}
			},
			$string
		);

		// ~ delimiters require non-word characters on either side
		$string = preg_replace_callback(
			'#(?<=\W|^)(~+?(?!~))(?!\s)(.+)(?<!\s)\\1(?!~)(?=\W|$)#U',
			function ($match) {
				$innerText = $this->parseInlineTags($match[2]);

				if ($match[1] == '__')
				{
					return "[B]{$innerText}[/B]";
				}
				else if ($match[1][0] == '~')
				{
					return "[S]{$innerText}[/S]";
				}
				else
				{
					return "[I]{$innerText}[/I]";
				}
			},
			$string
		);

		$string = preg_replace_callback(
			'#(\*\*|(?<!\[)\*(?!\*|]))(?!\s)(.+)(?<!\s)\\1(?!\*)#U',
			function ($match) {
				$innerText = $this->parseInlineTags($match[2]);

				if ($match[1] == '**')
				{
					return "[B]{$innerText}[/B]";
				}
				else
				{
					return "[I]{$innerText}[/I]";
				}
			},
			$string
		);

		return $string;
	}

	/**
	 * Parse unordered markdown lists into [LIST] [*]...
	 * Supports star, dash or plus then space or tab
	 *
	 * @param $string
	 *
	 * @return string
	 */
	protected function parseUnorderedLists($string)
	{
		return preg_replace_callback('#(?<=\n|^)(\*|-|\+)[ \t]+[^\r\n]+(\r?\n\\1[ \t]+[^\r\n]+)*(?:\r?\n|$)#', function($match)
		{
			$listItems = preg_split('#(\n|^)(\*|-|\+)[ \t]+#', $match[0], -1, PREG_SPLIT_NO_EMPTY);
			if (count($listItems) < 2)
			{
				return $match[0];
			}

			$listItems = $this->adjustListItems($listItems, $postListContent);
			return "[LIST]\n[*]" . implode("\n[*]", $listItems) . "\n[/LIST]\n$postListContent";
		}, $string);
	}

	/**
	 * Parse ordered markdown lists into [LIST="1"] [*]...
	 * Supports 1-9 digits followed by . or ) and then space or tab
	 *
	 * @param $string
	 *
	 * @return string
	 */
	protected function parseOrderedLists($string)
	{
		return preg_replace_callback('#(?<=\n|^)((?:\d{1,9})(?:\.|\))[ \t]+([^\r\n]+)(?:\r?\n|$)){2,}#', function($match)
		{
			$listItems = preg_split('#(\n|^)\d+(?:\.|\))[ \t]+#', $match[0], -1, PREG_SPLIT_NO_EMPTY);
			if (count($listItems) < 2)
			{
				return $match[0];
			}

			$listItems = $this->adjustListItems($listItems, $postListContent);
			return "[LIST=\"1\"]\n[*]" . implode("\n[*]", $listItems) . "\n[/LIST]\n$postListContent";
		}, $string);
	}

	/**
	 * Adjusts the content of the list items for BB code. Primarily, handles situations where there may be
	 * existing BB code at the end of the last list item which shouldn't be part of the list.
	 *
	 * @param array $listItems
	 * @param string $postListContent Content to move after the list if needed
	 *
	 * @return array
	 */
	protected function adjustListItems(array $listItems, &$postListContent)
	{
		$postListContent = '';

		$listItems = array_map('trim', $listItems);

		$lastItem = end($listItems);
		$lastId = key($listItems);

		if (preg_match_all("#\x1A(\d+)\x1A#", $lastItem, $tokenMatches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE))
		{
			$openTags = [];

			foreach ($tokenMatches AS $tokenMatch)
			{
				$tokenId = $tokenMatch[1][0];
				if (!isset($this->tokens[$tokenId]))
				{
					// this really shouldn't happen
					continue;
				}

				$token = $this->tokens[$tokenId];

				if (preg_match('#^\[([a-z0-9_]+)#i', $token, $openMatch))
				{
					// tag open
					array_unshift($openTags, strtolower($openMatch[1]));
				}
				else if (preg_match('#^\[/([a-z0-9_]+)\]#i', $token, $closeMatch))
				{
					// close match
					if (!$openTags || $openTags[0] != strtolower($closeMatch[1]))
					{
						// invalid close, split here and move outside the list
						$postListContent = strval(substr($lastItem, $tokenMatch[0][1])) . "\n";
						$listItems[$lastId] = strval(substr($lastItem, 0, $tokenMatch[0][1]));
						break;
					}
					else
					{
						// close the last tag as it matches
						array_shift($openTags);
					}
				}
			}
		}

		return $listItems;
	}
}