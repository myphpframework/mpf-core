<?php
namespace MPF\Exception\Config;
use MPF\Text;

class EnvironmentNotFound extends \Exception
{
    public function  __construct()
    {
        parent::__construct(Text::byXml('mpf_exception')->get('configUnknownEnvironment', array('Replace' => array('mpf_env' => $_SERVER['MPF_ENV']))));
    }
}