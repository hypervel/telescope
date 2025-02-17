<?php

declare(strict_types=1);

namespace LaravelHyperf\Telescope\Jobs;

use Hyperf\Collection\Collection;
use LaravelHyperf\Bus\Dispatchable;
use LaravelHyperf\Bus\Queueable;
use LaravelHyperf\Queue\Contracts\ShouldQueue;
use LaravelHyperf\Queue\InteractsWithQueue;
use LaravelHyperf\Queue\SerializesModels;
use LaravelHyperf\Telescope\Contracts\EntriesRepository;

use function LaravelHyperf\Config\config;

class ProcessPendingUpdates implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param Collection<int, \LaravelHyperf\Telescope\EntryUpdate> $pendingUpdates the pending entry updates
     * @param int $attempt the number of times the job has been attempted
     */
    public function __construct(
        public Collection $pendingUpdates,
        public int $attempt = 0
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(EntriesRepository $repository): void
    {
        ++$this->attempt;

        $delay = config('telescope.queue.delay');

        $repository->update($this->pendingUpdates)->whenNotEmpty(
            fn ($pendingUpdates) => static::dispatchIf(
                $this->attempt < 3,
                $pendingUpdates,
                $this->attempt
            )->onConnection(
                config('telescope.queue.connection')
            )->onQueue(
                config('telescope.queue.queue')
            )->delay(is_numeric($delay) && $delay > 0 ? now()->addSeconds($delay) : null),
        );
    }
}
