<?php

namespace ResolverTest\Exception;

class TestAlreadyExistsForDomainException extends \Exception {

    public function __construct($domainName) {
        parent::__construct("A test already exists in the specified time window for the domain name $domainName");
    }

}