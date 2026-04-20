<?php

namespace APP\plugins\generic\customQuestions\tests\classes\components\forms;

use APP\plugins\generic\customQuestions\classes\components\forms\CustomQuestions;
use APP\plugins\generic\customQuestions\classes\customQuestion\CustomQuestion;
use APP\plugins\generic\customQuestions\classes\facades\Repo;
use APP\plugins\generic\customQuestions\tests\CustomQuestionsTestCase;
use Illuminate\Support\LazyCollection;

class CustomQuestionsTest extends CustomQuestionsTestCase
{
    public function testFieldConfigUsesCanonicalFieldNameAndExposesLegacyCompatibilityMetadata(): void
    {
        $customQuestionId = Repo::customQuestion()->add(
            Repo::customQuestion()->newDataObject([
                'contextId' => $this->contextId,
                'title' => [
                    'en' => 'Legacy Field Name'
                ],
                'description' => [
                    'en' => 'Description'
                ],
                'sequence' => 1,
                'required' => true,
                'questionType' => CustomQuestion::CUSTOM_QUESTION_TYPE_TEXT_FIELD,
            ])
        );

        $customQuestion = Repo::customQuestion()->get($customQuestionId, $this->contextId);

        $form = new CustomQuestions(
            '/test/action',
            [['key' => 'en', 'label' => 'English']],
            LazyCollection::make([$customQuestion]),
            $this->submissionId
        );

        $fieldConfig = $form->getFieldConfig($form->fields[0]);

        self::assertSame('customQuestion-' . $customQuestionId, $fieldConfig['name']);
        self::assertSame('legacy-field-name-' . $customQuestionId, $fieldConfig['legacyName']);
        self::assertSame('custom-question-field-' . $customQuestionId, $fieldConfig['data-cy']);
    }
}
