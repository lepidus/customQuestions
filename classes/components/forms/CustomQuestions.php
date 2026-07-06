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
        $customQuestionResponse = Repo::customQuestionResponse()
            ->getByCustomQuestionId($customQuestion->getId(), $submissionId);

        $fieldName = 'customQuestion-' . $customQuestion->getId();
        $responseValue = $customQuestionResponse ? $customQuestionResponse->getValue() : null;
        $baseConfig = $this->getBaseFieldConfig($customQuestion);
        $optionConfig = array_merge(
            $baseConfig,
            [
                'options' => $this->getPossibleResponseOptions($customQuestion),
                'value' => $responseValue ?? [],
            ]
        );

        return match ($customQuestion->getQuestionType()) {
            CustomQuestion::CUSTOM_QUESTION_TYPE_SMALL_TEXT_FIELD => new FieldText(
                $fieldName,
                array_merge(
                    $baseConfig,
                    [
                        'isMultilingual' => true,
                        'size' => 'small',
                        'value' => $responseValue,
                    ]
                )
            ),
            CustomQuestion::CUSTOM_QUESTION_TYPE_TEXT_FIELD => new FieldText(
                $fieldName,
                array_merge(
                    $baseConfig,
                    [
                        'isMultilingual' => true,
                        'size' => 'large',
                        'value' => $responseValue,
                    ]
                )
            ),
            CustomQuestion::CUSTOM_QUESTION_TYPE_TEXTAREA => new FieldRichTextarea(
                $fieldName,
                array_merge(
                    $baseConfig,
                    [
                        'isMultilingual' => true,
                        'value' => $responseValue,
                    ]
                )
            ),
            CustomQuestion::CUSTOM_QUESTION_TYPE_CHECKBOXES => new FieldOptions(
                $fieldName,
                $optionConfig
            ),
            CustomQuestion::CUSTOM_QUESTION_TYPE_RADIO_BUTTONS => new FieldOptions(
                $fieldName,
                array_merge(
                    $optionConfig,
                    [
                        'type' => 'radio',
                    ]
                )
            ),
            CustomQuestion::CUSTOM_QUESTION_TYPE_DROP_DOWN_BOX => new FieldSelect(
                $fieldName,
                $optionConfig
            ),
        };
    }

    private function getBaseFieldConfig(CustomQuestion $customQuestion): array
    {
        return [
            'label' => $customQuestion->getLocalizedTitle(),
            'description' => $this->removeDescriptionWrapperParagraph($customQuestion->getLocalizedDescription()),
            'isRequired' => $customQuestion->getRequired(),
        ];
    }

    private function getPossibleResponseOptions(CustomQuestion $customQuestion): array
    {
        $possibleResponses = [];
        if (!$customQuestion->getLocalizedPossibleResponses()) {
            return $possibleResponses;
        }

        foreach ($customQuestion->getLocalizedPossibleResponses() as $index => $responseItem) {
            $possibleResponses[] = [
                'value' => $index,
                'label' => $responseItem,
            ];
        }

        return $possibleResponses;
    }

    private function removeDescriptionWrapperParagraph($description)
    {
        if (!is_string($description)) {
            return $description;
        }

        $trimmedDescription = trim($description);
        if (!preg_match('/^<p\b[^>]*>(.*)<\/p>$/is', $trimmedDescription, $matches)) {
            return $description;
        }

        if (preg_match('/<\/p>\s*<p\b/is', $matches[1])) {
            return $description;
        }

        return trim($matches[1]);
    }
}
