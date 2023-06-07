<?php

namespace ResolverTest\Exception;

class InvalidTestKeyException extends \Exception {

    public function __construct($key) {
        parent::__construct("$key is not a valid key");
    }

}