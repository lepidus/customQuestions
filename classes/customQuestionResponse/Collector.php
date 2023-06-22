<?php

namespace APP\plugins\generic\customQuestions\classes\customQuestionResponse;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use PKP\core\interfaces\CollectorInterface;

class Collector implements CollectorInterface
{
    public DAO $dao;
    public ?array $submissionIds = null;
    public ?array $customQuestionIds = null;
    public ?int $count = null;
    public ?int $offset = null;

    public function __construct(DAO $dao)
    {
        $this->dao = $dao;
    }

    public function getCount(): int
    {
        return $this->dao->getCount($this);
    }

    public function getIds(): Collection
    {
        return $this->dao->getIds($this);
    }

    public function getMany(): LazyCollection
    {
        return $this->dao->getMany($this);
    }

    public function filterBySubmissionIds(?array $submissionIds): self
    {
        $this->submissionIds = $submissionIds;
        return $this;
    }

    public function filterByCustomQuestionIds(?array $customQuestionIds): self
    {
        $this->customQuestionIds = $customQuestionIds;
        return $this;
    }

    public function limit(?int $count): self
    {
        $this->count = $count;
        return $this;
    }

    public function offset(?int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function getQueryBuilder(): Builder
    {
        $qb = DB::table($this->dao->table . ' as cqr')
            ->select(['cqr.*']);

        if (isset($this->submissionIds)) {
            $qb->whereIn('cqr.submission_id', $this->submissionIds);
        }

        if (isset($this->customQuestionIds)) {
            $qb->whereIn('cqr.custom_question_id', $this->customQuestionIds);
        }

        if (isset($this->count)) {
            $qb->limit($this->count);
        }

        if (isset($this->offset)) {
            $qb->offset($this->offset);
        }

        return $qb;
    }
}
