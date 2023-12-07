<?php

namespace Objects\Log;

use PHPUnit\Framework\TestCase;
use ResolverTest\Objects\Log\WebserverLog;
use ResolverTest\ValueObjects\TestType\TestTypeRules;

include_once "autoloader.php";

class BaseLogTest extends TestCase {

    public function testCanGetRelationalKeyValue() {

        $log = new WebserverLog("test.com", date_create(), "1.2.3.4", "", 200);

        $this->assertEquals("test.com", $log->getRelationalKeyValue(TestTypeRules::RELATIONAL_KEY_HOSTNAME));
        $this->assertEquals("test.com", $log->getRelationalKeyValue(TestTypeRules::RELATIONAL_KEY_IP_ADDRESS));

        $log->setHostname("a.b.c.test.com");
        $this->assertEquals("a.b.c.test.com", $log->getRelationalKeyValue(TestTypeRules::RELATIONAL_KEY_HOSTNAME));
        $this->assertEquals("c.test.com", $log->getRelationalKeyValue(TestTypeRules::RELATIONAL_KEY_IP_ADDRESS));

        $log->setHostname("red.yellow.blue.test.org");
        $this->assertEquals("red.yellow.blue.test.org", $log->getRelationalKeyValue(TestTypeRules::RELATIONAL_KEY_HOSTNAME));
        $this->assertEquals("blue.test.org", $log->getRelationalKeyValue(TestTypeRules::RELATIONAL_KEY_IP_ADDRESS));
    }

}