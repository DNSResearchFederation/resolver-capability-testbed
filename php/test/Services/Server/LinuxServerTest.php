<?php

namespace Services\Server;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\Configuration\FileResolver;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Template\MustacheTemplateParser;
use PHPUnit\Framework\TestCase;
use ResolverTest\Objects\Log\NameserverLog;
use ResolverTest\Objects\Log\WebserverLog;
use ResolverTest\Objects\Server\ServerOperation;
use ResolverTest\Services\Config\GlobalConfigService;
use ResolverTest\Services\Server\LinuxServer;
use ResolverTest\Services\Server\Server;
use ResolverTest\ValueObjects\TestType\Config\DNSRecord;
use ResolverTest\ValueObjects\TestType\Config\DNSSECAlgorithmEnum;
use ResolverTest\ValueObjects\TestType\Config\DNSSECConfig;
use ResolverTest\ValueObjects\TestType\Config\DNSZone;
use ResolverTest\ValueObjects\TestType\Config\WebServerVirtualHost;

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

        passthru("rm -rf " . Configuration::readParameter("server.bind.config.dir") . "/*conf*");

    }

    public function testCanInstallDNSZoneCorrectly() {

        $this->configService->setIPv4Address("1.2.3.4");
        $this->configService->setIPv6Address("2001::1234");

        $dnsRecords = [
            new DNSRecord("this", 300, "A", "1.2.3.4"),
            new DNSRecord("that", 200, "AAAA", "2001::1234"),
            new DNSRecord("", 250, "MX", "mail.testdomain.com"),
            new DNSRecord("www", 200, "CNAME", "testdomain.com")
        ];

        $dnsZone = new DNSZone("testdomain.com", ["ns1.testdomain.com", "ns2.testdomain.com"], $dnsRecords);
        $operation = new ServerOperation(ServerOperation::OPERATION_ADD, $dnsZone);

        $this->server->performOperations([$operation]);

        $path = Configuration::readParameter("server.bind.config.dir") . "/testdomain.com.conf";

        $this->assertTrue(file_exists($path));
        $this->assertEquals(file_get_contents(__DIR__ . "/test-bind-linux.com"), file_get_contents($path));

        $this->assertStringContainsString(file_get_contents(__DIR__ . "/test-bind-zones-linux"), file_get_contents(Configuration::readParameter("server.bind.zones.path")));

    }



    public function testCanInstallSignedDNSZoneCorrectlyIfNoMatchingWebserverVirtualHost() {

        $this->configService->setIPv4Address("1.2.3.4");
        $this->configService->setIPv6Address("2001::1234");

        $dnsRecords = [
            new DNSRecord("this", 300, "A", "1.2.3.4"),
            new DNSRecord("that", 200, "AAAA", "2001::1234"),
            new DNSRecord("", 250, "MX", "mail.testdomain.com"),
            new DNSRecord("www", 200, "CNAME", "testdomain.com")
        ];

        $dnsZone = new DNSZone("testdomain.com", ["ns1.testdomain.com", "ns2.testdomain.com"], $dnsRecords, "", null,
            new DNSSECConfig(8));
        $operation = new ServerOperation(ServerOperation::OPERATION_ADD, $dnsZone);

        $additionalInfo = $this->server->performOperations([$operation]);

        $path = Configuration::readParameter("server.bind.config.dir") . "/testdomain.com.conf.unsigned";
        $this->assertTrue(file_exists($path));
        $this->assertEquals(file_get_contents(__DIR__ . "/test-bind-linux-dnssec.com"), file_get_contents($path));

        $signedPath = Configuration::readParameter("server.bind.config.dir") . "/testdomain.com.conf";
        $this->assertTrue(file_exists($signedPath));

        $originalPath = Configuration::readParameter("server.bind.config.dir") . "/testdomain.com.conf";
        $this->assertEquals("-N INCREMENT -o testdomain.com -3 - $originalPath", file_get_contents($signedPath));

        $this->assertStringContainsString(file_get_contents(__DIR__ . "/test-bind-zones-linux"), file_get_contents(Configuration::readParameter("server.bind.zones.path")));

        // Check additional info as expected
        $this->assertEquals(1, sizeof($additionalInfo));
        $this->assertEquals("Please add the following DS records for testdomain.com via your Registrar\n\nEXAMPLE-DS-RECORDS-testdomain.com", $additionalInfo[0]);


    }


    public function testKeysCreatedButSignedZoneNotCreatedForDNSSECIfMatchingWebserverVirtualHost() {

        $this->configService->setIPv4Address("1.2.3.4");
        $this->configService->setIPv6Address("2001::1234");

        $dnsRecords = [
            new DNSRecord("this", 300, "A", "1.2.3.4"),
            new DNSRecord("that", 200, "AAAA", "2001::1234"),
            new DNSRecord("", 250, "MX", "mail.testdomain.com"),
            new DNSRecord("www", 200, "CNAME", "testdomain.com")
        ];

        $dnsZone = new DNSZone("testdomain.com", ["ns1.testdomain.com", "ns2.testdomain.com"], $dnsRecords, "", null,
            new DNSSECConfig(8), true);
        $operation = new ServerOperation(ServerOperation::OPERATION_ADD, $dnsZone);

        $this->server->performOperations([$operation]);

        $path = Configuration::readParameter("server.bind.config.dir") . "/testdomain.com.conf";

        $this->assertTrue(file_exists($path));
        $this->assertEquals(file_get_contents(__DIR__ . "/test-bind-linux-dnssec.com"), file_get_contents($path));

        $unsignedPath = Configuration::readParameter("server.bind.config.dir") . "/testdomain.com.unsigned";
        $this->assertFalse(file_exists($unsignedPath));

        $this->assertStringContainsString(file_get_contents(__DIR__ . "/test-bind-zones-linux"), file_get_contents(Configuration::readParameter("server.bind.zones.path")));


    }

    public function testSignedZoneNotCreatedIfSignZoneSetToFalse() {

        $this->configService->setIPv4Address("1.2.3.4");
        $this->configService->setIPv6Address("2001::1234");

        $dnsRecords = [
            new DNSRecord("this", 300, "A", "1.2.3.4"),
            new DNSRecord("that", 200, "AAAA", "2001::1234"),
            new DNSRecord("", 250, "MX", "mail.testdomain.com"),
            new DNSRecord("www", 200, "CNAME", "testdomain.com")
        ];

        $dnsZone = new DNSZone("testdomain.com", ["ns1.testdomain.com", "ns2.testdomain.com"], $dnsRecords, "", null,
            new DNSSECConfig(8, null, false));
        $operation = new ServerOperation(ServerOperation::OPERATION_ADD, $dnsZone);

        $additionalInfo = $this->server->performOperations([$operation]);

        $unsignedPath = Configuration::readParameter("server.bind.config.dir") . "/testdomain.com.conf.unsigned";
        $this->assertFalse(file_exists($unsignedPath));

        $zonePath = Configuration::readParameter("server.bind.config.dir") . "/testdomain.com.conf";
        $this->assertTrue(file_exists($zonePath));
        $this->assertEquals(file_get_contents(__DIR__ . "/test-bind-linux.com"), file_get_contents($zonePath));

        $this->assertStringContainsString(file_get_contents(__DIR__ . "/test-bind-zones-linux"), file_get_contents(Configuration::readParameter("server.bind.zones.path")));

        // Check additional info as expected as we still want DS records
        $this->assertEquals(1, sizeof($additionalInfo));
        $this->assertEquals("Please add the following DS records for testdomain.com via your Registrar\n\nEXAMPLE-DS-RECORDS-testdomain.com", $additionalInfo[0]);


    }

    public function testNSECUsedRatherThanNSEC3IfNSEC3SetToFalseForDNSSECZone() {

        $this->configService->setIPv4Address("1.2.3.4");
        $this->configService->setIPv6Address("2001::1234");

        $dnsRecords = [
            new DNSRecord("this", 300, "A", "1.2.3.4"),
            new DNSRecord("that", 200, "AAAA", "2001::1234"),
            new DNSRecord("", 250, "MX", "mail.testdomain.com"),
            new DNSRecord("www", 200, "CNAME", "testdomain.com")
        ];

        $dnsZone = new DNSZone("testdomain.com", ["ns1.testdomain.com", "ns2.testdomain.com"], $dnsRecords, "", null,
            new DNSSECConfig(8, null, true, true,false));
        $operation = new ServerOperation(ServerOperation::OPERATION_ADD, $dnsZone);

        $this->server->performOperations([$operation]);

        $path = Configuration::readParameter("server.bind.config.dir") . "/testdomain.com.conf.unsigned";
        $this->assertTrue(file_exists($path));
        $this->assertEquals(file_get_contents(__DIR__ . "/test-bind-linux-dnssec-nsec.com"), file_get_contents($path));

        $signedPath = Configuration::readParameter("server.bind.config.dir") . "/testdomain.com.conf";
        $this->assertTrue(file_exists($signedPath));

        $originalPath = Configuration::readParameter("server.bind.config.dir") . "/testdomain.com.conf";
        $this->assertEquals("-N INCREMENT -o testdomain.com $originalPath", file_get_contents($signedPath));

        $this->assertStringContainsString(file_get_contents(__DIR__ . "/test-bind-zones-linux"), file_get_contents(Configuration::readParameter("server.bind.zones.path")));



    }



    public function testAdditionalInfoNotReturnedIfGenerateDSRecordsSetToFalseForDNSSECZone() {

        $this->configService->setIPv4Address("1.2.3.4");
        $this->configService->setIPv6Address("2001::1234");

        $dnsRecords = [
            new DNSRecord("this", 300, "A", "1.2.3.4"),
            new DNSRecord("that", 200, "AAAA", "2001::1234"),
            new DNSRecord("", 250, "MX", "mail.testdomain.com"),
            new DNSRecord("www", 200, "CNAME", "testdomain.com")
        ];

        $dnsZone = new DNSZone("testdomain.com", ["ns1.testdomain.com", "ns2.testdomain.com"], $dnsRecords, "", null,
            new DNSSECConfig(8, null, true, false));
        $operation = new ServerOperation(ServerOperation::OPERATION_ADD, $dnsZone);

        $additionalInfo = $this->server->performOperations([$operation]);

        $path = Configuration::readParameter("server.bind.config.dir") . "/testdomain.com.conf.unsigned";
        $this->assertTrue(file_exists($path));
        $this->assertEquals(file_get_contents(__DIR__ . "/test-bind-linux-dnssec.com"), file_get_contents($path));

        $signedPath = Configuration::readParameter("server.bind.config.dir") . "/testdomain.com.conf";
        $this->assertTrue(file_exists($signedPath));

        $originalPath = Configuration::readParameter("server.bind.config.dir") . "/testdomain.com.conf";
        $this->assertEquals("-N INCREMENT -o testdomain.com -3 - $originalPath", file_get_contents($signedPath));

        $this->assertStringContainsString(file_get_contents(__DIR__ . "/test-bind-zones-linux"), file_get_contents(Configuration::readParameter("server.bind.zones.path")));

        // Check additional info as expected
        $this->assertEquals(0, sizeof($additionalInfo));


    }


    public function testCanUninstallDNSZoneCorrectly() {

        $path = Configuration::readParameter("server.bind.config.dir") . "/testdomain.com.conf";
        file_put_contents($path, "contents");

        file_put_contents(Configuration::readParameter("server.bind.zones.path"), file_get_contents(__DIR__ . "/test-bind-zones-linux") . " EXTRA CONTENT");

        $dnsZone = new DNSZone("testdomain.com");
        $operation = new ServerOperation(ServerOperation::OPERATION_REMOVE, $dnsZone);

        $this->assertTrue(file_exists($path));
        $this->server->performOperations([$operation]);
        $this->assertFalse(file_exists($path));
        $this->assertStringNotContainsString(file_get_contents(__DIR__ . "/test-bind-zones-linux"), file_get_contents(Configuration::readParameter("server.bind.zones.path")));
        $this->assertStringContainsString("EXTRA CONTENT", file_get_contents(Configuration::readParameter("server.bind.zones.path")));

    }


    public function testCanUninstallSignedDNSZoneCorrectly() {

        $path = Configuration::readParameter("server.bind.config.dir") . "/testdomain.com.conf";
        file_put_contents($path, "contents");
        file_put_contents($path . ".unsigned", "contents");

        file_put_contents(Configuration::readParameter("server.bind.zones.path"), file_get_contents(__DIR__ . "/test-bind-zones-linux") . " EXTRA DATA");

        $dnsZone = new DNSZone("testdomain.com", [], [], "", null, new DNSSECConfig(6));
        $operation = new ServerOperation(ServerOperation::OPERATION_REMOVE, $dnsZone);

        $this->assertTrue(file_exists($path));
        $this->assertTrue(file_exists($path . ".unsigned"));
        $this->assertTrue(file_exists(Configuration::readParameter("server.bind.config.dir") . "/dnssec/testdomain.com"));

        $this->server->performOperations([$operation]);

        $this->assertFalse(file_exists($path));
        $this->assertFalse(file_exists($path . ".unsigned"));
        $this->assertFalse(file_exists(Configuration::readParameter("server.bind.config.dir") . "/dnssec/testdomain.com"));

        $this->assertStringNotContainsString(file_get_contents(__DIR__ . "/test-bind-zones-linux"), file_get_contents(Configuration::readParameter("server.bind.zones.path")));
        $this->assertStringContainsString("EXTRA DATA", file_get_contents(Configuration::readParameter("server.bind.zones.path")));
    }


    public function testCanInstallWebServerVirtualHostCorrectly() {

        $content = "Hello World!";

        $webServerVirtualHost = new WebServerVirtualHost("testdomain.com", false, $content);
        $operation = new ServerOperation(ServerOperation::OPERATION_ADD, $webServerVirtualHost);

        $this->server->performOperations([$operation]);

        $path = Configuration::readParameter("server.httpd.config.dir") . "/testdomain.com.conf";

        $this->assertTrue(file_exists($path));
        $this->assertEquals(file_get_contents(__DIR__ . "/test-httpd-linux.com"), file_get_contents($path));

        $contentPath = Configuration::readParameter("server.httpd.webroot.dir") . "/testdomain.com/index.html";
        $this->assertTrue(file_exists($contentPath));
        $this->assertEquals($content, file_get_contents($contentPath));

    }


    public function testWhereWebServerVirtualHostIsForDNSSECDomainZoneFileIsSignedAfterDNSValidationRecordInserted() {

        $content = "Hello World!";

        $webServerVirtualHost = new WebServerVirtualHost("testdomain.com", false, $content, ["*"], "", true);
        $operation = new ServerOperation(ServerOperation::OPERATION_ADD, $webServerVirtualHost);

        $this->server->performOperations([$operation]);

        $path = Configuration::readParameter("server.httpd.config.dir") . "/testdomain.com.conf";

        $this->assertTrue(file_exists($path));
        $this->assertEquals(file_get_contents(__DIR__ . "/test-httpd-linux.com"), file_get_contents($path));

        $contentPath = Configuration::readParameter("server.httpd.webroot.dir") . "/testdomain.com/index.html";
        $this->assertTrue(file_exists($contentPath));
        $this->assertEquals($content, file_get_contents($contentPath));

        // Check DNSSEC activated.
        $bindPath = Configuration::readParameter("server.bind.config.dir") . "/testdomain.com.conf";
        $this->assertTrue(file_exists($bindPath));
        $this->assertEquals("-N INCREMENT -o testdomain.com -3 - $bindPath", file_get_contents($bindPath));


    }


    public function testCanInstallWebserverVirtualHostWithoutSSLCorrectly() {

        $content = "Hello World!";

        $webServerVirtualHost = new WebServerVirtualHost("testdomain.com", false, $content, []);
        $operation = new ServerOperation(ServerOperation::OPERATION_ADD, $webServerVirtualHost);

        $this->server->performOperations([$operation]);

        $path = Configuration::readParameter("server.httpd.config.dir") . "/testdomain.com.conf";

        $this->assertTrue(file_exists($path));
        $this->assertEquals(file_get_contents(__DIR__ . "/test-httpd-linux-insecure.com"), file_get_contents($path));

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

    public function testCanProcessWebserverLogCorrectly() {

        $logString = "\"example.com\" 127.0.0.1 [07/Jun/2023:11:39:57 +0100] \"GET / HTTP/1.1\" 200 \"Mozilla/4.08 [en] (Win98; I ;Nav)\"";
        $expectedLog = new WebserverLog("example.com", date_create("2023-06-07 11:39:57"), "127.0.0.1", "Mozilla/4.08 [en] (Win98; I ;Nav)", 200);

        $log = $this->server->processLog($logString, Server::SERVICE_WEBSERVER);

        $this->assertEquals($expectedLog, $log);

    }

    public function testCanProcessNameserverLogCorrectly() {

        $logString = "13-Jun-2023 09:44:19.306 queries: client @0x7f9e980bf990 192.168.0.0#12345 (monkey.a.b.c.resolvertest.xyz): query: monkey.a.b.c.resolvertest.xyz IN A -E(0) (10.128.0.5)";
        $expectedLog = new NameserverLog("monkey.a.b.c.resolvertest.xyz", date_create("13-Jun-2023 09:44:19.306"), "192.168.0.0", 12345, "monkey.a.b.c.resolvertest.xyz IN A", "A", "-E(0)");

        $log = $this->server->processLog($logString, Server::SERVICE_NAMESERVER);

        $this->assertEquals($expectedLog, $log);

    }

}