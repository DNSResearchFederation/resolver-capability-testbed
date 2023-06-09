<?php

namespace ResolverTest\Services\Logging;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Configuration\FileResolver;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Reflection\ClassInspectorProvider;
use Kinikit\Persistence\Database\Generator\TableDDLGenerator;
use Kinikit\Persistence\Database\Vendors\SQLite3\SQLite3DatabaseConnection;
use Kinikit\Persistence\ORM\ORM;
use Kinikit\Persistence\ORM\Tools\SchemaGenerator;
use Kinikit\Persistence\Tools\DBInstaller;
use ResolverTest\Objects\Test\Test;
use ResolverTest\Services\Server\Server;
use ResolverTest\Services\TestService;

class LoggingService {

    /**
     * @var Server
     */
    private $server;

    /**
     * @var TestService
     */
    private $testService;

    /**
     * @param Server $server
     * @param TestService $testService
     */
    public function __construct($server, $testService) {
        $this->server = $server;
        $this->testService = $testService;
    }

    /**
     * Create a log database for a given test
     *
     * @param Test $test
     * @return void
     */
    public function createLogDatabaseForTest($test) {
        $connection = new SQLite3DatabaseConnection([
            "filename" => Configuration::readParameter("storage.root") . "/logs/{$test->getKey()}.db"
        ]);

        $schemaGenerator = new SchemaGenerator(Container::instance()->get(ClassInspectorProvider::class), $connection, Container::instance()->get(FileResolver::class), Container::instance()->get(TableDDLGenerator::class));

        $dbInstaller = new DBInstaller($connection, $schemaGenerator, Container::instance()->get(FileResolver::class));
        $dbInstaller->run(["Objects/Log"]);

        // Manually add combined log table


    }

    /**
     * @param Test $test
     * @return void
     */
    public function removeLogDatabaseForTest($test) {
        $path = Configuration::readParameter("storage.root") . "/logs/{$test->getKey()}.db";
        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function processLog($logString, $service) {

        // Get standard log object from server
        $log = $this->server->processLog($logString, $service);

        // Identify which test logging for - can get via hostname in log string
        $test = $this->testService->getTestByHostname($log->getHostname());

        // Save into database
        $connection = new SQLite3DatabaseConnection([
            "filename" => Configuration::readParameter("storage.root") . "/logs/{$test->getKey()}.db"
        ]);

        $orm = ORM::get($connection);
        $orm->save($log);

        // Assert logs as required by test


        // Save combined log with result

    }

    private function analyseLogs() {


    }

}