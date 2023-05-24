<?php

namespace APP\plugins\generic\customQuestions\classes\customQuestion;

use Illuminate\Support\Facades\DB;
use PKP\core\EntityDAO;

class DAO extends EntityDAO
{
    public $schema = 'customQuestion';
    public $table = 'custom_questions';
    public $settingsTable = 'custom_question_settings';
    public $primaryKeyColumn = 'custom_question_id';
    public $primaryTableColumns = [
        'id' => 'custom_question_id',
        'sequence' => 'seq',
        'questionType' => 'question_type',
        'required' => 'required',
    ];

    public function newDataObject(): CustomQuestion
    {
        return app(CustomQuestion::class);
    }

    public function get(int $id): ?CustomQuestion
    {
        $row = DB::table($this->table)
            ->where($this->primaryKeyColumn, $id)
            ->first();
        return $row ? $this->fromRow($row) : null;
    }

    public function insert(CustomQuestion $customQuestion): int
    {
        return parent::_insert($customQuestion);
    }

    public function update(CustomQuestion $customQuestion): void
    {
        parent::_update($customQuestion);
    }

    public function delete(CustomQuestion $customQuestion): void
    {
        parent::_delete($customQuestion);
    }
}
