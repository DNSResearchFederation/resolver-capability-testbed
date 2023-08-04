<?php

namespace ResolverTest\Services\Logging;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\Database\Vendors\SQLite3\SQLite3DatabaseConnection;
use ResolverTest\Objects\Log\NameserverLog;
use ResolverTest\Objects\Log\WebserverLog;
use ResolverTest\Objects\Test\Test;
use ResolverTest\Services\Config\GlobalConfigService;
use ResolverTest\Services\Server\Server;
use ResolverTest\Services\TestService;
use ResolverTest\Services\TestType\TestTypeManager;
use ResolverTest\TestBase;
use ResolverTest\ValueObjects\TestType\TestType;
use ResolverTest\ValueObjects\TestType\TestTypeDNSRules;
use ResolverTest\ValueObjects\TestType\TestTypeExpectedQuery;
use ResolverTest\ValueObjects\TestType\TestTypeRules;
use ResolverTest\ValueObjects\TestType\TestTypeWebServerRules;

include_once "autoloader.php";

class LoggingServiceTest extends TestBase {

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

    /**
     * @var MockObject
     */
    private $testTypeManager;

    /**
     * @var GlobalConfigService
     */
    private $configService;

    public function setUp(): void {
        $this->server = MockObjectProvider::instance()->getMockInstance(Server::class);
        $this->testService = MockObjectProvider::instance()->getMockInstance(TestService::class);
        $this->testTypeManager = MockObjectProvider::instance()->getMockInstance(TestTypeManager::class);
        $this->loggingService = new LoggingService($this->server, $this->testService, $this->testTypeManager, $this->configService);
    }

    public function testCanCreateLoggingDatabasesForGivenTest() {

        $path = Configuration::readParameter("storage.root") . "/logs";
        if (file_exists($path . "/ourKey.db")) {
            unlink($path . "/ourKey.db");
        }

        $test = new Test("ourKey", "testType", "testing.com");

        $this->loggingService->createLogDatabaseForTest($test);

        $this->assertTrue(file_exists($path . "/ourKey.db"));

        $connection = new SQLite3DatabaseConnection(["filename" => $path . "/ourKey.db"]);
        $webserverTable = $connection->getTableMetaData("webserver_queue");
        $nameserverTable = $connection->getTableMetaData("nameserver_queue");

        $this->assertEquals(["ip_address", "user_agent", "status_code", "id", "hostname", "date"], array_keys($webserverTable->getColumns()));
        $this->assertEquals(["ip_address", "port", "request", "record_type", "flags", "id", "hostname", "date"], array_keys($nameserverTable->getColumns()));

    }

    public function testProcessLogCanSaveLogsObtainedFromServer() {

        $path = Configuration::readParameter("storage.root") . "/logs";
        if (file_exists($path . "/ourKey.db")) {
            unlink($path . "/ourKey.db");
        }

        $sampleWebserverLog = new WebserverLog("abc.test.com", date_create("2023-06-06"), "1.2.3.4", "UserAgent", 200);
        $sampleNameserverLog = new NameserverLog("abc.test.com", date_create("2023-06-07"), "1.2.3.4", 50, "test.com IN A", "A", "-E(0)D");
        $sampleTest = new Test("ourKey", "testType", "test.com");

        $sampleTestType = new TestType("testType", "", null, new TestTypeRules(new TestTypeDNSRules([new TestTypeExpectedQuery("A")]), new TestTypeWebServerRules([new TestTypeExpectedQuery()]), "hostname", null), null);

        $this->server->returnValue("processLog", $sampleWebserverLog, ["string1", Server::SERVICE_WEBSERVER]);
        $this->server->returnValue("processLog", $sampleNameserverLog, ["string2", Server::SERVICE_NAMESERVER]);
        $this->testService->returnValue("getTestByHostname", $sampleTest, ["abc.test.com"]);
        $this->testTypeManager->returnValue("getTestTypeForTest", $sampleTestType, [$sampleTest]);

        $this->loggingService->createLogDatabaseForTest($sampleTest);
        $this->loggingService->processWebserverLog("string1");
        $this->loggingService->processNameserverLog("string2");

        $connection = new SQLite3DatabaseConnection([
            "filename" => Configuration::readParameter("storage.root") . "/logs/ourKey.db"
        ]);

        $webserverResultSet = $connection->query("SELECT * FROM webserver_queue");
        $nameserverResultSet = $connection->query("SELECT * FROM nameserver_queue");

        $this->assertEquals([
            'ip_address' => '1.2.3.4',
            'user_agent' => 'UserAgent',
            'status_code' => 200,
            'id' => 1,
            'hostname' => 'abc.test.com',
            'date' => '2023-06-06 00:00:00'
        ], $webserverResultSet->nextRow());

        $this->assertNull($webserverResultSet->nextRow());


        $this->assertEquals([
            'ip_address' => '1.2.3.4',
            'port' => 50,
            'request' => 'test.com IN A',
            'record_type' => 'A',
            'flags' => '-E(0)D',
            'id' => 1,
            'hostname' => 'abc.test.com',
            'date' => '2023-06-07 00:00:00'
        ], $nameserverResultSet->nextRow());

        $this->assertNull($nameserverResultSet->nextRow());

    }

