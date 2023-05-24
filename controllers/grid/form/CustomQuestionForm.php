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
}
