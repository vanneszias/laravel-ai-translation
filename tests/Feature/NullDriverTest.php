<?php

declare(strict_types=1);

use Statikbe\AiTranslation\AiTranslationManager;
use Statikbe\AiTranslation\Drivers\NullDriver;

it('resolves the null driver', function () {
    $manager = app(AiTranslationManager::class);
    $driver = $manager->driver('null');

    expect($driver)->toBeInstanceOf(NullDriver::class);
});

it('null driver returns empty string for single translation', function () {
    $manager = app(AiTranslationManager::class);
    $result = $manager->translate('Hello', 'en', 'nl', [], 'null');

    expect($result)->toBeEmpty();
});

it('null driver returns empty strings for batch translation', function () {
    $manager = app(AiTranslationManager::class);
    $result = $manager->translateBatch(['greeting' => 'Hello', 'farewell' => 'Goodbye'], 'en', 'nl', [], 'null');

    expect($result)->toBe(['greeting' => '', 'farewell' => '']);
});
