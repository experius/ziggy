<?php
/**
 * this file is part of ziggy
 *
 * @author Mr. Lewis <https://github.com/lewisvoncken>
 */

if (!class_exists('Experius\ZiggyBootstrap')) {
    require_once __DIR__ . '/Experius/ZiggyBootstrap.php';
}

try {
    return Experius\ZiggyBootstrap::createApplication();
} catch (Exception $e) {
    printf("%s: %s\n", get_class($e), $e->getMessage());
    if (array_intersect(array('-vvv', '-vv', '-v', '--verbose'), $argv)) {
        printf("%s\n", $e->getTraceAsString());
    }
    exit(1);
}