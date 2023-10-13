<?php

namespace ResolverTest\Commands;

use ResolverTest\Framework\BaseNameserverCommand;

/**
 * @name nameservers-show
 * @description Show the sets of nameservers
 */
class NameserversShowCommand  extends BaseNameserverCommand {

    /**
     * @return void
     */
    public function handleCommand() {

        foreach ($this->nameserverConfig->getNameservers() as $key => $value) {
            print($key . ": " . implode(", ", $value) . "\n");
        }

    }

}