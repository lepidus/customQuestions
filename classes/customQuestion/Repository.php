<?php

namespace APP\plugins\generic\customQuestions\classes\customQuestion;

use APP\core\Request;
use PKP\services\PKPSchemaService;

class Repository
{
    public $dao;

    protected $request;

    protected $schemaService;

    public function __construct(DAO $dao, Request $request, PKPSchemaService $schemaService)
    {
        $this->dao = $dao;
        $this->request = $request;
        $this->schemaService = $schemaService;
    }

    public function newDataObject(array $params = []): CustomQuestion
    {
        $object = $this->dao->newDataObject();
        if (!empty($params)) {
            $object->setAllData($params);
        }
        return $object;
    }

    public function exists(int $id, int $contextId = null): bool
    {
        return $this->dao->exists($id, $contextId);
    }

    public function get(int $id, int $contextId = null): ?CustomQuestion
    {
        return $this->dao->get($id, $contextId);
    }

    public function add(CustomQuestion $customQuestion): int
    {
        $id = $this->dao->insert($customQuestion);
        return $id;
    }

    public function edit(CustomQuestion $customQuestion, array $params)
    {
        $newCustomQuestion = clone $customQuestion;
        $newCustomQuestion->setAllData(array_merge($newCustomQuestion->_data, $params));

        $this->dao->update($newCustomQuestion);
    }

    public function delete(CustomQuestion $customQuestion)
    {
        $this->dao->delete($customQuestion);
    }

    public function getCollector(): Collector
    {
        return app(Collector::class);
    }
}
