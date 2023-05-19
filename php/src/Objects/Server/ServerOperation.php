<?php

namespace ResolverTest\Objects\Server;

class ServerOperation {

    /**
     * @var string
     */
    private $mode;

    /**
     * @var mixed
     */
    private $object;

    const OPERATION_ADD = "add";
    const OPERATION_REMOVE = "remove";

    /**
     * @param string $mode
     * @param mixed $object
     */
    public function __construct($mode, $object) {
        $this->mode = $mode;
        $this->object = $object;
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
     * @return mixed
     */
    public function getObject() {
        return $this->object;
    }

    /**
     * @param mixed $object
     */
    public function setObject($object) {
        $this->object = $object;
    }

}