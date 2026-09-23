<?php

declare(strict_types=1);

/**
 * Belsnickel's autoloader. Admirable: Composer is used when present, but the committee
 * still assembles without it. A website that cannot start is the most impish website.
 */
$composerAutoload = __DIR__ . '/vendor/autoload.php';

if (is_file($composerAutoload)) {
    require_once $composerAutoload;

    return;
}

spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'PartyPlanningCommittee\\Tests\\' => __DIR__ . '/tests/',
        'PartyPlanningCommittee\\' => __DIR__ . '/src/',
    ];

    foreach ($prefixes as $prefix => $baseDirectory) {
        if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
            continue;
        }

        $relative = substr($class, strlen($prefix));
        $path = $baseDirectory . str_replace('\\', '/', $relative) . '.php';

        if (is_file($path)) {
            require_once $path;

            return;
        }
    }
});
