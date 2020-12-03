<?php

declare(strict_types=1);

namespace Ecurring\WooEcurring\EnvironmentChecker;

/**
 * Service checking requirements to run this plugin like PHP and WP versions, required plugins status etc.
 */
interface EnvironmentCheckerInterface
{
    /**
     * Whether plugin requirements are met.
     *
     * @return bool
     */
    public function checkEnvironment(): bool;

    /**
     * Return list of errors (ready for display) after environment check.
     *
     * @return iterable<string>
     */
    public function getErrors(): iterable;
}
