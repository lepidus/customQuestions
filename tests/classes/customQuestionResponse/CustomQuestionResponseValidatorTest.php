<?php

namespace APP\plugins\generic\customQuestions\tests\classes\customQuestionResponse;

use APP\plugins\generic\customQuestions\classes\customQuestion\CustomQuestion;
use APP\plugins\generic\customQuestions\classes\customQuestionResponse\CustomQuestionResponseValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class CustomQuestionResponseValidatorTest extends TestCase
{
    #[DataProvider('validResponses')]
    public function testItNormalizesValidResponses(int $questionType, mixed $value, mixed $expected): void
    {
        $question = new CustomQuestion();
        $question->setQuestionType($questionType);
        $question->setPossibleResponses(['First', 'Second', 'Third'], 'en');

        self::assertSame(
            $expected,
            (new CustomQuestionResponseValidator())->normalize($question, $value)
        );
    }

    public static function validResponses(): array
    {
        return [
            'localized text' => [1, ['en' => 'Response'], ['en' => 'Response']],
            'empty localized text' => [1, ['en' => 'Response', 'fr_CA' => null], ['en' => 'Response', 'fr_CA' => '']],
            'empty draft text' => [2, [], []],
            'checkbox indexes' => [4, ['0', 2], [0, 2]],
            'radio zero' => [5, '0', 0],
            'empty draft select' => [6, '', ''],
        ];
    }

    public function testItNormalizesAnOptionAgainstAFlatPersistedResponseList(): void
    {
        $question = new CustomQuestion();
        $question->setQuestionType(CustomQuestion::CUSTOM_QUESTION_TYPE_DROP_DOWN_BOX);
        $question->setData('possibleResponses', ['First', 'Second', 'Third']);

        self::assertSame(2, (new CustomQuestionResponseValidator())->normalize($question, '2'));
    }

    #[DataProvider('invalidResponses')]
    public function testItRejectsInvalidResponses(int $questionType, mixed $value): void
    {
        $question = new CustomQuestion();
        $question->setQuestionType($questionType);
        $question->setPossibleResponses(['First', 'Second', 'Third'], 'en');

        $this->expectException(UnexpectedValueException::class);
        (new CustomQuestionResponseValidator())->normalize($question, $value);
    }

    public static function invalidResponses(): array
    {
        return [
            'text scalar' => [1, 'Response'],
            'text nested value' => [3, ['en' => ['Response']]],
            'checkbox scalar' => [4, 0],
            'checkbox unknown option' => [4, [3]],
            'radio array' => [5, [1]],
            'select unknown option' => [6, 9],
        ];
    }

    #[DataProvider('fieldNames')]
    public function testItAcceptsOnlyCanonicalFieldNames(string $fieldName, ?int $expected): void
    {
        self::assertSame(
            $expected,
            (new CustomQuestionResponseValidator())->getQuestionId($fieldName)
        );
    }

    public static function fieldNames(): array
    {
        return [
            'canonical' => ['customQuestion-42', 42],
            'wrong prefix' => ['other-42', null],
            'extra segment' => ['customQuestion-extra-42', null],
            'zero' => ['customQuestion-0', null],
            'negative' => ['customQuestion--1', null],
        ];
    }
}
