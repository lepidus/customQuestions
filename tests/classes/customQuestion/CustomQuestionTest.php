<?php

namespace APP\plugins\generic\customQuestions\tests\classes\customQuestion;

use PKP\tests\PKPTestCase;
use APP\plugins\generic\customQuestions\classes\customQuestion\CustomQuestion;

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
            'en' => [
                'Test question possible response 1',
                'Test question possible response 2',
                'Test question possible response 3',
            ]
        ];

        $customQuestion = new CustomQuestion();
        $customQuestion->setTitle($title, null);
        $customQuestion->setDescription($description, null);
        $customQuestion->setQuestionType($questionType);
        $customQuestion->setRequired($required);
        $customQuestion->setSequence($seq);
        $customQuestion->setPossibleResponses($possibleResponses, null);

        $this->assertEquals($title, $customQuestion->getTitle(null));
        $this->assertEquals($description, $customQuestion->getDescription(null));
        $this->assertEquals($questionType, $customQuestion->getQuestionType());
        $this->assertEquals($required, $customQuestion->getRequired());
        $this->assertEquals($seq, $customQuestion->getSequence());
        $this->assertEquals($possibleResponses, $customQuestion->getPossibleResponses(null));
    }

    public function testGetLocalizedData(): void
    {
        $title = 'Test question title';
        $description = 'Test question description';
        $possibleResponses = [
            'Test question possible response 1',
            'Test question possible response 2',
            'Test question possible response 3',
        ];

        $customQuestion = new CustomQuestion();
        $customQuestion->setTitle($title, 'en');
        $customQuestion->setDescription($description, 'en');
        $customQuestion->setPossibleResponses($possibleResponses, 'en');
        $this->assertEquals($title, $customQuestion->getLocalizedTitle());
        $this->assertEquals($description, $customQuestion->getLocalizedDescription());
        $this->assertEquals($possibleResponses, $customQuestion->getLocalizedPossibleResponses());
    }

    public function testGetCustomQuestionTypeOptions(): void
    {
        $customQuestion = new CustomQuestion();
        $this->assertEquals([
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
        $this->assertEquals([
            CustomQuestion::CUSTOM_QUESTION_TYPE_CHECKBOXES,
            CustomQuestion::CUSTOM_QUESTION_TYPE_RADIO_BUTTONS,
            CustomQuestion::CUSTOM_QUESTION_TYPE_DROP_DOWN_BOX,
        ], $customQuestion->getMultipleResponsesQuestionTypes());
    }
}
