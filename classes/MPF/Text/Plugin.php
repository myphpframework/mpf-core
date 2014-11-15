<?php

namespace MPF\Text;

abstract class Plugin implements Plugin\Intheface
{

    protected $name = '';

    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Name of the plugin, as far as I know its specialy used for
     * looking up the right arguments for the right plugin
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

}
