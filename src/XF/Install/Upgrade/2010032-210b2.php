<?php

namespace XF\Install\Upgrade;

use XF\Db\Schema\Alter;
use XF\Db\Schema\Create;

class Version2010032 extends AbstractUpgrade
{
	public function getVersionName()
	{
		return '2.1.0 Beta 2';
	}

	public function step1($position, array $stepData)
	{
		return $this->entityColumnsToJson('XF:ConversationMaster', ['recipients'], $position, $stepData);
	}

	public function step2($position, array $stepData)
	{
		return $this->entityColumnsToJson('XF:ConversationMessage', ['reaction_users'], $position, $stepData);
	}

	public function step3($position, array $stepData)
	{
		return $this->entityColumnsToJson('XF:Draft', ['extra_data'], $position, $stepData);
	}

	public function step4($position, array $stepData)
	{
		return $this->entityColumnsToJson('XF:ErrorLog', ['request_state'], $position, $stepData);
	}

	public function step5($position, array $stepData)
	{
		return $this->entityColumnsToJson('XF:NewsFeed', ['extra_data'], $position, $stepData);
	}

	public function step6($position, array $stepData)
	{
		return $this->entityColumnsToJson('XF:PermissionCacheContent', ['cache_value'], $position, $stepData);
	}

	public function step7($position, array $stepData)
	{
		return $this->entityColumnsToJson('XF:PermissionCombination', ['cache_value'], $position, $stepData);
	}

	public function step8($position, array $stepData)
	{
		return $this->entityColumnsToJson('XF:Poll', ['responses'], $position, $stepData);
	}

	public function step9($position, array $stepData)
	{
		return $this->entityColumnsToJson('XF:PollResponse', ['voters'], $position, $stepData);
	}

	public function step10($position, array $stepData)
	{
		return $this->entityColumnsToJson('XF:Post', ['reaction_users'], $position, $stepData);
	}

	public function step11($position, array $stepData)
	{
		return $this->entityColumnsToJson('XF:ProfilePost', ['reaction_users', 'latest_comment_ids'], $position, $stepData);
	}

	public function step12($position, array $stepData)
	{
		return $this->entityColumnsToJson('XF:ProfilePostComment', ['reaction_users'], $position, $stepData);
	}

	public function step13($position, array $stepData)
	{
		return $this->entityColumnsToJson('XF:Report', ['content_info'], $position, $stepData);
	}

	public function step14($position, array $stepData)
	{
		return $this->entityColumnsToJson('XF:SpamCleanerLog', ['data'], $position, $stepData);
	}

	public function step15($position, array $stepData)
	{
		return $this->entityColumnsToJson('XF:SpamTriggerLog', ['details', 'request_state'], $position, $stepData);
	}

	public function step16($position, array $stepData)
	{
		return $this->entityColumnsToJson('XF:TagResultCache', ['results'], $position, $stepData);
	}

	public function step17($position, array $stepData)
	{
		return $this->entityColumnsToJson('XF:Thread', ['custom_fields', 'tags'], $position, $stepData);
	}

	public function step18($position, array $stepData)
	{
		return $this->entityColumnsToJson('XF:UserAlert', ['extra_data'], $position, $stepData);
	}

	public function step19($position, array $stepData)
	{
		return $this->entityColumnsToJson('XF:UserConnectedAccount', ['extra_data'], $position, $stepData);
	}

	public function step20($position, array $stepData)
	{
		return $this->entityColumnsToJson('XF:UserGroupPromotion', ['user_criteria'], $position, $stepData);
	}

	public function step21($position, array $stepData)
	{
		return $this->entityColumnsToJson('XF:UserProfile', ['ignored', 'custom_fields', 'connected_accounts'], $position, $stepData);
	}

	public function step22($position, array $stepData)
	{
		return $this->entityColumnsToJson('XF:UserTfa', ['provider_data'], $position, $stepData);
	}

	public function step23($position, array $stepData)
	{
		return $this->entityColumnsToJson('XF:UserUpgradeActive', ['extra'], $position, $stepData);
	}

	public function step24($position, array $stepData)
	{
		return $this->entityColumnsToJson('XF:UserUpgradeExpired', ['extra'], $position, $stepData);
	}
}