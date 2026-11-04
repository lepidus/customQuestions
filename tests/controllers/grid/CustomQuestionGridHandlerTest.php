<?php

namespace APP\plugins\generic\customQuestions\tests\controllers\grid;

use APP\core\Application;
use APP\core\Request;
use APP\plugins\generic\customQuestions\classes\customQuestion\CustomQuestion;
use APP\plugins\generic\customQuestions\classes\facades\Repo;
use APP\plugins\generic\customQuestions\controllers\grid\CustomQuestionGridHandler;
use APP\plugins\generic\customQuestions\controllers\grid\form\CustomQuestionForm;
use APP\plugins\generic\customQuestions\CustomQuestionsPlugin;
use APP\plugins\generic\customQuestions\tests\CustomQuestionsTestCase;
use PKP\context\Context;

class CustomQuestionGridHandlerTest extends CustomQuestionsTestCase
{
    public function testDeleteRejectsQuestionFromAnotherContextAndPreservesResponses(): void
    {
        $question = $this->createQuestion();
        $responseId = $this->createResponse($question);
        $contextDAO = Application::getContextDAO();
        $otherContext = clone $contextDAO->getById($this->contextId);
        $otherContext->setId(0);
        $otherContext->setPath('otherTestContext');
        $contextDAO->insertObject($otherContext);

        try {
            $request = $this->createDeleteRequest($question->getId(), $otherContext);

            $result = $this->createHandler()->deleteCustomQuestion([], $request);

            self::assertFalse($result->getStatus());
            self::assertNotNull(Repo::customQuestion()->get($question->getId(), $this->contextId));
            self::assertNotNull(Repo::customQuestionResponse()->get($responseId));
        } finally {
            $contextDAO->deleteObject($otherContext);
        }
    }

    public function testDeleteRemovesQuestionAndResponsesInCurrentContext(): void
    {
        $question = $this->createQuestion();
        $responseId = $this->createResponse($question);
        $context = Application::getContextDAO()->getById($this->contextId);
        $request = $this->createDeleteRequest($question->getId(), $context);

        $result = $this->createHandler()->deleteCustomQuestion([], $request);

        self::assertTrue($result->getStatus());
        self::assertNull(Repo::customQuestion()->get($question->getId()));
        self::assertNull(Repo::customQuestionResponse()->get($responseId));
    }

    public function testDeleteWithoutContextPreservesQuestionAndResponses(): void
    {
        $question = $this->createQuestion();
        $responseId = $this->createResponse($question);
        $request = $this->createDeleteRequest($question->getId(), null);

        $result = $this->createHandler()->deleteCustomQuestion([], $request);

        self::assertFalse($result->getStatus());
        self::assertNotNull(Repo::customQuestion()->get($question->getId()));
        self::assertNotNull(Repo::customQuestionResponse()->get($responseId));
    }

    public function testDeleteWithInvalidCsrfPreservesQuestionAndResponses(): void
    {
        $question = $this->createQuestion();
        $responseId = $this->createResponse($question);
        $context = Application::getContextDAO()->getById($this->contextId);
        $request = $this->createDeleteRequest($question->getId(), $context, false);

        $result = $this->createHandler()->deleteCustomQuestion([], $request);

        self::assertFalse($result->getStatus());
        self::assertNotNull(Repo::customQuestion()->get($question->getId()));
        self::assertNotNull(Repo::customQuestionResponse()->get($responseId));
    }

    public function testDeleteRejectsMissingQuestion(): void
    {
        $context = Application::getContextDAO()->getById($this->contextId);
        $request = $this->createDeleteRequest(0, $context);

        $result = $this->createHandler()->deleteCustomQuestion([], $request);

        self::assertFalse($result->getStatus());
    }

