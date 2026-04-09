<?php

declare(strict_types=1);

use Statikbe\AiTranslation\AiTranslationManager;
use Statikbe\AiTranslation\Drivers\NullDriver;

it('resolves the null driver', function () {
    $manager = app(AiTranslationManager::class);
    $driver = $manager->driver('null');

    expect($driver)->toBeInstanceOf(NullDriver::class);
});

it('null driver returns the input string unchanged for single translation', function () {
    $manager = app(AiTranslationManager::class);
    $result = $manager->translate('Hello', 'en', 'nl', [], 'null');

    expect($result)->toBe('Hello');
});

it('null driver returns the input strings unchanged for batch translation', function () {
    $manager = app(AiTranslationManager::class);
    $result = $manager->translateBatch(['greeting' => 'Hello', 'farewell' => 'Goodbye'], 'en', 'nl', [], 'null');

    expect($result)->toBe(['greeting' => 'Hello', 'farewell' => 'Goodbye']);
});
