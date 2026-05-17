<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected static ?string $appKey = null;

    protected function setUp(): void
    {
        $appKey = self::$appKey ??= 'base64:'.base64_encode(random_bytes(32));
        $envFile = dirname(__DIR__).'/.env';
        $envExampleFile = dirname(__DIR__).'/.env.example';
        $databaseFile = dirname(__DIR__).'/database/database.sqlite';

        if (! file_exists($envFile) && file_exists($envExampleFile)) {
            copy($envExampleFile, $envFile);
        }

        putenv("APP_KEY={$appKey}");
        $_ENV['APP_KEY'] = $appKey;
        $_SERVER['APP_KEY'] = $appKey;

        if (! file_exists($databaseFile)) {
            touch($databaseFile);
        }

        parent::setUp();
    }
}
