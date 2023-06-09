<?php

namespace ResolverTest\Services\Logging;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\Database\Vendors\SQLite3\SQLite3DatabaseConnection;
use PHPUnit\Framework\TestCase;
use ResolverTest\Objects\Log\WebserverLog;
use ResolverTest\Objects\Test\Test;
use ResolverTest\Services\Server\Server;
use ResolverTest\Services\TestService;

include_once "autoloader.php";

class LoggingServiceTest extends TestCase {

    /**
     * @var LoggingService
     */
    private $loggingService;

    /**
     * @var MockObject
     */
    private $server;

    /**
     * @var MockObject
     */
    private $testService;

    public function setUp(): void {
        $this->server = MockObjectProvider::instance()->getMockInstance(Server::class);
        $this->testService = MockObjectProvider::instance()->getMockInstance(TestService::class);
        $this->loggingService = new LoggingService($this->server, $this->testService);
    }

    public function testCanCreateLoggingDatabaseForGivenTest() {

        $path = Configuration::readParameter("storage.root") . "/logs";
        if (file_exists($path . "/ourKey.db")) {
            unlink($path . "/ourKey.db");
        }

        $test = new Test("ourKey", "testType", "testing.com");

        $this->loggingService->createLogDatabaseForTest($test);

        $this->assertTrue(file_exists($path . "/ourKey.db"));

        $connection = new SQLite3DatabaseConnection(["filename" => $path . "/ourKey.db"]);
        $webserverTable = $connection->getTableMetaData("webserver_log");
        $nameserverTable = $connection->getTableMetaData("nameserver_log");

        $this->assertEquals(["ip_address", "user_agent", "id", "hostname", "date"], array_keys($webserverTable->getColumns()));
        $this->assertEquals(["ip_address", "id", "hostname", "date"], array_keys($nameserverTable->getColumns()));

    }

    public function testProcessLogCanSaveLogsObtainedFromServer() {

        $path = Configuration::readParameter("storage.root") . "/logs";
        if (file_exists($path . "/ourKey.db")) {
            unlink($path . "/ourKey.db");
        }

        $sampleLog = new WebserverLog("test.com", date_create("2023-06-06"), "1.2.3.4", "UserAgent");
        $sampleTest = new Test("ourKey", "testType", "test.com");

        $this->server->returnValue("processLog", $sampleLog, ["string", "service"]);
        $this->testService->returnValue("getTestByHostname", $sampleTest, ["test.com"]);

        $this->loggingService->createLogDatabaseForTest($sampleTest);
        $this->loggingService->processLog("string", "service");

        $connection = new SQLite3DatabaseConnection([
            "filename" => Configuration::readParameter("storage.root") . "/logs/ourKey.db"
        ]);
        $this->assertEquals([
            'ip_address' => '1.2.3.4',
            'user_agent' => 'UserAgent',
            'id' => 1,
            'hostname' => 'test.com',
            'date' => '2023-06-06 00:00:00'
        ], $connection->query("SELECT * FROM webserver_log")->nextRow());

    }

}