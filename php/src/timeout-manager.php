#!/usr/bin/php
<?php

use Kinikit\Core\Bootstrapper;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Persistence\Database\Vendors\SQLite3\SQLite3DatabaseConnection;
use ResolverTest\Services\Logging\LoggingService;
use ResolverTest\Services\TestService;
use ResolverTest\Services\TestType\TestTypeManager;

chdir(__DIR__);
include_once "../vendor/autoload.php";

Container::instance()->get(Bootstrapper::class);

/**
 * @var LoggingService $loggingService
 */
$loggingService = Container::instance()->get(LoggingService::class);

$loggingService->compareLogs();