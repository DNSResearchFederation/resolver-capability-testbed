<?php

namespace ResolverTest\Services\Logging;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Configuration\FileResolver;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Reflection\ClassInspectorProvider;
use Kinikit\Core\Serialisation\JSON\JSONToObjectConverter;
use Kinikit\Persistence\Database\Generator\TableDDLGenerator;
use Kinikit\Persistence\Database\ResultSet\ResultSet;
use Kinikit\Persistence\Database\Vendors\SQLite3\SQLite3DatabaseConnection;
use Kinikit\Persistence\ORM\ORM;
use Kinikit\Persistence\ORM\Tools\SchemaGenerator;
use Kinikit\Persistence\Tools\DBInstaller;
use ResolverTest\Objects\Test\Test;
use ResolverTest\Services\Server\Server;
use ResolverTest\Services\TestService;
use ResolverTest\Services\TestType\TestTypeManager;

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
     * @var JSONToObjectConverter
     */
    private $jsonToObjectConverter;

    /**
     * @param Server $server
     * @param TestService $testService
     */
    public function __construct($server, $testService) {
        $this->server = $server;
        $this->testService = $testService;
        $this->jsonToObjectConverter = Container::instance()->get(JSONToObjectConverter::class);
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
        /**
         * @var TestTypeManager $testTypeManager
         */
        $testTypeManager = Container::instance()->get(TestTypeManager::class);
        $testType = $testTypeManager->getTestTypeForTest($test);

        if ($testType) {
            $rules = $testType->getRules();
            $columnsClause = "id INTEGER PRIMARY KEY, `date` DATETIME";

            for ($i = 1; $i <= $rules->getDns()->getExpectedQueries(); $i++) {
                $columnsClause .= ", dnsResolutionTime$i INT, dnsResolvedHostname$i VARCHAR(255), dnsClientIPAddress$i VARCHAR(255), dnsResolverQuery$i VARCHAR(255), dnsResolverRequest$i VARCHAR(255)";
            }

            for ($i = 1; $i <= $rules->getWebserver()->getExpectedQueries(); $i++) {
                $columnsClause .= ", webServerRequestTime$i INT, webServerRequestHostname$i VARCHAR(255), webServerClientIpAddress$i VARCHAR(255), webServerResponseCode$i INT";
            }

            $connection->query("CREATE TABLE combined_log ({$columnsClause});");
        }
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

        if (!$test) {
            return;
        }

        // Save into database
        $connection = new SQLite3DatabaseConnection([
            "filename" => Configuration::readParameter("storage.root") . "/logs/{$test->getKey()}.db"
        ]);

        $orm = ORM::get($connection);
        $orm->save($log);

        // Assert logs as required by test
        $this->compareLogs();

        // Save combined log with result

    }

    private function compareLogs() {

    }


    public function getLogsByDate($key, $start, $end, $limit, $format) {

        $connection = new SQLite3DatabaseConnection([
            "filename" => Configuration::readParameter("storage.root") . "/logs/$key.db"
        ]);

        $result = $connection->query("SELECT * FROM combined_log WHERE `date` > '{$start}' AND `date` < '{$end}' LIMIT {$limit};");

        return $this->formatLogs($result, $format);

    }

    public function getLogsById($key, $start, $end, $limit, $format) {

        $connection = new SQLite3DatabaseConnection([
            "filename" => Configuration::readParameter("storage.root") . "/logs/$key.db"
        ]);

        $result = $connection->query("SELECT * FROM combined_log WHERE `id` > '{$start}' AND `id` < '{$end}' LIMIT {$limit};");
        return $this->formatLogs($result, $format);

    }

    /**
     * @param ResultSet $logs
     * @param string $format
     * @return mixed
     */
    private function formatLogs($logs, $format) {

        $output = "";

        switch ($format) {
            case "jsonl":
                while ($nextLine = $logs->nextRow()) {
                    $output .= json_encode($nextLine) . "\n";
                }
                break;

            case "json":
                $output = json_encode($logs->fetchAll());
                break;

            case "csv":
                while ($nextLine = $logs->nextRow()) {
                    $output .= implode(",", $nextLine) . "\n";
                }
                break;
        }


        return $output;
    }

}