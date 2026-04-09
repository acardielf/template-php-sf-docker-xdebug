<?php

declare(strict_types=1);

use Behat\Behat\Context\Context;

/**
 * Main Behat context.
 *
 * Add feature-specific step definitions here, or create dedicated
 * context classes in this directory and register them in behat.yml.
 */
class FeatureContext implements Context
{
    /**
     * Initializes context.
     *
     * Called before each scenario. Use DI via behat.yml to inject services.
     */
    public function __construct()
    {
    }
}
