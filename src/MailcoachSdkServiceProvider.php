<?php

namespace Spatie\MailcoachSdk;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\MailcoachSdk\Exceptions\MailcoachException;

class MailcoachSdkServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-mailcoach-sdk')
            ->hasConfigFile();
    }

    public function registeringPackage(): void
    {
        $this->app->bind(Mailcoach::class, function () {
            if (config('mailcoach-sdk.api_token') === null) {
                throw MailcoachException::missingApiToken();
            }

            if (config('mailcoach-sdk.endpoint') === null) {
                throw MailcoachException::missingEndpoint();
            }

            return new Mailcoach(
                config('mailcoach-sdk.api_token'),
                config('mailcoach-sdk.endpoint'),
            );
        });
    }
}
