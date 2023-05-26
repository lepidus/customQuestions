<?php

namespace APP\plugins\generic\customQuestions\classes;

use APP\core\Application;
use APP\pages\submission\SubmissionHandler;
use APP\plugins\generic\customQuestions\classes\components\forms\CustomQuestions;
use APP\plugins\generic\customQuestions\CustomQuestionsPlugin;
use APP\submission\Submission;
use PKP\components\forms\FormComponent;
use PKP\context\Context;

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
        $context = $request->getContext();

        if ($request->getRequestedPage() !== 'submission' or $request->getRequestedOp() === 'saved') {
            return false;
        }

        $submission = $request
            ->getRouter()
            ->getHandler()
            ->getAuthorizedContextObject(Application::ASSOC_TYPE_SUBMISSION);

        if (!$submission or !$submission->getData('submissionProgress')) {
            return false;
        }

        $templateMgr = $params[0];

        $supportedSubmissionLocales = $context->getSupportedSubmissionLocaleNames();
        $formLocales = array_map(
            fn (string $locale, string $name) => ['key' => $locale, 'label' => $name],
            array_keys($supportedSubmissionLocales),
            $supportedSubmissionLocales
        );

        $customQuestionsForm = new CustomQuestions('apiUrl', $formLocales, []);
        $this->removeButtonFromForm($customQuestionsForm);
        $formConfig = $this->getLocalizedForm($customQuestionsForm, $submission, $request->getContext());

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

    private function getLocalizedForm(FormComponent $form, Submission $submission, Context $context): array
    {
        $config = $form->getConfig();

        $config['primaryLocale'] = $submission->getLocale();
        $config['visibleLocales'] = [$submission->getLocale()];

        $supportedFormLocales = [];
        foreach ($context->getSupportedSubmissionLocaleNames() as $localeKey => $name) {
            $supportedFormLocales[] = [
                'key' => $localeKey,
                'label' => $name,
            ];
        }

        usort($supportedFormLocales, fn ($a, $b) => $a['key'] === $submission->getLocale() ? -1 : 1);

        $config['supportedFormLocales'] = $supportedFormLocales;

        return $config;
    }
}
