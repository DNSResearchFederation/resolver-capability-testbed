<?php

namespace Services\Whois;

use Kinikit\Core\DependencyInjection\Container;
use PHPUnit\Framework\TestCase;
use ResolverTest\Services\Whois\WhoisService;

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

    public function testFailsSafelyIfDomainNameBad() {

        $domain = "uiragvfrelhgvewafhuerwbifl.com";

        try {
            $this->whoisService->getNameservers($domain);
            $this->fail("Should have thrown here");
        } catch (\Exception $e) {
            $this->assertEquals("Unknown domain\n", $e->getMessage());
            // Success!
        }

    }

}