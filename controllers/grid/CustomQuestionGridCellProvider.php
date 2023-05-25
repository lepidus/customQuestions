<?php

namespace APP\plugins\generic\customQuestions\controllers\grid;

use APP\plugins\generic\customQuestions\classes\customQuestion\CustomQuestion;
use PKP\controllers\grid\GridCellProvider;

class CustomQuestionGridCellProvider extends GridCellProvider
{
    public function getTemplateVarsFromRowColumn($row, $column): array
    {
        $element = $row->getData();
        $columnId = $column->getId();
        assert($element instanceof CustomQuestion && !empty($columnId));
        if ($columnId === 'title') {
            $label = $element->getLocalizedTitle();
            return ['label' => $label];
        }
        assert(false);
    }
}
