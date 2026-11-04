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

        $fetchedCustomQuestion = $customQuestionDAO->get($insertedCustomQuestionId, $this->contextId);
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

        $fetchedCustomQuestion = $customQuestionDAO->get($insertedCustomQuestionId, $this->contextId);
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
        self::assertFalse($customQuestionDAO->exists($insertedCustomQuestionId, $this->contextId));
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

        $fetchedCustomQuestion = $customQuestionDAO->get($customQuestion->getId(), $this->contextId);
        self::assertEquals(++$lastSeq, $fetchedCustomQuestion->getSequence());
    }
    public function testCollectorReturnsQuestionsInSequenceOrderWithPagination(): void
    {
        $dao = app(DAO::class);
        $last = $this->createQuestionWithSequence(3.0);
        $first = $this->createQuestionWithSequence(1.0);
        $middle = $this->createQuestionWithSequence(2.0);
        $collector = new \APP\plugins\generic\customQuestions\classes\customQuestion\Collector($dao);
        $collector->filterByContextIds([$this->contextId]);

        self::assertSame([$first, $middle, $last], $collector->getMany()->keys()->all());
        self::assertSame([$middle], $collector->offset(1)->limit(1)->getMany()->keys()->all());
    }

    public function testResequencePreservesReorderedQuestionsWhenAddingQuestion(): void
    {
        $dao = app(DAO::class);
        $second = $this->createQuestionWithSequence(2.0);
        $first = $this->createQuestionWithSequence(1.0);
        $newQuestion = $this->createQuestionWithSequence(REALLY_BIG_NUMBER);

        $dao->resequence($this->contextId);

        self::assertEquals(1.0, $dao->get($first, $this->contextId)->getSequence());
        self::assertEquals(2.0, $dao->get($second, $this->contextId)->getSequence());
        self::assertEquals(3.0, $dao->get($newQuestion, $this->contextId)->getSequence());
    }

    public function testFormAppendsNewQuestionAfterReorderedQuestions(): void
    {
        $second = $this->createQuestionWithSequence(2.0);
        $first = $this->createQuestionWithSequence(1.0);
        $form = new \APP\plugins\generic\customQuestions\controllers\grid\form\CustomQuestionForm(
            'unused.tpl',
            $this->contextId
        );
        $form->setData('title', ['en' => 'New question']);
        $form->setData('questionType', CustomQuestion::CUSTOM_QUESTION_TYPE_SMALL_TEXT_FIELD);

        $newQuestion = $form->execute();

        $collector = new \APP\plugins\generic\customQuestions\classes\customQuestion\Collector(app(DAO::class));
        self::assertSame(
            [$first, $second, $newQuestion],
            $collector->filterByContextIds([$this->contextId])->getMany()->keys()->all()
        );
    }

    private function createQuestionWithSequence(float $sequence): int
    {
        $dao = app(DAO::class);
        $question = $dao->newDataObject();
        $question->setContextId($this->contextId);
        $question->setSequence($sequence);
        $question->setQuestionType(CustomQuestion::CUSTOM_QUESTION_TYPE_SMALL_TEXT_FIELD);
        return $dao->insert($question);
    }

}
