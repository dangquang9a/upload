<?php

namespace XF\Api\Controller;

use XF\Mvc\Entity\Entity;
use XF\Mvc\ParameterBag;

class ProfilePosts extends AbstractController
{
	protected function preDispatchController($action, ParameterBag $params)
	{
		$this->assertApiScopeByRequestMethod('profile_post');
	}

	public function actionPost(ParameterBag $params)
	{
		$this->assertRequiredApiInput(['user_id', 'message']);
		$this->assertRegisteredUser();

		$userId = $this->filter('user_id', 'uint');

		/** @var \XF\Entity\User $user */
		$user = $this->assertRecordExists('XF:User', $userId);

		if (\XF::isApiCheckingPermissions())
		{
			if (!$user->canViewFullProfile($error) || !$user->canViewPostsOnProfile($error) || !$user->canPostOnProfile())
			{
				throw $this->exception($this->noPermission($error));
			}
		}

		$creator = $this->setupNewProfilePost($user);

		if (\XF::isApiCheckingPermissions())
		{
			$creator->checkForSpam();
		}

		if (!$creator->validate($errors))
		{
			return $this->error($errors);
		}

		/** @var \XF\Entity\ProfilePost $profilePost */
		$profilePost = $creator->save();
		$this->finalizeNewProfilePost($creator);

		return $this->apiSuccess([
			'profile_post' => $profilePost->toApiResult()
		]);
	}

	/**
	 * @param \XF\Entity\User $user
	 *
	 * @return \XF\Service\ProfilePost\Creator
	 */
	protected function setupNewProfilePost(\XF\Entity\User $user)
	{
		/** @var \XF\Service\ProfilePost\Creator $creator */
		$creator = $this->service('XF:ProfilePost\Creator', $user->Profile);

		$message = $this->filter('message', 'str');
		$creator->setContent($message);

		return $creator;
	}

	protected function finalizeNewProfilePost(\XF\Service\ProfilePost\Creator $creator)
	{
		$creator->sendNotifications();
	}
}