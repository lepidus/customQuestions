<?php

namespace APP\plugins\generic\customQuestions\classes\components\forms;

use APP\plugins\generic\customQuestions\classes\customQuestion\CustomQuestion;
use PKP\components\forms\Field;
use PKP\components\forms\FieldOptions;
use PKP\components\forms\FieldRichTextarea;
use PKP\components\forms\FieldSelect;
use PKP\components\forms\FieldText;
use PKP\components\forms\FormComponent;

class CustomQuestions extends FormComponent
{
    public $id = 'customQuestions';
    public $method = 'PUT';

    public function __construct(string $action, array $locales, array $customQuestions)
    {
        $this->action = $action;
        $this->locales = $locales;

        foreach ($customQuestions as $customQuestion) {
            $fieldComponent = $this->getCustomQuestionFieldComponent($customQuestion);
            $this->addField($fieldComponent);
        }
    }

    private function getCustomQuestionFieldComponent(CustomQuestion $customQuestion): Field
    {
        $possibleResponses = [];
        if ($customQuestion->getLocalizedPossibleResponses()) {
            foreach ($customQuestion->getLocalizedPossibleResponses() as $index => $responseItem) {
                $possibleResponses[] = [
                    'value' => $index,
                    'label' => $responseItem,
                ];
            }
        }

        $fieldComponents = [
            CustomQuestion::CUSTOM_QUESTION_TYPE_SMALL_TEXT_FIELD => new FieldText(
                $customQuestion->getLocalizedTitle(),
                [
                    'label' => $customQuestion->getLocalizedTitle(),
                    'description' => $customQuestion->getLocalizedDescription(),
                    'isMultilingual' => true,
                    'isRequired' => $customQuestion->getRequired(),
                    'size' => 'small',
                ]
            ),
            CustomQuestion::CUSTOM_QUESTION_TYPE_TEXT_FIELD => new FieldText(
                $customQuestion->getLocalizedTitle(),
                [
                    'label' => $customQuestion->getLocalizedTitle(),
                    'description' => $customQuestion->getLocalizedDescription(),
                    'isMultilingual' => true,
                    'isRequired' => $customQuestion->getRequired(),
                ]
            ),
            CustomQuestion::CUSTOM_QUESTION_TYPE_TEXTAREA => new FieldRichTextarea(
                $customQuestion->getLocalizedTitle(),
                [
                    'label' => $customQuestion->getLocalizedTitle(),
                    'description' => $customQuestion->getLocalizedDescription(),
                    'isMultilingual' => true,
                    'isRequired' => $customQuestion->getRequired(),
                ]
            ),
            CustomQuestion::CUSTOM_QUESTION_TYPE_CHECKBOXES => new FieldOptions(
                $customQuestion->getLocalizedTitle(),
                [
                    'label' => $customQuestion->getLocalizedTitle(),
                    'description' => $customQuestion->getLocalizedDescription(),
                    'isRequired' => $customQuestion->getRequired(),
                    'options' => $possibleResponses,
                    'value' => []
                ]
            ),
            CustomQuestion::CUSTOM_QUESTION_TYPE_RADIO_BUTTONS => new FieldOptions(
                $customQuestion->getLocalizedTitle(),
                [
                    'label' => $customQuestion->getLocalizedTitle(),
                    'description' => $customQuestion->getLocalizedDescription(),
                    'type' => 'radio',
                    'isRequired' => $customQuestion->getRequired(),
                    'options' => $possibleResponses,
                ]
            ),
            CustomQuestion::CUSTOM_QUESTION_TYPE_DROP_DOWN_BOX => new FieldSelect(
                $customQuestion->getLocalizedTitle(),
                [
                    'label' => $customQuestion->getLocalizedTitle(),
                    'description' => $customQuestion->getLocalizedDescription(),
                    'isRequired' => $customQuestion->getRequired(),
                    'options' => $possibleResponses,
                ]
            ),
        ];

        return $fieldComponents[$customQuestion->getQuestionType()];
    }
}