    public function testCanRetrieveLogsByDate() {

        if (file_exists(Configuration::readParameter("storage.root") . "/logs/testKey.db")) {
            unlink(Configuration::readParameter("storage.root") . "/logs/testKey.db");
        }

        $sampleConnection = new SQLite3DatabaseConnection([
            "filename" => Configuration::readParameter("storage.root") . "/logs/testKey.db"
        ]);

        $sampleConnection->query("CREATE TABLE combined_log ( id INTEGER PRIMARY KEY, `date` DATETIME, field VARCHAR(255));");

        for ($i = 10; $i < 31; $i++) {
            $date = "2023-06-$i 00:00:00";
            $sampleConnection->query("INSERT INTO combined_log (`date`, field) VALUES ('$date', 'content');");
        }

        $filename = Configuration::readParameter("storage.root") . "/testOutputLogs.txt";

        $this->loggingService->generateLogsByDate("testKey", "2023-05-31 00:00:00", "2023-07-01 00:00:00", 10000, "jsonl", fopen($filename, "w"));
        $logs = file_get_contents($filename);

        $this->assertEquals(22, sizeof(explode("\n", $logs)));
        $this->assertEquals("{\"id\":1,\"date\":\"2023-06-10 00:00:00\",\"field\":\"content\"}", explode("\n", $logs)[0]);
        $this->assertEquals("{\"id\":21,\"date\":\"2023-06-30 00:00:00\",\"field\":\"content\"}", explode("\n", $logs)[20]);


        $this->loggingService->generateLogsByDate("testKey", "2023-06-16 00:00:00", "2023-07-01 00:00:00", 2, "json", fopen($filename, "w"));
        $logs = file_get_contents($filename);
        $expectedLogs = "[{\"id\":7,\"date\":\"2023-06-16 00:00:00\",\"field\":\"content\"},{\"id\":8,\"date\":\"2023-06-17 00:00:00\",\"field\":\"content\"}]";

        $this->assertEquals($expectedLogs, $logs);

    }

    public function testCanRetrieveLogsByID() {

        if (file_exists(Configuration::readParameter("storage.root") . "/logs/testKey.db")) {
            unlink(Configuration::readParameter("storage.root") . "/logs/testKey.db");
        }

        $sampleConnection = new SQLite3DatabaseConnection([
            "filename" => Configuration::readParameter("storage.root") . "/logs/testKey.db"
        ]);

        $sampleConnection->query("CREATE TABLE combined_log ( id INTEGER PRIMARY KEY, `date` DATETIME, field VARCHAR(255));");

        for ($i = 1; $i < 31; $i++) {
            $date = "2023-06-$i 00:00:00";
            $sampleConnection->query("INSERT INTO combined_log (`date`, field) VALUES ('$date}', 'content');");
        }

        $filename = Configuration::readParameter("storage.root") . "/testOutputLogs.txt";

        $this->loggingService->generateLogsById("testKey", 0, 100, 10000, "jsonl", fopen($filename, "w"));
        $logs = file_get_contents($filename);

        $this->assertEquals(31, sizeof(explode("\n", $logs)));
        $this->assertEquals("{\"id\":1,\"date\":\"2023-06-1 00:00:00}\",\"field\":\"content\"}", explode("\n", $logs)[0]);
        $this->assertEquals("{\"id\":30,\"date\":\"2023-06-30 00:00:00}\",\"field\":\"content\"}", explode("\n", $logs)[29]);


        $this->loggingService->generateLogsById("testKey", 15, 18, 2, "json", fopen($filename, "w"));
        $logs = file_get_contents($filename);
        $expectedLogs = "[{\"id\":15,\"date\":\"2023-06-15 00:00:00}\",\"field\":\"content\"},{\"id\":16,\"date\":\"2023-06-16 00:00:00}\",\"field\":\"content\"}]";

        $this->assertEquals($expectedLogs, $logs);
    }

    public function testCanRetrieveLogsWithFormat() {

        if (file_exists(Configuration::readParameter("storage.root") . "/logs/testKey.db")) {
            unlink(Configuration::readParameter("storage.root") . "/logs/testKey.db");
        }

        $sampleConnection = new SQLite3DatabaseConnection([
            "filename" => Configuration::readParameter("storage.root") . "/logs/testKey.db"
        ]);

        $sampleConnection->query("CREATE TABLE combined_log ( id INTEGER PRIMARY KEY, `date` DATETIME, field VARCHAR(255));");

        for ($i = 1; $i < 31; $i++) {
            $date = "2023-06-$i 00:00:00";
            $sampleConnection->query("INSERT INTO combined_log (`date`, field) VALUES ('$date}', 'content');");
        }

        $filename = Configuration::readParameter("storage.root") . "/testOutputLogs.txt";

        $this->loggingService->generateLogsById("testKey", 0, 100, 10000, "csv", fopen($filename, "w"));
        $logs = file_get_contents($filename);

        $this->assertEquals(31, sizeof(explode("\n", $logs)));
        $this->assertEquals("1,\"2023-06-1 00:00:00}\",content", explode("\n", $logs)[0]);
        $this->assertEquals("30,\"2023-06-30 00:00:00}\",content", explode("\n", $logs)[29]);


        $this->loggingService->generateLogsById("testKey", 15, 18, 2, "csv", fopen($filename, "w"));
        $logs = file_get_contents($filename);
        $expectedLogs = "15,\"2023-06-15 00:00:00}\",content\n16,\"2023-06-16 00:00:00}\",content\n";

        $this->assertEquals($expectedLogs, $logs);

    }

}