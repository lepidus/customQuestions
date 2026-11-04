<?php

namespace APP\plugins\generic\customQuestions\tests;

use APP\core\Application;
use APP\plugins\generic\customQuestions\classes\migrations\AddCustomQuestionSettingsForeignKey;
use APP\plugins\generic\customQuestions\classes\migrations\RepairCustomQuestionsSchema;
use APP\plugins\generic\customQuestions\CustomQuestionsSchemaMigration;
use Illuminate\Container\Container;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\TestCase;
use PKP\install\Installer;
use RuntimeException;

class CustomQuestionsSchemaMigrationTest extends TestCase
{
    private $originalDatabase;
    private $originalSchema;
    private string $contextTable;
    private string $contextKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalDatabase = DB::getFacadeRoot();
        $this->originalSchema = Schema::getFacadeRoot();
        // External engines must use an empty, disposable database dedicated to this test.
        $config = getenv('CUSTOM_QUESTIONS_SCHEMA_TEST_CONFIG');
        $config = $config ? json_decode($config, true, 512, JSON_THROW_ON_ERROR)
            : ['driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true];
        $connection = (new ConnectionFactory(new Container()))->make($config);
        DB::swap($connection);
        Schema::swap($connection->getSchemaBuilder());
        $contextDao = Application::getContextDAO();
        $this->contextTable = $contextDao->tableName;
        $this->contextKey = $contextDao->primaryKeyColumn;
        Schema::create($this->contextTable, function (Blueprint $table) {
            $table->bigInteger($this->contextKey)->autoIncrement();
        });
        Schema::create('submissions', function (Blueprint $table) {
            $table->bigInteger('submission_id')->autoIncrement();
        });
    }

    protected function tearDown(): void
    {
        try {
            foreach (['custom_question_responses', 'custom_question_settings', 'custom_questions',
                'submissions', $this->contextTable] as $table) {
                Schema::dropIfExists($table);
            }
        } finally {
            DB::swap($this->originalDatabase);
            Schema::swap($this->originalSchema);
            parent::tearDown();
        }
    }

