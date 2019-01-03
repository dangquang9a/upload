<?php

namespace XF\Api\Controller;

use XF\Mvc\Entity\Entity;
use XF\Mvc\ParameterBag;

class ProfilePost extends AbstractController
{
	protected function preDispatchController($action, ParameterBag $params)
	{
		$this->assertApiScopeByRequestMethod('profile_post');
	}

	public function actionGet(ParameterBag $params)
	{
		$profilePost = $this->assertViewableProfilePost($params->profile_post_id, 'api|profile');

		if ($this->filter('with_comments', 'bool'))
		{
			$commentData = $this->getCommentsOnProfilePostPaginated($profilePost, $this->filterPage());
		}
		else
		{
			$commentData = [];
		}

		$result = $profilePost->toApiResult(Entity::VERBOSITY_VERBOSE, [
			'with_profile' => true
		]);

		$result = [
			'profile_post' => $result
		];
		$result += $commentData;

		return $this->apiResult($result);
	}

	public function actionGetComments(ParameterBag $params)
	{
		$profilePost = $this->assertViewableProfilePost($params->profile_post_id);

		$commentData = $this->getCommentsOnProfilePostPaginated($profilePost, $this->filterPage());

		return $this->apiResult($commentData);
	}

	protected function getCommentsOnProfilePostPaginated(\XF\Entity\ProfilePost $profilePost, $page = 1, $perPage = null)
	{
		$perPage = intval($perPage);
		if ($perPage <= 0)
		{
			$perPage = $this->options()->messagesPerPage;
		}

		$commentFinder = $this->setupCommentsFinder($profilePost);

		$total = $commentFinder->total();
		$this->assertValidApiPage($page, $perPage, $total);

		$commentFinder->limitByPage($page, $perPage);
		$postResults = $commentFinder->fetch()->toApiResults(Entity::VERBOSITY_VERBOSE);

		return [
			'comments' => $postResults,
			'pagination' => $this->getPaginationData($postResults, $page, $perPage, $total)
		];
	}

	/**
	 * @param \XF\Entity\ProfilePost $profilePost
	 *
	 * @return \XF\Finder\ProfilePostComment
	 */
	protected function setupCommentsFinder(\XF\Entity\ProfilePost $profilePost)
	{
		/** @var \XF\Finder\ProfilePostComment $finder */
		$finder = $this->finder('XF:ProfilePostComment');
		$finder
			->forProfilePost($profilePost)
			->setDefaultOrder('comment_date', 'desc')
			->with('api');

		return $finder;
	}

	public function actionPost(ParameterBag $params)
	{
		$profilePost = $this->assertViewableProfilePost($params->profile_post_id);

		if (\XF::isApiCheckingPermissions() && !$profilePost->canEdit($error))
		{
			return $this->noPermission($error);
		}

		$editor = $this->setupProfilePostEdit($profilePost);

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
			'profile_post' => $profilePost->toApiResult()
		]);
	}

	/**
	 * @param \XF\Entity\ProfilePost $profilePost
	 *
	 * @return \XF\Service\ProfilePost\Editor
	 */
	protected function setupProfilePostEdit(\XF\Entity\ProfilePost $profilePost)
	{
		$input = $this->filter([
			'message' => '?str',
			'author_alert' => 'bool',
			'author_alert_reason' => 'str'
		]);

		/** @var \XF\Service\ProfilePost\Editor $editor */
		$editor = $this->service('XF:ProfilePost\Editor', $profilePost);

		if ($input['message'] !== null)
		{
			$editor->setMessage($input['message']);
		}

		if ($input['author_alert'] && $profilePost->canSendModeratorActionAlert())
		{
			$editor->setSendAlert(true, $input['author_alert_reason']);
		}

		return $editor;
	}

	public function actionDelete(ParameterBag $params)
	{
		$profilePost = $this->assertViewableProfilePost($params->profile_post_id);

		if (\XF::isApiCheckingPermissions() && !$profilePost->canDelete('soft', $error))
		{
			return $this->noPermission($error);
		}

		$type = 'soft';
		$reason = $this->filter('reason', 'str');

		if ($this->filter('hard_delete', 'bool'))
		{
			$this->assertApiScope('profile_post:delete_hard');

			if (\XF::isApiCheckingPermissions() && !$profilePost->canDelete('hard', $error))
			{
				return $this->noPermission($error);
			}

			$type = 'hard';
		}

		/** @var \XF\Service\ProfilePost\Deleter $deleter */
		$deleter = $this->service('XF:ProfilePost\Deleter', $profilePost);

		if ($this->filter('author_alert', 'bool') && $profilePost->canSendModeratorActionAlert())
		{
			$deleter->setSendAlert(true, $this->filter('author_alert_reason', 'str'));
		}

		$deleter->delete($type, $reason);

		return $this->apiSuccess();
	}

	public function actionPostReact(ParameterBag $params)
	{
		$profilePost = $this->assertViewableProfilePost($params->profile_post_id);

		/** @var \XF\Api\ControllerPlugin\Reaction $reactPlugin */
		$reactPlugin = $this->plugin('XF:Api:Reaction');
		return $reactPlugin->actionReact($profilePost);
	}

	/**
	 * @param int $id
	 * @param string|array $with
	 *
	 * @return \XF\Entity\ProfilePost
	 *
	 * @throws \XF\Mvc\Reply\Exception
	 */
	protected function assertViewableProfilePost($id, $with = 'api')
	{
		return $this->assertViewableApiRecord('XF:ProfilePost', $id, $with);
	}
}