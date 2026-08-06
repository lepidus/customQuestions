<?php

declare(strict_types=1);

use APP\core\Application;
use Illuminate\Support\Facades\DB;

const PLUGIN_NAME = 'customquestionsplugin';
const QUESTIONS_TABLE = 'custom_questions';
const QUESTION_SETTINGS_TABLE = 'custom_question_settings';
const RESPONSES_TABLE = 'custom_question_responses';

function fail(string $message, int $status = 2): never
{
    fwrite(STDERR, json_encode(['error' => $message], JSON_UNESCAPED_SLASHES) . PHP_EOL);
    exit($status);
}

function parseOptions(array $arguments): array
{
    $options = [];
    for ($index = 0; $index < count($arguments); $index++) {
        $argument = $arguments[$index];
        if ($argument === '--apply') {
            $options['apply'] = true;
            continue;
        }
        if (!str_starts_with($argument, '--') || !isset($arguments[$index + 1])) {
            fail("Invalid argument: {$argument}");
        }
        $options[substr($argument, 2)] = $arguments[++$index];
    }
    return $options;
}

function getQuestionIds(string $marker): array
{
    return DB::table(QUESTION_SETTINGS_TABLE)
        ->where('setting_name', 'title')
        ->where('setting_value', 'like', '%' . $marker)
        ->pluck('custom_question_id')
        ->map(fn ($id) => (int) $id)
        ->all();
}

function cleanupSubmissions(int $contextId, string $marker): int
{
    $submissionIds = DB::table('submissions as s')
        ->join('publications as p', 'p.submission_id', '=', 's.submission_id')
        ->join('publication_settings as ps', 'ps.publication_id', '=', 'p.publication_id')
        ->where('s.context_id', $contextId)
        ->where('ps.setting_name', 'title')
        ->where('ps.setting_value', 'like', '%' . $marker)
        ->pluck('s.submission_id')
        ->unique()
        ->map(fn ($id) => (int) $id)
        ->all();

    foreach ($submissionIds as $submissionId) {
        $submission = \APP\facades\Repo::submission()->get($submissionId);
        if ($submission) {
            \APP\facades\Repo::submission()->delete($submission);
        }
    }
    return count($submissionIds);
}

function cleanup(int $contextId, string $marker): array
{
    $questionIds = getQuestionIds($marker);
    if ($questionIds) {
        DB::table(RESPONSES_TABLE)->whereIn('custom_question_id', $questionIds)->delete();
        DB::table(QUESTION_SETTINGS_TABLE)->whereIn('custom_question_id', $questionIds)->delete();
        DB::table(QUESTIONS_TABLE)->whereIn('custom_question_id', $questionIds)->delete();
    }
    return [
        'removedQuestions' => count($questionIds),
        'removedSubmissions' => cleanupSubmissions($contextId, $marker),
    ];
}

function enableCustomQuestionsPlugin(int $contextId): void
{
    DB::table('plugin_settings')->updateOrInsert(
        [
            'plugin_name' => PLUGIN_NAME,
            'context_id' => $contextId,
            'setting_name' => 'enabled',
        ],
        [
            'setting_value' => '1',
            'setting_type' => 'bool',
        ]
    );
}

function getSubmissionId(int $contextId, string $title): int
{
    $submissionIds = DB::table('submissions as s')
        ->join('publications as p', 'p.publication_id', '=', 's.current_publication_id')
        ->join('publication_settings as ps', 'ps.publication_id', '=', 'p.publication_id')
        ->where('s.context_id', $contextId)
        ->where('ps.setting_name', 'title')
        ->where('ps.setting_value', $title)
        ->pluck('s.submission_id')
        ->unique()
        ->values();

    if ($submissionIds->count() !== 1) {
        fail("Expected one submission titled '{$title}', found {$submissionIds->count()}.");
    }
    return (int) $submissionIds->first();
}

function responseType(int $questionType): string
{
    return match ($questionType) {
        1, 2, 3 => 'string',
        4 => 'array',
        5, 6 => 'int',
        default => fail("Unsupported question type: {$questionType}"),
    };
}

