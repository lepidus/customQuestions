<?php

namespace APP\plugins\generic\customQuestions\classes\customQuestion;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use PKP\core\EntityDAO;
use PKP\core\traits\EntityWithParent;

class DAO extends EntityDAO
{
    use EntityWithParent;

    public $schema = 'customQuestion';

    public $table = 'custom_questions';

    public $settingsTable = 'custom_question_settings';

    public $primaryKeyColumn = 'custom_question_id';

    public $primaryTableColumns = [
        'id' => 'custom_question_id',
        'contextId' => 'context_id',
        'sequence' => 'seq',
        'questionType' => 'question_type',
        'required' => 'required',
    ];

    public function getParentColumn(): string
    {
        return 'context_id';
    }

    public function newDataObject(): CustomQuestion
    {
        return app(CustomQuestion::class);
    }

    public function getCount(Collector $query): int
    {
        return $query
            ->getQueryBuilder()
            ->get('cq.' . $this->primaryKeyColumn)
            ->count();
    }

    public function getIds(Collector $query): Collection
    {
        return $query
            ->getQueryBuilder()
            ->select('cq.' . $this->primaryKeyColumn)
            ->pluck('cq.' . $this->primaryKeyColumn);
    }

    public function getMany(Collector $query): LazyCollection
    {
        $rows = $query
            ->getQueryBuilder()
            ->get();

        return LazyCollection::make(function () use ($rows) {
            foreach ($rows as $row) {
                yield $row->custom_question_id => $this->fromRow($row);
            }
        });
    }

    public function fromRow(object $row): CustomQuestion
    {
        return parent::fromRow($row);
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

    public function resequence(int $contextId): void
    {
        $customQuestionIds = DB::table($this->table)
            ->where('context_id', '=', $contextId)
            ->pluck($this->primaryKeyColumn);

        $i = 0;
        foreach ($customQuestionIds as $customQuestionId) {
            DB::table($this->table)->where($this->primaryKeyColumn, '=', $customQuestionId)->update(['seq' => ++$i]);
        }
    }
}
