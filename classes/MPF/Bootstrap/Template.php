<?php

namespace MPF\Bootstrap;

use MPF\Log\Category;
use MPF\Config;

require_once(__DIR__ . '/../Template.php');

class Template extends \MPF\Bootstrap implements Intheface
{

    public function init($args = array())
    {
        $this->initialized = true;

        header('x-content-type-options: nosniff');
        header('x-xss-protection: 1;mode=block');
        header('x-xss-protected: nosniff');
        header('x-frame-options: SAMEORIGIN');
        header('x-ua-compatible: IE=edge,chrome=1');

        if (Config::get('settings')->template->cache->enabled && !$this->checkDir(Config::get('settings')->template->cache->dir)) {
            $exception = new \MPF\Exception\FolderNotWritable(Config::get('settings')->template->cache->dir);

            $this->getLogger()->emergency($exception->getMessage(), array(
                'category' => Category::FRAMEWORK | Category::TEMPLATE, 
                'className' => 'Boostrap/Template',
                'exception' => $exception
            ));
            throw $exception;
        }

        ob_start();
        
        $this->getLogger()->info('Template system initialized, ob_start()', array(
            'category' => Category::FRAMEWORK | Category::TEMPLATE, 
            'className' => 'Boostrap/Template'
        ));

        set_error_handler(array('MPF\Template', 'errorHandler'), E_USER_ERROR | E_USER_NOTICE | E_USER_WARNING);
    }

    public function shutdown()
    {
        $this->getLogger()->info('Template system initialized, ob_start()', array(
            'category' => Category::FRAMEWORK | Category::TEMPLATE, 
            'className' => 'Boostrap/Template'
        ));
    }

}
