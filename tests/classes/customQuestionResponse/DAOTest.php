<?php

namespace APP\plugins\generic\customQuestions\tests\classes\customQuestionResponse;

use APP\plugins\generic\customQuestions\classes\customQuestion\CustomQuestion;
use APP\plugins\generic\customQuestions\classes\customQuestionResponse\CustomQuestionResponse;
use APP\plugins\generic\customQuestions\classes\customQuestionResponse\DAO as CustomQuestionResponseDAO;
use APP\plugins\generic\customQuestions\classes\facades\Repo;
use APP\plugins\generic\customQuestions\tests\CustomQuestionsTestCase;
use Illuminate\Support\Facades\DB;

class DAOTest extends CustomQuestionsTestCase
{
    public function testCreateNewDataObject(): void
    {
        $customQuestionResponseDAO = app(CustomQuestionResponseDAO::class);
        $customQuestionResponse = $customQuestionResponseDAO->newDataObject();
        self::assertInstanceOf(CustomQuestionResponse::class, $customQuestionResponse);
    }

    public function testCrud(): void
    {
        $customQuestion = Repo::customQuestion()->newDataObject();
        $customQuestion->setContextId($this->contextId);
        $customQuestion->setTitle('Test Custom Question', 'en');
        $customQuestion->setQuestionType(CustomQuestion::CUSTOM_QUESTION_TYPE_SMALL_TEXT_FIELD);
        $customQuestionId = Repo::customQuestion()->add($customQuestion);

        $customQuestionResponseDAO = app(CustomQuestionResponseDAO::class);
        $customQuestionResponse = $customQuestionResponseDAO->newDataObject();
        $customQuestionResponse->setSubmissionId($this->submissionId);
        $customQuestionResponse->setCustomQuestionId($customQuestionId);
        $customQuestionResponse->setValue(['en' => 'question response']);
        $customQuestionResponse->setResponseType('string');
        $customQuestionResponseDAO->insert($customQuestionResponse);

        $fetchedCustomQuestionResponse = $customQuestionResponseDAO->get($customQuestionResponse->getId());
        self::assertEquals([
            'id' => $customQuestionResponse->getId(),
            'submissionId' => $this->submissionId,
            'customQuestionId' => $customQuestionId,
            'value' => ['en' => 'question response'],
            'responseType' => 'string'
        ], $fetchedCustomQuestionResponse->_data);

        $customQuestionResponse->setValue(['option1', 'option2']);
        $customQuestionResponse->setResponseType('array');
        $customQuestionResponseDAO->update($customQuestionResponse);

        $fetchedCustomQuestionResponse = $customQuestionResponseDAO->getByCustomQuestionId(
            $customQuestionId,
            $this->submissionId
        );
        self::assertEquals([
            'id' => $customQuestionResponse->getId(),
            'submissionId' => $this->submissionId,
            'customQuestionId' => $customQuestion->getId(),
            'value' => ['option1', 'option2'],
            'responseType' => 'array'
        ], $fetchedCustomQuestionResponse->_data);

        $customQuestionResponseDAO->delete($customQuestionResponse);
        self::assertFalse($customQuestionResponseDAO->exists($customQuestionResponse->getId(), $customQuestionId));
    }

    public function testEmptyCheckboxResponseRoundTrip(): void
    {
        $customQuestion = Repo::customQuestion()->newDataObject();
        $customQuestion->setContextId($this->contextId);
        $customQuestion->setTitle('Empty checkbox response', 'en');
        $customQuestion->setQuestionType(CustomQuestion::CUSTOM_QUESTION_TYPE_CHECKBOXES);
        $customQuestionId = Repo::customQuestion()->add($customQuestion);

        $customQuestionResponseDAO = app(CustomQuestionResponseDAO::class);
        $customQuestionResponse = $customQuestionResponseDAO->newDataObject();
        $customQuestionResponse->setSubmissionId($this->submissionId);
        $customQuestionResponse->setCustomQuestionId($customQuestionId);
        $customQuestionResponse->setValue([]);
        $customQuestionResponse->setResponseType('array');
        $customQuestionResponseDAO->insert($customQuestionResponse);

        $fetchedCustomQuestionResponse = $customQuestionResponseDAO->get($customQuestionResponse->getId());

        self::assertSame([], $fetchedCustomQuestionResponse->getValue());
    }

    public function testStoredEmptySerializedValuesAreUnserialized(): void
    {
        $customQuestion = Repo::customQuestion()->newDataObject();
        $customQuestion->setContextId($this->contextId);
        $customQuestion->setTitle('Empty serialized response', 'en');
        $customQuestion->setQuestionType(CustomQuestion::CUSTOM_QUESTION_TYPE_SMALL_TEXT_FIELD);
        $customQuestionId = Repo::customQuestion()->add($customQuestion);

        $customQuestionResponseDAO = app(CustomQuestionResponseDAO::class);
        $customQuestionResponse = $customQuestionResponseDAO->newDataObject();
        $customQuestionResponse->setSubmissionId($this->submissionId);
        $customQuestionResponse->setCustomQuestionId($customQuestionId);
        $customQuestionResponse->setValue('placeholder');
        $customQuestionResponse->setResponseType('string');
        $customQuestionResponseDAO->insert($customQuestionResponse);

        $storedValues = [
            serialize([]) => [],
            serialize(false) => false,
            serialize('') => '',
            'plain text response' => 'plain text response',
        ];
        foreach ($storedValues as $storedValue => $expectedValue) {
            DB::table('custom_question_responses')
                ->where('custom_question_response_id', $customQuestionResponse->getId())
                ->update(['response_value' => $storedValue]);

            $fetchedCustomQuestionResponse = $customQuestionResponseDAO->get($customQuestionResponse->getId());

            self::assertSame($expectedValue, $fetchedCustomQuestionResponse->getValue(), $storedValue);
        }
    }

    public function testStoredSerializedObjectIsNotInstantiated(): void
    {
        $customQuestion = Repo::customQuestion()->newDataObject();
        $customQuestion->setContextId($this->contextId);
        $customQuestion->setTitle('Serialized object response', 'en');
        $customQuestion->setQuestionType(CustomQuestion::CUSTOM_QUESTION_TYPE_SMALL_TEXT_FIELD);
        $customQuestionId = Repo::customQuestion()->add($customQuestion);

        $customQuestionResponseDAO = app(CustomQuestionResponseDAO::class);
        $customQuestionResponse = $customQuestionResponseDAO->newDataObject();
        $customQuestionResponse->setSubmissionId($this->submissionId);
        $customQuestionResponse->setCustomQuestionId($customQuestionId);
        $customQuestionResponse->setValue(serialize(new SerializedResponseGadget()));
        $customQuestionResponse->setResponseType('string');
        $customQuestionResponseDAO->insert($customQuestionResponse);
        SerializedResponseGadget::$wokenUp = false;

        $fetchedCustomQuestionResponse = $customQuestionResponseDAO->get($customQuestionResponse->getId());

        self::assertFalse(SerializedResponseGadget::$wokenUp);
        self::assertNotInstanceOf(SerializedResponseGadget::class, $fetchedCustomQuestionResponse->getValue());
    }
}
