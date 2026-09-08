<?php

namespace Spatie\MailcoachSdk\Contracts;

use Spatie\MailcoachSdk\Resources\Subscriber;

interface MailcoachSubscriber
{
    public function mailcoachEmailListUuid(): string;

    public function mailcoachEmail(): string;

    public function mailcoachAttributes(): array;

    public function subscribeToMailcoach(array $attributes = [], ?string $emailListUuid = null): static;

    public function unsubscribeFromMailcoach(?string $emailListUuid = null): static;

    public function resubscribeToMailcoach(?string $emailListUuid = null): static;

    public function tagMailcoach(array $tags, ?string $emailListUuid = null): static;

    public function untagMailcoach(array $tags, ?string $emailListUuid = null): static;

    public function mailcoachSubscriber(?string $emailListUuid = null): ?Subscriber;

    public function isSubscribedToMailcoach(?string $emailListUuid = null): bool;
}