function responseValue(array $question): mixed
{
    return in_array($question['type'], [1, 2, 3], true)
        ? ['en' => $question['response']]
        : $question['response'];
}

$startedAt = microtime(true);
$operation = $argv[1] ?? '';
$options = parseOptions(array_slice($argv, 2));

if (!in_array($operation, ['seed', 'cleanup', 'count', 'enable'], true)) {
    fail('Operation must be seed, cleanup, count or enable.');
}
if (empty($options['apply']) && $operation !== 'count') {
    fail('--apply is required for seed, cleanup and enable.');
}

$contextPath = $options['context-path'] ?? '';
$testRunId = $options['test-run-id'] ?? '';
if (!preg_match('/^[A-Za-z0-9._-]+$/', $testRunId)) {
    fail('A safe --test-run-id is required.');
}
if ($contextPath === '') {
    fail('--context-path is required.');
}

$fixturePath = dirname(__DIR__) . '/fixtures/customQuestions.json';
$fixture = json_decode((string) file_get_contents($fixturePath), true, flags: JSON_THROW_ON_ERROR);
$marker = " [{$testRunId}]";

require_once getcwd() . '/tools/bootstrap.php';
Application::upgrade();

$context = Application::getContextDAO()->getByPath($contextPath);
if (!$context) {
    fail("Context not found: {$contextPath}");
}
$contextId = (int) $context->getId();

if ($operation === 'count') {
    echo json_encode([
        'operation' => 'count',
        'testRunId' => $testRunId,
        'questions' => count(getQuestionIds($marker)),
        'durationMs' => (int) round((microtime(true) - $startedAt) * 1000),
    ], JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(0);
}

$result = DB::transaction(function () use ($operation, $options, $fixture, $marker, $testRunId, $contextId) {
    if ($operation === 'enable') {
        enableCustomQuestionsPlugin($contextId);
        return [
            'operation' => 'enable',
            'testRunId' => $testRunId,
            'contextId' => $contextId,
        ];
    }

    $removed = cleanup($contextId, $marker);
    if ($operation === 'cleanup') {
        return array_merge([
            'operation' => 'cleanup',
            'testRunId' => $testRunId,
        ], $removed);
    }

    enableCustomQuestionsPlugin($contextId);

    $submissionId = isset($options['submission-title'])
        ? getSubmissionId($contextId, $options['submission-title'])
        : null;
    $questionsByKey = [];

    foreach ($fixture['questions'] as $key => $question) {
        $questionId = (int) DB::table(QUESTIONS_TABLE)->insertGetId([
            'context_id' => $contextId,
            'seq' => count($questionsByKey) + 1,
            'question_type' => $question['type'],
            'required' => $question['required'] ? 1 : 0,
        ], 'custom_question_id');
        $title = $question['title'] . $marker;
        $settings = [
            'title' => $title,
            'description' => $question['description'],
            'possibleResponses' => json_encode($question['possibleResponses'], JSON_UNESCAPED_UNICODE),
        ];
        foreach ($settings as $settingName => $settingValue) {
            DB::table(QUESTION_SETTINGS_TABLE)->insert([
                'custom_question_id' => $questionId,
                'locale' => 'en',
                'setting_name' => $settingName,
                'setting_value' => $settingValue,
            ]);
        }

        if ($submissionId !== null) {
            DB::table(RESPONSES_TABLE)->insert([
                'custom_question_id' => $questionId,
                'submission_id' => $submissionId,
                'response_type' => responseType($question['type']),
                'response_value' => serialize(responseValue($question)),
            ]);
        }

        $questionsByKey[$key] = array_merge($question, [
            'id' => $questionId,
            'title' => $title,
        ]);
    }

    return [
        'operation' => 'seed',
        'testRunId' => $testRunId,
        'contextId' => $contextId,
        'submissionId' => $submissionId,
        'questionsByKey' => $questionsByKey,
    ];
});

$result['durationMs'] = (int) round((microtime(true) - $startedAt) * 1000);
echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
