<?php

namespace APP\plugins\generic\customQuestions\classes;

use APP\core\Application;
use APP\core\Request;
use APP\pages\submission\SubmissionHandler;
use APP\plugins\generic\customQuestions\classes\components\forms\CustomQuestions;
use APP\plugins\generic\customQuestions\classes\customQuestion\DAO;
use APP\plugins\generic\customQuestions\CustomQuestionsPlugin;
use APP\submission\Submission;
use PKP\context\Context;
use PKP\components\forms\FormComponent;

class CustomQuestionsSectionHookCallbacks
{
    public $plugin;

    public function __construct(CustomQuestionsPlugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function addToSubmissionWizardSteps(string $hookName, array $params): bool
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
        $customQuestionDAO = app(DAO::class);
        $customQuestions = $customQuestionDAO->getByContextId($request->getContext()->getId());

        $customQuestionsForm = $this->getCustomQuestionsForm($apiUrl, $formLocales, $customQuestions->toArray());

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

    private function getCustomQuestionsForm(string $action, array $locales, array $customQuestions): CustomQuestions
    {
        return new CustomQuestions($action, $locales, $customQuestions);
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
}
