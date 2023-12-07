<?php

namespace ResolverTest\Services\Logging;

use DateInterval;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Configuration\FileResolver;
use Kinikit\Core\DependencyInjection\Container;
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
        $testType = $this->testTypeManager->getTestTypeForTest($test);

        if ($testType) {
            $rules = $testType->getRules();
            $columnsClause = "id INTEGER PRIMARY KEY, `date` DATETIME, status VARCHAR(50)";

            for ($i = 1; $i <= sizeof($rules->getDns()->getExpectedQueries() ?? []); $i++) {
                $columnsClause .= ", dnsResolutionTime$i INT, dnsResolvedHostname$i VARCHAR(255), dnsClientIpAddress$i VARCHAR(255), dnsResolverQuery$i VARCHAR(255)";
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

        foreach ($testType->getRules()->getDns()->getExpectedQueries() as $expectedQuery) {
            if ($log->getRecordType() == $expectedQuery->getType()) {

                if ($expectedQuery->getValue() && preg_match("/{$expectedQuery->getValue()}/", $log->getHostname()) == 1) {
                    $this->saveLog($log, $test->getKey());
                    return;
                }

                if ($expectedQuery->getPrefix() && preg_match("/{$expectedQuery->getPrefix()}{$test->getDomainName()}$/", $log->getHostname()) == 1) {
                    $this->saveLog($log, $test->getKey());
                    return;
                }

            }
        }
    }

    /**
     * Save nameserver log into the database
     *
     * @param BaseLog $log
     * @param string $key
     * @return void
     */
    public function saveLog($log, $key) {
        $connection = new SQLite3DatabaseConnection([
            "filename" => Configuration::readParameter("storage.root") . "/logs/$key.db"
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

        // Validate log entry is one we care about
        if (sizeof(explode(".", $log->getHostname())) < 3) {
            return;
        }

        // Save into database
        $this->saveLog($log, $test->getKey());

    }

    /**
     * Analyse webserver/nameserver logs and write a combined entry if test criteria is met
     *
     * @return void
     */
    public function compareLogs() {
        foreach ($this->testService->listTests() as $test) {
            $this->compareLogsForTest($test);
        }
    }

    /**
     * @param Test $test
     * @return void
     */
    public function compareLogsForTest($test) {

        $connection = new SQLite3DatabaseConnection([
            "filename" => Configuration::readParameter("storage.root") . "/logs/{$test->getKey()}.db"
        ]);

        $testType = $this->testTypeManager->getTestTypeForTest($test);
        $expectedNSQueries = sizeof($testType->getRules()->getDns()->getExpectedQueries());
        $timeoutSeconds = $testType->getRules()->getTimeoutSeconds();
        $pointOfQuery = date_create();

        switch ($testType->getRules()->getRelationalKey()) {
            case TestTypeRules::RELATIONAL_KEY_HOSTNAME:

                $twoMinsAgo = date_create()->sub(new DateInterval("PT2M"))->format("Y-m-d H:i:s");
                $uniqueUUIDS = $connection->query("SELECT DISTINCT SUBSTR(hostname, 0, 37) `uuid` FROM nameserver_queue WHERE `date` > '{$twoMinsAgo}';")->fetchAll();

                foreach ($uniqueUUIDS as $UUID) {
                    $UUID = $UUID["uuid"];

                    // Has it been dealt with?
                    for ($i = 1; $i < $expectedNSQueries + 1; $i++) {
                        if ($connection->query("SELECT * FROM combined_log WHERE `dnsResolvedHostname$i` LIKE '$UUID%'")->fetchAll()) {
                            continue 2;
                        }
                    }

                    // Get the matching logs
                    $matchingLogs = $connection->query("SELECT * FROM nameserver_queue WHERE `hostname` LIKE '$UUID%' ORDER BY `date`")->fetchAll();

                    // Check if not timed out
                    if (date_create($matchingLogs[0]["date"])->add(new DateInterval("PT{$timeoutSeconds}S")) > $pointOfQuery) {
                        continue;
                    }

                    // Run the validation
                    $validation = $this->validateNameserverLogs($matchingLogs, $testType->getRules()->getDns());

                    $status = $validation[1] ? "Success" : "Failed";

                    // ToDo: Call validateWebserverLogs() on matching logs, and add to write combined

                    $this->writeCombinedLog($connection, $test->getKey(), $testType->getType(), [
                        ["hostname" => "", "date" => date("Y-m-d H:i:s"), "ip_address" => "192.0.2.2", "user_agent" => "", "status_code" => 200]
                    ], $validation[0], $status);
                }
                break;

            case TestTypeRules::RELATIONAL_KEY_IP_ADDRESS:

                $twoMinsAgo = date_create()->sub(new DateInterval("PT2M"))->format("Y-m-d H:i:s");
                $uniqueIPs = $connection->query("SELECT DISTINCT ip_address FROM nameserver_queue WHERE `date` > '{$twoMinsAgo}';")->fetchAll();

                foreach ($uniqueIPs as $ipAddress) {

                    // Has it been dealt with?
                    for ($i = 1; $i < $expectedNSQueries + 1; $i++) {
                        // Limit time?
                        if ($connection->query("SELECT * FROM combined_log WHERE `dnsResolvedIPAddress$i` = '$ipAddress'")->fetchAll()) {
                            continue 2;
                        }
                    }

                    // Get the matching logs
                    $matchingLogs = $connection->query("SELECT * FROM nameserver_queue WHERE 'ip_address' = '$ipAddress' ORDER BY `date`")->fetchAll();

                    // Check if not timed out
                    if (date_create($matchingLogs[0]["date"])->add(new DateInterval("PT{$timeoutSeconds}S")) > $pointOfQuery) {
                        continue;
                    }

                    // Run the validation
                    $validation = $this->validateNameserverLogs($matchingLogs, $testType->getRules()->getDns());

                    $status = $validation[1] ? "Success" : "Failed";

                    // ToDo: Call validateWebserverLogs() on matching logs, and add to write combined

                    $this->writeCombinedLog($connection, $test->getKey(), $testType->getType(), [
                        ["hostname" => "", "date" => date("Y-m-d H:i:s"), "ip_address" => "192.0.2.2", "user_agent" => "", "status_code" => 200]
                    ], $validation[0], $status);
                }
                break;

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
        $ipAddress = $logs[0]["ip_address"];
        $passed = true;

        foreach ($rules->getExpectedQueries() as $expectedQuery) {
            $matched = false;
            foreach ($logs as $key => $log) {
                if ($log["ip_address"] != $ipAddress) {
                    unset($logs[$key]);
                    continue;
                }

                if ($this->matchRecord($log, $expectedQuery)) {

                    unset($logs[$key]);
                    $matchedLogs[] = $log;

                    if ($expectedQuery->isAbsent()) {
                        $passed = false;
                    }

                    $matched = true;
                    break;
                }
            }

            if (!$matched && !$expectedQuery->isAbsent()) {
                $matchedLogs[] = ["ip_address" => null, "port" => null, "request" => null, "record_type" => null, "flags" => null, "id" => null, "hostname" => null, "date" => null];
                $passed = false;
            }

        }

        return [$matchedLogs, $passed];

    }

    /**
     * @param array $log
     * @param TestTypeExpectedQuery $expectedQuery
     * @return bool
     */
    public function matchRecord($log, $expectedQuery) {

        $type = $expectedQuery->getType();
        $value = $expectedQuery->getValue();
        $prefix = $expectedQuery->getPrefix();

        if ($type && $type != $log["record_type"])
            return false;
        if ($value && !preg_match("/$value/", $log["hostname"]))
            return false;
        if ($prefix && !preg_match("/$prefix/", $log["hostname"]))
            return false;

        return true;

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

        $data = ["date" => date_create("now", new \DateTimeZone("UTC"))->format("Y-m-d H:i:s"), "status" => $status];

        $logFullIp = boolval($this->configService->isClientIpAddressLogging());

        for ($i = 1; $i < sizeof($nameserverLogs) + 1; $i++) {

            $ipAddress = $logFullIp ? $nameserverLogs[$i - 1]["ip_address"] : IPAddressUtils::anonymiseIP($nameserverLogs[$i - 1]["ip_address"]);

            $data["dnsResolutionTime$i"] = $nameserverLogs[$i - 1]["date"];
            $data["dnsResolvedHostname$i"] = $nameserverLogs[$i - 1]["hostname"];
            $data["dnsClientIpAddress$i"] = $ipAddress;
            $data["dnsResolverQuery$i"] = $nameserverLogs[$i - 1]["request"];
        }

        for ($i = 1; $i < sizeof($webserverLogs) + 1; $i++) {

            $ipAddress = $logFullIp ? $webserverLogs[$i - 1]["ip_address"] : IPAddressUtils::anonymiseIP($webserverLogs[$i - 1]["ip_address"]);

            $data["webServerRequestTime$i"] = $webserverLogs[$i - 1]["date"];
            $data["webServerRequestHostname$i"] = $webserverLogs[$i - 1]["hostname"];
            $data["webServerClientIpAddress$i"] = $ipAddress;
            $data["webServerResponseCode$i"] = $webserverLogs[$i - 1]["status_code"];
        }

        $connection->getBulkDataManager()->insert("combined_log", $data);
        $data["id"] = $connection->getLastAutoIncrementId();

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