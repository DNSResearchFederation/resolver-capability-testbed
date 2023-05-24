<?php

namespace ResolverTest\Services\TestManager;

use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Testing\MockObjectProvider;
use PHPUnit\Framework\TestCase;
use ResolverTest\Exception\NonExistentIPv6AddressException;
use ResolverTest\Objects\Server\Config\DNSRecord;
use ResolverTest\Objects\Server\Config\DNSZone;
use ResolverTest\Objects\Server\Config\WebServerVirtualHost;
use ResolverTest\Objects\Server\ServerOperation;
use ResolverTest\Objects\Test\Test;
use ResolverTest\Services\Config\GlobalConfigService;

include_once "autoloader.php";

class IPv6TestManagerTest extends TestCase {

    /**
     * @var GlobalConfigService
     */
    private $configService;

    /**
     * @var IPv6TestManager
     */
    private $testManager;

    public function setUp(): void {
        $this->testManager = Container::instance()->getInterfaceImplementation(TestManager::class, "ipv6");
        $this->configService = Container::instance()->get(GlobalConfigService::class);
    }

    public function testIPv6AddressHasBeenSupplied() {

        $test = MockObjectProvider::instance()->getMockInstance(Test::class);
        $this->configService->setIPv6Address(null);

        try {
            $this->testManager->validateConfig($test);
            $this->fail();
        } catch (NonExistentIPv6AddressException $e) {
            // Great
        }

        $this->configService->setIPv6Address("1234:5678:90ab:cdef:fedc:ba09:8765:4321");

        try {
            $this->testManager->validateConfig($test);
            $this->assertTrue(true);
        } catch (NonExistentIPv6AddressException $e) {
            $this->fail();
        }

    }

    public function testCanCreateCorrectServerOperationsUponInstall() {

        $test = new Test("test", "ipv6", "test.com");
        $operations = $this->testManager->install($test);

        $dnsRecord = new DNSRecord("*", 200, "AAAA", "test.com");
        $expectedOperations = [
            new ServerOperation(ServerOperation::OPERATION_ADD, new DNSZone("test.com", [$dnsRecord])),
            new ServerOperation(ServerOperation::OPERATION_ADD, new WebServerVirtualHost("test.com", "Hello World!"))
        ];

        $this->assertEquals($expectedOperations, $operations);

    }

    public function testCanCreateCorrectServerOperationsUponUninstall() {

        $test = new Test("test", "ipv6", "test.com");
        $operations = $this->testManager->uninstall($test);

        $expectedOperations = [
            new ServerOperation(ServerOperation::OPERATION_REMOVE, new DNSZone("test.com")),
            new ServerOperation(ServerOperation::OPERATION_REMOVE, new WebServerVirtualHost("test.com"))
        ];

        $this->assertEquals($expectedOperations, $operations);
    }
}