<?php

namespace ResolverTest\ValueObjects\TestType;

class TestTypeExpectedQuery {

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $value;

    /**
     * @param string $type
     * @param string $value
     */
    public function __construct($type = null, $value = null) {
        $this->type = $type;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type) {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue($value) {
        $this->value = $value;
    }
}