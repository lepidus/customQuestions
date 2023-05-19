<?php

namespace APP\plugins\generic\customQuestions;

use PKP\plugins\GenericPlugin;

class CustomQuestionsPlugin extends GenericPlugin
{
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path);

        if ($success && $this->getEnabled()) {
        }

        return $success;
    }

    public function getDisplayName()
    {
        return __('plugins.generic.customQuestions.displayName');
    }

    public function getDescription()
    {
        return __('plugins.generic.customQuestions.description');
    }
}
