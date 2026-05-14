<?php

use App\Services\License\UpdateService;

it('uses utc timestamps in the generated mysql updater script', function () {
    config()->set('database.default', 'mysql');
    config()->set('database.connections.mysql', [
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'reward_loyalty',
        'username' => 'root',
        'password' => '',
    ]);

    $service = (new ReflectionClass(UpdateService::class))->newInstanceWithoutConstructor();
    $method = new ReflectionMethod(UpdateService::class, 'generateUpdaterScript');
    $method->setAccessible(true);

    $script = $method->invoke(
        $service,
        base_path(),
        1,
        ['.env'],
        storage_path('app/manual-update/example.zip')
    );

    expect($script)->toContain("completed_at = UTC_TIMESTAMP()");
    expect($script)->toContain("TIMESTAMPDIFF(SECOND, started_at, UTC_TIMESTAMP())");
    expect($script)->not->toContain("completed_at = NOW()");
});
