<?php

namespace ResolverTest\Commands;

use Kinikit\Core\Testing\MockObjectProvider;
use PHPUnit\Framework\TestCase;
use ResolverTest\Exception\InvalidIPAddressException;
use ResolverTest\Services\Config\GlobalConfigService;

include_once "autoloader.php";

class ConfigUpdateCommandTest extends TestCase {

    public function testCanUpdateTheGlobalConfig() {

        $configService = MockObjectProvider::instance()->getMockInstance(GlobalConfigService::class);

        $command = new ConfigUpdateCommand($configService);

        $command->handleCommand("1.2.3.4");
        $this->assertTrue($configService->methodWasCalled("setIPv4Address", ["1.2.3.4"]));

        $command->handleCommand(null, "2001::");
        $this->assertTrue($configService->methodWasCalled("setIPv6Address", ["2001::"]));

        $command->handleCommand("9.8.7.6", "2002::");
        $this->assertTrue($configService->methodWasCalled("setIPv4Address", ["9.8.7.6"]));
        $this->assertTrue($configService->methodWasCalled("setIPv6Address", ["2002::"]));

        $command->handleCommand(null, null, "ns1.test.com");
        $this->assertTrue($configService->methodWasCalled("setNameservers", ["ns1.test.com"]));

        $command->handleCommand(null, null, null, false, 1234567890, 987654321);
        $this->assertTrue($configService->methodWasCalled("setClientIpAddressLogging", [false]));
        $this->assertTrue($configService->methodWasCalled("setDapApiKey", [1234567890]));
        $this->assertTrue($configService->methodWasCalled("setDapApiSecret", [987654321]));

    }

    /**
     * @doesNotPerformAssertions
     */
    public function testExceptionThrownIfIPsProvidedOfWrongType() {

        $configService = MockObjectProvider::instance()->getMockInstance(GlobalConfigService::class);

        $command = new ConfigUpdateCommand($configService);

        try {
            $command->handleCommand("bing");
            $this->fail();
        } catch (InvalidIPAddressException $e) {
            // Great
        }

        try {
            $command->handleCommand(null, "1.2.3.4");
            $this->fail();
        } catch (InvalidIPAddressException $e) {
            // Great
        }

    }
}