    public function testEditRejectsQuestionFromAnotherContext(): void
    {
        $question = $this->createQuestion();
        $form = $this->getMockBuilder(CustomQuestionForm::class)
            ->setConstructorArgs(['unused.tpl', 0, $question->getId()])
            ->onlyMethods(['fetch'])
            ->getMock();
        $form->expects(self::never())->method('fetch');
        $handler = $this->createFormHandler($form);
        $request = $this->createMock(Request::class);
        $request->method('getUserVar')->with('rowId')->willReturn((string) $question->getId());

        self::assertFalse($handler->editCustomQuestion([], $request)->getStatus());
        self::assertNotNull(Repo::customQuestion()->get($question->getId(), $this->contextId));
    }

    public function testUpdateRejectsQuestionFromAnotherContextAndPreservesResponses(): void
    {
        $question = $this->createQuestion();
        $responseId = $this->createResponse($question);
        $form = $this->getMockBuilder(CustomQuestionForm::class)
            ->setConstructorArgs(['unused.tpl', 0, $question->getId()])
            ->onlyMethods(['validate', 'readInputData'])
            ->getMock();
        $form->method('validate')->willReturn(true);
        $form->setData('title', ['en' => 'Must not be saved']);
        $form->setData('questionType', $question->getQuestionType());
        $handler = $this->createFormHandler($form);
        $request = $this->createMock(Request::class);
        $request->method('getUserVar')->with('customQuestionId')->willReturn((string) $question->getId());

        self::assertFalse($handler->updateCustomQuestion([], $request)->getStatus());
        self::assertSame(
            'Context-owned question',
            Repo::customQuestion()->get($question->getId(), $this->contextId)->getTitle('en')
        );
        self::assertNotNull(Repo::customQuestionResponse()->get($responseId));
    }

    public function testFormRejectsQuestionDeletedBeforeExecution(): void
    {
        $question = $this->createQuestion();
        $form = new CustomQuestionForm('unused.tpl', $this->contextId, $question->getId());
        $form->initData();
        Repo::customQuestion()->delete($question);

        self::assertNull($form->execute());
    }

    public function testFormRejectsMissingQuestionDuringInitialization(): void
    {
        $form = new CustomQuestionForm('unused.tpl', $this->contextId, -1);

        self::assertFalse($form->initData());
    }

    private function createFormHandler(CustomQuestionForm $form): CustomQuestionGridHandler
    {
        $handler = $this->getMockBuilder(CustomQuestionGridHandler::class)
            ->setConstructorArgs([$this->createMock(CustomQuestionsPlugin::class)])
            ->onlyMethods(['getCustomQuestionForm'])
            ->getMock();
        $handler->method('getCustomQuestionForm')->willReturn($form);
        return $handler;
    }

    private function createHandler(): CustomQuestionGridHandler
    {
        return new CustomQuestionGridHandler($this->createMock(CustomQuestionsPlugin::class));
    }

    private function createDeleteRequest(int $questionId, ?Context $context, bool $validCsrf = true): Request
    {
        $request = $this->createMock(Request::class);
        $request->method('getUserVar')->with('rowId')->willReturn((string) $questionId);
        $request->method('getContext')->willReturn($context);
        $request->method('checkCSRF')->willReturn($validCsrf);

        return $request;
    }

    private function createQuestion(): CustomQuestion
    {
        $question = Repo::customQuestion()->newDataObject([
            'contextId' => $this->contextId,
            'title' => ['en' => 'Context-owned question'],
            'questionType' => CustomQuestion::CUSTOM_QUESTION_TYPE_SMALL_TEXT_FIELD,
            'required' => false,
        ]);
        Repo::customQuestion()->add($question);

        return $question;
    }

    private function createResponse(CustomQuestion $question): int
    {
        $response = Repo::customQuestionResponse()->newDataObject([
            'submissionId' => $this->submissionId,
            'customQuestionId' => $question->getId(),
            'value' => ['en' => 'Saved response'],
            'responseType' => $question->getCustomQuestionResponseType(),
        ]);

        return Repo::customQuestionResponse()->add($response);
    }
}
