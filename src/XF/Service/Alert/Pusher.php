<?php

namespace XF\Service\Alert;

use Symfony\Component\DomCrawler\Crawler;
use XF\Alert\AbstractHandler;
use XF\Entity\User;
use XF\Entity\UserAlert;
use XF\Mvc\Entity\Entity;
use XF\Service\AbstractService;
use XF\Service\PusherTrait;
use XF\Service\PushNotification;

class Pusher extends AbstractService
{
	use PusherTrait;

	/**
	 * @var UserAlert
	 */
	protected $alert;

	/**
	 * @var AbstractHandler
	 */
	protected $handler;

	/**
	 * @var Entity
	 */
	protected $content;

	protected $notificationUrl;

	protected $notificationTag;

	protected function setInitialProperties(UserAlert $alert)
	{
		$this->alert = $alert;
		$this->handler = $alert->getHandler();
		$this->content = $alert->getContent();
	}

	protected function getNotificationTitle()
	{
		$phrase = $this->language->phrase('new_alert_at_x', ['boardTitle' => $this->app->options()->boardTitle]);

		return $phrase->render('raw');
	}

	protected function getNotificationBody()
	{
		$alert = $this->alert;
		$handler = $this->handler;
		$content = $this->content;
		$templater = $this->app->templater();

		$templateName = $handler->getPushTemplateName($alert->action);
		if (!$templater->isKnownTemplate($templateName))
		{
			$templateName = $handler->getTemplateName($alert->action);
		}
		$templateData = $handler->getTemplateData($alert->action, $alert, $content);

		$alertContent = $templater->renderTemplate($templateName, $templateData);

		$this->notificationUrl = $this->findNotificationUrl($alertContent);
		$this->notificationTag = $this->findNotificationTag($alertContent);

		$alertContent = strip_tags($alertContent);
		$alertContent = html_entity_decode($alertContent, ENT_QUOTES);

		return trim($alertContent);
	}

	protected function getNotificationUrl()
	{
		return $this->notificationUrl;
	}

	protected function getNotificationTag()
	{
		return $this->notificationTag;
	}

	protected function setAdditionalOptions(PushNotification $pushNotification)
	{
		$alert = $this->alert;
		$user = $alert->User;
		if ($user)
		{
			$avatar = $user->getAvatarUrl('m');
			if ($avatar)
			{
				$pushNotification->setIconAndBadge($avatar);
			}
		}
	}

	protected function findNotificationUrl(&$alertContent)
	{
		if (preg_match('#<push:url>(.*)</push:url>#siU', $alertContent, $match))
		{
			$targetLink = trim(htmlspecialchars_decode($match[1], ENT_QUOTES));
			$alertContent = preg_replace('#<push:url>.*</push:url>#siU', '', $alertContent);
		}
		else
		{
			// can do a quick bail out if there's no clear block link
			if (strpos($alertContent, 'fauxBlockLink-blockLink') === false)
			{
				return '';
			}

			$crawler = new Crawler($alertContent);
			$crawler = $crawler->filter('a.fauxBlockLink-blockLink');

			if (!$crawler->count())
			{
				return '';
			}

			$hrefAttrs = $crawler->extract(['href']);
			$targetLink = reset($hrefAttrs);
		}

		return $targetLink;
	}

	protected function findNotificationTag(&$alertContent)
	{
		$tag = '';

		if (preg_match('#<push:tag>(.*)</push:tag>#siU', $alertContent, $match))
		{
			$tag = trim(htmlspecialchars_decode($match[1], ENT_QUOTES));
			$alertContent = preg_replace('#<push:tag>.*</push:tag>#siU', '', $alertContent);
		}

		return $tag;
	}
}