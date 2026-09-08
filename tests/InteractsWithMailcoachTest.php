<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Spatie\MailcoachSdk\Concerns\InteractsWithMailcoach;
use Spatie\MailcoachSdk\Contracts\MailcoachSubscriber;
use Spatie\MailcoachSdk\Exceptions\MailcoachException;
use Spatie\MailcoachSdk\Exceptions\Unauthorized;
use Spatie\MailcoachSdk\Mailcoach;
use Spatie\MailcoachSdk\Resources\Subscriber;

beforeEach(function () {
    $this->requests = [];
    $this->responses = new MockHandler;
    $handler = HandlerStack::create($this->responses);
    $handler->push(Middleware::history($this->requests));

    $this->app->instance(Mailcoach::class, new Mailcoach(
        'test-token',
        'https://mailcoach.example/api/',
        new Client([
            'handler' => $handler,
            'base_uri' => 'https://mailcoach.example/api/',
            'http_errors' => false,
        ]),
    ));

    $this->user = new MailcoachTestUser(['email' => 'john+newsletter@example.com']);
    $this->subscriber = [
        'uuid' => 'subscriber-uuid',
        'email_list_uuid' => 'users-list',
        'email' => $this->user->email,
        'first_name' => 'John',
        'last_name' => null,
        'extra_attributes' => [],
        'tags' => ['existing'],
        'subscribed_at' => '2026-09-08T10:00:00Z',
        'unsubscribed_at' => null,
    ];
});

function mailcoachResponse(array $data): Response
{
    return new Response(200, [], json_encode(['data' => $data], JSON_THROW_ON_ERROR));
}

function mailcoachRequestData(array $transaction): array
{
    parse_str((string) $transaction['request']->getBody(), $data);

    return $data;
}

it('creates a subscriber using the model email and list', function () {
    $this->responses->append(mailcoachResponse([]), mailcoachResponse($this->subscriber));

    expect($this->user->subscribeToMailcoach())->toBe($this->user)
        ->and($this->user)->toBeInstanceOf(MailcoachSubscriber::class)
        ->and($this->user->exists)->toBeFalse()
        ->and($this->requests)->toHaveCount(2);

    $lookup = $this->requests[0]['request'];
    parse_str($lookup->getUri()->getQuery(), $query);

    expect($lookup->getMethod())->toBe('GET')
        ->and($lookup->getUri()->getPath())->toBe('/api/email-lists/users-list/subscribers')
        ->and($query)->toBe(['filter' => ['email' => $this->user->email]])
        ->and($this->requests[1]['request']->getMethod())->toBe('POST')
        ->and($this->requests[1]['request']->getUri()->getPath())->toBe('/api/email-lists/users-list/subscribers')
        ->and(mailcoachRequestData($this->requests[1]))->toBe(['email' => $this->user->email]);
});

it('merges creation attributes while keeping the model email', function () {
    $user = new class extends MailcoachTestUser
    {
        public function mailcoachEmail(): string
        {
            return $this->contact_email;
        }

        public function mailcoachAttributes(): array
        {
            return [
                'email' => 'ignored@example.com',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'extra_attributes' => ['plan' => 'trial'],
            ];
        }
    };
    $user->contact_email = 'custom@example.com';
    $this->responses->append(mailcoachResponse([]), mailcoachResponse($this->subscriber));

    $user->subscribeToMailcoach([
        'email' => 'also-ignored@example.com',
        'first_name' => 'Jane',
        'tags' => ['customer'],
        'skip_confirmation' => true,
    ]);

    parse_str($this->requests[0]['request']->getUri()->getQuery(), $query);

    expect($query['filter']['email'])->toBe('custom@example.com')
        ->and(mailcoachRequestData($this->requests[1]))->toBe([
            'email' => 'custom@example.com',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'extra_attributes' => ['plan' => 'trial'],
            'tags' => ['customer'],
            'skip_confirmation' => '1',
        ]);
});

it('leaves existing subscribers unchanged when subscribing', function (?string $subscribedAt, ?string $unsubscribedAt) {
    $this->subscriber['subscribed_at'] = $subscribedAt;
    $this->subscriber['unsubscribed_at'] = $unsubscribedAt;
    $this->responses->append(mailcoachResponse([$this->subscriber]));

    expect($this->user->subscribeToMailcoach(['first_name' => 'Changed']))->toBe($this->user)
        ->and($this->requests)->toHaveCount(1);
})->with([
    'active' => ['2026-09-08T10:00:00Z', null],
    'unconfirmed' => [null, null],
    'unsubscribed' => ['2026-09-08T10:00:00Z', '2026-09-08T11:00:00Z'],
]);

it('unsubscribes an existing subscriber', function () {
    $this->responses->append(mailcoachResponse([$this->subscriber]), new Response(204));

    expect($this->user->unsubscribeFromMailcoach())->toBe($this->user)
        ->and($this->requests)->toHaveCount(2)
        ->and($this->requests[1]['request']->getMethod())->toBe('POST')
        ->and($this->requests[1]['request']->getUri()->getPath())->toBe('/api/subscribers/subscriber-uuid/unsubscribe');
});

