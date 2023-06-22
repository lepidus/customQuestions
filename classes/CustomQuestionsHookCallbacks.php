<?php

namespace APP\plugins\generic\customQuestions\classes;

use APP\core\Application;
use APP\core\Request;
use APP\pages\submission\SubmissionHandler;
use APP\plugins\generic\customQuestions\classes\components\forms\CustomQuestions;
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
            ->filterByContextIds([$submission->getContextId()])
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
        $formConfig = $this->getLocalizedForm($customQuestionsForm, $submission, $formLocales);

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

    private function getLocalizedForm(FormComponent $form, Submission $submission, array $supportedFormLocales): array
    {
        $config = $form->getConfig();

        $config['primaryLocale'] = $submission->getLocale();
        $config['visibleLocales'] = [$submission->getLocale()];

        usort($supportedFormLocales, fn ($a, $b) => $a['key'] === $submission->getLocale() ? -1 : 1);

        $config['supportedFormLocales'] = $supportedFormLocales;

        return $config;
    }

    public function addToPublicationForms(string $hookName, array $params): bool
    {
        $templateMgr = $params[0];
        $template = $params[1];

        if (!in_array($template, ['workflow/workflow.tpl', 'authorDashboard/authorDashboard.tpl'])) {
            return false;
        }

        $request = Application::get()->getRequest();
        $submission = $templateMgr->getTemplateVars('submission');

        $customQuestions = Repo::customQuestion()->getCollector()
            ->filterByContextIds([$submission->getContextId()])
            ->getMany()
            ->remember();

        $apiUrl = $this->getCustomQuestionResponseApiUrl($request, $submission);
        $formLocales = $this->getFormLocales($request->getContext());

        $customQuestionsForm = $this->getCustomQuestionsForm(
            $apiUrl,
            $formLocales,
            $customQuestions,
            $submission->getId()
        );

        $components = $templateMgr->getState('components');
        $components[$customQuestionsForm->id] = $customQuestionsForm->getConfig();

        $templateMgr->setState([
            'components' => $components,
        ]);

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

    public function addCustomQuestionsTab(string $hookName, array $params): bool
    {
        $smarty = &$params[1];
        $output = &$params[2];

        $output .= sprintf(
            '<tab id="customQuestions" label="%s">%s</tab>',
            __('plugins.generic.customQuestions.displayName'),
            '<pkp-form v-bind="components.customQuestions" @set="set"></pkp-form>'
        );

        return false;
    }
}
