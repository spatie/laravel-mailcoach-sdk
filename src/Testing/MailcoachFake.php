<?php

namespace Spatie\MailcoachSdk\Testing;

use Illuminate\Support\Testing\Fakes\Fake;
use Spatie\MailcoachSdk\Mailcoach;

class MailcoachFake extends Mailcoach implements Fake
{
    protected array $requests = [];

    public function __construct()
    {
        parent::__construct('fake-token', 'fake-endpoint');
    }

    public function request(string $verb, string $uri, array $payload = []): array
    {
        $this->requests[] = [
            'verb' => $verb,
            'uri' => $uri,
            'payload' => $payload,
        ];

        return [
            'data' => [],
            'links' => [],
            'meta' => [],
        ];
    }

    public function getRequests(): array
    {
        return $this->requests;
    }
}
