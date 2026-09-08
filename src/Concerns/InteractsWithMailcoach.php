<?php

namespace Spatie\MailcoachSdk\Concerns;

use Spatie\MailcoachSdk\Exceptions\MailcoachException;
use Spatie\MailcoachSdk\Facades\Mailcoach;
use Spatie\MailcoachSdk\Resources\Subscriber;

trait InteractsWithMailcoach
{
    abstract public function mailcoachEmailListUuid(): string;

    public function mailcoachEmail(): string
    {
        return $this->email ?? '';
    }

    public function mailcoachAttributes(): array
    {
        return [];
    }

    public function subscribeToMailcoach(array $attributes = [], ?string $emailListUuid = null): static
    {
        $this->findOrCreateMailcoachSubscriber($attributes, $emailListUuid);

        return $this;
    }

    public function unsubscribeFromMailcoach(?string $emailListUuid = null): static
    {
        $this->mailcoachSubscriber($emailListUuid)?->unsubscribe();

        return $this;
    }

    public function resubscribeToMailcoach(?string $emailListUuid = null): static
    {
        $subscriber = $this->findOrCreateMailcoachSubscriber([], $emailListUuid);

        if ($subscriber->unsubscribedAt !== null) {
            $subscriber->resubscribe();
        }

        return $this;
    }

    public function tagMailcoach(array $tags, ?string $emailListUuid = null): static
    {
        if ($tags === []) {
            return $this;
        }

        $this->findOrCreateMailcoachSubscriber([], $emailListUuid)->addTags($tags);

        return $this;
    }

    public function untagMailcoach(array $tags, ?string $emailListUuid = null): static
    {
        if ($tags === []) {
            return $this;
        }

        $this->mailcoachSubscriber($emailListUuid)?->removeTags($tags);

        return $this;
    }

    public function mailcoachSubscriber(?string $emailListUuid = null): ?Subscriber
    {
        $emailListUuid = $this->resolveMailcoachEmailListUuid($emailListUuid);
        $email = $this->resolveMailcoachEmail();

        return Mailcoach::findByEmail($emailListUuid, $email);
    }

    public function isSubscribedToMailcoach(?string $emailListUuid = null): bool
    {
        return $this->mailcoachSubscriber($emailListUuid)?->isSubscribed() ?? false;
    }

    protected function findOrCreateMailcoachSubscriber(array $attributes, ?string $emailListUuid): Subscriber
    {
        $emailListUuid = $this->resolveMailcoachEmailListUuid($emailListUuid);

        $subscriber = $this->mailcoachSubscriber($emailListUuid);

        if ($subscriber !== null) {
            return $subscriber;
        }

        return Mailcoach::createSubscriber($emailListUuid, array_merge(
            $this->mailcoachAttributes(),
            $attributes,
            ['email' => $this->resolveMailcoachEmail()],
        ));
    }

    protected function resolveMailcoachEmailListUuid(?string $emailListUuid): string
    {
        $emailListUuid ??= $this->mailcoachEmailListUuid();

        if (trim($emailListUuid) === '') {
            throw MailcoachException::missingEmailListUuid();
        }

        return $emailListUuid;
    }

    protected function resolveMailcoachEmail(): string
    {
        $email = $this->mailcoachEmail();

        if (trim($email) === '') {
            throw MailcoachException::missingEmail();
        }

        return $email;
    }
}
