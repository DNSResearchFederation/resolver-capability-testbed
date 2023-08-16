<?php

namespace ResolverTest\Services\Whois;

use Kinikit\Core\DependencyInjection\Container;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class WhoisServiceTest extends TestCase {

    /**
     * @var WhoisService
     */
    private $whoisService;

    public function setUp(): void {
        $this->whoisService = Container::instance()->get(WhoisService::class);
    }

    public function testCanGetNameservers() {

        $nameservers = $this->whoisService->getNameservers("oxil.co.uk");
        $this->assertEquals(["abby.ns.cloudflare.com", "henry.ns.cloudflare.com"], $nameservers);

    }

}