    public function testFailedUpgradeKeepsRepairedSchemaAndOriginalError(): void
    {
        $installer = $this->getMockBuilder(Installer::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        self::assertTrue($installer->executeAction([
            'type' => 'migration', 'attr' => ['class' => RepairCustomQuestionsSchema::class],
        ]));

        $result = $installer->executeAction([
            'type' => 'migration', 'attr' => ['class' => FailingCustomQuestionsSchemaMigration::class],
        ]);

        self::assertFalse($result);
        self::assertStringContainsString('Expected migration failure', $installer->getErrorString());
        self::assertTrue(Schema::hasTable('custom_question_responses'));
        $this->assertUniqueConstraintsExist();
    }

    public function testFreshInstallCreatesAllTablesAndUniqueConstraints(): void
    {
        (new CustomQuestionsSchemaMigration())->up();

        self::assertTrue(Schema::hasTable('custom_questions'));
        self::assertTrue(Schema::hasTable('custom_question_responses'));
        $this->assertUniqueConstraintsExist();
    }

    public function testUpgradeRepairsPartialInstallAndPreservesSettings(): void
    {
        Schema::create('custom_questions', function (Blueprint $table) {
            $table->bigInteger('custom_question_id')->autoIncrement();
        });
        Schema::create('custom_question_settings', function (Blueprint $table) {
            $table->bigIncrements('custom_question_setting_id');
            $table->bigInteger('custom_question_id');
            $table->string('locale', 14)->default('');
            $table->string('setting_name');
            $table->text('setting_value')->nullable();
        });
        DB::table('custom_questions')->insert(['custom_question_id' => 1]);
        DB::table('custom_question_settings')->insert([
            'custom_question_id' => 1, 'locale' => 'en', 'setting_name' => 'title', 'setting_value' => 'Keep me',
        ]);

        (new RepairCustomQuestionsSchema())->up();
        (new RepairCustomQuestionsSchema())->up();

        self::assertTrue(Schema::hasTable('custom_question_responses'));
        self::assertSame('Keep me', DB::table('custom_question_settings')->value('setting_value'));
        $this->assertUniqueConstraintsExist();
    }

    public function testUpgradePreservesExistingQuestionsAndResponses(): void
    {
        (new CustomQuestionsSchemaMigration())->up();
        DB::table($this->contextTable)->insert([$this->contextKey => 1]);
        DB::table('submissions')->insert(['submission_id' => 1]);
        DB::table('custom_questions')->insert([
            'custom_question_id' => 1, 'context_id' => 1, 'question_type' => 1,
        ]);
        DB::table('custom_question_responses')->insert([
            'custom_question_id' => 1, 'submission_id' => 1, 'response_value' => 'Keep this answer',
        ]);

        (new RepairCustomQuestionsSchema())->up();

        self::assertSame('Keep this answer', DB::table('custom_question_responses')->value('response_value'));
        $this->assertUniqueConstraintsExist();
    }

    public function testDeletingContextCascadesToQuestionSettings(): void
    {
        (new CustomQuestionsSchemaMigration())->up();
        DB::table($this->contextTable)->insert([$this->contextKey => 1]);
        DB::table('custom_questions')->insert([
            'custom_question_id' => 1, 'context_id' => 1, 'question_type' => 1,
        ]);
        DB::table('custom_question_settings')->insert([
            'custom_question_id' => 1, 'locale' => 'en', 'setting_name' => 'title', 'setting_value' => 'Title',
        ]);

        DB::table($this->contextTable)->where($this->contextKey, 1)->delete();

        self::assertSame(0, DB::table('custom_questions')->count());
        self::assertSame(0, DB::table('custom_question_settings')->count());
    }

    public function testUpgradeRemovesOnlyOrphanSettingsAndAddsCascade(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            self::markTestSkipped(
                'Laravel 9 cannot add foreign keys to existing SQLite tables; run with MySQL or PostgreSQL.'
            );
        }

        Schema::create('custom_questions', function (Blueprint $table) {
            $table->bigInteger('custom_question_id')->autoIncrement();
        });
        Schema::create('custom_question_settings', function (Blueprint $table) {
            $table->bigIncrements('custom_question_setting_id');
            $table->bigInteger('custom_question_id');
            $table->string('setting_value');
        });
        DB::table('custom_questions')->insert(['custom_question_id' => 1]);
        DB::table('custom_question_settings')->insert([
            ['custom_question_id' => 1, 'setting_value' => 'Keep me'],
            ['custom_question_id' => 2, 'setting_value' => 'Orphan'],
        ]);

        (new AddCustomQuestionSettingsForeignKey())->up();
        (new AddCustomQuestionSettingsForeignKey())->up();

        self::assertSame(['Keep me'], DB::table('custom_question_settings')->pluck('setting_value')->all());
        DB::table('custom_questions')->where('custom_question_id', 1)->delete();
        self::assertSame(0, DB::table('custom_question_settings')->count());
    }

    private function assertUniqueConstraintsExist(): void
    {
        $manager = Schema::getConnection()->getDoctrineSchemaManager();
        foreach ([
            'custom_question_settings' => ['custom_question_id', 'locale', 'setting_name'],
            'custom_question_responses' => ['custom_question_id', 'submission_id'],
        ] as $table => $columns) {
            $uniqueIndexes = array_filter(
                $manager->listTableIndexes($table),
                fn ($index) => $index->isUnique() && $index->getColumns() === $columns
            );
            self::assertNotEmpty($uniqueIndexes);
        }
    }

}

class FailingCustomQuestionsSchemaMigration extends \Illuminate\Database\Migrations\Migration
{
    public function up(): void
    {
        throw new RuntimeException('Expected migration failure');
    }
}
