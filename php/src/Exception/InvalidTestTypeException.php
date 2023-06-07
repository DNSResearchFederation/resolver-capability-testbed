<?php

namespace ResolverTest\Exception;

class InvalidTestTypeException extends \Exception {

    public function __construct($key) {
        parent::__construct("$key is not a valid test. Please use one of:\nipv6\nqname-minimisation");
    }

}