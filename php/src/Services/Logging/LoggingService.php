<?php

namespace ResolverTest\Services\Logging;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Configuration\FileResolver;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Reflection\ClassInspectorProvider;
use Kinikit\Persistence\Database\Generator\TableDDLGenerator;
use Kinikit\Persistence\Database\ResultSet\ResultSet;
use Kinikit\Persistence\Database\Vendors\SQLite3\SQLite3DatabaseConnection;
use Kinikit\Persistence\ORM\ORM;
use Kinikit\Persistence\ORM\Tools\SchemaGenerator;
use Kinikit\Persistence\Tools\DBInstaller;
use ResolverTest\Objects\Log\BaseLog;
use ResolverTest\Objects\Log\NameserverLog;
use ResolverTest\Objects\Log\WebserverLog;
use ResolverTest\Objects\Test\Test;
use ResolverTest\Services\Config\GlobalConfigService;
use ResolverTest\Services\Server\Server;
use ResolverTest\Services\TestService;
use ResolverTest\Services\TestType\TestTypeManager;
use ResolverTest\Services\Util\IPAddressUtils;
use ResolverTest\ValueObjects\TestType\TestType;
use ResolverTest\ValueObjects\TestType\TestTypeDNSRules;
use ResolverTest\ValueObjects\TestType\TestTypeExpectedQuery;
use ResolverTest\ValueObjects\TestType\TestTypeRules;
use ResolverTest\ValueObjects\TestType\TestTypeWebServerRules;

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
     * @var TestTypeManager
     */
    private $testTypeManager;

    /**
     * @var GlobalConfigService
     */
    private $configService;

    /**
     * @param Server $server
     * @param TestService $testService
     * @param TestTypeManager $testTypeManager
     * @param GlobalConfigService $configService
     */
    public function __construct($server, $testService, $testTypeManager, $configService) {
        $this->server = $server;
        $this->testService = $testService;
        $this->testTypeManager = $testTypeManager;
        $this->configService = $configService;
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
            $columnsClause = "id INTEGER PRIMARY KEY, `date` DATETIME, status VARCHAR(50)";

            for ($i = 1; $i <= sizeof($rules->getDns()->getExpectedQueries()); $i++) {
                $columnsClause .= ", dnsResolutionTime$i INT, dnsResolvedHostname$i VARCHAR(255), dnsClientIpAddress$i VARCHAR(255), dnsResolverQuery$i VARCHAR(255), dnsResolverAnswer$i VARCHAR(255)";
            }

            for ($i = 1; $i <= $rules->getWebserver()->getExpectedQueries(); $i++) {
                $columnsClause .= ", webServerRequestTime$i INT, webServerRequestHostname$i VARCHAR(255), webServerClientIpAddress$i VARCHAR(255), webServerResponseCode$i INT";
            }

            $connection->query("CREATE TABLE combined_log ({$columnsClause});");
        }
    }

    /**
     * Delete the logs database for a given test
     *
     * @param Test $test
     * @return void
     */
    public function removeLogDatabaseForTest($test) {
        $path = Configuration::readParameter("storage.root") . "/logs/{$test->getKey()}.db";
        if (file_exists($path)) {
            unlink($path);
        }
    }


    /**
     * @param string $logString
     * @return void
     */
    public function processNameserverLog($logString) {

        /**
         * @var NameserverLog $log
         */
        $log = $this->server->processLog($logString, Server::SERVICE_NAMESERVER);
        $test = $this->testService->getTestByHostname($log->getHostname());

        if (!$test) {
            return;
        }

        $testType = $this->testTypeManager->getTestTypeForTest($test);

        $expectedRecordTypes = [];
        foreach ($testType->getRules()->getDns()->getExpectedQueries() as $expectedQuery) {
            $expectedRecordTypes[] = $expectedQuery->getType();
        }

        // Validate log entry is one we care about
        if (!in_array($log->getRecordType(), $expectedRecordTypes) || sizeof(explode(".", $log->getHostname())) < 3) {
            return;
        }

        // Save into database
        $connection = new SQLite3DatabaseConnection([
            "filename" => Configuration::readParameter("storage.root") . "/logs/{$test->getKey()}.db"
        ]);

        $orm = ORM::get($connection);
        $orm->save($log);

    }

    /**
     * @param string $logString
     * @return void
     */
    public function processWebserverLog($logString) {

        /**
         * @var WebserverLog $log
         */
        $log = $this->server->processLog($logString, Server::SERVICE_WEBSERVER);
        $test = $this->testService->getTestByHostname($log->getHostname());

        if (!$test) {
            return;
        }

        $testType = $this->testTypeManager->getTestTypeForTest($test);

        // Validate log entry is one we care about
        if (sizeof(explode(".", $log->getHostname())) < 3) {
            return;
        }


        // Save into database
        $connection = new SQLite3DatabaseConnection([
            "filename" => Configuration::readParameter("storage.root") . "/logs/{$test->getKey()}.db"
        ]);

        $orm = ORM::get($connection);
        $orm->save($log);

        // Get corresponding nameserver logs and check against rules
        $this->compareLogs($connection, $testType, $log, $test->getKey());
    }

    /**
     * Analyse webserver/nameserver logs and write a combined entry if test criteria is met
     *
     * @param SQLite3DatabaseConnection $connection
     * @param TestType $testType
     * @param BaseLog $triggerLog
     * @param string $sessionKey
     * @return void
     */
    public function compareLogs($connection, $testType, $triggerLog, $sessionKey) {

        if ($testType->getRules()->getRelationalKey() == TestTypeRules::RELATIONAL_KEY_HOSTNAME) {

            $relationalKey = $triggerLog->getRelationalKeyValue($testType->getRules()->getRelationalKey());

            $webserverLogs = $connection->query("SELECT * FROM webserver_queue WHERE hostname LIKE '%$relationalKey' ORDER BY `date`;")->fetchAll();
            if (!$webserverLogs)
                return;

            $nameserverLogs = $connection->query("SELECT * FROM nameserver_queue WHERE hostname LIKE '%$relationalKey' ORDER BY `date`;")->fetchAll();
            if (!$nameserverLogs)
                return;

        } else {

            $hostname = $triggerLog->getRelationalKeyValue(TestTypeRules::RELATIONAL_KEY_HOSTNAME);
            $firstLog = $connection->query("SELECT * FROM nameserver_queue WHERE hostname LIKE '%$hostname' ORDER BY `date`;")->fetchAll();

            if (!$firstLog) {
                return;
            }

            $clientIp = $firstLog[0]["ip_address"];
            $timeout = $testType->getRules()->getTimeoutSeconds();
            $recently = date_create("now", new \DateTimeZone("UTC"))->sub(new \DateInterval("PT{$timeout}S"))->format("Y-m-d H:i:s");

            $nameserverLogs = $connection->query("SELECT * FROM nameserver_queue WHERE `date` > '$recently' AND ip_address = '$clientIp' ORDER BY `date` DESC;")->fetchAll();
            $webserverLogs = $connection->query("SELECT * FROM webserver_queue WHERE hostname LIKE '%$hostname' ORDER BY `date`;")->fetchAll();

            $nameserverLogs = array_merge($firstLog, $nameserverLogs);
        }

        $matchedWebserverLogs = $this->validateWebserverLogs($webserverLogs, $testType->getRules()->getWebserver());
        $matchedNameserverLogs = $this->validateNameserverLogs($nameserverLogs, $testType->getRules()->getDns());

        if ($matchedWebserverLogs && sizeof($matchedNameserverLogs) == sizeof($testType->getRules()->getDns()->getExpectedQueries())) {
            $this->writeCombinedLog($connection, $sessionKey, $testType->getType(), $matchedWebserverLogs, $matchedNameserverLogs);
        }

    }

    /**
     * @param array $logs
     * @param TestTypeWebServerRules $rules
     * @return array
     */
    public function validateWebserverLogs($logs, $rules) {

        $expectedCount = $rules->getExpectedQueries();
        if (sizeof($logs) == $expectedCount) {
            return $logs;
        } else {
            return [];
        }

    }

    /**
     * @param array $logs
     * @param TestTypeDNSRules $rules
     * @return array|bool
     */
    public function validateNameserverLogs($logs, $rules) {

        $matchedLogs = [];

        foreach ($rules->getExpectedQueries() as $expectedQuery) {
            foreach ($logs as $log) {
                if ($this->matchRecord($log, $expectedQuery)) {
                    $matchedLogs[] = $log;
                    break;
                }
            }

        }

        return $matchedLogs;
    }

    /**
     * @param array $log
     * @param TestTypeExpectedQuery $expectedQuery
     * @return bool
     */
    public function matchRecord($log, $expectedQuery) {

        $type = $expectedQuery->getType();
        $value = $expectedQuery->getValue();

        if ($type && $type != $log["record_type"]) {
            return false;
        } elseif ($value && !preg_match("/$value/", $log["hostname"])) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param SQLite3DatabaseConnection $connection
     * @param string $key
     * @param string $type
     * @param array $webserverLogs
     * @param array $nameserverLogs
     * @param string $status
     * @return void
     */
    public function writeCombinedLog($connection, $key, $type, $webserverLogs = [], $nameserverLogs = [], $status = "Success") {

        $data = ["date" => date_create()->format("Y-m-d H:i:s"), "status" => $status];

        $logFullIp = boolval($this->configService->isClientIpAddressLogging());

        for ($i = 1; $i < sizeof($nameserverLogs) + 1; $i++) {

            $ipAddress = $logFullIp ? $nameserverLogs[$i - 1]["ip_address"] : IPAddressUtils::anonymiseIP($nameserverLogs[$i - 1]["ip_address"]);

            $data["dnsResolutionTime$i"] = $nameserverLogs[$i - 1]["date"];
            $data["dnsResolvedHostname$i"] = $nameserverLogs[$i - 1]["hostname"];
            $data["dnsClientIpAddress$i"] = $ipAddress;
            $data["dnsResolverQuery$i"] = $nameserverLogs[$i - 1]["request"];
            $data["dnsResolverAnswer$i"] = null;
        }

        for ($i = 1; $i < sizeof($webserverLogs) + 1; $i++) {

            $ipAddress = $logFullIp ? $webserverLogs[$i - 1]["ip_address"] : IPAddressUtils::anonymiseIP($webserverLogs[$i - 1]["ip_address"]);

            $data["webServerRequestTime$i"] = $webserverLogs[$i - 1]["date"];
            $data["webServerRequestHostname$i"] = $webserverLogs[$i - 1]["hostname"];
            $data["webServerClientIpAddress$i"] = $ipAddress;
            $data["webServerResponseCode$i"] = $webserverLogs[$i - 1]["status_code"];
        }

        $connection->getBulkDataManager()->insert("combined_log", $data);

        /**
         * @var DAPLogger $dapLogger
         */
        $dapLogger = Container::instance()->get(DAPLogger::class);

        if ($dapLogger->hasGotCredentials()) {
            $dapLogger->writeLogToDAP($key, $type, $data);
        }

    }

    /**
     * Generate logs based on passed dates
     *
     * @param string $key
     * @param string $start
     * @param string $end
     * @param integer $limit
     * @param string $format
     * @return void
     */
    public function generateLogsByDate($key, $start, $end, $limit, $format, $stream) {

        $connection = new SQLite3DatabaseConnection([
            "filename" => Configuration::readParameter("storage.root") . "/logs/$key.db"
        ]);

        $result = $connection->query("SELECT * FROM combined_log WHERE `date` >= '{$start}' AND `date` < '{$end}' LIMIT {$limit};");
        $this->formatLogs($result, $format, $stream);

    }

    /**
     * Generate logs based on passed IDs
     *
     * @param string $key
     * @param integer $start
     * @param integer $end
     * @param integer $limit
     * @param string $format
     * @return void
     */
    public function generateLogsById($key, $start, $end, $limit, $format, $stream) {

        $connection = new SQLite3DatabaseConnection([
            "filename" => Configuration::readParameter("storage.root") . "/logs/$key.db"
        ]);

        $result = $connection->query("SELECT * FROM combined_log WHERE `id` >= '{$start}' AND `id` < '{$end}' LIMIT {$limit};");
        $this->formatLogs($result, $format, $stream);

    }

    /**
     * @param ResultSet $logs
     * @param string $format
     * @return void
     */
    private function formatLogs($logs, $format, $stream) {

        switch ($format) {
            case "jsonl":
                while ($nextLine = $logs->nextRow()) {
                    fputs($stream, json_encode($nextLine) . "\n");
                }
                break;

            case "json":
                $output = json_encode($logs->fetchAll());
                fputs($stream, $output);
                break;

            case "csv":
                while ($nextLine = $logs->nextRow()) {
                    fputcsv($stream, $nextLine);
                }
                break;
        }
    }

}