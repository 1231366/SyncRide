<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Env;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

final class EnvTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset the loader's "loaded" gate so each test runs against a fresh file.
        $ref = new ReflectionClass(Env::class);
        $loaded = $ref->getProperty('loaded');
        $loaded->setAccessible(true);
        $loaded->setValue(null, false);
    }

    public function testParsesSimpleKeyValuePairs(): void
    {
        $file = $this->writeEnv("FOO=bar\nBAZ=qux");

        Env::load($file);

        self::assertSame('bar', Env::get('FOO'));
        self::assertSame('qux', Env::get('BAZ'));
    }

    public function testIgnoresCommentsAndBlankLines(): void
    {
        $file = $this->writeEnv("# comment\n\nALPHA=one\n# another\nBETA=two\n");

        Env::load($file);

        self::assertSame('one', Env::get('ALPHA'));
        self::assertSame('two', Env::get('BETA'));
    }

    public function testStripsSurroundingQuotes(): void
    {
        $file = $this->writeEnv("WITH_DOUBLE=\"hello world\"\nWITH_SINGLE='greetings'");

        Env::load($file);

        self::assertSame('hello world', Env::get('WITH_DOUBLE'));
        self::assertSame('greetings',   Env::get('WITH_SINGLE'));
    }

    public function testExpandsReferences(): void
    {
        $file = $this->writeEnv("NAME=SyncRide\nMAIL_FROM=\"\${NAME} <no-reply>\"");

        Env::load($file);

        self::assertSame('SyncRide <no-reply>', Env::get('MAIL_FROM'));
    }

    public function testCoercesBooleanAndNullLiterals(): void
    {
        $file = $this->writeEnv("DEBUG=true\nSAFE=false\nMAYBE=null");

        Env::load($file);

        self::assertTrue(Env::get('DEBUG'));
        self::assertFalse(Env::get('SAFE'));
        self::assertNull(Env::get('MAYBE'));
    }

    public function testRequireThrowsWhenMissing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing required env variable: NOT_THERE');

        Env::require('NOT_THERE');
    }

    public function testReturnsDefaultWhenMissing(): void
    {
        self::assertSame('fallback', Env::get('NEVER_SET', 'fallback'));
    }

    private function writeEnv(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'env_');
        file_put_contents($path, $contents);
        return $path;
    }
}
