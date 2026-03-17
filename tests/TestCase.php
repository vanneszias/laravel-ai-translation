<?php

declare(strict_types=1);

namespace Statikbe\AiTranslation\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Statikbe\AiTranslation\AiTranslationServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            AiTranslationServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('ai-translation.default_driver', 'null');
    }
}
