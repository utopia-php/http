#!/usr/bin/env php
<?php

function runCommand(string $command, bool $exitOnFailure = true): int
{
    passthru($command, $exitCode);
    if ($exitOnFailure && $exitCode !== 0) {
        cleanup();
        exit($exitCode);
    }
    return $exitCode;
}

function cleanup(): void
{
    passthru('docker compose down --remove-orphans 2>&1');
}

function waitForWebServer(int $timeoutSeconds = 15): bool
{
    $iterations = $timeoutSeconds * 2;

    for ($i = 0; $i < $iterations; $i++) {
        exec('docker compose exec -T web wget -q -O- http://localhost 2>&1', $output, $exitCode);
        if ($exitCode === 0) {
            return true;
        }
        usleep(500000);
    }

    return false;
}

register_shutdown_function(function () {
    if (error_get_last() !== null) {
        cleanup();
    }
});

runCommand('docker compose up -d --build');

if (!waitForWebServer()) {
    echo "Error: Web server failed to start\n";
    cleanup();
    exit(1);
}

$exitCode = runCommand('docker compose exec -T web php vendor/bin/phpunit --configuration phpunit.xml', false);

cleanup();

exit($exitCode);
