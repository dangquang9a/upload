<?php

namespace XF\Service\Conversation;

use XF\Entity\ConversationMessage;
use XF\Mail\Mail;
use XF\Service\AbstractService;
use XF\Service\PusherTrait;
use XF\Service\PushNotification;

class Pusher extends AbstractService
{
	use PusherTrait;

	/**
	 * @var ConversationMessage
	 */
	protected $message;

	/**
	 * @var string
	 */
	protected $actionType;

	/**
	 * @var \XF\Entity\User
	 */
	protected $sender;

	protected function setInitialProperties(ConversationMessage $message, $actionType, \XF\Entity\User $sender)
	{
		$this->message = $message;
		$this->actionType = $actionType;
		$this->sender = $sender;
	}

	protected function getNotificationTitle()
	{
		$phrase = $this->language->phrase('new_conversation_at_x', ['boardTitle' => $this->app->options()->boardTitle]);

		return $phrase->render('raw');
	}

	protected function getNotificationBody()
	{
		$phrase = $this->language->phrase('push_conversation_' . $this->actionType, [
			'boardTitle' => $this->app->options()->boardTitle,
			'title' => $this->message->Conversation->title,
			'sender' => $this->sender->username
		]);

		return $phrase->render('raw');
	}

	public function getNotificationUrl()
	{
		return $this->app->router('public')->buildLink(
			'canonical:conversations/unread', $this->message->Conversation
		);
	}

	protected function setAdditionalOptions(PushNotification $pushNotification)
	{
		$message = $this->message;
		$user = $message->User;
		if ($user)
		{
			$avatar = $user->getAvatarUrl('m');
			if ($avatar)
			{
				$pushNotification->setIconAndBadge($avatar);
			}
		}
	}
}