<?php

namespace APP\plugins\generic\customQuestions\classes\customQuestion;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use PKP\core\interfaces\CollectorInterface;

class Collector implements CollectorInterface
{
    public DAO $dao;
    public ?array $contextIds = null;
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

    public function filterByContextIds(?array $contextIds): self
    {
        $this->contextIds = $contextIds;
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
        $qb = DB::table($this->dao->table . ' as cq')
            ->select(['cq.*']);

        if (isset($this->contextIds)) {
            $qb->whereIn('cq.context_id', $this->contextIds);
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
