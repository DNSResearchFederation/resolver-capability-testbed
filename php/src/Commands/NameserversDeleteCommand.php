<?php

namespace ResolverTest\Commands;

use ResolverTest\Framework\BaseNameserverCommand;

/**
 * @name nameservers-delete
 * @description Delete a set of nameservers
 */
class NameserversDeleteCommand extends BaseNameserverCommand {

    /**
     * @param $key @argument The key of the nameserver set to delete
     *
     * @return void
     */
    public function handleCommand($key) {

        $this->nameserverConfig->deleteNameservers($key);

    }

}