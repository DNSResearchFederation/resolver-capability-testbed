<?php

namespace ResolverTest\Commands;

use ResolverTest\Framework\BaseConfigCommand;

/**
 * @name config-show
 * @description Display the current configuration
 */
class ConfigShowCommand extends BaseConfigCommand {

    /**
     * @return void
     */
    public function handleCommand() {

        print("IPv4 Address: " . $this->configService->getIPv4Address() . "\n");
        print("IPv6 Address: " . $this->configService->getIPv6Address() . "\n");
        print("Nameservers: " . implode(", ", $this->configService->getNameservers()) . "\n");
        print("Client IP Address Logging: " . ($this->configService->isClientIpAddressLogging() ?? "false") . "\n");
        print("DAP Api Key: " . $this->configService->getDapApiKey() . "\n");
        print("DAP Api Secret: " . $this->configService->getDapApiSecret() . "\n");

    }

}