<?php

namespace APP\plugins\generic\customQuestions;

use APP\core\Application;
use APP\plugins\generic\customQuestions\controllers\grid\CustomQuestionGridHandler;
use APP\template\TemplateManager;
use Illuminate\Database\Migrations\Migration;
use PKP\core\JSONMessage;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

class CustomQuestionsPlugin extends GenericPlugin
{
    public function register($category, $path, $mainContextId = null): bool
    {
        $success = parent::register($category, $path);

        if ($success && $this->getEnabled()) {
            Hook::add('LoadComponentHandler', [$this, 'setupGridHandler']);
        }

        return $success;
    }

    public function getDisplayName()
    {
        return __('plugins.generic.customQuestions.displayName');
    }

    public function getDescription()
    {
        return __('plugins.generic.customQuestions.description');
    }

    public function getInstallMigration(): Migration
    {
        return new CustomQuestionsSchemaMigration();
    }

    public function setupGridHandler(string $hookName, array $params): bool
    {
        $component = & $params[0];
        $componentInstance = & $params[2];
        if ($component == 'plugins.generic.customQuestions.controllers.grid.CustomQuestionGridHandler') {
            $componentInstance = new CustomQuestionGridHandler();
            return true;
        }
        return false;
    }

    public function getActions($request, $actionArgs): array
    {
        $router = $request->getRouter();
        return array_merge(
            $this->getEnabled() ? [
                new LinkAction(
                    'settings',
                    new AjaxModal(
                        $router->url(
                            $request,
                            null,
                            null,
                            'manage',
                            null,
                            [
                                'plugin' => $this->getName(),
                                'category' => $this->getCategory(),
                                'action' => 'index'
                            ]
                        ),
                        $this->getDisplayName()
                    ),
                    __('manager.plugins.settings'),
                    null
                )
            ] : [],
            parent::getActions($request, $actionArgs)
        );
    }

    public function manage($args, $request): JSONMessage
    {
        $templateMgr = TemplateManager::getManager($request);
        $dispatcher = $request->getDispatcher();
        return $templateMgr->fetchAjax(
            'customQuestionGridUrlGridContainer',
            $dispatcher->url(
                $request,
                Application::ROUTE_COMPONENT,
                null,
                'plugins.generic.customQuestions.controllers.grid.CustomQuestionGridHandler',
                'fetchGrid'
            )
        );
    }
}
