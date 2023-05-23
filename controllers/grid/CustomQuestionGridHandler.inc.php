<?php

use PKP\security\Role;
use PKP\core\JSONMessage;
use PKP\linkAction\LinkAction;
use PKP\controllers\grid\GridColumn;
use PKP\controllers\grid\GridHandler;
use PKP\linkAction\request\AjaxModal;
use PKP\security\authorization\ContextAccessPolicy;
use PKP\security\authorization\PKPSiteAccessPolicy;
use APP\plugins\generic\customQuestions\CustomQuestionForm;

class CustomQuestionGridHandler extends GridHandler
{
    public function __construct()
    {
        parent::__construct();
        $this->addRoleAssignment(
            [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN],
            ['fetchGrid', 'fetchRow', 'createCustomQuestion']
        );
    }

    public function authorize($request, &$args, $roleAssignments): bool
    {
        if ($request->getContext()) {
            $this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
        } else {
            $this->addPolicy(new PKPSiteAccessPolicy($request, null, $roleAssignments));
        }

        return parent::authorize($request, $args, $roleAssignments);
    }

    public function initialize($request, $args = null): void
    {
        parent::initialize($request, $args);

        $router = $request->getRouter();

        $this->addAction(
            new LinkAction(
                'createCustomQuestion',
                new AjaxModal(
                    $router->url($request, null, null, 'createCustomQuestion'),
                    __('plugins.generic.customQuestions.create'),
                    'modal_add_item',
                    true
                ),
                __('plugins.generic.customQuestions.create'),
                'add_item'
            )
        );

        $this->addColumn(
            new GridColumn(
                'question',
                'plugins.generic.customQuestions.question',
                null,
                null,
                null,
                ['html' => true, 'maxLength' => 220]
            )
        );

        $this->setTitle('plugins.generic.customQuestions.questions');
    }

    public function createCustomQuestion(array $args, PKPRequest $request): JSONMessage
    {
        $customQuestionsPlugin = PluginRegistry::getPlugin('generic', 'customquestionsplugin');
        $template = $customQuestionsPlugin->getTemplateResource('customQuestionForm.tpl');

        $customQuestionsPlugin->import('controllers.grid.form.CustomQuestionForm');
        $customQuestionForm = new CustomQuestionForm($template);
        $customQuestionForm->initData();

        return new JSONMessage(true, $customQuestionForm->fetch($request));
    }
}
