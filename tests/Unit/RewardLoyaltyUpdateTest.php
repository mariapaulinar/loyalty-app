<?php

use App\Models\RewardLoyaltyUpdate;

it('formats update durations as human-readable time', function (int $seconds, string $expected) {
    $update = new RewardLoyaltyUpdate([
        'duration_seconds' => $seconds,
    ]);

    expect($update->humanDuration())->toBe($expected);
})->with([
    'seconds only' => [45, '45 seconds'],
    'minutes and seconds' => [121, '2 minutes 1 second'],
    'hours and minutes' => [3661, '1 hour 1 minute'],
]);

it('returns null when an update has no duration', function () {
    $update = new RewardLoyaltyUpdate([
        'duration_seconds' => null,
    ]);

    expect($update->humanDuration())->toBeNull();
});
