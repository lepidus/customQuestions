<?php

namespace APP\plugins\generic\customQuestions\classes\components\forms;

use APP\core\Application;
use APP\core\Request;
use APP\plugins\generic\customQuestions\classes\facades\Repo;
use APP\submission\Submission;
use PKP\context\Context;

class CustomQuestionsFormProvider
{
    public function getConfig(Request $request, Submission $submission, Context $context): ?array
    {
        if ($submission->getData('contextId') !== $context->getId()) {
            return null;
        }

        $customQuestions = Repo::customQuestion()->getCollector()
            ->filterByContextIds([$context->getId()])
            ->getMany()
            ->remember();

        if ($customQuestions->isEmpty()) {
            return null;
        }

        $locales = $this->getFormLocales($context);
        $locale = $submission->getData('locale') ?: $context->getData('primaryLocale');
        $form = new CustomQuestions(
            $request->getDispatcher()->url(
                $request,
                Application::ROUTE_API,
                $context->getPath(),
                'customQuestionResponses/' . $submission->getId()
            ),
            $locales,
            $customQuestions,
            $submission->getId(),
            $locale
        );

        $config = $form->getConfig();
        $config['primaryLocale'] = $locale;
        $config['visibleLocales'] = [$locale];
        $config['supportedFormLocales'] = collect($locales)
            ->sortBy(fn (array $formLocale) => $formLocale['key'] === $locale ? 0 : 1)
            ->values()
            ->toArray();

        return $config;
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
}
