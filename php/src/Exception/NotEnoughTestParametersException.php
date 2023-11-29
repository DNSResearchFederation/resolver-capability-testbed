<?php

namespace ResolverTest\Exception;

class NotEnoughTestParametersException extends \Exception {

    public function __construct($testType, $missingParameterKeys = []) {
        parent::__construct("The parameters " . join(", ", $missingParameterKeys) . " were not supplied for test of type $testType but were required");
    }

}