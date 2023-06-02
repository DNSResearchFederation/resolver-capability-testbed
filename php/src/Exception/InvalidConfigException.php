<?php

namespace ResolverTest\Exception;

class InvalidConfigException extends \Exception {

    public function __construct() {
        parent::__construct("The config is invalid/incomplete");
    }

}