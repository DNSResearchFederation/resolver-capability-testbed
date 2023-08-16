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

// Select last 2 minutes worth from nameserver_queue

/**
 * @var TestService $testService
 */
$testService = Container::instance()->get(TestService::class);

/**
 * @var TestTypeManager $testTypeManager
 */
$testTypeManager = Container::instance()->get(TestTypeManager::class);

/**
 * @var LoggingService $loggingService
 */
$loggingService = Container::instance()->get(LoggingService::class);


foreach ($testService->listTests() as $test) {

    $connection = new SQLite3DatabaseConnection([
        "filename" => Configuration::readParameter("storage.root") . "/logs/{$test->getKey()}.db"
    ]);

    $testType = $testTypeManager->getTestTypeForTest($test);
    $timeoutSeconds = $testType->getRules()->getTimeoutSeconds();
    $webserverOptional = $testType->getRules()->isWebserverOptional();

    $from = date_create("now", new DateTimeZone("UTC"))->sub(new DateInterval("PT3M"))->format("Y-m-d H:i:s");
    $to = date_create("now", new DateTimeZone("UTC"))->sub(new DateInterval("PT{$timeoutSeconds}S"))->format("Y-m-d H:i:s");
    $latestNameserverEntries = $connection->query("SELECT * FROM nameserver_queue WHERE `date` BETWEEN '$from' AND '$to';")->fetchAll();


    foreach ($latestNameserverEntries as $log) {

        $latestCombinedEntries = $connection->query("SELECT * FROM combined_log WHERE `date` > '$from';")->fetchAll();

        // Filter to ones with a UUID
        if (!preg_match("/(?i)^[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}/", $log["hostname"], $uuidMatches)) {
            continue;
        }

        // Identify those not in combined log
        $matches = array_filter($latestCombinedEntries, function ($item) use ($log) {
            return in_array($log["hostname"], $item);
        });

        if (sizeof($matches) > 0) {
            continue;
        }

        // If webserver optional then success
        $uuid = $uuidMatches[0];

        $nameserverLogs = $connection->query("SELECT * FROM nameserver_queue WHERE hostname LIKE '%$uuid%' ORDER BY `date`;")->fetchAll();
        $matchedNameserverLogs = $loggingService->validateNameserverLogs($nameserverLogs, $testType->getRules()->getDns());

        if ($webserverOptional) {

            $matchedWebserverLogs = [];
            $status = sizeof($matchedNameserverLogs) < sizeof($testType->getRules()->getDns()->getExpectedQueries()) ? "Failed" : "Success";

        } else {

            $webserverLogs = $connection->query("SELECT * FROM webserver_queue WHERE hostname LIKE '%$uuid%' ORDER BY `date`;")->fetchAll();
            $matchedWebserverLogs = $loggingService->validateWebserverLogs($webserverLogs, $testType->getRules()->getWebserver());
            $status = "Failed";

        }


        $loggingService->writeCombinedLog($connection, $test->getKey(), $testType->getType(), $matchedWebserverLogs, $matchedNameserverLogs, $status);


        // Else failure

        // Write to combined log


    }

}