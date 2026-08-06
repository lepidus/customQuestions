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

    public function getLocalizedTitle(?string $preferredLocale = null)
    {
        return $this->getLocalizedCustomQuestionData('title', $preferredLocale);
    }

    public function getLocalizedDescription(?string $preferredLocale = null)
    {
        return $this->getLocalizedCustomQuestionData('description', $preferredLocale);
    }

    public function getLocalizedPossibleResponses(?string $preferredLocale = null)
    {
        return $this->getLocalizedCustomQuestionData('possibleResponses', $preferredLocale);
    }

    private function getLocalizedCustomQuestionData(string $key, ?string $preferredLocale = null)
    {
        if ($preferredLocale === null) {
            return $this->getLocalizedData($key);
        }

        $value = $this->getData($key);
        if (!is_array($value)) {
            return $value;
        }

        if (!empty($value[$preferredLocale])) {
            return $value[$preferredLocale];
        }

        foreach ($value as $localizedValue) {
            if (!empty($localizedValue)) {
                return $localizedValue;
            }
        }

        return null;
    }

    public function getContextId()
    {
        return $this->getData('contextId');
    }

    public function setContextId($contextId)
    {
        $this->setData('contextId', $contextId);
    }

    public function getSequence()
    {
        return $this->getData('sequence');
    }

    public function setSequence($sequence)
    {
        $this->setData('sequence', $sequence);
    }

    public function getQuestionType()
    {
        return $this->getData('questionType');
    }

    public function setQuestionType($questionType)
    {
        $this->setData('questionType', $questionType);
    }

    public function getRequired()
    {
        return $this->getData('required');
    }

    public function setRequired($required)
    {
        $this->setData('required', $required);
    }

    public function getTitle($locale)
    {
        return $this->getData('title', $locale);
    }

    public function setTitle($title, $locale)
    {
        $this->setData('title', $title, $locale);
    }

    public function getDescription($locale)
    {
        return $this->getData('description', $locale);
    }

    public function setDescription($description, $locale)
    {
        $this->setData('description', $description, $locale);
    }

    public function getPossibleResponses($locale)
    {
        return $this->getData('possibleResponses', $locale);
    }

    public function setPossibleResponses($possibleResponses, $locale)
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
