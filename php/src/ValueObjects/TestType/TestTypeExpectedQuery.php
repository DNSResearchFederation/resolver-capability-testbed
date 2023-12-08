<?php

namespace ResolverTest\ValueObjects\TestType;

class TestTypeExpectedQuery {

    /**
     * @var string
     * @required
     */
    private $type;

    /**
     * @var string
     */
    private $value;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var bool
     */
    private $absent;


    /**
     * @var bool
     */
    private $anchor;

    /**
     * @param string $type
     * @param string $value
     * @param bool $absent
     */
    public function __construct($type = null, $value = null, $prefix = null, $absent = false, $anchor = false) {
        $this->type = $type;
        $this->value = $value;
        $this->prefix = $prefix;
        $this->absent = $absent;
        $this->anchor = $anchor;
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

    public function getPrefix() {
        return $this->prefix;
    }

    public function setPrefix($prefix) {
        $this->prefix = $prefix;
    }

    /**
     * @return bool
     */
    public function isAbsent() {
        return $this->absent;
    }

    /**
     * @param bool $absent
     */
    public function setAbsent($absent) {
        $this->absent = $absent;
    }

    public function isAnchor() {
        return $this->anchor;
    }

    public function setAnchor($anchor) {
        $this->anchor = $anchor;
    }

}