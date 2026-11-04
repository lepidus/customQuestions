<?php

namespace APP\plugins\generic\customQuestions\classes\migrations;

use APP\plugins\generic\customQuestions\CustomQuestionsSchemaMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PKP\install\DowngradeNotSupportedException;

class RepairCustomQuestionsSchema extends Migration
{
    public function up(): void
    {
        (new CustomQuestionsSchemaMigration())->up();

        if (!$this->hasUniqueIndex(
            'custom_question_settings',
            ['custom_question_id', 'locale', 'setting_name']
        )) {
            Schema::table('custom_question_settings', function (Blueprint $table) {
                $table->unique(['custom_question_id', 'locale', 'setting_name'], 'custom_question_settings_unique');
            });
        }

        if (!$this->hasUniqueIndex('custom_question_responses', ['custom_question_id', 'submission_id'])) {
            Schema::table('custom_question_responses', function (Blueprint $table) {
                $table->unique(
                    ['custom_question_id', 'submission_id'],
                    'custom_question_responses_question_submission_unique'
                );
            });
        }
    }
    private function hasUniqueIndex(string $table, array $columns): bool
    {
        $connection = Schema::getConnection();
        $indexes = $connection->getDoctrineSchemaManager()->listTableIndexes($connection->getTablePrefix() . $table);
        foreach ($indexes as $index) {
            if ($index->isUnique() && $index->getColumns() === $columns) {
                return true;
            }
        }
        return false;
    }

    public function down(): void
    {
        throw new DowngradeNotSupportedException();
    }

}
