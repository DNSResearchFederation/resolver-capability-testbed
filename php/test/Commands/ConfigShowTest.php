<?php

namespace ResolverTest\Commands;

use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use MathieuViossat\Util\ArrayToTextTable;
use PHPUnit\Framework\TestCase;
use ResolverTest\Services\Config\GlobalConfigService;

include_once "autoloader.php";

class ConfigShowTest extends TestCase {

    /**
     * @var MockObject
     */
    private $configService;

    public function setUp(): void {
        $this->configService = MockObjectProvider::instance()->getMockInstance(GlobalConfigService::class);
    }

    public function testDoesDisplayConfigCorrectly() {

        $command = new ConfigShow($this->configService);
        $this->configService->returnValue("getIPv4Address", "1.2.3.4");
        $this->configService->returnValue("getIPv6Address", "2001::");
        $this->configService->returnValue("getNameservers", ["ns1.test.com", "ns2.test.com"]);

        ob_start();
        $command->handleCommand();
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals("IPv4 Address: 1.2.3.4\nIPv6 Address: 2001::\nNameservers: ns1.test.com, ns2.test.com\n", $output);
    }

}