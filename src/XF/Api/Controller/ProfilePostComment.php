<?php

namespace XF\Api\Controller;

use XF\Mvc\Entity\Entity;
use XF\Mvc\ParameterBag;

class ProfilePostComment extends AbstractController
{
	protected function preDispatchController($action, ParameterBag $params)
	{
		$this->assertApiScopeByRequestMethod('profile_post');
	}

	public function actionGet(ParameterBag $params)
	{
		$comment = $this->assertViewableProfilePostComment($params->profile_post_comment_id, 'api|post');

		$result = $comment->toApiResult(Entity::VERBOSITY_VERBOSE, [
			'with_post' => true
		]);

		return $this->apiResult(['comment' => $result]);
	}

	public function actionPost(ParameterBag $params)
	{
		$comment = $this->assertViewableProfilePostComment($params->profile_post_comment_id);

		if (\XF::isApiCheckingPermissions() && !$comment->canEdit($error))
		{
			return $this->noPermission($error);
		}

		$editor = $this->setupProfilePostCommentEdit($comment);

		if (\XF::isApiCheckingPermissions())
		{
			$editor->checkForSpam();
		}

		if (!$editor->validate($errors))
		{
			return $this->error($errors);
		}

		$editor->save();

		return $this->apiSuccess([
			'comment' => $comment->toApiResult()
		]);
	}

	/**
	 * @param \XF\Entity\ProfilePostComment $comment
	 *
	 * @return \XF\Service\ProfilePostComment\Editor
	 */
	protected function setupProfilePostCommentEdit(\XF\Entity\ProfilePostComment $comment)
	{
		$input = $this->filter([
			'message' => '?str',
			'author_alert' => 'bool',
			'author_alert_reason' => 'str'
		]);

		/** @var \XF\Service\ProfilePostComment\Editor $editor */
		$editor = $this->service('XF:ProfilePostComment\Editor', $comment);

		if ($input['message'] !== null)
		{
			$editor->setMessage($input['message']);
		}

		if ($input['author_alert'] && $comment->canSendModeratorActionAlert())
		{
			$editor->setSendAlert(true, $input['author_alert_reason']);
		}

		return $editor;
	}

	public function actionDelete(ParameterBag $params)
	{
		$comment = $this->assertViewableProfilePostComment($params->profile_post_comment_id);

		if (\XF::isApiCheckingPermissions() && !$comment->canDelete('soft', $error))
		{
			return $this->noPermission($error);
		}

		$type = 'soft';
		$reason = $this->filter('reason', 'str');

		if ($this->filter('hard_delete', 'bool'))
		{
			$this->assertApiScope('profile_post:delete_hard');

			if (\XF::isApiCheckingPermissions() && !$comment->canDelete('hard', $error))
			{
				return $this->noPermission($error);
			}

			$type = 'hard';
		}

		/** @var \XF\Service\ProfilePostComment\Deleter $deleter */
		$deleter = $this->service('XF:ProfilePostComment\Deleter', $comment);

		if ($this->filter('author_alert', 'bool') && $comment->canSendModeratorActionAlert())
		{
			$deleter->setSendAlert(true, $this->filter('author_alert_reason', 'str'));
		}

		$deleter->delete($type, $reason);

		return $this->apiSuccess();
	}
	
	public function actionPostReact(ParameterBag $params)
	{
		$comment = $this->assertViewableProfilePostComment($params->profile_post_comment_id);
		
		/** @var \XF\Api\ControllerPlugin\Reaction $reactPlugin */
		$reactPlugin = $this->plugin('XF:Api:Reaction');
		return $reactPlugin->actionReact($comment);
	}

	/**
	 * @param int $id
	 * @param string|array $with
	 *
	 * @return \XF\Entity\ProfilePostComment
	 *
	 * @throws \XF\Mvc\Reply\Exception
	 */
	protected function assertViewableProfilePostComment($id, $with = 'api')
	{
		return $this->assertViewableApiRecord('XF:ProfilePostComment', $id, $with);
	}
}