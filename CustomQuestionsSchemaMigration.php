<?php

namespace APP\plugins\generic\customQuestions;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CustomQuestionsSchemaMigration extends Migration
{
    public function up(): void
    {
        Schema::create('custom_questions', function (Blueprint $table) {
            $table->bigInteger('custom_question_id')->autoIncrement();
            $table->bigInteger('context_id');
            $table->float('seq', 8, 2)->default(0);
            $table->bigInteger('question_type');
            $table->smallInteger('required')->nullable();

            $table->foreign('context_id')
                ->references('server_id')
                ->on('servers')
                ->onDelete('cascade');
            $table->index(['context_id'], 'custom_questions_context_id');
        });

        Schema::create('custom_question_settings', function (Blueprint $table) {
            $table->bigIncrements('custom_question_setting_id');
            $table->bigInteger('custom_question_id');
            $table->string('locale', 14)->default('');
            $table->string('setting_name', 255);
            $table->longText('setting_value')->nullable();

            $table->index(['custom_question_id'], 'custom_question_settings_id');
            $table->unique(['custom_question_id', 'locale', 'setting_name'], 'custom_question_settings_pkey');
        });

        Schema::create('custom_question_responses', function (Blueprint $table) {
            $table->bigIncrements('custom_question_response_id');
            $table->bigInteger('custom_question_id');
            $table->bigInteger('submission_id');
            $table->string('response_type', 6)->nullable();
            $table->text('response_value')->nullable();

            $table->foreign('custom_question_id')
                ->references('custom_question_id')
                ->on('custom_questions')
                ->onDelete('cascade');
            $table->index(['custom_question_id'], 'custom_question_responses_custom_question_id');

            $table->foreign('submission_id')
                ->references('submission_id')
                ->on('submissions')
                ->onDelete('cascade');
            $table->index(['submission_id'], 'custom_question_responses_submission_id');

            $table->index(['custom_question_id', 'submission_id'], 'custom_question_responses_unique');
            $table->unique(['custom_question_id', 'submission_id'], 'custom_question_responses_pkey');
        });
    }
}
