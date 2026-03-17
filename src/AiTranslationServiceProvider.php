<?php

namespace Statikbe\AiTranslation;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Statikbe\AiTranslation\Console\Commands\TranslateMissingCommand;
use Statikbe\AiTranslation\Facades\AiTranslation;

class AiTranslationServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('ai-translation')
            ->hasConfigFile('ai-translation')
            ->hasCommands([
                TranslateMissingCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        // Register the manager as a singleton
        $this->app->singleton(AiTranslationManager::class, function ($app) {
            return new AiTranslationManager($app);
        });

        // Bind the facade accessor
        $this->app->alias(AiTranslationManager::class, 'ai-translation');

        // Register the high-level service
        $this->app->singleton(AiTranslationService::class, function ($app) {
            return new AiTranslationService($app->make(AiTranslationManager::class));
        });
    }
}
