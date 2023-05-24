<?php

namespace ResolverTest\Objects\Server\Config;

use Kinikit\CLI\Commands\Pull;

class DNSRecord {

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var integer
     */
    private $ttl;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $data;

    /**
     * @param string $prefix
     * @param int $ttl
     * @param string $type
     * @param string $data
     */
    public function __construct($prefix, $ttl, $type, $data) {
        $this->prefix = $prefix;
        $this->ttl = $ttl;
        $this->type = $type;
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getPrefix() {
        return $this->prefix;
    }

    /**
     * @param string $prefix
     */
    public function setPrefix($prefix) {
        $this->prefix = $prefix;
    }

    /**
     * @return int
     */
    public function getTtl() {
        return $this->ttl;
    }

    /**
     * @param int $ttl
     */
    public function setTtl($ttl) {
        $this->ttl = $ttl;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type) {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getData() {
        if ($this->getType() == "TXT") {
            return "\"{$this->data}\"";
        } else if (preg_match("/[a-z]/", $this->data)) {
            return $this->data . ".";
        } else {
            return $this->data;
        }
    }

    /**
     * @param string $data
     */
    public function setData($data) {
        $this->data = $data;
    }

}