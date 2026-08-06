<?php

namespace APP\plugins\generic\customQuestions\classes;

use APP\core\Application;
use APP\core\Request;
use APP\pages\submission\SubmissionHandler;
use APP\plugins\generic\customQuestions\classes\components\forms\CustomQuestions;
use APP\plugins\generic\customQuestions\classes\customQuestion\CustomQuestion;
use APP\plugins\generic\customQuestions\classes\facades\Repo;
use APP\plugins\generic\customQuestions\CustomQuestionsPlugin;
use APP\submission\Submission;
use APP\template\TemplateManager;
use Illuminate\Support\LazyCollection;
use PKP\components\forms\FormComponent;
use PKP\context\Context;

class CustomQuestionsHookCallbacks
{
    public $plugin;

    public function __construct(CustomQuestionsPlugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function addToDetailsStep(string $hookName, array $params): bool
    {
        $request = Application::get()->getRequest();
        $templateMgr = $params[0];

        if (
            $request->getRequestedPage() !== 'submission'
            || $request->getRequestedOp() === 'saved'
        ) {
            return false;
        }

        $submission = $request
            ->getRouter()
            ->getHandler()
            ->getAuthorizedContextObject(Application::ASSOC_TYPE_SUBMISSION);

        if (
            !$submission
            || !$submission->getData('submissionProgress')
        ) {
            return false;
        }

        $customQuestions = Repo::customQuestion()->getCollector()
            ->filterByContextIds([$submission->getData('contextId')])
            ->getMany()
            ->remember();

        if ($customQuestions->isEmpty()) {
            return false;
        }

        $apiUrl = $this->getCustomQuestionResponseApiUrl($request, $submission);
        $formLocales = $this->getFormLocales($request->getContext());

        $customQuestionsForm = $this->getCustomQuestionsForm(
            $apiUrl,
            $formLocales,
            $customQuestions,
            $submission->getId()
        );

        $this->removeButtonFromForm($customQuestionsForm);
        $formConfig = $this->getLocalizedForm(
            $customQuestionsForm,
            $submission,
            $formLocales,
            $request->getContext()
        );

        $steps = $templateMgr->getState('steps');
        $steps = array_map(function ($step) use ($formConfig) {
            if ($step['id'] === 'details') {
                $step['sections'][] = [
                    'id' => 'customQuestions',
                    'name' => __('plugins.generic.customQuestions.submissionWizard.name'),
                    'description' => __('plugins.generic.customQuestions.submissionWizard.description'),
                    'type' => SubmissionHandler::SECTION_TYPE_FORM,
                    'form' => $formConfig,
                ];
            }
            return $step;
        }, $steps);

        $customQuestionsProps = [];
        $customQuestionResponsesProps = [];

        foreach ($customQuestions as $customQuestion) {
            $customQuestionResponse = Repo::customQuestionResponse()
                ->getByCustomQuestionId($customQuestion->getId(), $submission->getId());

            if ($customQuestionResponse) {
                $customQuestionResponsesProps[] = $customQuestionResponse->getAllData();
            }

            $customQuestionsProps[] = $customQuestion->getAllData();
        }

        $templateMgr->setState([
            'steps' => $steps,
            'customQuestions' => $customQuestionsProps,
            'customQuestionResponses' => $customQuestionResponsesProps,
        ]);

        $templateMgr->addJavaScript(
            'custom-questions',
            $request->getBaseUrl() . '/' . $this->plugin->getPluginPath() . '/js/CustomQuestions.js',
            [
                'contexts' => 'backend',
                'priority' => TemplateManager::STYLE_SEQUENCE_LATE,
            ]
        );

        return false;
    }

    private function removeButtonFromForm(FormComponent $form): void
    {
        $form->addPage([
            'id' => 'default',
        ])
            ->addGroup([
                'id' => 'default',
                'pageId' => 'default'
            ]);

        foreach ($form->fields as $field) {
            $field->groupId = 'default';
        }
    }

    private function getLocalizedForm(
        FormComponent $form,
        Submission $submission,
        array $supportedFormLocales,
        Context $context
    ): array {
        $config = $form->getConfig();
        $locale = $submission->getData('locale') ?: $context->getData('primaryLocale');

        $config['primaryLocale'] = $locale;
        $config['visibleLocales'] = [$locale];

        usort($supportedFormLocales, fn ($a, $b) => $a['key'] === $locale ? -1 : 1);

        $config['supportedFormLocales'] = $supportedFormLocales;

        return $config;
    }

    public function addToDashboard(string $hookName, array $params): bool
    {
        $templateMgr = $params[0];
        $template = $params[1];

        if ($template !== 'dashboard/editors.tpl') {
            return false;
        }

        $request = Application::get()->getRequest();
        $customQuestions = Repo::customQuestion()->getCollector()
            ->filterByContextIds([$request->getContext()->getId()])
            ->getMany()
            ->remember();

        if ($customQuestions->isEmpty()) {
            return false;
        }

        $customQuestionsApiUrl = $request
            ->getDispatcher()
            ->url(
                $request,
                Application::ROUTE_API,
                $request->getContext()->getPath(),
                'customQuestionResponses/__submissionId__'
            );
        $workflowConfig = json_encode([
            'apiUrl' => $customQuestionsApiUrl,
            'label' => __('plugins.generic.customQuestions.displayName'),
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);

        $templateMgr->addJavaScript(
            'custom-questions-workflow-config',
            'window.pkp.customQuestions = ' . $workflowConfig . ';',
            [
                'contexts' => 'backend',
                'inline' => true,
                'priority' => TemplateManager::STYLE_SEQUENCE_LATE,
            ]
        );

        $templateMgr->addJavaScript(
            'custom-questions-workflow',
            $request->getBaseUrl() . '/' . $this->plugin->getPluginPath() . '/js/CustomQuestionsWorkflow.js',
            [
                'contexts' => 'backend',
                'priority' => TemplateManager::STYLE_SEQUENCE_LATE,
            ]
        );

        return false;
    }

    private function getCustomQuestionResponseApiUrl(Request $request, Submission $submission): string
    {
        return $request
            ->getDispatcher()
            ->url(
                $request,
                Application::ROUTE_API,
                $request->getContext()->getPath(),
                'customQuestionResponses' . '/' . $submission->getId(),
            );
    }

    private function getFormLocales(Context $context): array
    {
        $supportedSubmissionLocales = $context->getSupportedSubmissionLocaleNames();
        return array_map(
            fn (string $locale, string $name) => ['key' => $locale, 'label' => $name],
            array_keys($supportedSubmissionLocales),
            $supportedSubmissionLocales
        );
    }

    private function getCustomQuestionsForm(
        string $action,
        array $locales,
        LazyCollection $customQuestions,
        int $submissionId
    ): CustomQuestions {
        return new CustomQuestions($action, $locales, $customQuestions, $submissionId);
    }

    public function addToReviewStep(string $hookName, array $params): bool
    {
        $step = $params[0]['step'];
        $templateMgr = $params[1];
        $output = &$params[2];
        $context = Application::get()->getRequest()->getContext();

        if (
            Repo::customQuestion()->getCollector()
                ->filterByContextIds([$context->getId()])
                ->getMany()
                ->isEmpty()
        ) {
            return false;
        }

        if ($step === 'details') {
            $output .= $templateMgr->fetch($this->plugin->getTemplateResource('review-customQuestions.tpl'));
        }

        return false;
    }

    public function validateRequiredCustomQuestionResponses(string $hookName, array $params): bool
    {
        $errors = &$params[0];
        $submission = $params[1];
        $context = $params[2];
        $locale = $submission->getData('locale') ?: $context->getData('primaryLocale');

        $customQuestions = Repo::customQuestion()->getCollector()
            ->filterByContextIds([$context->getId()])
            ->getMany();

        foreach ($customQuestions as $customQuestion) {
            if (!$customQuestion->getRequired()) {
                continue;
            }

            $customQuestionResponse = Repo::customQuestionResponse()
                ->getByCustomQuestionId($customQuestion->getId(), $submission->getId());

            if (!$this->isCustomQuestionResponseMissing($customQuestion, $customQuestionResponse?->getValue(), $locale)) {
                continue;
            }

            $fieldName = 'customQuestion-' . $customQuestion->getId();
            $errors[$fieldName] = $this->getRequiredCustomQuestionError($customQuestion, $locale);
        }

        return false;
    }

    private function isCustomQuestionResponseMissing(CustomQuestion $customQuestion, $value, string $locale): bool
    {
        switch ($customQuestion->getQuestionType()) {
            case CustomQuestion::CUSTOM_QUESTION_TYPE_SMALL_TEXT_FIELD:
            case CustomQuestion::CUSTOM_QUESTION_TYPE_TEXT_FIELD:
            case CustomQuestion::CUSTOM_QUESTION_TYPE_TEXTAREA:
                return !is_array($value)
                    || !isset($value[$locale])
                    || trim((string) $value[$locale]) === '';
            case CustomQuestion::CUSTOM_QUESTION_TYPE_CHECKBOXES:
                return !is_array($value) || count($value) === 0;
            case CustomQuestion::CUSTOM_QUESTION_TYPE_RADIO_BUTTONS:
            case CustomQuestion::CUSTOM_QUESTION_TYPE_DROP_DOWN_BOX:
                return $value === null || $value === '' || $value === [];
        }

        return false;
    }

    private function getRequiredCustomQuestionError(CustomQuestion $customQuestion, string $locale): array
    {
        $error = [__('validator.required')];

        if (
            in_array(
                $customQuestion->getQuestionType(),
                [
                    CustomQuestion::CUSTOM_QUESTION_TYPE_SMALL_TEXT_FIELD,
                    CustomQuestion::CUSTOM_QUESTION_TYPE_TEXT_FIELD,
                    CustomQuestion::CUSTOM_QUESTION_TYPE_TEXTAREA,
                ]
            )
        ) {
            return [$locale => $error];
        }

        return $error;
    }
}
