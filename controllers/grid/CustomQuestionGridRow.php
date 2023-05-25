<?php

namespace APP\plugins\generic\customQuestions\controllers\grid;

use APP\plugins\generic\customQuestions\classes\customQuestion\CustomQuestion;
use PKP\controllers\grid\GridRow;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\linkAction\request\RemoteActionConfirmationModal;

class CustomQuestionGridRow extends GridRow
{
    public function initialize($request, $template = null)
    {
        parent::initialize($request, $template);

        $element = parent::getData();
        assert($element instanceof CustomQuestion);
        $rowId = $this->getId();

        $router = $request->getRouter();
        if (!empty($rowId) && is_numeric($rowId)) {
            $this->addAction(
                new LinkAction(
                    'edit',
                    new AjaxModal(
                        $router->url(
                            $request,
                            null,
                            null,
                            'editCustomQuestion',
                            null,
                            ['rowId' => $rowId]
                        ),
                        __('grid.action.edit'),
                        'modal_edit',
                        true
                    ),
                    __('grid.action.edit'),
                    'edit'
                )
            );
            $this->addAction(
                new LinkAction(
                    'delete',
                    new RemoteActionConfirmationModal(
                        $request->getSession(),
                        __('manager.reviewFormElements.confirmDelete'),
                        null,
                        $router->url(
                            $request,
                            null,
                            null,
                            'deleteReviewFormElement',
                            null,
                            ['rowId' => $rowId]
                        )
                    ),
                    __('grid.action.delete'),
                    'delete'
                )
            );
        }
    }
}
