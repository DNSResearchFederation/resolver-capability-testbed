<?php

namespace ResolverTest\Services\TestManager;

use Kinikit\Core\DependencyInjection\Container;
use ResolverTest\Exception\NonExistentIPv6AddressException;
use ResolverTest\Objects\Server\ServerOperation;
use ResolverTest\Services\Config\GlobalConfigService;
use ResolverTest\ValueObjects\TestType\Config\DNSRecord;
use ResolverTest\ValueObjects\TestType\Config\DNSZone;
use ResolverTest\ValueObjects\TestType\Config\WebServerVirtualHost;

class IPv6TestManager implements TestManager {

    // Create install operations
    public function install($test) {

        $domain = $test->getDomainName();
        $globalConfig = new GlobalConfigService();

        // Create zone and records
        $aaaaRecord = new DNSRecord("*", "200", "AAAA", $globalConfig->getIPv6Address());
        $dnsZone = new DNSZone($domain, [$aaaaRecord]);

        $webServerVirtualHost = new WebServerVirtualHost($domain, false, "Hello World!");

        return [
            new ServerOperation(ServerOperation::OPERATION_ADD, $dnsZone),
            new ServerOperation(ServerOperation::OPERATION_ADD, $webServerVirtualHost)
        ];

    }

    public function uninstall($test) {

        $domain = $test->getDomainName();
        $dnsZone = new DNSZone($domain);
        $webServerVirtualHost = new WebServerVirtualHost($domain);

        return [
            new ServerOperation(ServerOperation::OPERATION_REMOVE, $dnsZone),
            new ServerOperation(ServerOperation::OPERATION_REMOVE, $webServerVirtualHost)
        ];
    }

    /**
     * Validate the config
     *
     * @param $test
     * @return void
     * @throws NonExistentIPv6AddressException
     */
    public function validateConfig($test) {

        $configService = Container::instance()->get(GlobalConfigService::class);

        if (!$configService->getIPv6Address()) {
            throw new NonExistentIPv6AddressException();
        }

    }
}