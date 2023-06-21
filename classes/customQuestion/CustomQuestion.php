<?php

namespace APP\plugins\generic\customQuestions\classes\customQuestion;

class CustomQuestion extends \PKP\core\DataObject
{
    public const CUSTOM_QUESTION_TYPE_SMALL_TEXT_FIELD = 1;
    public const CUSTOM_QUESTION_TYPE_TEXT_FIELD = 2;
    public const CUSTOM_QUESTION_TYPE_TEXTAREA = 3;
    public const CUSTOM_QUESTION_TYPE_CHECKBOXES = 4;
    public const CUSTOM_QUESTION_TYPE_RADIO_BUTTONS = 5;
    public const CUSTOM_QUESTION_TYPE_DROP_DOWN_BOX = 6;

    public function getLocalizedTitle(): ?string
    {
        return $this->getLocalizedData('title');
    }

    public function setLocalizedTitle(array $title): void
    {
        $this->setData('title', $title, null);
    }

    public function getLocalizedDescription(): ?string
    {
        return $this->getLocalizedData('description');
    }

    public function setLocalizedDescription(array $description): void
    {
        $this->setData('description', $description, null);
    }

    public function getLocalizedPossibleResponses(): ?array
    {
        return $this->getLocalizedData('possibleResponses');
    }

    public function setLocalizedPossibleResponses(?array $possibleResponses): void
    {
        $this->setData('possibleResponses', $possibleResponses, null);
    }

    public function getContextId(): int
    {
        return $this->getData('contextId');
    }

    public function setContextId(int $contextId): void
    {
        $this->setData('contextId', $contextId);
    }

    public function getSequence(): ?float
    {
        return $this->getData('sequence');
    }

    public function setSequence(float $sequence): void
    {
        $this->setData('sequence', $sequence);
    }

    public function getQuestionType(): ?string
    {
        return $this->getData('questionType');
    }

    public function setQuestionType(string $questionType): void
    {
        $this->setData('questionType', $questionType);
    }

    public function getRequired(): ?bool
    {
        return $this->getData('required');
    }

    public function setRequired(bool $required): void
    {
        $this->setData('required', $required);
    }

    public function getTitle(?string $locale): ?string
    {
        return $this->getData('title', $locale);
    }

    public function setTitle(string $title, ?string $locale): void
    {
        $this->setData('title', $title, $locale);
    }

    public function getDescription(?string $locale): ?string
    {
        return $this->getData('description', $locale);
    }

    public function setDescription(string $description, ?string $locale): void
    {
        $this->setData('description', $description, $locale);
    }

    public function getPossibleResponses(?string $locale): ?array
    {
        return $this->getData('possibleResponses', $locale);
    }

    public function setPossibleResponses(array $possibleResponses, ?string $locale): void
    {
        $this->setData('possibleResponses', $possibleResponses, $locale);
    }

    public function getCustomQuestionTypeOptions(): array
    {
        return [
            '' => 'plugins.generic.customQuestions.chooseType',
            self::CUSTOM_QUESTION_TYPE_SMALL_TEXT_FIELD => 'plugins.generic.customQuestions.smalltextfield',
            self::CUSTOM_QUESTION_TYPE_TEXT_FIELD => 'plugins.generic.customQuestions.textfield',
            self::CUSTOM_QUESTION_TYPE_TEXTAREA => 'plugins.generic.customQuestions.textarea',
            self::CUSTOM_QUESTION_TYPE_CHECKBOXES => 'plugins.generic.customQuestions.checkboxes',
            self::CUSTOM_QUESTION_TYPE_RADIO_BUTTONS => 'plugins.generic.customQuestions.radiobuttons',
            self::CUSTOM_QUESTION_TYPE_DROP_DOWN_BOX => 'plugins.generic.customQuestions.dropdownbox',
        ];
    }

    public function getMultipleResponsesQuestionTypes(): array
    {
        return [
            self::CUSTOM_QUESTION_TYPE_CHECKBOXES,
            self::CUSTOM_QUESTION_TYPE_RADIO_BUTTONS,
            self::CUSTOM_QUESTION_TYPE_DROP_DOWN_BOX,
        ];
    }

    public function getCustomQuestionResponseType(): string
    {
        switch ($this->getQuestionType()) {
            case self::CUSTOM_QUESTION_TYPE_SMALL_TEXT_FIELD:
            case self::CUSTOM_QUESTION_TYPE_TEXT_FIELD:
            case self::CUSTOM_QUESTION_TYPE_TEXTAREA:
                return 'string';
                break;
            case self::CUSTOM_QUESTION_TYPE_RADIO_BUTTONS:
            case self::CUSTOM_QUESTION_TYPE_DROP_DOWN_BOX:
                return 'int';
                break;
            case self::CUSTOM_QUESTION_TYPE_CHECKBOXES:
                return 'array';
                break;
        }
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\plugins\generic\customQuestions\classes\customQuestion\CustomQuestion', '\CustomQuestion');
    foreach (
        [
            'CUSTOM_QUESTION_TYPE_SMALL_TEXT_FIELD',
            'CUSTOM_QUESTION_TYPE_TEXT_FIELD',
            'CUSTOM_QUESTION_TYPE_TEXTAREA',
            'CUSTOM_QUESTION_TYPE_CHECKBOXES',
            'CUSTOM_QUESTION_TYPE_RADIO_BUTTONS',
            'CUSTOM_QUESTION_TYPE_DROP_DOWN_BOX',
        ] as $constantName
    ) {
        define($constantName, constant('\CustomQuestion::' . $constantName));
    }
}
