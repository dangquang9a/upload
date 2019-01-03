<?php

namespace XF\Repository;

use XF\Mvc\Entity\Repository;

class UserPush extends Repository
{
	public function insertUserPushSubscription(\XF\Entity\User $user, array $subscription)
	{
		$db = $this->db();

		$endpointHash = $this->getEndpointHash($subscription['endpoint']);

		$exists = $db->fetchOne('
			SELECT endpoint_id 
			FROM xf_user_push_subscription 
			WHERE endpoint_hash = ?
		', $endpointHash);

		if ($exists)
		{
			return $db->update('xf_user_push_subscription', [
				'user_id' => $user->user_id,
				'data' => json_encode([
					'key' => $subscription['key'],
					'token' => $subscription['token'],
					'encoding' => $subscription['encoding']
				]),
				'last_seen' => time()
			], 'endpoint_id = ?', $exists);
		}
		else
		{
			return $db->insert('xf_user_push_subscription', [
				'endpoint_hash' => $endpointHash,
				'endpoint' => $subscription['endpoint'],
				'user_id' => $user->user_id,
				'data' => json_encode([
					'key' => $subscription['key'],
					'token' => $subscription['token'],
					'encoding' => $subscription['encoding']
				]),
				'last_seen' => time()
			]);
		}
	}

	public function deletePushSubscription(array $subscription)
	{
		$db = $this->db();
		$endpointHash = $this->getEndpointHash($subscription['endpoint']);
		return $db->delete('xf_user_push_subscription', 'endpoint_hash = ?', $endpointHash);
	}

	public function deleteUserPushSubscription(\XF\Entity\User $user, array $subscription)
	{
		$db = $this->db();
		$endpointHash = $this->getEndpointHash($subscription['endpoint']);
		return $db->delete('xf_user_push_subscription', 'endpoint_hash = ? AND user_id = ?', [
			$endpointHash, $user->user_id
		]);
	}

	public function getUserSubscriptions(\XF\Entity\User $user)
	{
		return $this->db()->fetchAll('
			SELECT *
			FROM xf_user_push_subscription
			WHERE user_id = ?
			ORDER BY endpoint_id
		', $user->user_id);
	}

	public function getEndpointHash($endpoint)
	{
		return md5($endpoint);
	}
}