<?php

namespace APP\plugins\generic\customQuestions\controllers\listbuilder;

use APP\plugins\generic\customQuestions\classes\customQuestion\DAO;
use APP\template\TemplateManager;
use PKP\controllers\listbuilder\ListbuilderHandler;
use PKP\controllers\listbuilder\MultilingualListbuilderGridColumn;
use PKP\controllers\listbuilder\settings\SetupListbuilderHandler;
use PKP\core\JSONMessage;

class CustomQuestionResponseItemListbuilderHandler extends SetupListbuilderHandler
{
    public $customQuestionId;

    public function initialize($request, $args = null)
    {
        parent::initialize($request, $args);
        $this->customQuestionId = (int) $request->getUserVar('customQuestionId');

        $this->setTitle('plugins.generic.customQuestions.responseItems');
        $this->setSourceType(ListbuilderHandler::LISTBUILDER_SOURCE_TYPE_TEXT);
        $this->setSaveType(ListbuilderHandler::LISTBUILDER_SAVE_TYPE_EXTERNAL);
        $this->setSaveFieldName('possibleResponses');

        $responseColumn = new MultilingualListbuilderGridColumn(
            $this,
            'possibleResponse',
            'plugins.generic.customQuestions.possibleResponse',
            null,
            null,
            null,
            null,
            ['tabIndex' => 1]
        );
        $responseColumn->setCellProvider(new CustomQuestionResponseItemListbuilderGridCellProvider());
        $this->addColumn($responseColumn);
    }

    protected function loadData($request, $filter = null): array
    {
        $customQuestionDAO = app(DAO::class);
        $customQuestion = $customQuestionDAO->get($this->customQuestionId);
        $formattedResponses = [];
        if ($customQuestion) {
            $possibleResponses = $customQuestion->getPossibleResponses(null);
            foreach ((array) $possibleResponses as $locale => $values) {
                foreach ($values as $rowId => $value) {
                    $formattedResponses[$rowId + 1][0]['content'][$locale] = $value;
                }
            }
        }
        return $formattedResponses;
    }

    protected function getRowDataElement($request, &$rowId): array
    {
        if (!empty($rowId)) {
            return parent::getRowDataElement($request, $rowId);
        }

        $rowData = $this->getNewRowId($request);
        if ($rowData) {
            return [['content' => $rowData['possibleResponse']]];
        }

        return [['content' => []]];
    }

    public function fetch($args, $request): JSONMessage
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('availableOptions', true);
        return $this->fetchGrid($args, $request);
    }
}
