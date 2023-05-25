<?php

namespace APP\plugins\generic\customQuestions\controllers\listbuilder;

use PKP\controllers\grid\GridCellProvider;

class CustomQuestionResponseItemListbuilderGridCellProvider extends GridCellProvider
{
    public function getTemplateVarsFromRowColumn($row, $column): array
    {
        if ($column->getId() === 'possibleResponse') {
            $possibleResponse = $row->getData();
            $contentColumn = $possibleResponse[0];
            $content = $contentColumn['content'];
            return ['label' => $content];
        }
        assert(false);
    }
}
