<?php

namespace APP\plugins\generic\customQuestions;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CustomQuestionsSchemaMigration extends Migration
{
    public function up()
    {
        Schema::create('custom_questions', function (Blueprint $table) {
            $table->bigInteger('custom_question_id')->autoIncrement();
            $table->float('seq', 8, 2)->default(0);
            $table->bigInteger('question_type');
            $table->smallInteger('required')->nullable();
        });

        Schema::create('custom_question_settings', function (Blueprint $table) {
            $table->bigIncrements('custom_question_setting_id');
            $table->bigInteger('custom_question_id');
            $table->string('locale', 14)->default('');
            $table->string('setting_name', 255);
            $table->longText('setting_value')->nullable();
            $table->string('setting_type', 6)->comment('(bool|int|float|string|object)');
            $table->index(['custom_question_id'], 'custom_question_settings_id');
            $table->unique(['custom_question_id', 'locale', 'setting_name'], 'custom_question_settings_pkey');
        });

        Schema::create('custom_question_responses', function (Blueprint $table) {
            $table->bigIncrements('custom_question_response_id');
            $table->bigInteger('publication_id');
            $table->foreign('publication_id')->references('publication_id')->on('publications')->onDelete('cascade');
            $table->index(['publication_id'], 'custom_question_responses_publication_id');
            $table->string('response_type', 6)->nullable();
            $table->text('response_value')->nullable();
            $table->index(['custom_question_response_id', 'publication_id'], 'custom_question_responses_unique');
        });
    }
}
