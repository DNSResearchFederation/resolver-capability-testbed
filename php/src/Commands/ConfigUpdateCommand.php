<?php

namespace ResolverTest\Commands;

use ResolverTest\Exception\InvalidIPAddressException;
use ResolverTest\Framework\BaseConfigCommand;

/**
 * @name config-update
 * @description Update the configuration
 */
class ConfigUpdateCommand extends BaseConfigCommand {

    /**
     * @param string $ipv4Address @option The IPv6 address
     * @param string $ipv6Address @option The IPv6 address
     * @param string $nameservers @option Nameservers for the testbed
     * @param bool $clientIpAddressLogging @option Anonymise IP Addresses in the logs
     * @param string $dapApiKey @option API Key to connect to the DAP
     * @param string $dapApiSecret @option API Secret to connect to the DAP
     *
     * @return void
     */
    public function handleCommand($ipv4Address = null, $ipv6Address = null, $nameservers = null, $clientIpAddressLogging = null, $dapApiKey = null, $dapApiSecret = null) {

        if ($ipv4Address) {
            if (!filter_var($ipv4Address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                throw new InvalidIPAddressException();
            }
            $this->configService->setIPv4Address($ipv4Address);
        }

        if ($ipv6Address) {
            if (!filter_var($ipv6Address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                throw new InvalidIPAddressException();
            }
            $this->configService->setIPv6Address($ipv6Address);
        }

        if ($nameservers) {
            $this->configService->setNameservers($nameservers);
        }

        if (is_bool($clientIpAddressLogging)) {
            $this->configService->setClientIpAddressLogging($clientIpAddressLogging);
        }

        if ($dapApiKey) {
            $this->configService->setDapApiKey($dapApiKey);
        }

        if ($dapApiSecret) {
            $this->configService->setDapApiSecret($dapApiSecret);
        }
    }

}