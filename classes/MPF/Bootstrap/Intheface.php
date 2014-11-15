<?php

namespace MPF\Bootstrap;

interface Intheface
{

    public function init($args = array());

    public function isInitialized();

    public function shutdown();
}
