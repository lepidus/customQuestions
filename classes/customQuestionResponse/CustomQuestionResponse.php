<?php

namespace APP\plugins\generic\customQuestions\classes\customQuestionResponse;

class CustomQuestionResponse extends \PKP\core\DataObject
{
    public function getSubmissionId()
    {
        return $this->getData('submissionId');
    }

    public function setSubmissionId($submissionId)
    {
        $this->setData('submissionId', $submissionId);
    }

    public function getCustomQuestionId()
    {
        return $this->getData('customQuestionId');
    }

    public function setCustomQuestionId($customQuestionId)
    {
        $this->setData('customQuestionId', $customQuestionId);
    }

    public function getValue()
    {
        return $this->getData('value');
    }

    public function setValue($value)
    {
        $this->setData('value', $value);
    }

    public function getResponseType()
    {
        return $this->getData('responseType');
    }

    public function setResponseType($responseType)
    {
        $this->setData('responseType', $responseType);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias(
        'APP\plugins\generic\customQuestions\classes\customQuestionResponse\CustomQuestionResponse',
        '\CustomQuestionResponse'
    );
}
