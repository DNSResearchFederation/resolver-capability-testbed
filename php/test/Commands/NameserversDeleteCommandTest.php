<?php

namespace Commands;

use Kinikit\Core\Testing\MockObjectProvider;
use PHPUnit\Framework\TestCase;
use ResolverTest\Commands\NameserversDeleteCommand;
use ResolverTest\Services\Config\NameserverConfigService;

include_once "autoloader.php";

class NameserversDeleteCommandTest extends TestCase {

    public function testCanDeleteANameserverSet() {

        $nameserverConfig = MockObjectProvider::instance()->getMockInstance(NameserverConfigService::class);
        $command = new NameserversDeleteCommand($nameserverConfig);

        $command->handleCommand("bob");
        $this->assertTrue($nameserverConfig->methodWasCalled("deleteNameservers", ["bob"]));

    }

}