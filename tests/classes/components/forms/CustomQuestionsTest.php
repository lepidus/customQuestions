<?php

namespace APP\plugins\generic\customQuestions\tests\classes\components\forms;

require_once __DIR__ . '/../../../../classes/components/forms/CustomQuestions.php';

use APP\plugins\generic\customQuestions\classes\components\forms\CustomQuestions;
use APP\plugins\generic\customQuestions\classes\customQuestion\CustomQuestion;
use APP\plugins\generic\customQuestions\classes\facades\Repo;
use APP\plugins\generic\customQuestions\tests\CustomQuestionsTestCase;
use Illuminate\Support\LazyCollection;

class CustomQuestionsTest extends CustomQuestionsTestCase
{
    public function testDescriptionWrapperParagraphIsRemovedFromQuestionField(): void
    {
        $customQuestion = $this->createCustomQuestion([
            'description' => [
                'en' => '<p>Describe the <strong>research context</strong>.</p>',
            ],
        ]);

        $form = new CustomQuestions(
            'http://example.com/customQuestionResponses',
            [['key' => 'en', 'label' => 'English']],
            LazyCollection::make([$customQuestion]),
            $this->submissionId
        );

        self::assertSame(
            'Describe the <strong>research context</strong>.',
            $form->getField('customQuestion-' . $customQuestion->getId())->description
        );
    }

    private function createCustomQuestion(array $overrides = []): CustomQuestion
    {
        $customQuestion = Repo::customQuestion()->newDataObject(array_merge([
            'contextId' => $this->contextId,
            'title' => ['en' => 'Question title'],
            'questionType' => CustomQuestion::CUSTOM_QUESTION_TYPE_SMALL_TEXT_FIELD,
            'required' => false,
        ], $overrides));

        Repo::customQuestion()->add($customQuestion);

        return $customQuestion;
    }
}
