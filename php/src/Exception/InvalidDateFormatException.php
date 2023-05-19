<?php

namespace ResolverTest\Exception;

class InvalidDateFormatException extends \Exception {

    public function __construct() {
        parent::__construct("Please ensure dates are of the format 'Y-m-d H:i:s'");
    }

}