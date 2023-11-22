<?php

namespace ResolverTest\ValueObjects\TestType;

class TestTypeParameter {

    /**
     * @var string
     */
    private $identifier;

    /**
     * @param string $identifier
     */
    public function __construct($identifier = null) {
        $this->identifier = $identifier;
    }


    /**
     * @return string
     */
    public function getIdentifier() {
        return $this->identifier;
    }


}