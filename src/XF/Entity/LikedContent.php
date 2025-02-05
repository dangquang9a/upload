<?php

namespace XF\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;
use XF\Repository;

/**
 * COLUMNS
 * @property int|null reaction_content_id
 * @property int reaction_id_
 * @property string content_type
 * @property int content_id
 * @property int reaction_user_id
 * @property int reaction_date
 * @property int content_user_id
 * @property bool is_counted
 *
 * GETTERS
 * @property Entity|null Content
 * @property mixed like_id
 * @property mixed like_user_id
 * @property mixed like_date
 * @property mixed reaction_id
 *
 * RELATIONS
 * @property \XF\Entity\Reaction Reaction
 * @property \XF\Entity\User ReactionUser
 * @property \XF\Entity\User Owner
 * @property \XF\Entity\User Liker
 */
class LikedContent extends ReactionContent
{
	public function getLikeId()
	{
		return $this->reaction_content_id;
	}

	public function getLikeUserId()
	{
		return $this->reaction_user_id;
	}

	public function getLikeDate()
	{
		return $this->reaction_date;
	}

	public function getReactionId()
	{
		// likes are always ID 1
		return 1;
	}

	public static function getStructure(Structure $structure)
	{
		$structure = parent::getStructure($structure);

		$structure->getters['like_id'] = false;
		$structure->getters['like_user_id'] = false;
		$structure->getters['like_date'] = false;
		$structure->getters['reaction_id'] = false;

		$structure->relations['Liker'] = $structure->relations['ReactionUser'];

		$structure->columnAliases['like_id'] = 'reaction_content_id';
		$structure->columnAliases['like_user_id'] = 'reaction_user_id';
		$structure->columnAliases['like_date'] = 'reaction_date';

		return $structure;
	}
}