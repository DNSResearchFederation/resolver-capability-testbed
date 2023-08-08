<?php

namespace ResolverTest\Objects\Server;

use ResolverTest\ValueObjects\TestType\Config\OperationConfig;

class ServerOperation {

    /**
     * @var string
     */
    private $mode;

    /**
     * @var OperationConfig
     */
    private $config;

    const OPERATION_ADD = "add";
    const OPERATION_REMOVE = "remove";

    /**
     * @param string $mode
     * @param OperationConfig $config
     */
    public function __construct($mode, $config) {
        $this->mode = $mode;
        $this->config = $config;
    }

    /**
     * @return string
     */
    public function getMode() {
        return $this->mode;
    }

    /**
     * @param string $mode
     */
    public function setMode($mode) {
        $this->mode = $mode;
    }

    /**
     * @return OperationConfig
     */
    public function getConfig() {
        return $this->config;
    }

    /**
     * @param OperationConfig $config
     */
    public function setConfig($config) {
        $this->config = $config;
    }

}