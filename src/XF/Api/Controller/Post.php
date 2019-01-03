<?php

namespace XF\Api\Controller;

use XF\Mvc\Entity\Entity;
use XF\Mvc\ParameterBag;

class Post extends AbstractController
{
	protected function preDispatchController($action, ParameterBag $params)
	{
		$this->assertApiScopeByRequestMethod('thread');
	}

	public function actionGet(ParameterBag $params)
	{
		$post = $this->assertViewablePost($params->post_id, 'api|thread');

		$result = $post->toApiResult(Entity::VERBOSITY_VERBOSE, [
			'with_thread' => true
		]);

		return $this->apiResult(['post' => $result]);
	}

	public function actionPost(ParameterBag $params)
	{
		$post = $this->assertViewablePost($params->post_id);

		if (\XF::isApiCheckingPermissions() && !$post->canEdit($error))
		{
			return $this->noPermission($error);
		}

		$editor = $this->setupPostEdit($post);

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
			'post' => $post->toApiResult()
		]);
	}

	/**
	 * @param \XF\Entity\Post $post
	 *
	 * @return \XF\Service\Post\Editor
	 */
	protected function setupPostEdit(\XF\Entity\Post $post)
	{
		$input = $this->filter([
			'message' => '?str',
			'silent' => 'bool',
			'clear_edit' => 'bool',
			'author_alert' => 'bool',
			'author_alert_reason' => 'str',
			'attachment_key' => 'str'
		]);

		/** @var \XF\Service\Post\Editor $editor */
		$editor = $this->service('XF:Post\Editor', $post);

		if ($input['message'] !== null)
		{
			if ($input['silent'] && (\XF::isApiBypassingPermissions() || $post->canEditSilently()))
			{
				$editor->logEdit(false);
				if ($input['clear_edit'])
				{
					$post->last_edit_date = 0;
				}
			}

			$editor->setMessage($input['message']);
		}

		if (\XF::isApiBypassingPermissions() || $post->Thread->Forum->canUploadAndManageAttachments())
		{
			$hash = $this->getAttachmentTempHashFromKey($input['attachment_key'], 'post', ['post_id' => $post->post_id]);
			$editor->setAttachmentHash($hash);
		}

		if ($input['author_alert'] && $post->canSendModeratorActionAlert())
		{
			$editor->setSendAlert(true, $input['author_alert_reason']);
		}

		return $editor;
	}

	public function actionDelete(ParameterBag $params)
	{
		$post = $this->assertViewablePost($params->post_id);

		if (\XF::isApiCheckingPermissions() && !$post->canDelete('soft', $error))
		{
			return $this->noPermission($error);
		}

		$type = 'soft';
		$reason = $this->filter('reason', 'str');

		if ($this->filter('hard_delete', 'bool'))
		{
			$this->assertApiScope('thread:delete_hard');

			if (\XF::isApiCheckingPermissions() && !$post->canDelete('hard', $error))
			{
				return $this->noPermission($error);
			}

			$type = 'hard';
		}

		/** @var \XF\Service\Post\Deleter $deleter */
		$deleter = $this->service('XF:Post\Deleter', $post);

		if ($this->filter('author_alert', 'bool') && $post->canSendModeratorActionAlert())
		{
			$deleter->setSendAlert(true, $this->filter('author_alert_reason', 'str'));
		}

		$deleter->delete($type, $reason);

		return $this->apiSuccess();
	}

	public function actionPostReact(ParameterBag $params)
	{
		$post = $this->assertViewablePost($params->post_id);

		/** @var \XF\Api\ControllerPlugin\Reaction $reactPlugin */
		$reactPlugin = $this->plugin('XF:Api:Reaction');
		return $reactPlugin->actionReact($post);
	}

	/**
	 * @param int $id
	 * @param string|array $with
	 *
	 * @return \XF\Entity\Post
	 *
	 * @throws \XF\Mvc\Reply\Exception
	 */
	protected function assertViewablePost($id, $with = 'api')
	{
		return $this->assertViewableApiRecord('XF:Post', $id, $with);
	}

	/**
	 * @return \XF\Repository\Thread
	 */
	protected function getThreadRepo()
	{
		return $this->repository('XF:Thread');
	}

	/**
	 * @return \XF\Repository\Post
	 */
	protected function getPostRepo()
	{
		return $this->repository('XF:Post');
	}
}