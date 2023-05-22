<?php

namespace ResolverTest\Commands;

use ResolverTest\Framework\BaseConfigCommand;

/**
 * @name config-show
 * @description Display the current configuration
 */
class ConfigShow extends BaseConfigCommand {

    /**
     * @return void
     */
    public function handleCommand() {

        print("IPv4 Address: " . $this->configService->getIPv4Address() . "\n");
        print("IPv6 Address: " . $this->configService->getIPv6Address() . "\n");

    }

}