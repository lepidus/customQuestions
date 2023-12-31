<?php

namespace APP\plugins\generic\customQuestions\controllers\grid\form;

use APP\core\Application;
use APP\core\Request;
use APP\plugins\generic\customQuestions\classes\customQuestion\CustomQuestion;
use APP\plugins\generic\customQuestions\classes\facades\Repo;
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

        $customQuestion = Repo::customQuestion()->get(
            $this->customQuestionId,
            $this->contextId
        );
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
        $request = Application::get()->getRequest();

        if ($this->customQuestionId) {
            $customQuestion = Repo::customQuestion()->get($this->customQuestionId, $this->contextId);
        } else {
            $customQuestion = Repo::customQuestion()->newDataObject(['sequence' => REALLY_BIG_NUMBER]);
        }

        $params = [
            'contextId' => $this->contextId,
            'title' => $this->getData('title'),
            'description' => $this->getData('description'),
            'required' => $this->getData('required') ? 1 : 0,
            'questionType' => $this->getData('questionType'),
        ];

        if (in_array($this->getData('questionType'), $customQuestion->getMultipleResponsesQuestionTypes())) {
            $this->setData('possibleResponsesProcessed', $customQuestion->getPossibleResponses(null));
            ListbuilderHandler::unpack(
                $request,
                $this->getData('possibleResponses'),
                [$this, 'deleteEntry'],
                [$this, 'insertEntry'],
                [$this, 'updateEntry']
            );
            $params['possibleResponses'] = $this->getData('possibleResponsesProcessed');
        } else {
            $params['possibleResponses'] = null;
        }
        if ($customQuestion->getId()) {
            Repo::customQuestion()->edit($customQuestion, $params);
        } else {
            $customQuestion->setAllData($params);
            $this->customQuestionId = Repo::customQuestion()->add($customQuestion);
            Repo::customQuestion()->dao->resequence($this->contextId);
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