it('resubscribes an unsubscribed subscriber', function () {
    $this->subscriber['unsubscribed_at'] = '2026-09-08T11:00:00Z';
    $this->responses->append(mailcoachResponse([$this->subscriber]), new Response(204));

    expect($this->user->resubscribeToMailcoach())->toBe($this->user)
        ->and($this->requests)->toHaveCount(2)
        ->and($this->requests[1]['request']->getMethod())->toBe('POST')
        ->and($this->requests[1]['request']->getUri()->getPath())->toBe('/api/subscribers/subscriber-uuid/resubscribe');
});

it('leaves active and unconfirmed subscribers unchanged when resubscribing', function (?string $subscribedAt) {
    $this->subscriber['subscribed_at'] = $subscribedAt;
    $this->responses->append(mailcoachResponse([$this->subscriber]));

    expect($this->user->resubscribeToMailcoach())->toBe($this->user)
        ->and($this->requests)->toHaveCount(1);
})->with(['active' => '2026-09-08T10:00:00Z', 'unconfirmed' => null]);

it('creates a missing subscriber when resubscribing', function () {
    $this->subscriber['subscribed_at'] = null;
    $this->responses->append(mailcoachResponse([]), mailcoachResponse($this->subscriber));

    expect($this->user->resubscribeToMailcoach())->toBe($this->user)
        ->and($this->requests)->toHaveCount(2)
        ->and($this->requests[1]['request']->getUri()->getPath())->toBe('/api/email-lists/users-list/subscribers');
});

it('creates a subscriber before adding tags', function () {
    $this->responses->append(
        mailcoachResponse([]),
        mailcoachResponse($this->subscriber),
        new Response(204),
    );

    expect($this->user->tagMailcoach(['activated', 'subscribed']))->toBe($this->user)
        ->and($this->requests)->toHaveCount(3)
        ->and(mailcoachRequestData($this->requests[1]))->toBe(['email' => $this->user->email])
        ->and($this->requests[2]['request']->getMethod())->toBe('POST')
        ->and($this->requests[2]['request']->getUri()->getPath())->toBe('/api/subscribers/subscriber-uuid/tags')
        ->and(mailcoachRequestData($this->requests[2]))->toBe(['tags' => ['activated', 'subscribed']]);
});

it('adds tags without replacing tags or changing subscription status', function (?string $subscribedAt, ?string $unsubscribedAt) {
    $this->subscriber['subscribed_at'] = $subscribedAt;
    $this->subscriber['unsubscribed_at'] = $unsubscribedAt;
    $this->responses->append(mailcoachResponse([$this->subscriber]), new Response(204));

    expect($this->user->tagMailcoach(['customer']))->toBe($this->user)
        ->and($this->requests)->toHaveCount(2)
        ->and($this->requests[1]['request']->getMethod())->toBe('POST')
        ->and($this->requests[1]['request']->getUri()->getPath())->toBe('/api/subscribers/subscriber-uuid/tags')
        ->and(mailcoachRequestData($this->requests[1]))->toBe(['tags' => ['customer']]);
})->with([
    'active' => ['2026-09-08T10:00:00Z', null],
    'unconfirmed' => [null, null],
    'unsubscribed' => ['2026-09-08T10:00:00Z', '2026-09-08T11:00:00Z'],
]);

it('removes only the given tags', function () {
    $this->responses->append(mailcoachResponse([$this->subscriber]), new Response(204));

    expect($this->user->untagMailcoach(['trial']))->toBe($this->user)
        ->and($this->requests)->toHaveCount(2)
        ->and($this->requests[1]['request']->getMethod())->toBe('DELETE')
        ->and($this->requests[1]['request']->getUri()->getPath())->toBe('/api/subscribers/subscriber-uuid/tags')
        ->and(mailcoachRequestData($this->requests[1]))->toBe(['tags' => ['trial']]);
});

it('does not create missing subscribers when unsubscribing or removing tags', function (string $method, array $arguments) {
    $this->responses->append(mailcoachResponse([]));

    expect($this->user->{$method}(...$arguments))->toBe($this->user)
        ->and($this->requests)->toHaveCount(1);
})->with([
    'unsubscribe' => ['unsubscribeFromMailcoach', []],
    'untag' => ['untagMailcoach', [['trial']]],
]);

it('does nothing for empty tag arrays', function (string $method) {
    expect($this->user->{$method}([]))->toBe($this->user)
        ->and($this->requests)->toBeEmpty();
})->with(['tagMailcoach', 'untagMailcoach']);

it('returns the SDK subscriber or null', function () {
    $this->responses->append(mailcoachResponse([$this->subscriber]), mailcoachResponse([]));

    $subscriber = $this->user->mailcoachSubscriber();

    expect($subscriber)->toBeInstanceOf(Subscriber::class)
        ->and($subscriber->uuid)->toBe('subscriber-uuid')
        ->and($this->user->mailcoachSubscriber())->toBeNull()
        ->and($this->requests)->toHaveCount(2);
});

