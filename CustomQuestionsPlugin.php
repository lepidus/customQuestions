<?php

namespace APP\plugins\generic\customQuestions;

use APP\core\Application;
use APP\plugins\generic\customQuestions\api\v1\customQuestionResponses\CustomQuestionResponseHandler;
use APP\plugins\generic\customQuestions\classes\CustomQuestionsHookCallbacks;
use APP\plugins\generic\customQuestions\controllers\grid\CustomQuestionGridHandler;
use APP\plugins\generic\customQuestions\controllers\listbuilder\CustomQuestionResponseItemListbuilderHandler;
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
            $hookCallbacks = new CustomQuestionsHookCallbacks($this);
            Hook::add('TemplateManager::display', [$hookCallbacks, 'addToDetailsStep']);
            Hook::add('TemplateManager::display', [$hookCallbacks, 'addToPublicationForms']);
            Hook::add('Template::SubmissionWizard::Section::Review', [$hookCallbacks, 'addToReviewStep']);
            Hook::add('Template::Workflow::Publication', [$hookCallbacks, 'addCustomQuestionsTab']);

            Hook::add('LoadComponentHandler', [$this, 'setupGridHandler']);
            Hook::add('Dispatcher::dispatch', [$this, 'setupAPIHandler']);
            Hook::add('Schema::get::customQuestion', [$this, 'addCustomQuestionSchema']);
            Hook::add('Schema::get::customQuestionResponse', [$this, 'addCustomQuestionResponseSchema']);
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

    public function addCustomQuestionSchema(string $hookName, array $params): bool
    {
        $schema = &$params[0];
        $schema = $this->getJsonSchema('customQuestion');
        return true;
    }

    public function addCustomQuestionResponseSchema(string $hookName, array $params): bool
    {
        $schema = &$params[0];
        $schema = $this->getJsonSchema('customQuestionResponse');
        return true;
    }

    private function getJsonSchema(string $schemaName): ?\stdClass
    {
        $schemaFile = sprintf(
            '%s/plugins/generic/customQuestions/schemas/%s.json',
            BASE_SYS_DIR,
            $schemaName
        );
        if (file_exists($schemaFile)) {
            $schema = json_decode(file_get_contents($schemaFile));
            if (!$schema) {
                throw new \Exception(
                    'Schema failed to decode. This usually means it is invalid JSON. Requested: '
                    . $schemaFile
                    . '. Last JSON error: '
                    . json_last_error()
                );
            }
        }
        return $schema;
    }

    public function setupGridHandler(string $hookName, array $params): bool
    {
        $component = &$params[0];
        $componentInstance = &$params[2];
        if ($component == 'plugins.generic.customQuestions.controllers.grid.CustomQuestionGridHandler') {
            $componentInstance = new CustomQuestionGridHandler($this);
            return true;
        }
        $listbuilderHandlerClass = 'CustomQuestionResponseItemListbuilderHandler';
        if ($component == 'plugins.generic.customQuestions.controllers.listbuilder.' . $listbuilderHandlerClass) {
            $componentInstance = new CustomQuestionResponseItemListbuilderHandler();
            return true;
        }
        return false;
    }

    public function setupAPIHandler(string $hookName, array $args): void
    {
        $request = $args[0];
        $router = $request->getRouter();

        if (!($router instanceof \PKP\core\APIRouter)) {
            return;
        }

        if (str_contains($request->getRequestPath(), 'api/v1/customQuestionResponses')) {
            $handler = new CustomQuestionResponseHandler();
        }

        if (!isset($handler)) {
            return;
        }

        $router->setHandler($handler);
        $handler->getApp()->run();
        exit;
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
