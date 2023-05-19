<?php

namespace ResolverTest\Exception;

class StartAfterExpiryException extends \Exception {

    public function __construct() {
        parent::__construct("The expiry time should be after the start time");
    }

}