<?php

namespace APP\plugins\generic\customQuestions\tests\classes\customQuestion;

use APP\plugins\generic\customQuestions\classes\customQuestion\CustomQuestion;
use APP\plugins\generic\customQuestions\classes\customQuestion\DAO;
use APP\plugins\generic\customQuestions\tests\CustomQuestionsTestCase;

class DAOTest extends CustomQuestionsTestCase
{
    public function testCreateNewDataObject(): void
    {
        $customQuestionDAO = app(DAO::class);
        $customQuestion = $customQuestionDAO->newDataObject();
        self::assertInstanceOf(CustomQuestion::class, $customQuestion);
    }

    public function testCrud(): void
    {
        $locale = 'en';

        $customQuestionDAO = app(DAO::class);
        $customQuestion = $customQuestionDAO->newDataObject();
        $customQuestion->setContextId($this->contextId);
        $customQuestion->setTitle('Test title', $locale);
        $customQuestion->setDescription('Test description', $locale);
        $customQuestion->setSequence(REALLY_BIG_NUMBER);
        $customQuestion->setRequired(true);
        $customQuestion->setQuestionType(CustomQuestion::CUSTOM_QUESTION_TYPE_SMALL_TEXT_FIELD);
        $customQuestion->setPossibleResponses(['Test possible response'], $locale);
        $insertedCustomQuestionId = $customQuestionDAO->insert($customQuestion);

        $fetchedCustomQuestion = $customQuestionDAO->get($insertedCustomQuestionId);
        self::assertEquals([
            'id' => $insertedCustomQuestionId,
            'contextId' => $this->contextId,
            'title' => ['en' => 'Test title'],
            'description' => ['en' => 'Test description'],
            'sequence' => REALLY_BIG_NUMBER,
            'required' => true,
            'questionType' => CustomQuestion::CUSTOM_QUESTION_TYPE_SMALL_TEXT_FIELD,
            'possibleResponses' => ['en' => ['Test possible response']]
        ], $fetchedCustomQuestion->_data);

        $customQuestion->setTitle('Updated title', $locale);
        $customQuestion->setDescription('Updated description', $locale);
        $customQuestion->setSequence(3.0);
        $customQuestion->setRequired(false);
        $customQuestion->setQuestionType(CustomQuestion::CUSTOM_QUESTION_TYPE_DROP_DOWN_BOX);
        $customQuestion->setPossibleResponses(['Updated possible response'], $locale);
        $customQuestionDAO->update($customQuestion);

        $fetchedCustomQuestion = $customQuestionDAO->get($insertedCustomQuestionId);
        self::assertEquals([
            'id' => $insertedCustomQuestionId,
            'contextId' => $this->contextId,
            'title' => ['en' => 'Updated title'],
            'description' => ['en' => 'Updated description'],
            'sequence' => 3.0,
            'required' => false,
            'questionType' => CustomQuestion::CUSTOM_QUESTION_TYPE_DROP_DOWN_BOX,
            'possibleResponses' => ['en' => ['Updated possible response']]
        ], $fetchedCustomQuestion->_data);

        $customQuestionDAO->delete($customQuestion);
        self::assertFalse($customQuestionDAO->exists($insertedCustomQuestionId));
    }

    public function testGetByContextId(): void
    {
        $customQuestionDAO = app(DAO::class);
        $customQuestion = $customQuestionDAO->newDataObject();
        $customQuestion->setContextId($this->contextId);
        $customQuestion->setTitle('Question in context', 'en');
        $customQuestion->setSequence(1.0);
        $customQuestion->setRequired(false);
        $customQuestion->setQuestionType(CustomQuestion::CUSTOM_QUESTION_TYPE_SMALL_TEXT_FIELD);
        $customQuestionDAO->insert($customQuestion);

        $customQuestions = $customQuestionDAO->getByContextId($this->contextId);
        self::assertEquals([$customQuestion], $customQuestions->toArray());
    }

    public function testResequenceQuestions(): void
    {
        $customQuestionDAO = app(DAO::class);

        $firstCustomQuestion = $customQuestionDAO->newDataObject();
        $firstCustomQuestion->setContextId($this->contextId);
        $firstCustomQuestion->setSequence(1.0);
        $firstCustomQuestion->setQuestionType(CustomQuestion::CUSTOM_QUESTION_TYPE_SMALL_TEXT_FIELD);
        $customQuestionDAO->insert($firstCustomQuestion);
        $lastSeq = $firstCustomQuestion->getSequence();

        $customQuestion = $customQuestionDAO->newDataObject();
        $customQuestion->setContextId($this->contextId);
        $customQuestion->setSequence(1.0);
        $customQuestion->setQuestionType(CustomQuestion::CUSTOM_QUESTION_TYPE_SMALL_TEXT_FIELD);
        $customQuestionDAO->insert($customQuestion);

        $customQuestionDAO->resequence($this->contextId);

        $fetchedCustomQuestion = $customQuestionDAO->get($customQuestion->getId());
        self::assertEquals(++$lastSeq, $fetchedCustomQuestion->getSequence());
    }
}
