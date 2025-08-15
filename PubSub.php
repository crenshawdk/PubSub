<?php

declare(strict_types=1);

final class EventBus
{
    /**
     * @var array<string, array<string, callable>>
     */
    private array $events = [];

    /**
     * @var array<string, string> // key => event name
     */
    private array $keyMap = [];

    /**
     * Subscribe to an event.
     */
    public function on(string $event, callable $listener): string
    {
        $this->events[$event] ??= [];

        $key = bin2hex(random_bytes(8));
        $this->events[$event][$key] = $listener;
        $this->keyMap[$key] = $event;

        return $key;
    }

    /**
     * Subscribe to an event and remove listener after first call.
     */
    public function once(string $event, callable $listener): string
    {
        $wrapper = null;
        $wrapper = function (...$args) use (&$wrapper, $listener, &$key) {
            $listener(...$args);
            $this->offByKey($key);
        };
        $key = $this->on($event, $wrapper);
        return $key;
    }

    /**
     * Emit an event with arguments.
     */
    public function emit(string $event, mixed ...$args): array
    {
        $calledKeys = [];

        // Exact event listeners
        foreach ($this->events[$event] ?? [] as $key => $listener) {
            $calledKeys[] = $key;
            $listener(...$args);
        }

        // Wildcard listeners
        foreach ($this->events as $pattern => $listeners) {
            if (str_ends_with($pattern, '*') && str_starts_with($event, rtrim($pattern, '*'))) {
                foreach ($listeners as $key => $listener) {
                    $calledKeys[] = $key;
                    $listener(...$args);
                }
            }
        }

        return $calledKeys;
    }

    /**
     * Remove a listener by subscription key.
     */
    public function offByKey(string $key): bool
    {
        if (!isset($this->keyMap[$key])) {
            return false;
        }

        $event = $this->keyMap[$key];
        unset($this->events[$event][$key], $this->keyMap[$key]);
        return true;
    }

    /**
     * Remove all listeners for an event or all events.
     */
    public function clear(?string $event = null): void
    {
        if ($event) {
            foreach ($this->events[$event] ?? [] as $key => $_) {
                unset($this->keyMap[$key]);
            }
            unset($this->events[$event]);
        } else {
            $this->events = [];
            $this->keyMap = [];
        }
    }

    /**
     * Count listeners.
     */
    public function count(?string $event = null): int
    {
        if ($event) {
            return count($this->events[$event] ?? []);
        }
        return array_sum(array_map('count', $this->events));
    }
}
