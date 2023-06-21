<?php

namespace ResolverTest\Framework;

use ResolverTest\Services\Logging\LoggingService;

class BaseLogCommand {

    /**
     * @var LoggingService
     */
    protected $loggingService;

    /**
     * @param LoggingService $loggingService
     */
    public function __construct($loggingService) {
        $this->loggingService = $loggingService;
    }

}