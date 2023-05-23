<?php

namespace APP\plugins\generic\customQuestions;

use APP\core\Application;
use APP\template\TemplateManager;
use PKP\controllers\listbuilder\ListbuilderHandler;
use PKP\db\DAORegistry;
use PKP\form\Form;
use PKP\reviewForm\ReviewFormElement;
use PKP\security\Validation;

class CustomQuestionForm extends Form
{
    public $customQuestionId;

    public function __construct(string $template, int $customQuestionId = null)
    {
        parent::__construct($template);

        $this->customQuestionId = $customQuestionId;

        $this->addCheck(new \PKP\form\validation\FormValidatorLocale(
            $this,
            'question',
            'required',
            'manager.reviewFormElements.form.questionRequired'
        ));
        $this->addCheck(new \PKP\form\validation\FormValidator(
            $this,
            'elementType',
            'required',
            'manager.reviewFormElements.form.elementTypeRequired'
        ));
        $this->addCheck(new \PKP\form\validation\FormValidatorPost($this));
        $this->addCheck(new \PKP\form\validation\FormValidatorCSRF($this));
    }
}
