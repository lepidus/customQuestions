<?php

namespace APP\plugins\generic\customQuestions\classes\customQuestionResponse;

use APP\plugins\generic\customQuestions\classes\facades\Repo;
use Illuminate\Support\LazyCollection;
use PKP\core\EntityDAO;
use PKP\core\traits\EntityWithParent;

class DAO extends EntityDAO
{
    use EntityWithParent;

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

    public function getParentColumn(): string
    {
        return 'custom_question_id';
    }

    public function newDataObject(): CustomQuestionResponse
    {
        return app(CustomQuestionResponse::class);
    }

    public function getCount(Collector $query): int
    {
        return $query
            ->getQueryBuilder()
            ->count();
    }

    public function getIds(Collector $query): Collection
    {
        return $query
            ->getQueryBuilder()
            ->select('cqr.' . $this->primaryKeyColumn)
            ->pluck('cqr.' . $this->primaryKeyColumn);
    }

    public function getMany(Collector $query): LazyCollection
    {
        $rows = $query
            ->getQueryBuilder()
            ->get();

        return LazyCollection::make(function () use ($rows) {
            foreach ($rows as $row) {
                yield $row->custom_question_response_id => $this->fromRow($row);
            }
        });
    }

    public function getByCustomQuestionId(int $customQuestionId, int $submissionId): ?CustomQuestionResponse
    {
        $results = Repo::customQuestionResponse()->getCollector()
            ->filterByCustomQuestionIds([$customQuestionId])
            ->filterBySubmissionIds([$submissionId])
            ->getMany();

        return $results->isNotEmpty() ? $results->first() : null;
    }

    public function fromRow(object $row): CustomQuestionResponse
    {
        $customQuestionResponse = parent::fromRow($row);
        if (@unserialize($row->response_value)) {
            $customQuestionResponse->setValue(unserialize($row->response_value));
        }

        return $customQuestionResponse;
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
