<?php

namespace APP\plugins\generic\customQuestions\classes\facades;

use APP\plugins\generic\customQuestions\classes\customQuestion\Repository as CustomQuestionRepository;

class Repo extends \APP\facades\Repo
{
    public static function customQuestion(): CustomQuestionRepository
    {
        return app(CustomQuestionRepository::class);
    }
}
