<?php

namespace Commands;

use Kinikit\Core\Testing\MockObjectProvider;
use PHPUnit\Framework\TestCase;
use ResolverTest\Commands\NameserversAddCommand;
use ResolverTest\Services\Config\NameserverConfigService;

include_once "autoloader.php";

class NameserversAddCommandTest extends TestCase {

    public function testCanAddANewNameserverSet() {

        $nameserverConfig = MockObjectProvider::instance()->getMockInstance(NameserverConfigService::class);
        $command = new NameserversAddCommand($nameserverConfig);

        // Add an initial set of nameservers
        $command->handleCommand("bob", "ns1.test.com", "ns2.test.com");
        $this->assertTrue($nameserverConfig->methodWasCalled("setNameservers", ["bob", ["ns1.test.com", "ns2.test.com"]]));

        // Add a supplementary nameserver set
        $command->handleCommand("steve", "ns20.test.co.uk", "ns21.test.co.uk");
        $this->assertTrue($nameserverConfig->methodWasCalled("setNameservers", ["steve", ["ns20.test.co.uk", "ns21.test.co.uk"]]));

    }

}