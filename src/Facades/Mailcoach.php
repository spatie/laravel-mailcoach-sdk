<?php

namespace Spatie\MailcoachSdk\Facades;

use Illuminate\Support\Facades\Facade;
use Spatie\MailcoachSdk\Testing\MailcoachFake;

/**
 * @see \Spatie\MailcoachSdk\Mailcoach
 */
class Mailcoach extends Facade
{
    /**
     * Replace the bound instance with a fake.
     *
     * @return MailcoachFake
     */
    public static function fake()
    {
        return tap(new MailcoachFake, function ($fake) {
            static::swap($fake);
        });
    }

    protected static function getFacadeAccessor()
    {
        return \Spatie\MailcoachSdk\Mailcoach::class;
    }
}
