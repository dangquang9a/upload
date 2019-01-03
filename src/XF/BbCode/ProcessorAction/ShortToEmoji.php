<?php

namespace XF\BbCode\ProcessorAction;

class ShortToEmoji implements FiltererInterface
{
	/**
	 * @var \XF\Str\Formatter
	 */
	protected $formatter;

	/**
	 * @var \XF\Str\EmojiFormatter
	 */
	protected $emojiFormatter;

	public function __construct(\XF\Str\Formatter $formatter)
	{
		$this->formatter = $formatter;
		$this->emojiFormatter = $this->formatter->getEmojiFormatter();
	}

	public function addFiltererHooks(FiltererHooks $hooks)
	{
		if (!\XF::app()->options()->shortcodeToEmoji || !\XF::app()->config('fullUnicode'))
		{
			return;
		}

		$hooks->addStringHook('convertToEmoji');
	}

	public function convertToEmoji($string, array $options)
	{
		if (!empty($options['stopSmilies']))
		{
			return $string;
		}

		return $this->emojiFormatter->formatShortnameToEmoji($string);
	}

	public static function factory(\XF\App $app)
	{
		return new static($app->stringFormatter());
	}
}