<?php

namespace XF\Api\Docs\Annotation;

abstract class AbstractValueLine extends AbstractLine
{
	public $name;
	public $description;
	public $types = [];
	public $modifiers = [];

	public function __construct($name, $description, array $types = [], array $modifiers = [])
	{
		$this->name = $name;
		$this->description = $description;
		$this->types = $types ?: ['mixed'];
		$this->modifiers = $modifiers;
	}
}