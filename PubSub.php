<?php

declare(strict_types=1);

class PubSub
{
    /**
     * @var array<string, array<string, callable>>  // action => [key => callable]
     */
    private array $actions = [];

    /**
     * @var array<string, array{action: string, fn: callable}> // key => { action, fn }
     */
    private array $keyIndex = [];

    /**
     * Subscribe to an action.
     */
    public function subscribe(string $action, callable $fn): string
    {
        $this->actions[$action] ??= [];

        $key = bin2hex(random_bytes(8)); // secure unique key
        $this->actions[$action][$key] = $fn;
        $this->keyIndex[$key] = ['action' => $action, 'fn' => $fn];

        return $key;
    }

    /**
     * Subscribe to an action, auto-unsubscribe after first call.
     */
    public function subscribeOnce(string $action, callable $fn): string
    {
        $key = null;
        $key = $this->subscribe($action, function (mixed $data) use ($fn, &$key) {
            $fn($data);
            $this->unsubscribeByKey($key);
        });
        return $key;
    }

    /**
     * Publish data to all subscribers of an action.
     *
     * @return string[] List of called subscription keys
     */
    public function publish(string $action, mixed $data): array
    {
        if (empty($this->actions[$action])) {
            return [];
        }

        // Clone to avoid modification during iteration
        $subscriptions = $this->actions[$action];
        $keys = [];

        foreach ($subscriptions as $key => $subscription) {
            $keys[] = $key;
            $subscription($data);
        }

        return $keys;
    }

    /**
     * Unsubscribe by key.
     */
    public function unsubscribeByKey(string $key): bool
    {
        if (!isset($this->keyIndex[$key])) {
            return false;
        }

        $action = $this->keyIndex[$key]['action'];
        unset($this->actions[$action][$key], $this->keyIndex[$key]);

        return true;
    }

    /**
     * Unsubscribe all occurrences of a specific callable.
     *
     * @return string[] Keys that were removed
     */
    public function unsubscribe(callable $fn): array
    {
        $removed = [];

        foreach ($this->keyIndex as $key => $meta) {
            if ($meta['fn'] === $fn) {
                $this->unsubscribeByKey($key);
                $removed[] = $key;
            }
        }

        return $removed;
    }

    /**
     * Remove all subscriptions.
     */
    public function clear(): void
    {
        $this->actions = [];
        $this->keyIndex = [];
    }

    /**
     * Count subscriptions, optionally for a specific action.
     */
    public function count(string $action = ""): int
    {
        return $action !== ""
            ? count($this->actions[$action] ?? [])
            : array_sum(array_map('count', $this->actions));
    }

    /**
     * Check if a subscription key exists.
     */
    public function keyExists(string $key): bool
    {
        return isset($this->keyIndex[$key]);
    }
}
