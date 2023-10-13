<?php

namespace ResolverTest\Commands;

use ResolverTest\Framework\BaseNameserverCommand;

/**
 * @name nameservers-add
 * @description Add a set of nameservers
 */
class NameserversAddCommand extends BaseNameserverCommand {

    /**
     * @param $key @argument The key for the nameserver set
     * @param ...$nameservers @argument The nameservers for the set
     *
     * @return void
     */
    public function handleCommand($key, ...$nameservers) {

        $this->nameserverConfig->setNameservers($key, $nameservers);

    }

}