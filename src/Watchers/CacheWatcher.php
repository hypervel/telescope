<?php

declare(strict_types=1);

namespace SwooleTW\Hyperf\Telescope\Watchers;

use Hyperf\Stringable\Str;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use SwooleTW\Hyperf\Cache\Events\CacheHit;
use SwooleTW\Hyperf\Cache\Events\CacheMissed;
use SwooleTW\Hyperf\Cache\Events\KeyForgotten;
use SwooleTW\Hyperf\Cache\Events\KeyWritten;
use SwooleTW\Hyperf\Telescope\IncomingEntry;
use SwooleTW\Hyperf\Telescope\Telescope;

class CacheWatcher extends Watcher
{
    /**
     * Register the watcher.
     */
    public function register(ContainerInterface $app): void
    {
        $event = $app->get(EventDispatcherInterface::class);

        $event->listen(CacheHit::class, [$this, 'recordCacheHit']);
        $event->listen(CacheMissed::class, [$this, 'recordCacheMissed']);
        $event->listen(KeyWritten::class, [$this, 'recordKeyWritten']);
        $event->listen(KeyForgotten::class, [$this, 'recordKeyForgotten']);
    }

    /**
     * Record a cache key was found.
     */
    public function recordCacheHit(CacheHit $event): void
    {
        if (! Telescope::isRecording() || $this->shouldIgnore($event)) {
            return;
        }

        Telescope::recordCache(IncomingEntry::make([
            'type' => 'hit',
            'key' => $event->key,
            'value' => $this->formatValue($event),
        ]));
    }

    /**
     * Record a missing cache key.
     */
    public function recordCacheMissed(CacheMissed $event): void
    {
        if (! Telescope::isRecording() || $this->shouldIgnore($event)) {
            return;
        }

        Telescope::recordCache(IncomingEntry::make([
            'type' => 'missed',
            'key' => $event->key,
        ]));
    }

    /**
     * Record a cache key was updated.
     */
    public function recordKeyWritten(KeyWritten $event): void
    {
        if (! Telescope::isRecording() || $this->shouldIgnore($event)) {
            return;
        }

        Telescope::recordCache(IncomingEntry::make([
            'type' => 'set',
            'key' => $event->key,
            'value' => $this->formatValue($event),
            'expiration' => $this->formatExpiration($event),
        ]));
    }

    /**
     * Record a cache key was forgotten / removed.
     */
    public function recordKeyForgotten(KeyForgotten $event): void
    {
        if (! Telescope::isRecording() || $this->shouldIgnore($event)) {
            return;
        }

        Telescope::recordCache(IncomingEntry::make([
            'type' => 'forget',
            'key' => $event->key,
        ]));
    }

    /**
     * Determine the value of an event.
     */
    private function formatValue(mixed $event): mixed
    {
        return (! $this->shouldHideValue($event))
            ? $event->value
            : '********';
    }

    /**
     * Determine if the event value should be ignored.
     */
    private function shouldHideValue(mixed $event): bool
    {
        return Str::is(
            $this->options['hidden'] ?? [],
            $event->key
        );
    }

    protected function formatExpiration(KeyWritten $event): mixed
    {
        return property_exists($event, 'seconds')
            ? $event->seconds : $event->minutes * 60; /* @phpstan-ignore-line */
    }

    /**
     * Determine if the event should be ignored.
     */
    private function shouldIgnore(mixed $event): bool
    {
        return Str::is([
            'illuminate:queue:restart',
            'telescope:*',
        ], $event->key);
    }
}
