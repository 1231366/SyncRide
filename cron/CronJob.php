<?php

declare(strict_types=1);

namespace Cron;

/**
 * Contract for every scheduled job executed by {@see CronRunner}.
 *
 * Implementations must be idempotent within the same calendar minute — the
 * runner offers no de-duplication guarantee.
 */
interface CronJob
{
    /** Unique slug used to invoke the job from the runner. */
    public function name(): string;

    /** Human-readable description for logging and the README. */
    public function description(): string;

    /**
     * Execute the job. Return value is a short summary used by the runner
     * for logging. Throw on hard failure — the runner converts exceptions
     * into a non-zero exit code.
     */
    public function run(): string;
}
