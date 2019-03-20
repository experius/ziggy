#!/usr/bin/env php
<?php

Phar::mapPhar('ziggy.phar');

$application = require_once 'phar://ziggy.phar/src/bootstrap.php';
$application->setPharMode(true);
$application->run();

__HALT_COMPILER();
