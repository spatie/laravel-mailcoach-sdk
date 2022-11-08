<?php

namespace Spatie\MailcoachSdk\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Spatie\MailcoachSdk\MailcoachSdk
 */
class Mailcoach extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Spatie\MailcoachSdk\Mailcoach::class;
    }
}
