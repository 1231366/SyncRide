<?php

declare(strict_types=1);

namespace Cron;

use App\Support\Env;
use RuntimeException;
use Throwable;

/**
 * Resolves a job slug to an implementation and executes it inside the
 * production guardrails (CRON_ENABLED flag + shared-secret check).
 *
 * Intended to be invoked from `cron/run.php` either via the system crontab
 * or a privileged HTTPS endpoint behind the CRON_SECRET token.
 */
final class CronRunner
{
    /** @var array<string,class-string<CronJob>> */
    private array $registry;

    /** @param array<string,class-string<CronJob>> $registry */
    public function __construct(array $registry)
    {
        $this->registry = $registry;
    }

    public function run(string $jobName, ?string $providedSecret = null): string
    {
        if (!(bool) Env::get('CRON_ENABLED', false)) {
            throw new RuntimeException('Cron runner is disabled in this environment (CRON_ENABLED=false).');
        }

        $expected = (string) Env::get('CRON_SECRET', '');
        if ($expected === '' || !hash_equals($expected, (string) $providedSecret)) {
            throw new RuntimeException('Unauthorized cron invocation.');
        }

        if (!isset($this->registry[$jobName])) {
            throw new RuntimeException("Unknown cron job: {$jobName}");
        }

        $class = $this->registry[$jobName];
        /** @var CronJob $job */
        $job = new $class();

        $start = microtime(true);
        try {
            $summary = $job->run();
            $elapsed = number_format((microtime(true) - $start) * 1000, 1);
            error_log("[cron] {$jobName} OK ({$elapsed} ms): {$summary}");
            return $summary;
        } catch (Throwable $e) {
            error_log("[cron] {$jobName} FAILED: " . $e->getMessage());
            throw $e;
        }
    }
}
