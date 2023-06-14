<?php

namespace APP\plugins\generic\customQuestions\classes;

use APP\core\Application;
use APP\core\Request;
use APP\pages\submission\SubmissionHandler;
use APP\plugins\generic\customQuestions\classes\components\forms\CustomQuestions;
use APP\plugins\generic\customQuestions\classes\customQuestion\DAO as CustomQuestionDAO;
use APP\plugins\generic\customQuestions\classes\customQuestionResponse\DAO as CustomQuestionResponseDAO;
use APP\plugins\generic\customQuestions\CustomQuestionsPlugin;
use APP\submission\Submission;
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

        $apiUrl = $this->getCustomQuestionResponseApiUrl($request, $submission);
        $formLocales = $this->getFormLocales($request->getContext());

        $customQuestions = [];
        $customQuestionDAO = app(CustomQuestionDAO::class);
        $customQuestionsIterator = $customQuestionDAO->getByContextId($request->getContext()->getId());

        $customQuestionResponseDAO = app(CustomQuestionResponseDAO::class);
        $customQuestionResponses = [];

        foreach ($customQuestionsIterator as $customQuestion) {
            $customQuestions[] = $customQuestion;
            $customQuestionResponses[$customQuestion->getId()] = $customQuestionResponseDAO->getByCustomQuestionId(
                $customQuestion->getId(),
                $submission->getId()
            );
        }

        $customQuestionsForm = $this->getCustomQuestionsForm(
            $apiUrl,
            $formLocales,
            $customQuestions,
            $customQuestionResponses
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

        $templateMgr->assign([
            'customQuestions' => $customQuestions,
            'customQuestionResponses' => $customQuestionResponses,
        ]);

        $templateMgr->setState([
            'steps' => $steps,
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
        array $customQuestions,
        array $customQuestionResponses
    ): CustomQuestions {
        return new CustomQuestions($action, $locales, $customQuestions, $customQuestionResponses);
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

    public function addToReviewStep(string $hookName, array $params): bool
    {
        $step = $params[0]['step'];
        $templateMgr = $params[1];
        $output = &$params[2];

        if ($step === 'details') {
            $output .= $templateMgr->fetch($this->plugin->getTemplateResource('review-customQuestions.tpl'));
        }

        return false;
    }
}
