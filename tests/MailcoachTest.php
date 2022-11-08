<?php

use Spatie\MailcoachSdk\Facades\Mailcoach;
use Spatie\MailcoachSdk\MailcoachSdkServiceProvider;

it('can get mailcoach instance', function () {
    (new MailcoachSdkServiceProvider($this->app))->bootingPackage();

    config()->set('mailcoach-sdk', [
        'api_token' => 'fake-token',
        'endpoint' => 'fake-endpoint'
    ]);

    $token = Mailcoach::apiToken();

    expect($token)->toBe('fake-token');
});
