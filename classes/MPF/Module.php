<?php

namespace \MPF;

abstract class Module {
    abstract public function install();
    abstract public function uninstall();

    protected $config;

    public function __construct(stdClass $json) {
        $this->config = $json;
    }
}