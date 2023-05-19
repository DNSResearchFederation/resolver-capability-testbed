<?php

namespace ResolverTest\Exception;

class InvalidTestKeyException extends \Exception {

    public function __construct($key) {
        parent::__construct("There already exists a test with key $key");
    }

}