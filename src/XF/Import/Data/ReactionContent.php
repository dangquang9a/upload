<?php

namespace XF\Import\Data;

class ReactionContent extends AbstractEmulatedData
{
	public function getImportType()
	{
		return 'reaction_content';
	}

	public function getEntityShortName()
	{
		return 'XF:ReactionContent';
	}

	// TODO: This actually needs to support passing in a reaction ID to calculate the score properly

	protected function postSave($oldId, $newId)
	{
		if ($this->is_counted && $this->content_user_id)
		{
			$this->db()->query("
				UPDATE xf_user
				SET reaction_score = reaction_score + 1
				WHERE user_id = ?
			", $this->content_user_id);
		}

		$this->app()->repository('XF:Reaction')->rebuildContentReactionCache(
			$this->content_type, $this->content_id, false
		);
	}
}