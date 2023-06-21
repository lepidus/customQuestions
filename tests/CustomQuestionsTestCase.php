<?php

namespace APP\plugins\generic\customQuestions\tests;

use PKP\db\DAORegistry;
use PKP\plugins\Hook;
use PKP\tests\DatabaseTestCase;

class CustomQuestionsTestCase extends DatabaseTestCase
{
    protected $contextId;

    protected function getAffectedTables(): array
    {
        return [
            ...parent::getAffectedTables(),
            'custom_questions',
            'custom_question_settings',
            'custom_question_responses'
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->contextId = $this->createContext();
        $this->addSchemaFile('customQuestion');
        $this->addSchemaFile('customQuestionResponse');
    }

    protected function tearDown(): void
    {
        $contextDAO = DAORegistry::getDAO('ServerDAO');
        $context = $contextDAO->getById($this->contextId);
        $contextDAO->deleteObject($context);

        parent::tearDown();
    }

    protected function createContext(): int
    {
        $contextDAO = DAORegistry::getDAO('ServerDAO');
        $context = $contextDAO->newDataObject();
        $context->setData('seq', 2.0);
        $context->setData('enabled', true);
        $context->setData('primaryLocale', 'en');
        $context->setPath('testContext');
        return $contextDAO->insertObject($context);
    }

    protected function addSchemaFile(string $schemaName): void
    {
        Hook::add(
            'Schema::get::' . $schemaName,
            function (string $hookName, array $args) use ($schemaName) {
                $schema = & $args[0];

                $schemaFile = sprintf(
                    '%s/plugins/generic/customQuestions/schemas/%s.json',
                    BASE_SYS_DIR,
                    $schemaName
                );
                if (file_exists($schemaFile)) {
                    $schema = json_decode(file_get_contents($schemaFile));
                    if (!$schema) {
                        throw new \Exception(
                            'Schema failed to decode. This usually means it is invalid JSON. Requested: '
                            . $schemaFile
                            . '. Last JSON error: '
                            . json_last_error()
                        );
                    }
                }
                return true;
            }
        );
    }
}
