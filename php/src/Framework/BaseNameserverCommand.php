<?php

namespace ResolverTest\Framework;

use ResolverTest\Services\Config\NameserverConfigService;

class BaseNameserverCommand {

    /**
     * @var NameserverConfigService
     */
    protected $nameserverConfig;

    /**
     * @param NameserverConfigService $nameserverConfig
     */
    public function __construct($nameserverConfig) {
        $this->nameserverConfig = $nameserverConfig;
    }
}