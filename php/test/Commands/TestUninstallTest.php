<?php

namespace ResolverTest\Commands;

use Kinikit\Core\Configuration\Configuration;
use Kinikit\Core\DependencyInjection\Container;
use PHPUnit\Framework\TestCase;
use ResolverTest\Services\TestService;

include_once "autoloader.php";

class TestUninstallTest extends TestCase {

    /**
     * @var TestService
     */
    private $testService;

    /**
     * @var string
     */
    private $basePath;

    public function setUp(): void {
        $this->testService = Container::instance()->get(TestService::class);
        $this->basePath = Configuration::readParameter("storage.root") . "/tests";
        passthru("rm -rf {$this->basePath}/*");
    }

    public function testCanRemoveTestWhenCommandExecuted() {

        $command = new TestUninstall($this->testService);

        file_put_contents($this->basePath . "/someKey.json", "some content");

        $this->assertTrue(file_exists($this->basePath . "/someKey.json"));
        $command->handleCommand("someKey");
        $this->assertFalse(file_exists($this->basePath . "/someKey.json"));

    }

}