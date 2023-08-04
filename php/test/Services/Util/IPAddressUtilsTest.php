<?php

namespace ResolverTest\Services\Util;

use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class IPAddressUtilsTest extends TestCase {

    public function testCanAnonymiseIPv4Addresses() {

        $ipUtils = new IPAddressUtils();

        $this->assertEquals("192.168.1.0", $ipUtils::anonymiseIP("192.168.1.10"));
        $this->assertEquals("255.255.255.0", $ipUtils::anonymiseIP("255.255.255.255"));
        $this->assertEquals("12.34.56.0", $ipUtils::anonymiseIP("12.34.56.78"));
        $this->assertEquals("1.0.0.0", $ipUtils::anonymiseIP("1.0.0.0"));

    }

    public function testCanAnonymiseIPv6Addresses() {

        $ipUtils = new IPAddressUtils();

        $this->assertEquals("2001:FFFF:FFFF::", $ipUtils::anonymiseIP("2001:FFFF:FFFF:FFFF:FFFF:FFFF:FFFF:FFFF"));
        $this->assertEquals("1234:5678:90AB::", $ipUtils::anonymiseIP("1234:5678:90AB:CDEF:FEDC:BA09:8765:4321"));
        $this->assertEquals("2001:1F:2E::", $ipUtils::anonymiseIP("2001:1F:2E:3D:4C:5B:6A:70"));
        $this->assertEquals("2001:AB1:CD2::", $ipUtils::anonymiseIP("2001:AB1:CD2:EF3:FE4:DC5:BA6:1234"));

    }

}