<?php

namespace APP\plugins\generic\customQuestions\controllers\grid\form;

use PKP\form\Form;
use PKP\db\DAORegistry;
use APP\core\Application;
use PKP\security\Validation;
use APP\template\TemplateManager;
use PKP\reviewForm\ReviewFormElement;
use PKP\controllers\listbuilder\ListbuilderHandler;
use APP\plugins\generic\customQuestions\classes\customQuestion\CustomQuestion;

class CustomQuestionForm extends Form
{
    public $customQuestionId;

    public function __construct(string $template, int $customQuestionId = null)
    {
        parent::__construct($template);

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
            'multipleResponsesQuestionTypes' => $customQuestion->getMultipleResponsesQuestionTypes(),
            'multipleResponsesQuestionTypesString' => $multipleResponsesQuestionTypesString,
            'customQuestionTypeOptions' => $customQuestion->getCustomQuestionTypeOptions(),
        ]);
        return parent::fetch($request, $template, $display);
    }

    public function readInputData()
    {
        $this->readUserVars(['title', 'description', 'required', 'questionType', 'possibleResponses']);
    }

    public function execute(...$functionArgs)
    {
        $customQuestionDAO = DAORegistry::getDAO('CustomQuestionDAO');
        $request = Application::get()->getRequest();

        if ($this->customQuestionId) {
            $customQuestion = $customQuestionDAO->getById($this->customQuestionId);
        } else {
            $customQuestion = $customQuestionDAO->newDataObject();
            $customQuestion->setSequence(REALLY_BIG_NUMBER);
        }

        $customQuestion->setTitle($this->getData('title'), null);
        $customQuestion->setDescription($this->getData('description'), null);
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
            $customQuestion->setPossibleResponses($this->getData('possibleResponsesProcessed'), null);
        } else {
            $customQuestion->setPossibleResponses(null, null);
        }
        if ($customQuestion->getId()) {
            $customQuestionDAO->deleteSetting($customQuestion->getId(), 'possibleResponses');
            $customQuestionDAO->updateObject($customQuestion);
        } else {
            $this->customQuestionId = $customQuestionDAO->insertObject($customQuestion);
            $customQuestionDAO->resequenceReviewFormElements();
        }
        parent::execute(...$functionArgs);
        return $this->customQuestionId;
    }

    public function insertEntry(PKPRequest $request, int $newRowId): bool
    {
        $possibleResponsesProcessed = (array) $this->getData('possibleResponsesProcessed');
        foreach ($newRowId['possibleResponse'] as $key => $value) {
            $possibleResponsesProcessed[$key][] = $value;
        }
        $this->setData('possibleResponsesProcessed', $possibleResponsesProcessed);
        return true;
    }

    public function deleteEntry(PKPRequest $request, int $newRowId): bool
    {
        $possibleResponsesProcessed = (array) $this->getData('possibleResponsesProcessed');
        foreach (array_keys($possibleResponsesProcessed) as $locale) {
            unset($possibleResponsesProcessed[$locale][$rowId - 1]);
        }
        $this->setData('possibleResponsesProcessed', $possibleResponsesProcessed);
        return true;
    }

    public function updateEntry(PKPRequest $request, int $rowId, int $newRowId): bool
    {
        $possibleResponsesProcessed = (array) $this->getData('possibleResponsesProcessed');
        foreach ($newRowId['possibleResponse'] as $locale => $value) {
            $possibleResponsesProcessed[$locale][$rowId - 1] = $value;
        }
        $this->setData('possibleResponsesProcessed', $possibleResponsesProcessed);
        return true;
    }
}
