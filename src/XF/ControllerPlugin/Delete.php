<?php

namespace XF\ControllerPlugin;

use XF\Mvc\Entity\Entity;

class Delete extends AbstractPlugin
{
	public function actionDelete(Entity $entity, $confirmUrl, $editUrl, $returnUrl, $contentTitle, $template = null, array $params = [])
	{
		if (!$entity->preDelete())
		{
			return $this->error($entity->getErrors());
		}

		if ($this->isPost())
		{
			$entity->delete();
			return $this->redirect($returnUrl);
		}
		else
		{
			$viewParams = [
				'content' => $entity,
				'confirmUrl' => $confirmUrl,
				'editUrl' => $editUrl,
				'contentTitle' => $contentTitle
			] + $params;
			return $this->view('XF:Delete\Delete', $template ?: 'delete_confirm', $viewParams);
		}
	}
}