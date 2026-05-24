<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Guards the documented project layout. If a contributor moves or deletes
 * one of these directories the test suite breaks immediately, forcing the
 * architectural decision to be revisited rather than silently accepted.
 */
final class StructureTest extends TestCase
{
    private const REQUIRED_DIRECTORIES = [
        'app/Auth',
        'app/Support',
        'config',
        'cron/Jobs',
        'public',
        'public/admin',
        'public/driver',
        'public/partner',
        'public/api',
        'public/auth',
        'public/assets',
        'resources/views',
        'storage/logs',
        'tests/Unit',
        'tests/Integration',
    ];

    private const REQUIRED_FILES = [
        'composer.json',
        'phpunit.xml',
        '.env.example',
        '.gitignore',
        'bootstrap.php',
        'app/Support/Env.php',
        'app/Support/Database.php',
        'app/Support/Session.php',
        'app/Auth/AuthController.php',
        'app/Auth/RememberMeService.php',
        'cron/CronJob.php',
        'cron/CronRunner.php',
        'cron/run.php',
        'public/index.php',
        'config/app.php',
        'config/database.php',
        'config/cron.php',
    ];

    /** @return iterable<array{string}> */
    public static function directoriesProvider(): iterable
    {
        foreach (self::REQUIRED_DIRECTORIES as $dir) {
            yield $dir => [$dir];
        }
    }

    /** @return iterable<array{string}> */
    public static function filesProvider(): iterable
    {
        foreach (self::REQUIRED_FILES as $file) {
            yield $file => [$file];
        }
    }

    /** @dataProvider directoriesProvider */
    public function testRequiredDirectoryExists(string $dir): void
    {
        self::assertDirectoryExists(self::root() . '/' . $dir);
    }

    /** @dataProvider filesProvider */
    public function testRequiredFileExists(string $file): void
    {
        self::assertFileExists(self::root() . '/' . $file);
    }

    public function testAppClassesAreAutoloadable(): void
    {
        self::assertTrue(class_exists(\App\Support\Env::class));
        self::assertTrue(class_exists(\App\Support\Database::class));
        self::assertTrue(class_exists(\App\Support\Session::class));
        self::assertTrue(class_exists(\App\Auth\AuthController::class));
        self::assertTrue(class_exists(\App\Auth\RememberMeService::class));
    }

    public function testCronClassesAreAutoloadable(): void
    {
        self::assertTrue(interface_exists(\Cron\CronJob::class));
        self::assertTrue(class_exists(\Cron\CronRunner::class));
        self::assertTrue(class_exists(\Cron\Jobs\DailyReportJob::class));
        self::assertTrue(class_exists(\Cron\Jobs\WhatsappAgendaJob::class));
        self::assertTrue(class_exists(\Cron\Jobs\SyncAiEngineJob::class));
        self::assertTrue(class_exists(\Cron\Jobs\FinalTripReportJob::class));
    }

    private static function root(): string
    {
        return dirname(__DIR__, 2);
    }
}
