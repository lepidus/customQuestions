<?php

namespace APP\plugins\generic\customQuestions;

use APP\core\Application;
use APP\plugins\generic\customQuestions\api\v1\customQuestionResponses\CustomQuestionResponseHandler;
use APP\plugins\generic\customQuestions\classes\CustomQuestionsHookCallbacks;
use APP\plugins\generic\customQuestions\controllers\grid\CustomQuestionGridHandler;
use APP\plugins\generic\customQuestions\controllers\listbuilder\CustomQuestionResponseItemListbuilderHandler;
use APP\template\TemplateManager;
use Illuminate\Database\Migrations\Migration;
use PKP\core\APIRouter;
use PKP\core\JSONMessage;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

class CustomQuestionsPlugin extends GenericPlugin
{
    public function register($category, $path, $mainContextId = null): bool
    {
        $success = parent::register($category, $path, $mainContextId);

        if ($success && $this->getEnabled($mainContextId)) {
            $hookCallbacks = new CustomQuestionsHookCallbacks($this);
            Hook::add('TemplateManager::display', [$hookCallbacks, 'addToDetailsStep']);
            Hook::add('TemplateManager::display', [$hookCallbacks, 'addToDashboard']);
            Hook::add('Template::SubmissionWizard::Section::Review', [$hookCallbacks, 'addToReviewStep']);
            Hook::add('Submission::validateSubmit', [$hookCallbacks, 'validateRequiredCustomQuestionResponses']);

            Hook::add('LoadComponentHandler', [$this, 'setupGridHandler']);
            Hook::add('APIHandler::endpoints::plugin', [$this, 'setupAPIHandler']);
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

    public function setupAPIHandler(string $hookName, APIRouter $router): bool
    {
        $router->registerPluginApiControllers([
            new CustomQuestionResponseHandler()
        ]);

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
