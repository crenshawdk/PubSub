<?php

declare(strict_types=1);

require dirname(__DIR__) . '/PubSub.php';

function expect(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$bus = new PubSub();
$received = [];
$exactKey = $bus->on('user.created', function (string $id) use (&$received): void {
    $received[] = "exact:{$id}";
});
$wildcardKey = $bus->on('user.*', function (string $id) use (&$received): void {
    $received[] = "wildcard:{$id}";
});

$calledKeys = $bus->emit('user.created', '42');
expect($received === ['exact:42', 'wildcard:42'], 'Exact and wildcard listeners should run in order.');
expect($calledKeys === [$exactKey, $wildcardKey], 'emit() should return invoked subscription keys.');

$onceCalls = 0;
$bus->once('recursive', function () use ($bus, &$onceCalls): void {
    $onceCalls++;
    $bus->emit('recursive');
});
$bus->emit('recursive');
expect($onceCalls === 1, 'A once listener should be removed before recursive emission.');

$exceptionBus = new PubSub();
$exceptionBus->once('throws', static function (): void {
    throw new RuntimeException('expected');
});
try {
    $exceptionBus->emit('throws');
    expect(false, 'Listener exceptions should propagate.');
} catch (RuntimeException $exception) {
    expect($exception->getMessage() === 'expected', 'The original listener exception should propagate.');
}
expect($exceptionBus->count('throws') === 0, 'A throwing once listener should still be removed.');

$mutationBus = new PubSub();
$mutated = false;
$removedCalls = 0;
$lateCalls = 0;
$removedKey = $mutationBus->on('change', static function () use (&$removedCalls): void {
    $removedCalls++;
});
$mutationBus->on('change', function () use ($mutationBus, &$mutated, &$lateCalls, $removedKey): void {
    if (!$mutated) {
        $mutated = true;
        $mutationBus->offByKey($removedKey);
        $mutationBus->on('change', static function () use (&$lateCalls): void {
            $lateCalls++;
        });
    }
});
$mutationBus->emit('change');
expect($removedCalls === 1 && $lateCalls === 0, 'Dispatch should use a snapshot of listeners.');
$mutationBus->emit('change');
expect($removedCalls === 1 && $lateCalls === 1, 'Listener mutations should affect subsequent emissions.');

$emptyBus = new PubSub();
$emptyEventKey = $emptyBus->on('', static function (): void {});
$otherEventKey = $emptyBus->on('other', static function (): void {});
expect($emptyBus->count('') === 1, 'An empty event name should be counted as an event.');
$emptyBus->clear('');
expect($emptyBus->count('') === 0, 'An empty event name should be clearable without clearing other events.');
expect($emptyBus->count() === 1, 'Clearing one event should preserve other subscriptions.');
expect(!$emptyBus->offByKey($emptyEventKey), 'Cleared subscriptions should no longer be removable.');
expect($emptyBus->offByKey($otherEventKey), 'Other event subscriptions should remain removable.');

echo "PubSub tests passed.\n";
