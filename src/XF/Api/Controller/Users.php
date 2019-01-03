<?php

namespace XF\Api\Controller;

use XF\Mvc\Entity\Entity;
use XF\Mvc\ParameterBag;

class Users extends AbstractController
{
	protected function preDispatchController($action, ParameterBag $params)
	{
		$this->assertApiScopeByRequestMethod('user');
	}

	public function actionGet()
	{
		$visitor = \XF::visitor();

		// always let user admins get a full list
		if (\XF::isApiCheckingPermissions() && !$visitor->hasAdminPermission('user'))
		{
			if (!$this->options()->enableMemberList || !$visitor->canViewMemberList())
			{
				return $this->noPermission();
			}
		}

		$page = $this->filterPage();
		$perPage = $this->options()->membersPerPage;

		/** @var \XF\Finder\User $finder */
		$finder = $this->finder('XF:User');
		$finder->isValidUser()
			->with('api')
			->setDefaultOrder('username', 'asc')
			->limitByPage($page, $perPage);

		// TODO: allow filtering and sorting options for admins

		$total = $finder->total();
		$this->assertValidApiPage($page, $perPage, $total);

		$users = $finder->fetch();

		return $this->apiResult([
			'users' => $users->toApiResults(Entity::VERBOSITY_VERBOSE),
			'pagination' => $this->getPaginationData($users, $page, $perPage, $total)
		]);
	}

	public function actionGetFindName()
	{
		$this->assertRequiredApiInput('username');

		$username = ltrim($this->filter('username', 'str', ['no-trim']));

		if ($username !== '' && utf8_strlen($username) >= 2)
		{
			/** @var \XF\Finder\User $userFinder */
			$userFinder = $this->finder('XF:User');

			$recommendations = $userFinder
				->where('username', 'like', $userFinder->escapeLike($username, '?%'))
				->isValidUser(true)
				->with('api')
				->order('username')
				->fetch(10);
		}
		else
		{
			$recommendations = $this->em()->getEmptyCollection();
		}

		$exact = null;
		if ($username !== '')
		{
			$exact = $this->em()->findOne('XF:User', ['username' => $username], 'api');
			if ($exact && $recommendations)
			{
				unset($recommendations[$exact->user_id]);
			}
		}

		return $this->apiResult([
			'exact' => $exact ? $exact->toApiResult(Entity::VERBOSITY_VERBOSE) : null,
			'recommendations' => $recommendations->toApiResults()
		]);
	}

	public function actionPost()
	{
		$this->assertAdminPermission('user');
		$this->assertRequiredApiInput(['username', 'password']);

		$user = $this->getUserRepo()->setupBaseUser();

		/** @var \XF\Api\ControllerPlugin\User $userPlugin */
		$userPlugin = $this->plugin('XF:Api:User');
		$userPlugin->userSaveProcessAdmin($user)->run();

		return $this->apiSuccess([
			'user' => $user->toApiResult()
		]);
	}

	/**
	 * @return \XF\Repository\User
	 */
	protected function getUserRepo()
	{
		return $this->repository('XF:User');
	}
}