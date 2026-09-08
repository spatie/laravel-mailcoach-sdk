<?php

namespace Spatie\MailcoachSdk\Exceptions;

class MailcoachException extends \RuntimeException
{
    public static function missingEmailListUuid(): self
    {
        return new static('No Mailcoach email list UUID was provided. Implement mailcoachEmailListUuid() on your model or pass an emailListUuid to the operation.');
    }

    public static function missingEmail(): self
    {
        return new static('No Mailcoach email address was provided. Set the email attribute on your model or override mailcoachEmail().');
    }

    public static function missingApiToken(): self
    {
        return new static('No Mailcoach API token was provided. Please provide an API token in the `mailcoach-sdk.api_token` config key.');
    }

    public static function missingEndpoint(): self
    {
        return new static('No Mailcoach endpoint was provided. Please provide an endpoint in the `mailcoach-sdk.endpoint` config key.');
    }
}
