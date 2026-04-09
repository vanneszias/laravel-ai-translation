<?php

declare(strict_types=1);

namespace Statikbe\AiTranslation;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Statikbe\AiTranslation\Console\Commands\TranslateMissingCommand;

class AiTranslationServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('ai-translation')
            ->hasConfigFile('ai-translation')
            ->hasViews()
            ->hasCommands([
                TranslateMissingCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(AiTranslationManager::class, static fn($app) => new AiTranslationManager($app));

        $this->app->alias(AiTranslationManager::class, 'ai-translation');

        $this->app->singleton(
            AiTranslationService::class,
            static fn($app) => new AiTranslationService($app->make(AiTranslationManager::class)),
        );
    }
}
