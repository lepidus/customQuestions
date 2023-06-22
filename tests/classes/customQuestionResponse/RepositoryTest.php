<?php

namespace APP\plugins\generic\customQuestions\tests\classes\customQuestionResponse;

use APP\plugins\generic\customQuestions\classes\customQuestion\CustomQuestion;
use APP\plugins\generic\customQuestions\classes\customQuestionResponse\CustomQuestionResponse;
use APP\plugins\generic\customQuestions\classes\customQuestionResponse\Repository;
use APP\plugins\generic\customQuestions\classes\facades\Repo;
use APP\plugins\generic\customQuestions\tests\CustomQuestionsTestCase;

class RepositoryTest extends CustomQuestionsTestCase
{
    public function testGetNewCustomQuestionResponseObject(): void
    {
        $repository = app(Repository::class);
        $customQuestionResponse = $repository->newDataObject();
        self::assertInstanceOf(CustomQuestionResponse::class, $customQuestionResponse);

        $params = [
            'id' => rand(1, 10),
            'submissionId' => rand(1, 10),
            'customQuestionId' => rand(1, 10),
            'value' => 'Test response',
            'responseType' => 'string',
        ];
        $customQuestionResponse = $repository->newDataObject($params);
        self::assertEquals($params, $customQuestionResponse->_data);
    }

    public function testCrud(): void
    {
        $customQuestion = Repo::customQuestion()->newDataObject([
            'contextId' => $this->contextId,
            'title' => 'Test Custom Question',
            'questionType' => CustomQuestion::CUSTOM_QUESTION_TYPE_SMALL_TEXT_FIELD,
        ]);
        $customQuestionId = Repo::customQuestion()->add($customQuestion);

        $params = [
            'submissionId' => $this->submissionId,
            'customQuestionId' => $customQuestionId,
            'value' => ['en' => 'Test response'],
            'responseType' => 'string',
        ];

        $repository = app(Repository::class);
        $customQuestionResponse = $repository->newDataObject($params);
        $params['id'] = $repository->add($customQuestionResponse);

        $fetchedCustomQuestionResponse = $repository->get($customQuestionResponse->getId());
        self::assertEquals($params, $fetchedCustomQuestionResponse->_data);

        $params['value'] = ['option1', 'option2'];
        $params['responseType'] = 'array';
        $repository->edit($customQuestionResponse, $params);

        $fetchedCustomQuestionResponse = $repository->getByCustomQuestionId($customQuestionId, $this->submissionId);
        self::assertEquals($params, $fetchedCustomQuestionResponse->_data);

        $customQuestionResponses = $repository->getCollector()
            ->filterBySubmissionIds([$this->submissionId])
            ->filterByCustomQuestionIds([$customQuestionId])
            ->getMany();

        self::assertEquals(
            [$fetchedCustomQuestionResponse->getId() => $fetchedCustomQuestionResponse],
            $customQuestionResponses->all()
        );

        $repository->delete($customQuestionResponse);
        self::assertFalse($repository->exists($customQuestionResponse->getId(), $customQuestionId));
    }
}
