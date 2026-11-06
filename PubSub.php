<?php

declare(strict_types=1);

final class PubSub
{
    /**
     * @var array<string, array<string, callable(mixed...): mixed>>
     */
    private array $events = [];

    /**
     * @var array<string, string> Subscription key to event name.
     */
    private array $keyMap = [];

    /**
     * Subscribe to an event. A trailing "*" subscribes to matching prefixes.
     */
    public function on(string $event, callable $listener): string
    {
        return $this->addListener($event, $listener);
    }

    /**
     * Subscribe to an event and remove the listener before its first call.
     */
    public function once(string $event, callable $listener): string
    {
        $key = '';
        $key = $this->addListener($event, function (...$args) use (&$key, $listener): void {
            $this->offByKey($key);
            $listener(...$args);
        });

        return $key;
    }

    /**
     * Emit an event with arguments and return the keys of listeners invoked.
     */
    public function emit(string $event, mixed ...$args): array
    {
        // Snapshot all matching listeners before dispatch so mutations during a
        // callback affect only subsequent emissions.
        $listenersToCall = $this->events[$event] ?? [];

        foreach ($this->events as $pattern => $listeners) {
            if (str_ends_with($pattern, '*')
                && str_starts_with($event, substr($pattern, 0, -1))) {
                $listenersToCall += $listeners;
            }
        }

        $calledKeys = [];
        foreach ($listenersToCall as $key => $listener) {
            $calledKeys[] = $key;
            $listener(...$args);
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

        if ($this->events[$event] === []) {
            unset($this->events[$event]);
        }

        return true;
    }

    /**
     * Remove all listeners for an event or all events.
     */
    public function clear(?string $event = null): void
    {
        if ($event !== null) {
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
        if ($event !== null) {
            return count($this->events[$event] ?? []);
        }

        return array_sum(array_map('count', $this->events));
    }

    private function addListener(string $event, callable $listener): string
    {
        do {
            $key = bin2hex(random_bytes(16));
        } while (isset($this->keyMap[$key]));

        $this->events[$event][$key] = $listener;
        $this->keyMap[$key] = $event;

        return $key;
    }
}
