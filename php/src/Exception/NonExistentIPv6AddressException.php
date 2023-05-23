<?php

namespace ResolverTest\Exception;

class NonExistentIPv6AddressException extends \Exception {

    public function __construct() {
        parent::__construct("No IPv6 address in the config");
    }
}