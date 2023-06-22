<?php

namespace APP\plugins\generic\customQuestions\tests\classes\customQuestionResponse;

use APP\plugins\generic\customQuestions\classes\customQuestion\CustomQuestion;
use APP\plugins\generic\customQuestions\classes\customQuestionResponse\CustomQuestionResponse;
use APP\plugins\generic\customQuestions\classes\customQuestionResponse\DAO as CustomQuestionResponseDAO;
use APP\plugins\generic\customQuestions\classes\facades\Repo;
use APP\plugins\generic\customQuestions\tests\CustomQuestionsTestCase;

class DAOTest extends CustomQuestionsTestCase
{
    private $submissionId;
    private $publicationId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createSubmission();
    }

    protected function tearDown(): void
    {
        $submission = Repo::submission()->get($this->submissionId);
        Repo::submission()->delete($submission);

        parent::tearDown();
    }

    private function createSubmission(): void
    {
        $submission = Repo::submission()->newDataObject();
        $submission->setData('contextId', $this->contextId);
        $this->submissionId = Repo::submission()->dao->insert($submission);

        $publication = Repo::publication()->newDataObject();
        $publication->setData('submissionId', $submission->getId());
        $this->publicationId = Repo::publication()->dao->insert($publication);

        $submission->setData('currentPublicationId', $publication->getId());
        Repo::submission()->dao->update($submission);
    }

    public function testCreateNewDataObject(): void
    {
        $customQuestionResponseDAO = app(CustomQuestionResponseDAO::class);
        $customQuestionResponse = $customQuestionResponseDAO->newDataObject();
        self::assertInstanceOf(CustomQuestionResponse::class, $customQuestionResponse);
    }

    public function testCrud(): void
    {
        $customQuestion = Repo::customQuestion()->newDataObject();
        $customQuestion->setContextId($this->contextId);
        $customQuestion->setTitle('Test Custom Question', 'en');
        $customQuestion->setQuestionType(CustomQuestion::CUSTOM_QUESTION_TYPE_SMALL_TEXT_FIELD);
        $customQuestionId = Repo::customQuestion()->add($customQuestion);

        $customQuestionResponseDAO = app(CustomQuestionResponseDAO::class);
        $customQuestionResponse = $customQuestionResponseDAO->newDataObject();
        $customQuestionResponse->setSubmissionId($this->submissionId);
        $customQuestionResponse->setCustomQuestionId($customQuestionId);
        $customQuestionResponse->setValue(['en' => 'question response']);
        $customQuestionResponse->setResponseType('string');
        $customQuestionResponseDAO->insert($customQuestionResponse);

        $fetchedCustomQuestionResponse = $customQuestionResponseDAO->get($customQuestionResponse->getId());
        self::assertEquals([
            'id' => $customQuestionResponse->getId(),
            'submissionId' => $this->submissionId,
            'customQuestionId' => $customQuestionId,
            'value' => ['en' => 'question response'],
            'responseType' => 'string'
        ], $fetchedCustomQuestionResponse->_data);

        $customQuestionResponse->setValue(['option1', 'option2']);
        $customQuestionResponse->setResponseType('array');
        $customQuestionResponseDAO->update($customQuestionResponse);

        $fetchedCustomQuestionResponse = $customQuestionResponseDAO->getByCustomQuestionId(
            $customQuestionId,
            $this->submissionId
        );
        self::assertEquals([
            'id' => $customQuestionResponse->getId(),
            'submissionId' => $this->submissionId,
            'customQuestionId' => $customQuestion->getId(),
            'value' => ['option1', 'option2'],
            'responseType' => 'array'
        ], $fetchedCustomQuestionResponse->_data);

        $customQuestionResponseDAO->delete($customQuestionResponse);
        $fetchedCustomQuestionResponse = $customQuestionResponseDAO->get($customQuestionResponse->getId());
        self::assertNull($fetchedCustomQuestionResponse);
    }
}
