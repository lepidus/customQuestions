<?php

namespace APP\plugins\generic\customQuestions\tests\classes\customQuestion;

use APP\plugins\generic\customQuestions\classes\customQuestion\CustomQuestion;
use APP\plugins\generic\customQuestions\classes\customQuestion\Repository;
use APP\plugins\generic\customQuestions\tests\CustomQuestionsTestCase;

class RepositoryTest extends CustomQuestionsTestCase
{
    public function testGetNewCustomQuestionObject(): void
    {
        $repository = app(Repository::class);
        $customQuestion = $repository->newDataObject();
        self::assertInstanceOf(CustomQuestion::class, $customQuestion);

        $params = [
            'id' => 123,
            'contextId' => $this->contextId,
            'title' => [
                'en' => 'Test title'
            ],
            'description' => [
                'en' => 'Test description'
            ],
            'sequence' => 1,
            'required' => true,
            'questionType' => CustomQuestion::CUSTOM_QUESTION_TYPE_RADIO_BUTTONS,
            'possibleResponses' => [
                'en' => [
                    'Yes',
                    'No'
                ]
            ]
        ];
        $customQuestion = $repository->newDataObject($params);
        self::assertEquals($params, $customQuestion->_data);
    }

    public function testCrud(): void
    {
        $params = [
            'contextId' => $this->contextId,
            'title' => [
                'en' => 'Test title'
            ],
            'description' => [
                'en' => 'Test description'
            ],
            'sequence' => REALLY_BIG_NUMBER,
            'required' => true,
            'questionType' => CustomQuestion::CUSTOM_QUESTION_TYPE_SMALL_TEXT_FIELD
        ];

        $repository = app(Repository::class);
        $customQuestion = $repository->newDataObject($params);
        $insertedCustomQuestionId = $repository->add($customQuestion);
        $params['id'] = $insertedCustomQuestionId;

        $fetchedCustomQuestion = $repository->get($insertedCustomQuestionId);
        self::assertEquals($params, $fetchedCustomQuestion->_data);

        $params['title']['en'] = 'Updated title';
        $params['description']['en'] = 'Updated description';
        $params['sequence'] = 3.0;
        $params['required'] = false;
        $params['questionType'] = CustomQuestion::CUSTOM_QUESTION_TYPE_DROP_DOWN_BOX;
        $params['possibleResponses'] = ['en' => ['Yes', 'No']];
        $repository->edit($customQuestion, $params);

        $fetchedCustomQuestion = $repository->get($customQuestion->getId());
        self::assertEquals($params, $fetchedCustomQuestion->_data);

        $repository->delete($customQuestion);
        self::assertFalse($repository->exists($customQuestion->getId()));
    }

    public function testCollectorFilterByContextId(): void
    {
        $params = [
            'title' => ['en' => 'Test title'],
            'contextId' => $this->contextId,
            'questionType' => CustomQuestion::CUSTOM_QUESTION_TYPE_SMALL_TEXT_FIELD,
            'sequence' => 1,
            'required' => true,
        ];

        $repository = app(Repository::class);
        $customQuestion = $repository->newDataObject($params);

        $repository->add($customQuestion);

        $customQuestions = $repository->getCollector()
            ->filterByContextIds([$this->contextId])
            ->getMany();

        self::assertEquals(
            [$customQuestion->getId() => $customQuestion],
            $customQuestions->all()
        );
    }
}
