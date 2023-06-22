<?php

namespace APP\plugins\generic\customQuestions\classes\facades;

use APP\plugins\generic\customQuestions\classes\customQuestion\Repository as CustomQuestionRepository;
use APP\plugins\generic\customQuestions\classes\customQuestionResponse\Repository as CustomQuestionResponseRepository;

class Repo extends \APP\facades\Repo
{
    public static function customQuestion(): CustomQuestionRepository
    {
        return app(CustomQuestionRepository::class);
    }

    public static function customQuestionResponse(): CustomQuestionResponseRepository
    {
        return app(CustomQuestionResponseRepository::class);
    }
}
