<?php

namespace APP\plugins\generic\customQuestions\controllers\grid;

use APP\core\Request;
use APP\notification\NotificationManager;
use APP\plugins\generic\customQuestions\classes\customQuestion\DAO;
use APP\plugins\generic\customQuestions\controllers\grid\form\CustomQuestionForm;
use PKP\controllers\grid\feature\OrderGridItemsFeature;
use PKP\controllers\grid\GridColumn;
use PKP\controllers\grid\GridHandler;
use PKP\core\JSONMessage;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\plugins\PluginRegistry;
use PKP\security\authorization\ContextAccessPolicy;
use PKP\security\authorization\PKPSiteAccessPolicy;
use PKP\security\Role;

class CustomQuestionGridHandler extends GridHandler
{
    public function __construct()
    {
        parent::__construct();
        $this->addRoleAssignment(
            [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SITE_ADMIN],
            ['fetchGrid', 'fetchRow', 'saveSequence', 'createCustomQuestion', 'updateCustomQuestion']
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

        $customQuestionGridCellProvider = new CustomQuestionGridCellProvider();

        $this->addColumn(
            new GridColumn(
                'title',
                'plugins.generic.customQuestions.question',
                null,
                null,
                $customQuestionGridCellProvider,
                ['html' => true, 'maxLength' => 220]
            )
        );

        $this->setTitle('plugins.generic.customQuestions.questions');
    }

    public function initFeatures($request, $args): array
    {
        return [new OrderGridItemsFeature()];
    }

    protected function getRowInstance(): CustomQuestionGridRow
    {
        return new CustomQuestionGridRow();
    }

    protected function loadData($request, $filter = null): array
    {
        $customQuestionDAO = app(DAO::class);
        $customQuestions = [];

        foreach ($customQuestionDAO->getAll() as $customQuestion) {
            $customQuestions[$customQuestion->getId()] = $customQuestion;
        }
        return $customQuestions;
    }

    public function getDataElementSequence($gridDataElement): int
    {
        return $gridDataElement->getSequence();
    }

    public function setDataElementSequence($request, $rowId, $gridDataElement, $newSequence): void
    {
        $gridDataElement->setSequence($newSequence);
        app(DAO::class)->update($gridDataElement);
    }

    public function getCustomQuestionFormTemplate(): string
    {
        $customQuestionsPlugin = PluginRegistry::getPlugin('generic', 'customquestionsplugin');
        return $customQuestionsPlugin->getTemplateResource('customQuestionForm.tpl');
    }

    public function createCustomQuestion(array $args, Request $request): JSONMessage
    {
        $template = $this->getCustomQuestionFormTemplate();
        $customQuestionForm = new CustomQuestionForm($template);
        $customQuestionForm->initData();

        return new JSONMessage(true, $customQuestionForm->fetch($request));
    }

    public function updateCustomQuestion(array $args, Request $request): JSONMessage
    {
        $customQuestionId = (int) $request->getUserVar('custonQuestionId');

        $template = $this->getCustomQuestionFormTemplate();
        $customQuestionForm = new CustomQuestionForm($template, $customQuestionId);
        $customQuestionForm->readInputData();

        if ($customQuestionForm->validate()) {
            $customQuestionId = $customQuestionForm->execute();

            $notificationMgr = new NotificationManager();
            $user = $request->getUser();
            $notificationMgr->createTrivialNotification($user->getId());

            return \PKP\db\DAO::getDataChangedEvent($customQuestionId);
        }

        return new JSONMessage(false);
    }
}
