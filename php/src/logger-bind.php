#!/usr/bin/php
<?php

use Kinikit\Core\Bootstrapper;
use Kinikit\Core\DependencyInjection\Container;
use ResolverTest\Services\Logging\LoggingService;

chdir(__DIR__);
include_once "../vendor/autoload.php";

Container::instance()->get(Bootstrapper::class);

/**
 * @var LoggingService $loggingService
 */
$loggingService = Container::instance()->get(LoggingService::class);

while ($f = fgets(STDIN)) {
    $loggingService->processNameserverLog($f);
}