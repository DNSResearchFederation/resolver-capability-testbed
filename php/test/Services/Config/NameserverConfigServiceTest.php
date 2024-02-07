<?php

namespace Services\Config;

use PHPUnit\Framework\TestCase;
use ResolverTest\Services\Config\NameserverConfigService;

include_once "autoloader.php";

class NameserverConfigServiceTest extends TestCase {

    public function testCanUpdateNameserversCorrectly() {

        $nameserverConfigService = new NameserverConfigService();

        $nameserverConfigService->setNameservers("key1", ["ns1.test.com", "ns2.test.com"]);
        $nameserverConfigService->setNameservers("key2", ["ns3.test.com", "ns4.test.com"]);
        $this->assertEquals(["key1" => ["ns1.test.com", "ns2.test.com"], "key2" => ["ns3.test.com", "ns4.test.com"]], $nameserverConfigService->getNameservers());

        $nameserverConfigService->deleteNameservers("key1");
        $this->assertEquals(["key2" => ["ns3.test.com", "ns4.test.com"]], $nameserverConfigService->getNameservers());

    }

}