<?php

namespace ResolverTest\Commands;

use Kinikit\Core\Testing\MockObjectProvider;
use PHPUnit\Framework\TestCase;
use ResolverTest\Exception\InvalidIPAddressException;
use ResolverTest\Services\Config\GlobalConfigService;

include_once "autoloader.php";

class ConfigUpdateTest extends TestCase {

    public function testCanUpdateTheGlobalConfig() {

        $configService = MockObjectProvider::instance()->getMockInstance(GlobalConfigService::class);

        $command = new ConfigUpdate($configService);

        $command->handleCommand("1.2.3.4");
        $this->assertTrue($configService->methodWasCalled("setIPv4Address", ["1.2.3.4"]));

        $command->handleCommand(null, "2001::");
        $this->assertTrue($configService->methodWasCalled("setIPv6Address", ["2001::"]));

        $command->handleCommand("9.8.7.6", "2002::");
        $this->assertTrue($configService->methodWasCalled("setIPv4Address", ["9.8.7.6"]));
        $this->assertTrue($configService->methodWasCalled("setIPv6Address", ["2002::"]));

    }

    /**
     * @doesNotPerformAssertions
     */
    public function testExceptionThrownIfIPsProvidedOfWrongType() {

        $configService = MockObjectProvider::instance()->getMockInstance(GlobalConfigService::class);

        $command = new ConfigUpdate($configService);

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