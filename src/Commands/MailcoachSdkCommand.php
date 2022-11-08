<?php

namespace Spatie\MailcoachSdk\Commands;

use Illuminate\Console\Command;

class MailcoachSdkCommand extends Command
{
    public $signature = 'laravel-mailcoach-sdk';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
