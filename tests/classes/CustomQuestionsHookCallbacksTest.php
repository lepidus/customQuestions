<?php

namespace APP\plugins\generic\customQuestions\tests\classes;

use APP\core\Application;
use APP\plugins\generic\customQuestions\classes\components\forms\CustomQuestionsFormProvider;
use APP\plugins\generic\customQuestions\classes\customQuestion\CustomQuestion;
use APP\plugins\generic\customQuestions\classes\CustomQuestionsHookCallbacks;
use APP\plugins\generic\customQuestions\CustomQuestionsPlugin;
use APP\plugins\generic\customQuestions\tests\CustomQuestionsTestCase;

class CustomQuestionsHookCallbacksTest extends CustomQuestionsTestCase
{
    public function testRequiredCustomQuestionWithoutResponseAddsSubmissionValidationError(): void
    {
        $customQuestion = $this->createRequiredCustomQuestion();
        $submission = \APP\facades\Repo::submission()->get($this->submissionId);
        $context = Application::getContextDAO()->getById($this->contextId);
        $errors = [];

        $hookCallbacks = new CustomQuestionsHookCallbacks($this->createMock(CustomQuestionsPlugin::class));
        $hookCallbacks->validateRequiredCustomQuestionResponses(
            'Submission::validateSubmit',
            [&$errors, $submission, $context]
        );

        $locale = $submission->getData('locale') ?: $context->getData('primaryLocale');

        self::assertSame(
            [$locale => [__('validator.required')]],
            $errors['customQuestion-' . $customQuestion->getId()]
        );
    }

    public function testRequiredCustomQuestionWithZeroValueResponseDoesNotAddSubmissionValidationError(): void
    {
        $customQuestion = $this->createRequiredCustomQuestion(CustomQuestion::CUSTOM_QUESTION_TYPE_RADIO_BUTTONS);
        $this->createCustomQuestionResponse($customQuestion, 0);
        $submission = \APP\facades\Repo::submission()->get($this->submissionId);
        $context = Application::getContextDAO()->getById($this->contextId);
        $errors = [];

        $hookCallbacks = new CustomQuestionsHookCallbacks($this->createMock(CustomQuestionsPlugin::class));
        $hookCallbacks->validateRequiredCustomQuestionResponses(
            'Submission::validateSubmit',
            [&$errors, $submission, $context]
        );

        self::assertArrayNotHasKey('customQuestion-' . $customQuestion->getId(), $errors);
    }

    public function testPostSubmissionFormConfigContainsSavedResponses(): void
    {
        $customQuestion = $this->createRequiredCustomQuestion();
        $this->createCustomQuestionResponse($customQuestion, ['en' => 'Saved response']);
        $submission = \APP\facades\Repo::submission()->get($this->submissionId);
        $context = Application::getContextDAO()->getById($this->contextId);

        $config = (new CustomQuestionsFormProvider())->getConfig(
            Application::get()->getRequest(),
            $submission,
            $context
        );

        self::assertNotNull($config);
        self::assertSame('customQuestions', $config['id']);
        self::assertSame('PUT', $config['method']);
        self::assertSame('en', $config['primaryLocale']);
        self::assertSame(['en' => 'Saved response'], $config['fields'][0]['value']);
        self::assertStringEndsWith(
            '/testContext/api/v1/customQuestionResponses/' . $submission->getId(),
            $config['action']
        );
    }

    public function testOjs35WorkflowExtensionRegistersMenuAndForm(): void
    {
        $script = file_get_contents(dirname(__DIR__, 2) . '/js/CustomQuestionsWorkflow.js');

        self::assertStringContainsString(
            "storeExtendFn('workflow', 'getMenuItems'",
            $script
        );
        self::assertStringContainsString(
            "storeExtendFn('workflow', 'getPrimaryItems'",
            $script
        );
        self::assertStringContainsString('publication_customQuestions', $script);
        self::assertStringContainsString('CustomQuestionsWorkflowForm', $script);
    }

    private function createRequiredCustomQuestion(
        int $questionType = CustomQuestion::CUSTOM_QUESTION_TYPE_SMALL_TEXT_FIELD
    ): CustomQuestion {
        $customQuestion = \APP\plugins\generic\customQuestions\classes\facades\Repo::customQuestion()->newDataObject([
            'contextId' => $this->contextId,
            'title' => ['en' => 'Required custom question'],
            'possibleResponses' => ['en' => ['First option', 'Second option']],
            'required' => true,
            'questionType' => $questionType,
        ]);

        \APP\plugins\generic\customQuestions\classes\facades\Repo::customQuestion()->add($customQuestion);

        return $customQuestion;
    }

    private function createCustomQuestionResponse(CustomQuestion $customQuestion, $value): void
    {
        $repository = \APP\plugins\generic\customQuestions\classes\facades\Repo::customQuestionResponse();
        $customQuestionResponse = $repository->newDataObject([
            'submissionId' => $this->submissionId,
            'customQuestionId' => $customQuestion->getId(),
            'value' => $value,
            'responseType' => $customQuestion->getCustomQuestionResponseType(),
        ]);

        $repository->add($customQuestionResponse);
    }
}
