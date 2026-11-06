# PubSub

A lightweight event bus for PHP 8.5 and later.

```php
require __DIR__ . '/PubSub.php';

$bus = new PubSub();
$subscription = $bus->on('user.created', function (string $userId): void {
    echo "Created user: {$userId}\n";
});

$bus->once('user.*', function (string $userId): void {
    echo "First user event: {$userId}\n";
});

$bus->emit('user.created', '42');
$bus->offByKey($subscription);
```

Listeners run in registration order: exact event listeners first, then matching
prefix listeners registered with a trailing `*`. Emission uses a snapshot, so
listeners added or removed during a callback affect only later emissions.
One-time listeners are removed before being called, including when a callback
emits the same event recursively or throws an exception. `emit()` returns the
subscription keys of listeners it invoked; listener exceptions propagate to the
caller.

Run the dependency-free test script with `php tests/PubSubTest.php`.
