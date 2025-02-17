<?php

declare(strict_types=1);

namespace LaravelHyperf\Telescope\Console;

use LaravelHyperf\Cache\Contracts\Factory as CacheFactory;
use LaravelHyperf\Foundation\Console\Command;

class ResumeCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected ?string $signature = 'telescope:resume';

    /**
     * The console command description.
     */
    protected string $description = 'Unpause all Telescope watchers';

    /**
     * Execute the console command.
     */
    public function handle(CacheFactory $cache)
    {
        /* @phpstan-ignore-next-line */
        if ($cache->get('telescope:pause-recording')) {
            /* @phpstan-ignore-next-line */
            $cache->forget('telescope:pause-recording');
        }

        $this->info('Telescope watchers resumed successfully.');
    }
}
