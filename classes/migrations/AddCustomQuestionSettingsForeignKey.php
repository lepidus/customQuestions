<?php

namespace APP\plugins\generic\customQuestions\classes\migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PKP\install\DowngradeNotSupportedException;

class AddCustomQuestionSettingsForeignKey extends Migration
{
    public function up(): void
    {
        $connection = Schema::getConnection();
        $prefix = $connection->getTablePrefix();
        $foreignKeys = $connection->getDoctrineSchemaManager()->listTableForeignKeys(
            $prefix . 'custom_question_settings'
        );
        foreach ($foreignKeys as $foreignKey) {
            if ($foreignKey->getLocalColumns() === ['custom_question_id']
                && $foreignKey->getForeignTableName() === $prefix . 'custom_questions'
                && $foreignKey->getForeignColumns() === ['custom_question_id']
                && strtolower((string) $foreignKey->onDelete()) === 'cascade') {
                return;
            }
        }

        // Cascaded context deletions may already have left settings without a question.
        DB::table('custom_question_settings')
            ->whereNotIn('custom_question_id', DB::table('custom_questions')->select('custom_question_id'))
            ->delete();

        Schema::table('custom_question_settings', function (Blueprint $table) {
            $table->foreign('custom_question_id')
                ->references('custom_question_id')
                ->on('custom_questions')
                ->onDelete('cascade');
        });
    }
    public function down(): void
    {
        throw new DowngradeNotSupportedException();
    }

}
