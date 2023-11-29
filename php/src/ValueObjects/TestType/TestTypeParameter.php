<?php

namespace ResolverTest\ValueObjects\TestType;

class TestTypeParameter {

    /**
     * @var string
     */
    private $identifier;


    /**
     * @var boolean
     */
    private $optional;

    /**
     * @param string $identifier
     */
    public function __construct($identifier = null, $optional = false) {
        $this->identifier = $identifier;
        $this->optional = $optional;
    }


    /**
     * @return string
     */
    public function getIdentifier() {
        return $this->identifier;
    }

    /**
     * @return bool
     */
    public function getOptional() {
        return $this->optional;
    }


}