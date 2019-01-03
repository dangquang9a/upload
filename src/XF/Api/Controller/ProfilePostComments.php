<?php

namespace XF\Api\Controller;

use XF\Mvc\Entity\Entity;
use XF\Mvc\ParameterBag;

class ProfilePostComments extends AbstractController
{
	protected function preDispatchController($action, ParameterBag $params)
	{
		$this->assertApiScopeByRequestMethod('profile_post');
	}

	public function actionPost(ParameterBag $params)
	{
		$this->assertRequiredApiInput(['profile_post_id', 'message']);
		$this->assertRegisteredUser();

		$profilePostId = $this->filter('profile_post_id', 'uint');

		/** @var \XF\Entity\ProfilePost $profilePost */
		$profilePost = $this->assertViewableApiRecord('XF:ProfilePost', $profilePostId);

		if (\XF::isApiCheckingPermissions() && !$profilePost->canComment($error))
		{
			return $this->noPermission($error);
		}

		$creator = $this->setupNewProfilePostComment($profilePost);

		if (\XF::isApiCheckingPermissions())
		{
			$creator->checkForSpam();
		}

		if (!$creator->validate($errors))
		{
			return $this->error($errors);
		}

		/** @var \XF\Entity\ProfilePostComment $comment */
		$comment = $creator->save();
		$this->finalizeNewProfilePostComment($creator);

		return $this->apiSuccess([
			'comment' => $comment->toApiResult()
		]);
	}

	/**
	 * @param \XF\Entity\ProfilePost $profilePost
	 *
	 * @return \XF\Service\ProfilePostComment\Creator
	 */
	protected function setupNewProfilePostComment(\XF\Entity\ProfilePost $profilePost)
	{
		/** @var \XF\Service\ProfilePostComment\Creator $creator */
		$creator = $this->service('XF:ProfilePostComment\Creator', $profilePost);

		$message = $this->filter('message', 'str');
		$creator->setContent($message);

		return $creator;
	}

	protected function finalizeNewProfilePostComment(\XF\Service\ProfilePostComment\Creator $creator)
	{
		$creator->sendNotifications();
	}
}