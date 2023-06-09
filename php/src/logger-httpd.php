#!/usr/bin/php
<?php

use Kinikit\Core\DependencyInjection\Container;
use ResolverTest\Services\Logging\LoggingService;
use ResolverTest\Services\Server\Server;

/**
 * @var LoggingService $loggingService
 */
$loggingService = Container::instance()->get(Server::class);

while ($f = fgets(STDIN)) {
    $loggingService->processLog($f, Server::SERVICE_WEBSERVER);
}