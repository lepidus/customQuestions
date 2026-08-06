<?php

namespace APP\plugins\generic\customQuestions\classes\customQuestionResponse;

use APP\plugins\generic\customQuestions\classes\customQuestion\CustomQuestion;
use UnexpectedValueException;

class CustomQuestionResponseValidator
{
    public function getQuestionId(string $fieldName): ?int
    {
        if (!preg_match('/^customQuestion-([1-9][0-9]*)$/', $fieldName, $matches)) {
            return null;
        }
        return (int) $matches[1];
    }

    public function normalize(CustomQuestion $customQuestion, mixed $value): mixed
    {
        return match ($customQuestion->getQuestionType()) {
            CustomQuestion::CUSTOM_QUESTION_TYPE_SMALL_TEXT_FIELD,
            CustomQuestion::CUSTOM_QUESTION_TYPE_TEXT_FIELD,
            CustomQuestion::CUSTOM_QUESTION_TYPE_TEXTAREA => $this->normalizeText($value),
            CustomQuestion::CUSTOM_QUESTION_TYPE_CHECKBOXES => $this->normalizeCheckboxes(
                $customQuestion,
                $value
            ),
            CustomQuestion::CUSTOM_QUESTION_TYPE_RADIO_BUTTONS,
            CustomQuestion::CUSTOM_QUESTION_TYPE_DROP_DOWN_BOX => $this->normalizeSingleOption(
                $customQuestion,
                $value
            ),
            default => throw new UnexpectedValueException('Unsupported custom question type.'),
        };
    }

    private function normalizeText(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }
        if (!is_array($value)) {
            throw new UnexpectedValueException('Text responses must be localized values.');
        }
        foreach ($value as $locale => &$localizedValue) {
            if (!is_string($locale) || (!is_string($localizedValue) && $localizedValue !== null)) {
                throw new UnexpectedValueException('Text responses contain an invalid localized value.');
            }
            $localizedValue ??= '';
        }
        unset($localizedValue);
        return $value;
    }

    private function normalizeCheckboxes(CustomQuestion $customQuestion, mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        if (!is_array($value)) {
            throw new UnexpectedValueException('Checkbox responses must be a list.');
        }
        $normalized = array_map(fn ($option) => $this->normalizeOptionIndex($option), $value);
        $normalized = array_values(array_unique($normalized));
        foreach ($normalized as $option) {
            $this->assertAllowedOption($customQuestion, $option);
        }
        return $normalized;
    }

    private function normalizeSingleOption(CustomQuestion $customQuestion, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }
        $normalized = $this->normalizeOptionIndex($value);
        $this->assertAllowedOption($customQuestion, $normalized);
        return $normalized;
    }

    private function normalizeOptionIndex(mixed $value): int
    {
        if (is_int($value) && $value >= 0) {
            return $value;
        }
        if (is_string($value) && ctype_digit($value)) {
            return (int) $value;
        }
        throw new UnexpectedValueException('Option responses must use a non-negative integer index.');
    }

    private function assertAllowedOption(CustomQuestion $customQuestion, int $option): void
    {
        $possibleResponses = $customQuestion->getData('possibleResponses');
        if (!is_array($possibleResponses)) {
            throw new UnexpectedValueException('Custom question has no response options.');
        }
        $allowedResponses = $possibleResponses;
        if (!array_is_list($possibleResponses) || !array_reduce(
            $possibleResponses,
            fn (bool $allStrings, mixed $response): bool => $allStrings && is_string($response),
            true
        )) {
            $allowedResponses = reset($possibleResponses);
        }
        if (!is_array($allowedResponses) || !array_key_exists($option, $allowedResponses)) {
            throw new UnexpectedValueException('Response option is not allowed.');
        }
    }
}
