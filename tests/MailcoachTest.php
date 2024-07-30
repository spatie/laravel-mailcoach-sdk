<?php

use Spatie\MailcoachSdk\Facades\Mailcoach;
use Spatie\MailcoachSdk\MailcoachSdkServiceProvider;

it('can get mailcoach instance', function () {
    (new MailcoachSdkServiceProvider($this->app))->bootingPackage();

    config()->set('mailcoach-sdk', [
        'api_token' => 'fake-token',
        'endpoint' => 'fake-endpoint',
    ]);

    $token = Mailcoach::apiToken();

    expect($token)->toBe('fake-token');
});

it('throws an exception when no api token is set', function () {
    (new MailcoachSdkServiceProvider($this->app))->bootingPackage();

    config()->set('mailcoach-sdk', [
        'api_token' => null,
        'endpoint' => 'fake-endpoint',
    ]);

    Mailcoach::apiToken();
})->throws('No Mailcoach API token was provided. Please provide an API token in the `mailcoach-sdk.api_token` config key.');

it('throws an exception when no endpoint is set', function () {
    (new MailcoachSdkServiceProvider($this->app))->bootingPackage();

    config()->set('mailcoach-sdk', [
        'api_token' => 'fake-token',
        'endpoint' => null,
    ]);

    Mailcoach::apiToken();
})->throws('No Mailcoach endpoint was provided. Please provide an endpoint in the `mailcoach-sdk.endpoint` config key.');

it('can create a fake', function () {
    config()->set('mailcoach-sdk', [
        'api_token' => 'fake-token',
        'endpoint' => 'fake-endpoint',
    ]);

    Mailcoach::fake();

    Mailcoach::campaigns();

    expect(Mailcoach::getRequests())->toBe([
        [
            'verb' => 'GET',
            'uri' => 'campaigns',
            'payload' => [],
        ]
    ]);
});
