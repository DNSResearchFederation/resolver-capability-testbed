<?php

namespace Services\TestManager;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Serialisation\JSON\JSONToObjectConverter;
use Kinikit\Core\Serialisation\JSON\ObjectToJSONConverter;
use PHPUnit\Framework\TestCase;
use ResolverTest\Objects\Server\ServerOperation;
use ResolverTest\Objects\Test\Test;
use ResolverTest\Services\TestType\TestTypeManager;
use ResolverTest\ValueObjects\TestType\Config\DNSRecord;
use ResolverTest\ValueObjects\TestType\Config\DNSZone;
use ResolverTest\ValueObjects\TestType\Config\WebServerVirtualHost;
use ResolverTest\ValueObjects\TestType\TestType;
use ResolverTest\ValueObjects\TestType\TestTypeConfig;
use ResolverTest\ValueObjects\TestType\TestTypeDNSRules;
use ResolverTest\ValueObjects\TestType\TestTypeExpectedQuery;
use ResolverTest\ValueObjects\TestType\TestTypeRules;
use ResolverTest\ValueObjects\TestType\TestTypeWebServerRules;

include_once "autoloader.php";

class TestTypeManagerTest extends TestCase {

    /**
     * @var JSONToObjectConverter
     */
    private $jsonToObjectConverter;

    /**
     * @var ObjectToJSONConverter
     */
    private $objectToJSONConverter;

    public function setUp(): void {
        $this->jsonToObjectConverter = Container::instance()->get(JSONToObjectConverter::class);
        $this->objectToJSONConverter = Container::instance()->get(ObjectToJSONConverter::class);
    }

    public function testDoesCorrectlyListAllAvailableTests() {

        if (file_exists(Configuration::readParameter("config.root") . "/resolvertest/custom.json")) {
            unlink(Configuration::readParameter("config.root") . "/resolvertest/custom.json");
        }

        // Test defaults are read
        $testTypeManager1 = new TestTypeManager();
        $expected = [
            "dnssec" => $this->jsonToObjectConverter->convert(file_get_contents(__DIR__ . "/../../../src/Config/templates/test-type/dnssec.json"), TestType::class),
            "ipv6" => $this->jsonToObjectConverter->convert(file_get_contents(__DIR__ . "/../../../src/Config/templates/test-type/ipv6.json"), TestType::class),
            "qname-minimisation" => $this->jsonToObjectConverter->convert(file_get_contents(__DIR__ . "/../../../src/Config/templates/test-type/qname.json"), TestType::class),
            "minimum-ttl" => $this->jsonToObjectConverter->convert(file_get_contents(__DIR__ . "/../../../src/Config/templates/test-type/minimum-ttl.json"), TestType::class),
            "nsec" => $this->jsonToObjectConverter->convert(file_get_contents(__DIR__ . "/../../../src/Config/templates/test-type/nsec.json"), TestType::class),
            "tcp-fallback" => $this->jsonToObjectConverter->convert(file_get_contents(__DIR__ . "/../../../src/Config/templates/test-type/tcp-fallback.json"), TestType::class)
        ];

        $this->assertEquals($expected, $testTypeManager1->listTestTypes());

        // Test can add in a custom
        $testTypeManager2 = new TestTypeManager();
        $customTestType = new TestType("custom", "Custom Test", new TestTypeConfig(new DNSZone("test.com"), null, new WebServerVirtualHost()), new TestTypeRules(new TestTypeDNSRules([new TestTypeExpectedQuery("", "")]), new TestTypeWebServerRules(1), false, "", 5), []);
        file_put_contents(Configuration::readParameter("config.root") . "/resolvertest/custom.json", $this->objectToJSONConverter->convert($customTestType));
        $expected["custom"] = $customTestType;

        $this->assertEquals($expected, $testTypeManager2->listTestTypes());

    }

    public function testCanCreateCorrectServerOperations() {

        $testTypeManager = new TestTypeManager();
        $test = new Test("aKey", "example", "test.co.uk");

        // Temp move example.json to the custom directory
        file_put_contents(Configuration::readParameter("config.root") . "/resolvertest/example.json", file_get_contents(__DIR__ . "/example.json"));

        $operations = $testTypeManager->getInstallServerOperations($test);

        $dnsRecord1 = new DNSRecord("*", 200, "AAAA", "2001::1234");
        $dnsRecord2 = new DNSRecord("*", 250, "A", "1.2.3.4");

        $expectedOperations = [
            new ServerOperation(ServerOperation::OPERATION_ADD, new DNSZone("test.co.uk", [""], [$dnsRecord1, $dnsRecord2])),
            new ServerOperation(ServerOperation::OPERATION_ADD, new WebServerVirtualHost("test.co.uk", true, "OK"))
        ];

        unlink(Configuration::readParameter("config.root") . "/resolvertest/example.json");

        $this->assertEquals($expectedOperations, $operations);

    }


