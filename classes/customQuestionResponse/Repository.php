<?php

namespace APP\plugins\generic\customQuestions\classes\customQuestionResponse;

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

    public function newDataObject(array $params = []): CustomQuestionResponse
    {
        $object = $this->dao->newDataObject();
        if (!empty($params)) {
            $object->setAllData($params);
        }
        return $object;
    }

    public function exists(int $id): bool
    {
        return $this->dao->exists($id);
    }

    public function get(int $id): ?CustomQuestionResponse
    {
        return $this->dao->get($id);
    }

    public function getByCustomQuestionId(int $customQuestionId, int $submissionId): ?CustomQuestionResponse
    {
        return $this->dao->getByCustomQuestionId($customQuestionId, $submissionId);
    }

    public function add(CustomQuestionResponse $customQuestionResponse): int
    {
        $id = $this->dao->insert($customQuestionResponse);
        return $id;
    }

    public function edit(CustomQuestionResponse $customQuestionResponse, array $params)
    {
        $newCustomQuestionResponse = clone $customQuestionResponse;
        $newCustomQuestionResponse->setAllData(array_merge(
            $newCustomQuestionResponse->_data,
            $params
        ));

        $this->dao->update($newCustomQuestionResponse);
    }

    public function delete(CustomQuestionResponse $customQuestionResponse)
    {
        $this->dao->delete($customQuestionResponse);
    }

    public function getCollector(): Collector
    {
        return app(Collector::class);
    }
}
