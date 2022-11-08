<?php

namespace Spatie\MailcoachSdk\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\MailcoachSdk\MailcoachSdkServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {

        return [
            MailcoachSdkServiceProvider::class,
        ];
    }
}
