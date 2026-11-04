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
        foreach (Schema::getForeignKeys('custom_question_settings') as $foreignKey) {
            if ($foreignKey['columns'] === ['custom_question_id']
                && $foreignKey['foreign_table'] === 'custom_questions'
                && $foreignKey['foreign_columns'] === ['custom_question_id']
                && strtolower($foreignKey['on_delete']) === 'cascade') {
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
