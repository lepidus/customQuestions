<?php

namespace APP\plugins\generic\customQuestions\tests\classes\customQuestion;

use APP\plugins\generic\customQuestions\classes\customQuestionResponse\CustomQuestionResponse;
use PKP\tests\PKPTestCase;

class CustomQuestionResponseTest extends PKPTestCase
{
    public function testGettersAndSetters(): void
    {
        $submissionId = 1;
        $customQuestionId = 1;
        $value = 'Test question response value';
        $responseType = 'text';

        $customQuestionResponse = new CustomQuestionResponse();
        $customQuestionResponse->setSubmissionId($submissionId);
        $customQuestionResponse->setCustomQuestionId($customQuestionId);
        $customQuestionResponse->setValue($value);
        $customQuestionResponse->setResponseType($responseType);

        self::assertEquals($submissionId, $customQuestionResponse->getSubmissionId());
        self::assertEquals($customQuestionId, $customQuestionResponse->getCustomQuestionId());
        self::assertEquals($value, $customQuestionResponse->getValue());
        self::assertEquals($responseType, $customQuestionResponse->getResponseType());
    }
}