it('checks the current subscription status', function (?string $subscribedAt, ?string $unsubscribedAt, bool $exists, bool $expected) {
    $this->subscriber['subscribed_at'] = $subscribedAt;
    $this->subscriber['unsubscribed_at'] = $unsubscribedAt;
    $this->responses->append(mailcoachResponse($exists ? [$this->subscriber] : []));

    expect($this->user->isSubscribedToMailcoach())->toBe($expected);
})->with([
    'active' => ['2026-09-08T10:00:00Z', null, true, true],
    'unconfirmed' => [null, null, true, false],
    'unsubscribed' => ['2026-09-08T10:00:00Z', '2026-09-08T11:00:00Z', true, false],
    'missing' => [null, null, false, false],
]);

it('allows every operation to override the model list', function (string $method, array $arguments) {
    $this->user->list_uuid = '';
    $this->subscriber['unsubscribed_at'] = '2026-09-08T11:00:00Z';
    $this->responses->append(mailcoachResponse([$this->subscriber]), new Response(204));

    $this->user->{$method}(...array_merge($arguments, ['emailListUuid' => 'other-list']));

    expect($this->requests[0]['request']->getUri()->getPath())->toBe('/api/email-lists/other-list/subscribers');
})->with([
    'subscribe' => ['subscribeToMailcoach', []],
    'unsubscribe' => ['unsubscribeFromMailcoach', []],
    'resubscribe' => ['resubscribeToMailcoach', []],
    'tag' => ['tagMailcoach', [['customer']]],
    'untag' => ['untagMailcoach', [['trial']]],
    'lookup' => ['mailcoachSubscriber', []],
    'status' => ['isSubscribedToMailcoach', []],
]);

it('creates and tags in the explicitly selected list', function () {
    $this->responses->append(mailcoachResponse([]), mailcoachResponse($this->subscriber), new Response(204));

    $this->user->tagMailcoach(['customer'], emailListUuid: 'other-list');

    expect($this->requests[0]['request']->getUri()->getPath())->toBe('/api/email-lists/other-list/subscribers')
        ->and($this->requests[1]['request']->getUri()->getPath())->toBe('/api/email-lists/other-list/subscribers');
});

it('returns the model for chaining without keeping a list override', function () {
    $this->responses->append(
        mailcoachResponse([$this->subscriber]),
        mailcoachResponse([$this->subscriber]),
        new Response(204),
    );

    $result = $this->user->subscribeToMailcoach(emailListUuid: 'other-list')->tagMailcoach(['customer']);

    expect($result)->toBe($this->user)
        ->and($this->requests[0]['request']->getUri()->getPath())->toBe('/api/email-lists/other-list/subscribers')
        ->and($this->requests[1]['request']->getUri()->getPath())->toBe('/api/email-lists/users-list/subscribers');
});

it('uses the current model email and list on each call', function () {
    $this->responses->append(mailcoachResponse([]), mailcoachResponse([]));

    $this->user->mailcoachSubscriber();
    $this->user->email = 'new@example.com';
    $this->user->list_uuid = 'tenant-list';
    $this->user->mailcoachSubscriber();

    parse_str($this->requests[1]['request']->getUri()->getQuery(), $query);

    expect($this->requests[1]['request']->getUri()->getPath())->toBe('/api/email-lists/tenant-list/subscribers')
        ->and($query['filter']['email'])->toBe('new@example.com');
});

it('rejects missing or blank model emails before making a request', function (?string $email) {
    $this->user->email = $email;

    expect(fn () => $this->user->subscribeToMailcoach())->toThrow(MailcoachException::class, 'No Mailcoach email address was provided.')
        ->and($this->requests)->toBeEmpty();
})->with([null, '', '   ']);

it('rejects blank model lists before making a request', function (string $listUuid) {
    $this->user->list_uuid = $listUuid;

    expect(fn () => $this->user->subscribeToMailcoach())->toThrow(MailcoachException::class, 'No Mailcoach email list UUID was provided.')
        ->and($this->requests)->toBeEmpty();
})->with(['', '   ']);

it('rejects a blank list override instead of falling back to the model', function () {
    expect(fn () => $this->user->subscribeToMailcoach(emailListUuid: ''))->toThrow(MailcoachException::class, 'No Mailcoach email list UUID was provided.')
        ->and($this->requests)->toBeEmpty();
});

it('propagates SDK errors without retrying or creating a subscriber', function () {
    $this->responses->append(new Response(401, [], 'Unauthorized'));

    expect(fn () => $this->user->tagMailcoach(['customer']))->toThrow(Unauthorized::class)
        ->and($this->requests)->toHaveCount(1);
});

it('propagates errors while changing a subscriber', function () {
    $this->responses->append(mailcoachResponse([$this->subscriber]), new Response(401, [], 'Unauthorized'));

    expect(fn () => $this->user->unsubscribeFromMailcoach())->toThrow(Unauthorized::class)
        ->and($this->requests)->toHaveCount(2);
});

class MailcoachTestUser extends Model implements MailcoachSubscriber
{
    use InteractsWithMailcoach;

    protected $guarded = [];

    public function mailcoachEmailListUuid(): string
    {
        return $this->list_uuid ?? 'users-list';
    }
}