    public function testCanCreateMultipleNameserverServerOperationsForMultipleZonesOrWebserverHosts() {

        $testTypeManager = new TestTypeManager();
        $test = new Test("aKey", "example2", "test.com");

        file_put_contents(Configuration::readParameter("config.root") . "/resolvertest/example2.json", file_get_contents(__DIR__ . "/example2.json"));

        $operations = $testTypeManager->getInstallServerOperations($test);

        $expectedOperations = [
            new ServerOperation(ServerOperation::OPERATION_ADD, new DNSZone("test.com", [""], [new DNSRecord("*", 250, "A", "1.2.3.4"), new DNSRecord("*", 200, "AAAA", "2001::1234")], "", "DEFAULT")),
            new ServerOperation(ServerOperation::OPERATION_ADD, new DNSZone("alt-test.com", [""], [new DNSRecord("*", 250, "A", "1.2.3.4")], "alt-", "NAMESERVER_SET")),
            new ServerOperation(ServerOperation::OPERATION_ADD, new WebServerVirtualHost("test.com", true, "OK")),
            new ServerOperation(ServerOperation::OPERATION_ADD, new WebServerVirtualHost("alt-test.com", true, "OK", ["*"], "alt-"))
        ];

        unlink(Configuration::readParameter("config.root") . "/resolvertest/example2.json");

        $this->assertEquals($expectedOperations, $operations);

    }


    public function testTestParametersAreCorrectlySpunInDynamicallyIfUsedInZoneFileConfig() {

        $testTypeManager = new TestTypeManager();
        $test = new Test("aKey", "example3", "test.co.uk", null, null, null, null, null, ["88.77.66.55", "2001::1234", "SUCCESS"]);

        file_put_contents(Configuration::readParameter("config.root") . "/resolvertest/example3.json", file_get_contents(__DIR__ . "/example3.json"));

        $operations = $testTypeManager->getInstallServerOperations($test);

        $dnsRecord1 = new DNSRecord("*", 200, "A", "88.77.66.55");
        $dnsRecord2 = new DNSRecord("*", 250, "AAAA", "2001::1234");

        $expectedOperations = [
            new ServerOperation(ServerOperation::OPERATION_ADD, new DNSZone("test.co.uk", [""], [$dnsRecord1, $dnsRecord2])),
            new ServerOperation(ServerOperation::OPERATION_ADD, new WebServerVirtualHost("test.co.uk", true, "SUCCESS"))
        ];

        unlink(Configuration::readParameter("config.root") . "/resolvertest/example3.json");

        $this->assertEquals($expectedOperations, $operations);


    }


    public function testCanCreateCorrectServerOperationsUponUninstall() {

        $testTypeManager = new TestTypeManager();
        $test = new Test("aKey", "example", "test.co.uk");

        // Temp move example.json to the custom directory
        file_put_contents(Configuration::readParameter("config.root") . "/resolvertest/example.json", file_get_contents(__DIR__ . "/example.json"));

        $operations = $testTypeManager->getUninstallServerOperations($test);

        $dnsRecord1 = new DNSRecord("*", 200, "AAAA", "2001::1234");
        $dnsRecord2 = new DNSRecord("*", 250, "A", "1.2.3.4");

        $expectedOperations = [
            new ServerOperation(ServerOperation::OPERATION_REMOVE, new DNSZone("test.co.uk", [""], [$dnsRecord1, $dnsRecord2])),
            new ServerOperation(ServerOperation::OPERATION_REMOVE, new WebServerVirtualHost("test.co.uk", true, "OK"))
        ];

        unlink(Configuration::readParameter("config.root") . "/resolvertest/example.json");

        $this->assertEquals($expectedOperations, $operations);

    }

    public function testCanCreateMultipleNameserverServerOperationsForMultipleZonesOrWebserverHostsUponUninstall() {

        $testTypeManager = new TestTypeManager();
        $test = new Test("aKey", "example2", "test.com");

        file_put_contents(Configuration::readParameter("config.root") . "/resolvertest/example2.json", file_get_contents(__DIR__ . "/example2.json"));

        $operations = $testTypeManager->getUninstallServerOperations($test);

        $expectedOperations = [
            new ServerOperation(ServerOperation::OPERATION_REMOVE, new DNSZone("test.com", [""], [new DNSRecord("*", 250, "A", "1.2.3.4"), new DNSRecord("*", 200, "AAAA", "2001::1234")], "", "DEFAULT")),
            new ServerOperation(ServerOperation::OPERATION_REMOVE, new DNSZone("alt-test.com", [""], [new DNSRecord("*", 250, "A", "1.2.3.4")], "alt-", "NAMESERVER_SET")),
            new ServerOperation(ServerOperation::OPERATION_REMOVE, new WebServerVirtualHost("test.com", true, "OK")),
            new ServerOperation(ServerOperation::OPERATION_REMOVE, new WebServerVirtualHost("alt-test.com", true, "OK", ["*"], "alt-"))
        ];

        unlink(Configuration::readParameter("config.root") . "/resolvertest/example2.json");

        $this->assertEquals($expectedOperations, $operations);

    }





    public function testCanReturnTheTestTypeObjectForAGivenTest() {

        $testTypeManager = new TestTypeManager();
        $test = new Test("aKey", "example", "test.co.uk");

        // Temp move example.json to the custom directory
        file_put_contents(Configuration::readParameter("config.root") . "/resolvertest/example.json", file_get_contents(__DIR__ . "/example.json"));

        $testType = $testTypeManager->getTestTypeForTest($test);
        $expected = $this->jsonToObjectConverter->convert(file_get_contents(__DIR__ . "/example.json", true), TestType::class);

        unlink(Configuration::readParameter("config.root") . "/resolvertest/example.json");

        $this->assertEquals($expected, $testType);

    }
}