<?php

namespace Spatie\MailcoachSdk;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MailcoachSdkServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-mailcoach-sdk')
            ->hasConfigFile();
    }

    public function registeringPackage()
    {
        $this->app->bind(Mailcoach::class, function () {
            if (config('mailcoach-sdk.api_token') === null) {
                return null;
            }

            if (config('mailcoach-sdk.endpoint') === null) {
                return null;
            }

            return new Mailcoach(
                config('mailcoach-sdk.api_token'),
                config('mailcoach-sdk.endpoint'),
            );
        });
    }
}
