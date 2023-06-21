<?php

namespace ResolverTest\Services\TestType;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Serialisation\JSON\JSONToObjectConverter;
use Kinikit\Core\Serialisation\JSON\ObjectToJSONConverter;
use PHPUnit\Framework\TestCase;
use ResolverTest\Objects\Server\ServerOperation;
use ResolverTest\Objects\Test\Test;
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
            "ipv6" => $this->jsonToObjectConverter->convert(file_get_contents(__DIR__ . "/../../../src/Config/templates/test-type/ipv6.json"), TestType::class),
            "qname-minimisation" => $this->jsonToObjectConverter->convert(file_get_contents(__DIR__ . "/../../../src/Config/templates/test-type/qname.json"), TestType::class)
        ];

        $this->assertEquals($expected, $testTypeManager1->listTestTypes());

        // Test can add in a custom
        $testTypeManager2 = new TestTypeManager();
        $customTestType = new TestType("custom", "Custom Test", new TestTypeConfig(new DNSZone("test.com"), new WebServerVirtualHost()), new TestTypeRules(new TestTypeDNSRules(new TestTypeExpectedQuery("","")), new TestTypeWebServerRules(1), "", 5), []);
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
            new ServerOperation(ServerOperation::OPERATION_ADD, new DNSZone("test.co.uk", [$dnsRecord1, $dnsRecord2])),
            new ServerOperation(ServerOperation::OPERATION_ADD, new WebServerVirtualHost("test.co.uk", true, "OK"))
        ];

        unlink(Configuration::readParameter("config.root") . "/resolvertest/example.json");

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
            new ServerOperation(ServerOperation::OPERATION_REMOVE, new DNSZone("test.co.uk", [$dnsRecord1, $dnsRecord2])),
            new ServerOperation(ServerOperation::OPERATION_REMOVE, new WebServerVirtualHost("test.co.uk", true, "OK"))
        ];

        unlink(Configuration::readParameter("config.root") . "/resolvertest/example.json");

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