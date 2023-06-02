<?php

use Kinikit\Core\Bootstrapper;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Init;
use ResolverTest\Services\TestService;

include_once "../vendor/autoload.php";

Container::instance()->get(Init::class);
Container::instance()->get(Bootstrapper::class);

$testService = Container::instance()->get(TestService::class);

$testService->synchroniseTests();