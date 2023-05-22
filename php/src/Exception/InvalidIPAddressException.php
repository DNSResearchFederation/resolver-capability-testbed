<?php

namespace ResolverTest\Exception;

class InvalidIPAddressException extends \Exception {

    public function __construct() {
        parent::__construct("The IP Address is of a bad format");
    }

}