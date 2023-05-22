<?php

namespace ResolverTest\Framework;

use ResolverTest\Services\Config\GlobalConfigService;

class BaseConfigCommand {

    /**
     * @var GlobalConfigService
     */
    protected $configService;

    /**
     * @param GlobalConfigService $configService
     */
    public function __construct($configService) {
        $this->configService = $configService;
    }


}