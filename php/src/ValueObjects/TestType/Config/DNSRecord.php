<?php

namespace ResolverTest\ValueObjects\TestType\Config;

class DNSRecord {

    /**
     * @var string
     * @required
     */
    private $prefix;

    /**
     * @var integer
     */
    private $ttl;

    /**
     * @var string
     * @required
     */
    private $type;

    /**
     * @var string
     * @required
     */
    private $data;

    /**
     * @var boolean
     */
    private $anchor;



    /**
     * @param string $prefix
     * @param int $ttl
     * @param string $type
     * @param string $data
     * @param boolean $anchor
     */
    public function __construct($prefix, $ttl, $type, $data, $anchor = false) {
        $this->prefix = $prefix;
        $this->ttl = $ttl;
        $this->type = $type;
        $this->data = $data;
        $this->anchor = $anchor;
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
        } else if (filter_var($this->data, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 + FILTER_FLAG_IPV6) || preg_match("/^[A-Z0-9_]+$/", $this->data)) {
            return $this->data;
        } else {
            return $this->data . ".";
        }
    }

    /**
     * @param string $data
     */
    public function setData($data) {
        $this->data = $data;
    }

    /**
     * @return bool
     */
    public function isAnchor() {
        return $this->anchor;
    }

    /**
     * @param bool $anchor
     */
    public function setAnchor($anchor) {
        $this->anchor = $anchor;
    }



}