<?php

namespace ResolverTest\Services\Server;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Configuration\FileResolver;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Template\MustacheTemplateParser;
use PHPUnit\Framework\TestCase;
use ResolverTest\Objects\Server\Config\DNSRecord;
use ResolverTest\Objects\Server\Config\DNSZone;
use ResolverTest\Objects\Server\Config\WebServerVirtualHost;
use ResolverTest\Objects\Server\ServerOperation;
use ResolverTest\Services\Config\GlobalConfigService;

include_once "autoloader.php";

class LinuxServerTest extends TestCase {

    /**
     * @var GlobalConfigService
     */
    private $configService;

    /**
     * @var LinuxServer
     */
    private $server;

    public function setUp(): void {
        $this->configService = Container::instance()->get(GlobalConfigService::class);
        $this->server = new LinuxServer($this->configService, Container::instance()->get(MustacheTemplateParser::class),
            Container::instance()->get(FileResolver::class), "");

        file_put_contents(Configuration::readParameter("server.bind.zones.path"), "");
    }

    public function testCanInstallDNSZoneCorrectly() {

        $this->configService->setNameservers("ns1.testdomain.com,ns2.testdomain.com");
        $this->configService->setIPv4Address("1.2.3.4");
        $this->configService->setIPv6Address("2001::1234");

        $dnsRecords = [
            new DNSRecord("", 300, "A", "1.2.3.4"),
            new DNSRecord("", 200, "AAAA", "2001::1234"),
            new DNSRecord("", 250, "MX", "mail.testdomain.com"),
            new DNSRecord("www", 200, "CNAME", "testdomain.com")
        ];

        $dnsZone = new DNSZone("testdomain.com", $dnsRecords);
        $operation = new ServerOperation(ServerOperation::OPERATION_ADD, $dnsZone);

        $this->server->performOperations([$operation]);

        $path = Configuration::readParameter("server.bind.config.dir") . "/testdomain.com.conf";

        $this->assertTrue(file_exists($path));
        $this->assertEquals(file_get_contents(__DIR__ . "/test-bind-linux.com"), file_get_contents($path));

        $this->assertStringContainsString(file_get_contents(__DIR__ . "/test-bind-zones-linux"), file_get_contents(Configuration::readParameter("server.bind.zones.path")));

    }

    public function testCanUninstallDNSZoneCorrectly() {

        $path = Configuration::readParameter("server.bind.config.dir") . "/1.com.conf";
        file_put_contents($path, "contents");

        file_put_contents(Configuration::readParameter("server.bind.zones.path"), "zone \"1.com\" IN {\n
        type master;\n
        file \"testdomain.com.hosts\";\n
        };");

        $dnsZone = new DNSZone("1.com");
        $operation = new ServerOperation(ServerOperation::OPERATION_REMOVE, $dnsZone);

        $this->assertTrue(file_exists($path));
        $this->server->performOperations([$operation]);
        $this->assertFalse(file_exists($path));
        $this->assertStringNotContainsString(file_get_contents(__DIR__ . "/test-bind-zones-linux"), file_get_contents(Configuration::readParameter("server.bind.zones.path")));

    }

    public function testCanInstallWebServerVirtualHostCorrectly() {

        $content = "Hello World!";

        $webServerVirtualHost = new WebServerVirtualHost("testdomain.com", $content);
        $operation = new ServerOperation(ServerOperation::OPERATION_ADD, $webServerVirtualHost);

        $this->server->performOperations([$operation]);

        $path = Configuration::readParameter("server.httpd.config.dir") . "/testdomain.com.conf";

        $this->assertTrue(file_exists($path));
        $this->assertEquals(file_get_contents(__DIR__ . "/test-httpd-linux.com"), file_get_contents($path));

        $contentPath = Configuration::readParameter("server.httpd.webroot.dir") . "/testdomain.com/index.html";
        $this->assertTrue(file_exists($contentPath));
        $this->assertEquals($content, file_get_contents($contentPath));

    }

    public function testCanUninstallWebServerVirtualHostCorrectly() {

        $path = Configuration::readParameter("server.httpd.config.dir") . "/1.com.conf";
        $contentDir = Configuration::readParameter("server.httpd.webroot.dir") . "/1.com";

        if (!file_exists($contentDir)) {
            mkdir($contentDir);
        }
        file_put_contents($path, "content");
        file_put_contents($contentDir . "/index.html", "Hello!");

        $webServerVirtualHost = new WebServerVirtualHost("1.com");
        $operation = new ServerOperation(ServerOperation::OPERATION_REMOVE, $webServerVirtualHost);

        $this->server->performOperations([$operation]);

        $this->assertFalse(file_exists($path));
        $this->assertFalse(file_exists($contentDir));

    }

}