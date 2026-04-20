<?php

namespace APP\plugins\generic\customQuestions\tests\api\v1\customQuestionResponses;

use APP\plugins\generic\customQuestions\api\v1\customQuestionResponses\CustomQuestionResponseHandler;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class CustomQuestionResponseHandlerTest extends TestCase
{
    /**
     * @dataProvider fieldNameProvider
     */
    public function testExtractCustomQuestionIdFromFieldName(string $fieldName, ?int $expectedCustomQuestionId): void
    {
        $handler = new CustomQuestionResponseHandler();
        $reflection = new ReflectionClass($handler);
        $method = $reflection->getMethod('extractCustomQuestionIdFromFieldName');
        $method->setAccessible(true);

        self::assertSame($expectedCustomQuestionId, $method->invoke($handler, $fieldName));
    }

    public static function fieldNameProvider(): array
    {
        return [
            'canonical name' => ['customQuestion-42', 42],
            'legacy name' => ['question-with-comma,-42', 42],
            'invalid name' => ['customQuestion', null],
        ];
    }
}
