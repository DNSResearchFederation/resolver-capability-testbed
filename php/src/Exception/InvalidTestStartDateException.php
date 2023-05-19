<?php

namespace ResolverTest\Exception;

class InvalidTestStartDateException extends \Exception {

    public function __construct() {
        parent::__construct("The start time supplied is in the past");
    }

}