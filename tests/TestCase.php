<?php

namespace DevSarfo\LaraPhone\Tests;

use DevSarfo\LaraPhone\LaraPhoneServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            LaraPhoneServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app) {}
}
