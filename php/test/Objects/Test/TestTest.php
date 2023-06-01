<?php

namespace ResolverTest\Objects\Test;

use PHPUnit\Framework\TestCase;
use ResolverTest\Exception\InvalidDateFormatException;
use ResolverTest\Exception\InvalidTestTypeException;
use ResolverTest\Exception\StartAfterExpiryException;

include_once "autoloader.php";

class TestTest extends TestCase {

    /**
     * @doesNotPerformAssertions
     */
    public function testCanValidateStartAndExpiryAreOfCorrectFormatAndOrder() {

        $date = (new \DateTime())->add(new \DateInterval("P1M"));
        $date2 = (new \DateTime())->add(new \DateInterval("P2M"));

        // Bad start format
        $test1 = new Test("test1", "type", "1.com", null, "bad");
        $test2 = new Test("test2", "type", "2.com", null, "4-6-2022");

        // Bad expiry format
        $test3 = new Test("test3", "type", "3.com", null, $date->format("Y-m-d H:i:s"), "wrong");
        $test4 = new Test("test4", "type", "4.com", null, $date->format("Y-m-d H:i:s"), "2023-04-21");

        // Expires before starts
        $test5 = new Test("test5", "type", "5.com", null, $date2->format("Y-m-d H:i:s"), $date->format("Y-m-d H:i:s"));


        try {
            $test1->validate();
            $this->fail("Should have thrown here");
        } catch (InvalidDateFormatException $e) {
            // Great
        }

        try {
            $test2->validate();
            $this->fail("Should have thrown here");
        } catch (InvalidDateFormatException $e) {
            // Great
        }

        try {
            $test3->validate();
            $this->fail("Should have thrown here");
        } catch (InvalidDateFormatException $e) {
            // Great
        }

        try {
            $test4->validate();
            $this->fail("Should have thrown here");
        } catch (InvalidDateFormatException $e) {
            // Great
        }

        try {
            $test5->validate();
            $this->fail("Should have thrown here");
        } catch (StartAfterExpiryException $e) {
            // Great
        }
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testCanValidateTheTestTypeExists() {

        $test = new Test("name", "Nonsense", "1.com");

        try {
            $test->validate();
            $this->fail();
        } catch (InvalidTestTypeException $e) {
            // Great
        }

    }

}