<?php

namespace APP\plugins\generic\customQuestions\tests\classes\customQuestion;

use APP\plugins\generic\customQuestions\classes\customQuestion\CustomQuestion;
use PKP\tests\PKPTestCase;

class CustomQuestionTest extends PKPTestCase
{
    public function testGettersAndSetters(): void
    {
        $title = 'Test question title';
        $description = 'Test question description';
        $questionType = CustomQuestion::CUSTOM_QUESTION_TYPE_SMALL_TEXT_FIELD;
        $required = true;
        $seq = 1.0;
        $possibleResponses = [
            'Test question possible response 1',
            'Test question possible response 2',
            'Test question possible response 3',
        ];

        $customQuestion = new CustomQuestion();
        $customQuestion->setTitle($title, null);
        $customQuestion->setDescription($description, null);
        $customQuestion->setQuestionType($questionType);
        $customQuestion->setRequired($required);
        $customQuestion->setSequence($seq);
        $customQuestion->setPossibleResponses($possibleResponses, null);

        self::assertEquals($title, $customQuestion->getTitle(null));
        self::assertEquals($description, $customQuestion->getDescription(null));
        self::assertEquals($questionType, $customQuestion->getQuestionType());
        self::assertEquals($required, $customQuestion->getRequired());
        self::assertEquals($seq, $customQuestion->getSequence());
        self::assertEquals($possibleResponses, $customQuestion->getPossibleResponses(null));
    }

    public function testLocalizedGettersAndSetters(): void
    {
        $title = [
            'en' => 'Test question title',
        ];
        $description = [
            'en' => 'Test question description',
        ];

        $possibleResponses = [
            'en' => [
                'Test question possible response 1',
                'Test question possible response 2',
                'Test question possible response 3'
            ]
        ];

        $customQuestion = new CustomQuestion();
        $customQuestion->setLocalizedTitle($title);
        $customQuestion->setLocalizedDescription($description);
        $customQuestion->setLocalizedPossibleResponses($possibleResponses);
        self::assertEquals($title['en'], $customQuestion->getLocalizedTitle());
        self::assertEquals($description['en'], $customQuestion->getLocalizedDescription());
        self::assertEquals($possibleResponses['en'], $customQuestion->getLocalizedPossibleResponses());
    }

    public function testGetCustomQuestionTypeOptions(): void
    {
        $customQuestion = new CustomQuestion();
        self::assertEquals([
            '' => 'plugins.generic.customQuestions.chooseType',
            CustomQuestion::CUSTOM_QUESTION_TYPE_SMALL_TEXT_FIELD => 'plugins.generic.customQuestions.smalltextfield',
            CustomQuestion::CUSTOM_QUESTION_TYPE_TEXT_FIELD => 'plugins.generic.customQuestions.textfield',
            CustomQuestion::CUSTOM_QUESTION_TYPE_TEXTAREA => 'plugins.generic.customQuestions.textarea',
            CustomQuestion::CUSTOM_QUESTION_TYPE_CHECKBOXES => 'plugins.generic.customQuestions.checkboxes',
            CustomQuestion::CUSTOM_QUESTION_TYPE_RADIO_BUTTONS => 'plugins.generic.customQuestions.radiobuttons',
            CustomQuestion::CUSTOM_QUESTION_TYPE_DROP_DOWN_BOX => 'plugins.generic.customQuestions.dropdownbox',
        ], $customQuestion->getCustomQuestionTypeOptions());
    }

    public function testGetMultipleResponsesQuestionTypes(): void
    {
        $customQuestion = new CustomQuestion();
        self::assertEquals([
            CustomQuestion::CUSTOM_QUESTION_TYPE_CHECKBOXES,
            CustomQuestion::CUSTOM_QUESTION_TYPE_RADIO_BUTTONS,
            CustomQuestion::CUSTOM_QUESTION_TYPE_DROP_DOWN_BOX,
        ], $customQuestion->getMultipleResponsesQuestionTypes());
    }
}
