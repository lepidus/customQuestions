<?php

namespace APP\plugins\generic\customQuestions\classes\customQuestionResponse;

use Illuminate\Support\Facades\DB;
use PKP\core\EntityDAO;

class DAO extends EntityDAO
{
    public $schema = 'customQuestionResponse';
    public $table = 'custom_question_responses';
    public $primaryKeyColumn = 'custom_question_response_id';
    public $primaryTableColumns = [
        'id' => 'custom_question_response_id',
        'submissionId' => 'submission_id',
        'customQuestionId' => 'custom_question_id',
        'responseType' => 'response_type',
        'value' => 'response_value'
    ];

    public function newDataObject(): CustomQuestionResponse
    {
        return app(CustomQuestionResponse::class);
    }

    public function get(int $id): ?CustomQuestionResponse
    {
        $row = DB::table($this->table)
            ->where($this->primaryKeyColumn, $id)
            ->first();
        return $row ? $this->fromRow($row) : null;
    }

    public function getByCustomQuestionId(int $customQuestionId, int $submissionId): ?CustomQuestionResponse
    {
        $row = DB::table('custom_question_responses as cqr')
            ->where('cqr.custom_question_id', $customQuestionId)
            ->where('cqr.submission_id', $submissionId)
            ->first();

        return $row ? $this->fromRow($row) : null;
    }

    public function insert(CustomQuestionResponse $customQuestionResponse): int
    {
        return parent::_insert($customQuestionResponse);
    }

    public function update(CustomQuestionResponse $customQuestionResponse): void
    {
        parent::_update($customQuestionResponse);
    }

    public function delete(CustomQuestionResponse $customQuestionResponse): void
    {
        parent::_delete($customQuestionResponse);
    }
}
