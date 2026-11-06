<?php

namespace APP\plugins\generic\customQuestions\tests\classes\customQuestionResponse;

class SerializedResponseGadget
{
    public static bool $wokenUp = false;

    public function __wakeup(): void
    {
        self::$wokenUp = true;
    }
}
