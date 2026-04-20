<?php

namespace APP\plugins\generic\customQuestions\classes\components\forms;

use APP\plugins\generic\customQuestions\classes\customQuestion\CustomQuestion;
use APP\plugins\generic\customQuestions\classes\facades\Repo;
use Illuminate\Support\LazyCollection;
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
    private array $fieldDataCyByName = [];
    private array $legacyFieldNamesByName = [];

    public function __construct(string $action, array $locales, LazyCollection $customQuestions, int $submissionId)
    {
        $this->action = $action;
        $this->locales = $locales;

        foreach ($customQuestions as $customQuestion) {
            $fieldComponent = $this->getCustomQuestionFieldComponent($customQuestion, $submissionId);
            $this->addField($fieldComponent);
        }
    }

    private function getCustomQuestionFieldComponent(CustomQuestion $customQuestion, int $submissionId): Field
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

        $customQuestionResponse = Repo::customQuestionResponse()
            ->getByCustomQuestionId($customQuestion->getId(), $submissionId);

        $fieldName = $this->getFieldName($customQuestion);
        $this->legacyFieldNamesByName[$fieldName] = $this->getLegacyFieldName($customQuestion);
        $this->fieldDataCyByName[$fieldName] = $this->getFieldDataCy($customQuestion);
        $fieldComponents = [
            CustomQuestion::CUSTOM_QUESTION_TYPE_SMALL_TEXT_FIELD => new FieldText(
                $fieldName,
                [
                    'label' => $customQuestion->getLocalizedTitle(),
                    'description' => $customQuestion->getLocalizedDescription(),
                    'isMultilingual' => true,
                    'isRequired' => $customQuestion->getRequired(),
                    'size' => 'small',
                    'value' => $customQuestionResponse ? $customQuestionResponse->getValue() : null,
                ]
            ),
            CustomQuestion::CUSTOM_QUESTION_TYPE_TEXT_FIELD => new FieldText(
                $fieldName,
                [
                    'label' => $customQuestion->getLocalizedTitle(),
                    'description' => $customQuestion->getLocalizedDescription(),
                    'isMultilingual' => true,
                    'isRequired' => $customQuestion->getRequired(),
                    'size' => 'large',
                    'value' => $customQuestionResponse ? $customQuestionResponse->getValue() : null,
                ]
            ),
            CustomQuestion::CUSTOM_QUESTION_TYPE_TEXTAREA => new FieldRichTextarea(
                $fieldName,
                [
                    'label' => $customQuestion->getLocalizedTitle(),
                    'description' => $customQuestion->getLocalizedDescription(),
                    'isMultilingual' => true,
                    'isRequired' => $customQuestion->getRequired(),
                    'value' => $customQuestionResponse ? $customQuestionResponse->getValue() : null,
                ]
            ),
            CustomQuestion::CUSTOM_QUESTION_TYPE_CHECKBOXES => new FieldOptions(
                $fieldName,
                [
                    'label' => $customQuestion->getLocalizedTitle(),
                    'description' => $customQuestion->getLocalizedDescription(),
                    'isRequired' => $customQuestion->getRequired(),
                    'options' => $possibleResponses,
                    'value' => $customQuestionResponse ? $customQuestionResponse->getValue() : []
                ]
            ),
            CustomQuestion::CUSTOM_QUESTION_TYPE_RADIO_BUTTONS => new FieldOptions(
                $fieldName,
                [
                    'label' => $customQuestion->getLocalizedTitle(),
                    'description' => $customQuestion->getLocalizedDescription(),
                    'type' => 'radio',
                    'isRequired' => $customQuestion->getRequired(),
                    'options' => $possibleResponses,
                    'value' => $customQuestionResponse ? $customQuestionResponse->getValue() : []
                ]
            ),
            CustomQuestion::CUSTOM_QUESTION_TYPE_DROP_DOWN_BOX => new FieldSelect(
                $fieldName,
                [
                    'label' => $customQuestion->getLocalizedTitle(),
                    'description' => $customQuestion->getLocalizedDescription(),
                    'isRequired' => $customQuestion->getRequired(),
                    'options' => $possibleResponses,
                    'value' => $customQuestionResponse ? $customQuestionResponse->getValue() : []
                ]
            ),
        ];

        return $fieldComponents[$customQuestion->getQuestionType()];
    }

    public function getFieldConfig($field)
    {
        $config = parent::getFieldConfig($field);

        if (isset($this->legacyFieldNamesByName[$field->name])) {
            $config['legacyName'] = $this->legacyFieldNamesByName[$field->name];
        }

        if (isset($this->fieldDataCyByName[$field->name])) {
            $config['data-cy'] = $this->fieldDataCyByName[$field->name];
        }

        return $config;
    }

    private function getFieldName(CustomQuestion $customQuestion): string
    {
        return 'customQuestion-' . $customQuestion->getId();
    }

    private function getLegacyFieldName(CustomQuestion $customQuestion): string
    {
        return $this->toKebabCase($customQuestion->getLocalizedTitle()) . '-' . $customQuestion->getId();
    }

    private function getFieldDataCy(CustomQuestion $customQuestion): string
    {
        return 'custom-question-field-' . $customQuestion->getId();
    }

    private function toKebabCase(string $text): string
    {
        return strtolower(str_replace(' ', '-', $text));
    }
}
