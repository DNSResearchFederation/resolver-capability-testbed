<?php

namespace ResolverTest\Exception;

class NonExistentTestException extends \Exception {

    public function __construct($key) {
        parent::__construct("There doesn't exist a test with the key $key");
    }

}