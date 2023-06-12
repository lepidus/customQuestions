<?php

namespace APP\plugins\generic\customQuestions\tests\classes\customQuestionResponse;

use APP\facades\Repo;
use APP\plugins\generic\customQuestions\classes\customQuestion\CustomQuestion;
use APP\plugins\generic\customQuestions\classes\customQuestion\DAO as CustomQuestionDAO;
use APP\plugins\generic\customQuestions\classes\customQuestionResponse\CustomQuestionResponse;
use APP\plugins\generic\customQuestions\classes\customQuestionResponse\DAO as CustomQuestionResponseDAO;
use PKP\plugins\Hook;
use PKP\tests\DatabaseTestCase;

class DAOTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Hook::add('Schema::get::customQuestionResponse', function ($hookName, $args) {
            $schema = & $args[0];
            $schemaFile = sprintf(
                '%s/plugins/generic/customQuestions/schemas/%s.json',
                BASE_SYS_DIR,
                'customQuestionResponse'
            );
            if (file_exists($schemaFile)) {
                $schema = json_decode(file_get_contents($schemaFile));
                if (!$schema) {
                    throw new \Exception(
                        'Schema failed to decode. This usually means it is invalid JSON. Requested: '
                        . $schemaFile
                        . '. Last JSON error: '
                        . json_last_error()
                    );
                }
            }
            return true;
        });
    }

    protected function getAffectedTables(): array
    {
        return ['custom_question_responses', 'submissions', 'custom_questions', 'custom_question_settings'];
    }

    private function createTestSubmission(): int
    {
        $submission = Repo::submission()->newDataObject();
        $submission->setData('contextId', 1);
        $submission->setData('currentPublicationId', null);
        return Repo::submission()->dao->insert($submission);
    }

    public function testCreateNewDataObject(): void
    {
        $customQuestionResponseDAO = app(CustomQuestionResponseDAO::class);
        $customQuestionResponse = $customQuestionResponseDAO->newDataObject();
        self::assertInstanceOf(CustomQuestionResponse::class, $customQuestionResponse);
    }

    public function testCrud(): void
    {
        $customQuestionDAO = app(CustomQuestionDAO::class);
        $customQuestion = $customQuestionDAO->newDataObject();
        $customQuestion->setContextId(1);
        $customQuestion->setTitle('Test Custom Question', 'en');
        $customQuestion->setQuestionType(CustomQuestion::CUSTOM_QUESTION_TYPE_SMALL_TEXT_FIELD);
        $customQuestionDAO->insert($customQuestion);

        $submissionId = $this->createTestSubmission();

        $customQuestionResponseDAO = app(CustomQuestionResponseDAO::class);
        $customQuestionResponse = $customQuestionResponseDAO->newDataObject();
        $customQuestionResponse->setSubmissionId($submissionId);
        $customQuestionResponse->setCustomQuestionId($customQuestion->getId());
        $customQuestionResponse->setValue('question response');
        $customQuestionResponse->setResponseType('text');
        $customQuestionResponseDAO->insert($customQuestionResponse);

        $fetchedCustomQuestionResponse = $customQuestionResponseDAO->get($customQuestionResponse->getId());
        self::assertEquals([
            'id' => $customQuestionResponse->getId(),
            'submissionId' => $submissionId,
            'customQuestionId' => $customQuestion->getId(),
            'value' => 'question response',
            'responseType' => 'text'
        ], $fetchedCustomQuestionResponse->_data);

        $customQuestionResponse->setValue(['option1', 'option2']);
        $customQuestionResponse->setResponseType('array');
        $customQuestionResponseDAO->update($customQuestionResponse);

        $fetchedCustomQuestionResponse = $customQuestionResponseDAO->getByCustomQuestionId(
            $customQuestion->getId(),
            $submissionId
        );
        self::assertEquals([
            'id' => $customQuestionResponse->getId(),
            'submissionId' => $submissionId,
            'customQuestionId' => $customQuestion->getId(),
            'value' => serialize(['option1', 'option2']),
            'responseType' => 'array'
        ], $fetchedCustomQuestionResponse->_data);

        $customQuestionResponseDAO->delete($customQuestionResponse);
        $fetchedCustomQuestionResponse = $customQuestionResponseDAO->get($customQuestionResponse->getId());
        self::assertNull($fetchedCustomQuestionResponse);
    }
}
