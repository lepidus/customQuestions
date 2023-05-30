<?php

namespace APP\plugins\generic\customQuestions\controllers\grid\form;

use APP\core\Application;
use APP\core\Request;
use APP\plugins\generic\customQuestions\classes\customQuestion\CustomQuestion;
use APP\plugins\generic\customQuestions\classes\customQuestion\DAO;
use APP\template\TemplateManager;
use PKP\controllers\listbuilder\ListbuilderHandler;
use PKP\form\Form;

class CustomQuestionForm extends Form
{
    public $contextId;
    public $customQuestionId;

    public function __construct(string $template, int $contextId, int $customQuestionId = null)
    {
        parent::__construct($template);

        $this->contextId = $contextId;
        $this->customQuestionId = $customQuestionId;

        $this->addCheck(new \PKP\form\validation\FormValidatorLocale(
            $this,
            'title',
            'required',
            'plugins.generic.customQuestions.form.questionRequired'
        ));
        $this->addCheck(new \PKP\form\validation\FormValidator(
            $this,
            'questionType',
            'required',
            'plugins.generic.customQuestions.form.questionTypeRequired'
        ));
        $this->addCheck(new \PKP\form\validation\FormValidatorPost($this));
        $this->addCheck(new \PKP\form\validation\FormValidatorCSRF($this));
    }

    public function fetch($request, $template = null, $display = false): string
    {
        $templateMgr = TemplateManager::getManager($request);
        $customQuestion = new CustomQuestion();
        $multipleResponsesQuestionTypesString = ';'
            . implode(';', $customQuestion->getMultipleResponsesQuestionTypes())
            . ';';
        $templateMgr->assign([
            'customQuestionId' => $this->customQuestionId,
            'multipleResponsesQuestionTypes' => $customQuestion->getMultipleResponsesQuestionTypes(),
            'multipleResponsesQuestionTypesString' => $multipleResponsesQuestionTypesString,
            'customQuestionTypeOptions' => $customQuestion->getCustomQuestionTypeOptions(),
        ]);
        return parent::fetch($request, $template, $display);
    }

    public function initData(): void
    {
        if (!$this->customQuestionId) {
            return ;
        }

        $request = Application::get()->getRequest();
        $customQuestionDAO = app(DAO::class);
        $customQuestion = $customQuestionDAO->get($this->customQuestionId);
        $this->_data = [
            'title' => $customQuestion->getData('title'),
            'description' => $customQuestion->getData('description'),
            'required' => $customQuestion->getRequired(),
            'questionType' => $customQuestion->getQuestionType(),
            'possibleResponses' => $customQuestion->getData('possibleResponses')
        ];
    }

    public function readInputData(): void
    {
        $this->readUserVars(['title', 'description', 'required', 'questionType', 'possibleResponses']);
    }

    public function execute(...$functionArgs): int
    {
        $customQuestionDAO = app(DAO::class);
        $request = Application::get()->getRequest();

        if ($this->customQuestionId) {
            $customQuestion = $customQuestionDAO->get($this->customQuestionId);
        } else {
            $customQuestion = $customQuestionDAO->newDataObject();
            $customQuestion->setSequence(REALLY_BIG_NUMBER);
        }

        $customQuestion->setContextId($this->contextId);
        $customQuestion->setLocalizedTitle($this->getData('title'));
        $customQuestion->setLocalizedDescription($this->getData('description'));
        $customQuestion->setRequired($this->getData('required') ? 1 : 0);
        $customQuestion->setQuestionType($this->getData('questionType'));

        if (in_array($this->getData('questionType'), $customQuestion->getMultipleResponsesQuestionTypes())) {
            $this->setData('possibleResponsesProcessed', $customQuestion->getPossibleResponses(null));
            ListbuilderHandler::unpack(
                $request,
                $this->getData('possibleResponses'),
                [$this, 'deleteEntry'],
                [$this, 'insertEntry'],
                [$this, 'updateEntry']
            );
            $customQuestion->setLocalizedPossibleResponses($this->getData('possibleResponsesProcessed'));
        } else {
            $customQuestion->setLocalizedPossibleResponses(null);
        }
        if ($customQuestion->getId()) {
            $customQuestionDAO->update($customQuestion);
        } else {
            $this->customQuestionId = $customQuestionDAO->insert($customQuestion);
            $customQuestionDAO->resequence($this->contextId);
        }
        parent::execute(...$functionArgs);
        return $this->customQuestionId;
    }

    public function insertEntry(Request $request, array $newRowId): bool
    {
        $possibleResponsesProcessed = (array) $this->getData('possibleResponsesProcessed');
        foreach ($newRowId['possibleResponse'] as $key => $value) {
            $possibleResponsesProcessed[$key][] = $value;
        }
        $this->setData('possibleResponsesProcessed', $possibleResponsesProcessed);
        return true;
    }

    public function deleteEntry(Request $request, string $rowId): bool
    {
        $possibleResponsesProcessed = (array) $this->getData('possibleResponsesProcessed');
        foreach (array_keys($possibleResponsesProcessed) as $locale) {
            unset($possibleResponsesProcessed[$locale][$rowId - 1]);
        }
        $this->setData('possibleResponsesProcessed', $possibleResponsesProcessed);
        return true;
    }

    public function updateEntry(Request $request, array $rowId, array $newRowId): bool
    {
        $possibleResponsesProcessed = (array) $this->getData('possibleResponsesProcessed');
        foreach ($newRowId['possibleResponse'] as $locale => $value) {
            $possibleResponsesProcessed[$locale][$rowId - 1] = $value;
        }
        $this->setData('possibleResponsesProcessed', $possibleResponsesProcessed);
        return true;
    }
}
