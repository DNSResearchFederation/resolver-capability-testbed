<?php

namespace Services\Logging;

use Kinikit\Core\Binding\ObjectBinder;
use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinikit\Persistence\Database\Vendors\SQLite3\SQLite3DatabaseConnection;
use ResolverTest\Objects\Log\NameserverLog;
use ResolverTest\Objects\Log\WebserverLog;
use ResolverTest\Objects\Test\Test;
use ResolverTest\Services\Config\GlobalConfigService;
use ResolverTest\Services\Logging\LoggingService;
use ResolverTest\Services\Server\Server;
use ResolverTest\Services\TestService;
use ResolverTest\Services\TestType\TestTypeManager;
use TestBase;
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

    /**
     * @var ObjectBinder
     */
    private $objectBinder;

    public function setUp(): void {
        $this->server = MockObjectProvider::instance()->getMockInstance(Server::class);
        $this->testService = MockObjectProvider::instance()->getMockInstance(TestService::class);
        $this->testTypeManager = MockObjectProvider::instance()->getMockInstance(TestTypeManager::class);
        $this->configService = MockObjectProvider::instance()->getMockInstance(GlobalConfigService::class);
        $this->loggingService = new LoggingService($this->server, $this->testService, $this->testTypeManager, $this->configService);
        $this->objectBinder = Container::instance()->get(ObjectBinder::class);
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

    public function testProcessLogCanSaveLogsObtainedFromNameServer() {

        $path = Configuration::readParameter("storage.root") . "/logs";
        if (file_exists($path . "/ourKey.db")) {
            unlink($path . "/ourKey.db");
        }

        $sampleNameserverLog1 = new NameserverLog("12345.test.com", date_create("2023-06-07"), "1.2.3.4", 50, "12345.test.com IN A", "A", "-E(0)D");
        $sampleNameserverLog2 = new NameserverLog("56789.test.com", date_create("2023-06-07"), "1.2.3.4", 50, "56789.test.com IN AAAA", "AAAA", "-E(0)D");
        $sampleNameserverLog3 = new NameserverLog("76543.prefix-test.com", date_create("2023-06-07"), "1.2.3.4", 50, "76543.prefix-test.com IN A", "A", "-E(0)D");
        $sampleNameserverLog4 = new NameserverLog("01234.subdomain.test.com", date_create("2023-06-07"), "5.6.7.8", 50, "01234.subdomain.test.com IN A", "A", "-E(0)D");
        $sampleNameserverLog5 = new NameserverLog("67890.subdomain.test.com", date_create("2023-06-07"), "5.6.7.8", 50, "67890.subdomain.test.com IN AAAA", "AAAA", "-E(0)D");

        $sampleTest = new Test("ourKey", "testType", "test.com");

        $sampleTestType = new TestType("testType", "", null, new TestTypeRules(new TestTypeDNSRules([
            new TestTypeExpectedQuery("A", "(?i)^[0-9]+\\.[a-z]+\\.[a-z]+$"),
            new TestTypeExpectedQuery("A", null, "prefix-"),
            new TestTypeExpectedQuery("AAAA", "(?i)^[0-9]+\\.subdomain\\.[a-z]+\\.[a-z]+$")
        ]), new TestTypeWebServerRules(1), false, "HOSTNAME", null));

        $this->server->returnValue("processLog", $sampleNameserverLog1, ["logString1", Server::SERVICE_NAMESERVER]);
        $this->server->returnValue("processLog", $sampleNameserverLog2, ["logString2", Server::SERVICE_NAMESERVER]);
        $this->server->returnValue("processLog", $sampleNameserverLog3, ["logString3", Server::SERVICE_NAMESERVER]);
        $this->server->returnValue("processLog", $sampleNameserverLog4, ["logString4", Server::SERVICE_NAMESERVER]);
        $this->server->returnValue("processLog", $sampleNameserverLog5, ["logString5", Server::SERVICE_NAMESERVER]);

        $this->testService->returnValue("getTestByHostname", $sampleTest, ["12345.test.com"]);
        $this->testService->returnValue("getTestByHostname", $sampleTest, ["56789.test.com"]);
        $this->testService->returnValue("getTestByHostname", $sampleTest, ["76543.prefix-test.com"]);
        $this->testService->returnValue("getTestByHostname", $sampleTest, ["01234.subdomain.test.com"]);
        $this->testService->returnValue("getTestByHostname", $sampleTest, ["67890.subdomain.test.com"]);

        $this->testTypeManager->returnValue("getTestTypeForTest", $sampleTestType, [$sampleTest]);

        $this->loggingService->createLogDatabaseForTest($sampleTest);
        $this->loggingService->processNameserverLog("logString1");
        $this->loggingService->processNameserverLog("logString2");
        $this->loggingService->processNameserverLog("logString3");
        $this->loggingService->processNameserverLog("logString4");
        $this->loggingService->processNameserverLog("logString5");

        $connection = new SQLite3DatabaseConnection([
            "filename" => Configuration::readParameter("storage.root") . "/logs/ourKey.db"
        ]);

        $nameserverResultSet = $connection->query("SELECT * FROM nameserver_queue");

        $this->assertEquals([
            'ip_address' => '1.2.3.4',
            'port' => 50,
            'request' => '12345.test.com IN A',
            'record_type' => 'A',
            'flags' => '-E(0)D',
            'id' => 1,
            'hostname' => '12345.test.com',
            'date' => '2023-06-07 00:00:00'
        ], $nameserverResultSet->nextRow());

        $this->assertEquals([
            'ip_address' => '1.2.3.4',
            'port' => 50,
            'request' => '76543.prefix-test.com IN A',
            'record_type' => 'A',
            'flags' => '-E(0)D',
            'id' => 2,
            'hostname' => '76543.prefix-test.com',
            'date' => '2023-06-07 00:00:00'
        ], $nameserverResultSet->nextRow());

        $this->assertEquals([
            'ip_address' => '5.6.7.8',
            'port' => 50,
            'request' => '67890.subdomain.test.com IN AAAA',
            'record_type' => 'AAAA',
            'flags' => '-E(0)D',
            'id' => 3,
            'hostname' => '67890.subdomain.test.com',
            'date' => '2023-06-07 00:00:00'
        ], $nameserverResultSet->nextRow());

        $this->assertNull($nameserverResultSet->nextRow());

    }

    public function testProcessLogCanSaveLogsObtainedFromWebServer() {

        $path = Configuration::readParameter("storage.root") . "/logs";
        if (file_exists($path . "/ourKey.db")) {
            unlink($path . "/ourKey.db");
        }

        $sampleWebserverLog = new WebserverLog("abc.test.com", date_create("2023-06-06"), "1.2.3.4", "UserAgent", 200);
        $sampleTest = new Test("ourKey", "testType", "test.com");

        $sampleTestType = new TestType("testType", "", null, new TestTypeRules(new TestTypeDNSRules([new TestTypeExpectedQuery("A")]), new TestTypeWebServerRules(1), false, "HOSTNAME", null));

        $this->server->returnValue("processLog", $sampleWebserverLog, ["string1", Server::SERVICE_WEBSERVER]);
        $this->testService->returnValue("getTestByHostname", $sampleTest, ["abc.test.com"]);
        $this->testTypeManager->returnValue("getTestTypeForTest", $sampleTestType, [$sampleTest]);

        $this->loggingService->createLogDatabaseForTest($sampleTest);
        $this->loggingService->processWebserverLog("string1");

        $connection = new SQLite3DatabaseConnection([
            "filename" => Configuration::readParameter("storage.root") . "/logs/ourKey.db"
        ]);

        $webserverResultSet = $connection->query("SELECT * FROM webserver_queue");

        $this->assertEquals([
            'ip_address' => '1.2.3.4',
            'user_agent' => 'UserAgent',
            'status_code' => 200,
            'id' => 1,
            'hostname' => 'abc.test.com',
            'date' => '2023-06-06 00:00:00'
        ], $webserverResultSet->nextRow());

        $this->assertNull($webserverResultSet->nextRow());

    }

    public function testCanValidateNameserverLogsCorrectly() {

        $logs = [
            ["hostname" => "123456789.test.com", "date" => "", "ip_address" => "", "port" => "", "request" => "", "record_type" => "A", "flags" => ""],
            ["hostname" => "123456789.test.com", "date" => "", "ip_address" => "", "port" => "", "request" => "", "record_type" => "AAAA", "flags" => ""],
            ["hostname" => "123456789.other-test.com", "date" => "", "ip_address" => "", "port" => "", "request" => "", "record_type" => "AAAA", "flags" => ""],
            ["hostname" => "123456789.test.com", "date" => "", "ip_address" => "", "port" => "", "request" => "", "record_type" => "A", "flags" => ""],
            ["hostname" => "123456789.other-test.com", "date" => "", "ip_address" => "", "port" => "", "request" => "", "record_type" => "A", "flags" => ""]
        ];

        $testRules = new TestTypeDNSRules([
            new TestTypeExpectedQuery("A"),
            new TestTypeExpectedQuery("A", null, "other-"),
            new TestTypeExpectedQuery("AAAA")
        ]);

        $matchedLogs = $this->loggingService->validateNameserverLogs($logs, $testRules);
        $expectedMatches = [
            ["hostname" => "123456789.test.com", "date" => "", "ip_address" => "", "port" => "", "request" => "", "record_type" => "A", "flags" => ""],
            ["hostname" => "123456789.other-test.com", "date" => "", "ip_address" => "", "port" => "", "request" => "", "record_type" => "A", "flags" => ""],
            ["hostname" => "123456789.test.com", "date" => "", "ip_address" => "", "port" => "", "request" => "", "record_type" => "AAAA", "flags" => ""],
        ];

        $this->assertEquals($expectedMatches, $matchedLogs[0]);
        $this->assertTrue($matchedLogs[1]);
    }

    public function testCanValidateNameserverLogsCorrectlyWhenAbsentUsed() {

        $logs = [
            ["hostname" => "123456789.test.com", "date" => "", "ip_address" => "", "port" => "", "request" => "", "record_type" => "A", "flags" => ""],
            ["hostname" => "123456789.test.com", "date" => "", "ip_address" => "", "port" => "", "request" => "", "record_type" => "AAAA", "flags" => ""],
            ["hostname" => "123456789.other-test.com", "date" => "", "ip_address" => "", "port" => "", "request" => "", "record_type" => "AAAA", "flags" => ""],
            ["hostname" => "123456789.test.com", "date" => "", "ip_address" => "", "port" => "", "request" => "", "record_type" => "A", "flags" => ""],
            ["hostname" => "123456789.other-test.com", "date" => "", "ip_address" => "", "port" => "", "request" => "", "record_type" => "A", "flags" => ""]
        ];

        $testRules = new TestTypeDNSRules([
            new TestTypeExpectedQuery("A"),
            new TestTypeExpectedQuery("A", null, "other-"),
            new TestTypeExpectedQuery("AAAA"),
            new TestTypeExpectedQuery("AAAA", null, "other-", true)
        ]);

        $matchedLogs = $this->loggingService->validateNameserverLogs($logs, $testRules);
        $expectedMatches = [
            ["hostname" => "123456789.test.com", "date" => "", "ip_address" => "", "port" => "", "request" => "", "record_type" => "A", "flags" => ""],
            ["hostname" => "123456789.other-test.com", "date" => "", "ip_address" => "", "port" => "", "request" => "", "record_type" => "A", "flags" => ""],
            ["hostname" => "123456789.test.com", "date" => "", "ip_address" => "", "port" => "", "request" => "", "record_type" => "AAAA", "flags" => ""],
            ["hostname" => "123456789.other-test.com", "date" => "", "ip_address" => "", "port" => "", "request" => "", "record_type" => "AAAA", "flags" => ""]
        ];

        $this->assertEquals($expectedMatches, $matchedLogs[0]);
        $this->assertFalse($matchedLogs[1]);

    }

    public function testCanValidateNameserverLogsCorrectlyWhenInsufficientLogs() {

        $logs = [
            ["hostname" => "123456789.test.com", "date" => "", "ip_address" => "", "port" => "", "request" => "", "record_type" => "A", "flags" => ""],
            ["hostname" => "123456789.test.com", "date" => "", "ip_address" => "", "port" => "", "request" => "", "record_type" => "AAAA", "flags" => ""],
        ];

        $testRules = new TestTypeDNSRules([
            new TestTypeExpectedQuery("A"),
            new TestTypeExpectedQuery("A")
        ]);

        $matchedLogs = $this->loggingService->validateNameserverLogs($logs, $testRules);
        $expectedMatches = [
            ["hostname" => "123456789.test.com", "date" => "", "ip_address" => "", "port" => "", "request" => "", "record_type" => "A", "flags" => ""],
            ["hostname" => null, "date" => null, "ip_address" => null, "port" => null, "request" => null, "record_type" => null, "flags" => null, "id" => null],
        ];

        $this->assertEquals($expectedMatches, $matchedLogs[0]);
        $this->assertFalse($matchedLogs[1]);

    }

    public function testCanValidateNameserverLogsWhenMultpleIpAddressForSameUUID() {

        $logs = [
            ["hostname" => "123456789.test.com", "date" => "", "ip_address" => "192.0.2.0", "port" => "", "request" => "", "record_type" => "A", "flags" => ""],
            ["hostname" => "123456789.test.com", "date" => "", "ip_address" => "192.0.2.2", "port" => "", "request" => "", "record_type" => "A", "flags" => ""],
            ["hostname" => "123456789.test.com", "date" => "", "ip_address" => "192.0.2.0", "port" => "", "request" => "", "record_type" => "A", "flags" => ""],
        ];

        $testRules = new TestTypeDNSRules([
            new TestTypeExpectedQuery("A"),
            new TestTypeExpectedQuery("A")
        ]);

        $matchedLogs = $this->loggingService->validateNameserverLogs($logs, $testRules);
        $expectedMatches = [
            ["hostname" => "123456789.test.com", "date" => "", "ip_address" => "192.0.2.0", "port" => "", "request" => "", "record_type" => "A", "flags" => ""],
            ["hostname" => "123456789.test.com", "date" => "", "ip_address" => "192.0.2.0", "port" => "", "request" => "", "record_type" => "A", "flags" => ""],
        ];

        $this->assertEquals($expectedMatches, $matchedLogs[0]);
        $this->assertTrue($matchedLogs[1]);

    }

    public function testCanCompareLogsCorrectly() {

        if (file_exists(Configuration::readParameter("storage.root") . "/logs/compareTest.db")) {
            unlink(Configuration::readParameter("storage.root") . "/logs/compareTest.db");
        }

        $connection = new SQLite3DatabaseConnection([
            "filename" => Configuration::readParameter("storage.root") . "/logs/compareTest.db"
        ]);

        $now = date("Y-m-d H:i:s");
        $oneSecAgo = date_create()->sub(new \DateInterval("PT1S"));
        $twoSecAgo = date_create()->sub(new \DateInterval("PT2S"));
        $fiveSecAgo = date_create()->sub(new \DateInterval("PT5S"));
        $tenSecAgo = date_create()->sub(new \DateInterval("PT10S"));

        // Create test data
        $nameServerLogs = [
            new NameserverLog("16ba5fa0-7ffd-499a-9832-9770f80e4c30.test.com", $tenSecAgo, "192.0.2.0", 22813, "A IN 16ba5fa0-7ffd-499a-9832-9770f80e4c30.test.com", "A", ""),         // A - timed out, expected query
            new NameserverLog("16ba5fa0-7ffd-499a-9832-9770f80e4c30.test.com", $fiveSecAgo, "192.0.2.0", 22813, "AAAA IN 16ba5fa0-7ffd-499a-9832-9770f80e4c30.test.com", "AAAA", ""),  // A - other expected query
            new NameserverLog("16ba5fa0-7ffd-499a-9832-9770f80e4c30.test.com", $tenSecAgo, "192.0.2.1", 22814, "A IN 16ba5fa0-7ffd-499a-9832-9770f80e4c30.test.com", "A", ""),         // B - same as A, but different IP
            new NameserverLog("16ba5fa0-7ffd-499a-9832-9770f80e4c30.test.com", $fiveSecAgo, "192.0.2.1", 22814, "AAAA IN 16ba5fa0-7ffd-499a-9832-9770f80e4c30.test.com", "AAAA", ""),  // B - same as A, but different IP
            new NameserverLog("08dd00f2-7ead-474d-9008-aca9c51d4071.test.com", $tenSecAgo, "192.0.2.8", 22815, "A IN 08dd00f2-7ead-474d-9008-aca9c51d4071.test.com", "A", ""),        // C - timed out, expected query
            new NameserverLog("08dd00f2-7ead-474d-9008-aca9c51d4071.test.com", $twoSecAgo, "192.0.2.8", 22815, "A IN 08dd00f2-7ead-474d-9008-aca9c51d4071.test.com", "A", ""),         // C - should be absent
            new NameserverLog("08dd00f2-7ead-474d-9008-aca9c51d4071.test.com", $oneSecAgo, "192.0.2.8", 22815, "AAAA IN 08dd00f2-7ead-474d-9008-aca9c51d4071.test.com", "AAAA", ""),   // C - expected query
            new NameserverLog("9a2cb532-f7e6-443a-b8f3-e9c688bc090b.test.com", $tenSecAgo, "192.0.2.10", 22816, "A IN 9a2cb532-f7e6-443a-b8f3-e9c688bc090b.test.com", "A", ""),        // D - timed out, expected query
            new NameserverLog("8e1e4931-173c-4e3e-987e-4f893fb4b982.test.com", $oneSecAgo, "192.0.2.12", 22817, "A IN 8e1e4931-173c-4e3e-987e-4f893fb4b982.test.com", "A", ""),        // E - not yet timed out
        ];

        // ToDo: Make some corresponding webserver logs and add to assertions
        $webServerLogs = [

        ];

        // Mockery
        $test = MockObjectProvider::instance()->getMockInstance(Test::class);
        $testType = MockObjectProvider::instance()->getMockInstance(TestType::class);
        $this->configService->returnValue("isClientIpAddressLogging", true, []);

        // Rules for the logging
        $testTypeRules = new TestTypeRules(new TestTypeDNSRules([
            new TestTypeExpectedQuery("A", "[a-z0-9\-]\.test.com"),
            new TestTypeExpectedQuery("AAAA", "[a-z0-9\-]\.test.com"),
            new TestTypeExpectedQuery("A", "[a-z0-9\-]\.test.com", null, true)
        ]), new TestTypeWebServerRules(2), true, TestTypeRules::RELATIONAL_KEY_HOSTNAME, 3);

        $this->testTypeManager->returnValue("getTestTypeForTest", $testType, [$test]);
        $test->returnValue("getKey", "compareTest", []);
        $testType->returnValue("getRules", $testTypeRules, []);

        // Create DB and insert test data
        $this->loggingService->createLogDatabaseForTest($test);

        foreach ($nameServerLogs as $nameServerLog) {
            $this->loggingService->saveLog($nameServerLog, "compareTest");
        }
        foreach ($webServerLogs as $webServerLog) {
            $this->loggingService->saveLog($webServerLog, "compareTest");
        }
        $this->loggingService->compareLogsForTest($test);

        $outputLogs = $connection->query("SELECT * FROM combined_log;");

        $this->assertEquals([
            'id' => 1,
            'date' => $now,
            'status' => 'Success',
            'dnsResolutionTime1' => $tenSecAgo->format("Y-m-d H:i:s"),
            'dnsResolvedHostname1' => '16ba5fa0-7ffd-499a-9832-9770f80e4c30.test.com',
            'dnsClientIpAddress1' => '192.0.2.0',
            'dnsResolverQuery1' => 'A IN 16ba5fa0-7ffd-499a-9832-9770f80e4c30.test.com',
            'dnsResolutionTime2' => $fiveSecAgo->format("Y-m-d H:i:s"),
            'dnsResolvedHostname2' => '16ba5fa0-7ffd-499a-9832-9770f80e4c30.test.com',
            'dnsClientIpAddress2' => '192.0.2.0',
            'dnsResolverQuery2' => 'AAAA IN 16ba5fa0-7ffd-499a-9832-9770f80e4c30.test.com',
            'dnsResolutionTime3' => null,
            'dnsResolvedHostname3' => null,
            'dnsClientIpAddress3' => null,
            'dnsResolverQuery3' => null,
            'webServerRequestTime1' => $now,
            'webServerRequestHostname1' => '',
            'webServerClientIpAddress1' => '192.0.2.2',
            'webServerResponseCode1' => 200,
            'webServerRequestTime2' => null,
            'webServerRequestHostname2' => null,
            'webServerClientIpAddress2' => null,
            'webServerResponseCode2' => null
        ], $outputLogs->nextRow());

        $this->assertEquals([
            'id' => 2,
            'date' => $now,
            'status' => 'Failed',
            'dnsResolutionTime1' => $tenSecAgo->format("Y-m-d H:i:s"),
            'dnsResolvedHostname1' => '08dd00f2-7ead-474d-9008-aca9c51d4071.test.com',
            'dnsClientIpAddress1' => '192.0.2.8',
            'dnsResolverQuery1' => 'A IN 08dd00f2-7ead-474d-9008-aca9c51d4071.test.com',
            'dnsResolutionTime2' => $oneSecAgo->format("Y-m-d H:i:s"),
            'dnsResolvedHostname2' => '08dd00f2-7ead-474d-9008-aca9c51d4071.test.com',
            'dnsClientIpAddress2' => '192.0.2.8',
            'dnsResolverQuery2' => 'AAAA IN 08dd00f2-7ead-474d-9008-aca9c51d4071.test.com',
            'dnsResolutionTime3' => $twoSecAgo->format("Y-m-d H:i:s"),
            'dnsResolvedHostname3' => "08dd00f2-7ead-474d-9008-aca9c51d4071.test.com",
            'dnsClientIpAddress3' => "192.0.2.8",
            'dnsResolverQuery3' => "A IN 08dd00f2-7ead-474d-9008-aca9c51d4071.test.com",
            'webServerRequestTime1' => $now,
            'webServerRequestHostname1' => '',
            'webServerClientIpAddress1' => '192.0.2.2',
            'webServerResponseCode1' => 200,
            'webServerRequestTime2' => null,
            'webServerRequestHostname2' => null,
            'webServerClientIpAddress2' => null,
            'webServerResponseCode2' => null
        ], $outputLogs->nextRow());

        $this->assertEquals([
            'id' => 3,
            'date' => $now,
            'status' => 'Failed',
            'dnsResolutionTime1' => $tenSecAgo->format("Y-m-d H:i:s"),
            'dnsResolvedHostname1' => '9a2cb532-f7e6-443a-b8f3-e9c688bc090b.test.com',
            'dnsClientIpAddress1' => '192.0.2.10',
            'dnsResolverQuery1' => 'A IN 9a2cb532-f7e6-443a-b8f3-e9c688bc090b.test.com',
            'dnsResolutionTime2' => null,
            'dnsResolvedHostname2' => null,
            'dnsClientIpAddress2' => null,
            'dnsResolverQuery2' => null,
            'dnsResolutionTime3' => null,
            'dnsResolvedHostname3' => null,
            'dnsClientIpAddress3' => null,
            'dnsResolverQuery3' => null,
            'webServerRequestTime1' => $now,
            'webServerRequestHostname1' => '',
            'webServerClientIpAddress1' => '192.0.2.2',
            'webServerResponseCode1' => 200,
            'webServerRequestTime2' => null,
            'webServerRequestHostname2' => null,
            'webServerClientIpAddress2' => null,
            'webServerResponseCode2' => null
        ], $outputLogs->nextRow());

        $this->assertNull($outputLogs->nextRow());
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