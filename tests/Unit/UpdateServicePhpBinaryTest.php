<?php

use App\Services\License\UpdateService;

it('extracts the PHP version from fpm binary variants', function (string $binaryName, string $binaryPath, ?string $expectedVersion) {
    $service = (new ReflectionClass(UpdateService::class))->newInstanceWithoutConstructor();
    $method = new ReflectionMethod(UpdateService::class, 'extractPhpVersionFromFpmBinary');
    $method->setAccessible(true);

    expect($method->invoke($service, $binaryName, $binaryPath))->toBe($expectedVersion);
})->with([
    'herd binary' => ['php84-fpm', '/Users/admin/Library/Application Support/Herd/bin/php84-fpm', '8.4'],
    'version suffix' => ['php-fpm8.4', '/usr/sbin/php-fpm8.4', '8.4'],
    'plesk path fallback' => ['php-fpm', '/opt/plesk/php/8.4/sbin/php-fpm', '8.4'],
    'missing version' => ['php-fpm', '/usr/sbin/php-fpm', null],
]);

it('builds same-directory CLI candidates for herd-style php-fpm binaries', function () {
    $service = (new ReflectionClass(UpdateService::class))->newInstanceWithoutConstructor();
    $method = new ReflectionMethod(UpdateService::class, 'buildCliCandidatesForFpmBinary');
    $method->setAccessible(true);

    $candidates = $method->invoke(
        $service,
        '/Users/admin/Library/Application Support/Herd/bin/php84-fpm',
        'php84-fpm',
        '8.4'
    );

    expect($candidates)->toContain('/Users/admin/Library/Application Support/Herd/bin/php84');
    expect($candidates)->toContain('/Users/admin/Library/Application Support/Herd/bin/php');
});

it('probes php binaries correctly when their path contains spaces', function () {
    $service = (new ReflectionClass(UpdateService::class))->newInstanceWithoutConstructor();
    $method = new ReflectionMethod(UpdateService::class, 'probePhpBinaryVersion');
    $method->setAccessible(true);

    $dir = sys_get_temp_dir().'/php probe '.uniqid();
    $binary = $dir.'/php84';

    mkdir($dir, 0777, true);
    file_put_contents($binary, "#!/bin/sh\nif [ \"\$1\" = \"-r\" ]; then\n  echo \"8.4.99\"\nfi\n");
    chmod($binary, 0755);

    expect($method->invoke($service, $binary))->toBe('8.4.99');

    unlink($binary);
    rmdir($dir);
});
