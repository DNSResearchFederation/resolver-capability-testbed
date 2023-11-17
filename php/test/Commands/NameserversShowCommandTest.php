<?php

namespace Commands;

use Kinikit\Core\Testing\MockObjectProvider;
use PHPUnit\Framework\TestCase;
use ResolverTest\Commands\NameserversShowCommand;
use ResolverTest\Services\Config\NameserverConfigService;

include_once "autoloader.php";

class NameserversShowCommandTest extends TestCase {

    public function testDoesShowNameserverSetsCorrectly() {

        $nameserverConfig = MockObjectProvider::instance()->getMockInstance(NameserverConfigService::class);
        $nameserverConfig->returnValue("getNameservers", ["default" => ["ns1.test.com", "ns2.test.com"], "special" => ["ns4.testing.com", "ns5.testing.com"]]);

        $command = new NameserversShowCommand($nameserverConfig);

        ob_start();
        $command->handleCommand();
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals("default: ns1.test.com, ns2.test.com\nspecial: ns4.testing.com, ns5.testing.com\n", $output);

    }

}