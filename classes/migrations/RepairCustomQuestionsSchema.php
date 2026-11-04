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

        if (!Schema::hasIndex(
            'custom_question_settings',
            ['custom_question_id', 'locale', 'setting_name'],
            'unique'
        )) {
            Schema::table('custom_question_settings', function (Blueprint $table) {
                $table->unique(['custom_question_id', 'locale', 'setting_name'], 'custom_question_settings_unique');
            });
        }

        if (!Schema::hasIndex('custom_question_responses', ['custom_question_id', 'submission_id'], 'unique')) {
            Schema::table('custom_question_responses', function (Blueprint $table) {
                $table->unique(
                    ['custom_question_id', 'submission_id'],
                    'custom_question_responses_question_submission_unique'
                );
            });
        }
    }
    public function down(): void
    {
        throw new DowngradeNotSupportedException();
    }

}
