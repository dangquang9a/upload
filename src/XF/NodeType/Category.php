<?php

namespace XF\NodeType;

class Category extends AbstractHandler
{
	public function setupApiTypeDataEdit(
		\XF\Entity\Node $node, \XF\Entity\AbstractNode $data, \XF\InputFiltererArray $inputFilterer
	)
	{
		// don't need to do anything
	}
}