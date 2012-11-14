<?php

namespace MPF\Bootstrap;

use MPF\Logger;
use MPF\Config;

require_once(__DIR__ . '/../Template.php');

class Template extends \MPF\Bootstrap implements Intheface {

    public function init($args = array()) {
        $this->initialized = true;

        header('x-content-type-options: nosniff');
        header('x-xss-protection: 1;mode=block');
        header('x-xss-protected: nosniff');
        header('x-frame-options: SAMEORIGIN');
        header('x-ua-compatible: IE=edge,chrome=1');

        if (Config::get('settings')->template->cache->enabled && !$this->checkDir(Config::get('settings')->template->cache->dir)) {
            // TODO: need custom exception with multi-language message
            $exception = new \Exception('Cache dir "' . Config::get('settings')->template->cache->dir . '" is not writable');
            Logger::Log('Bootstrap/Template', $exception->getMessage(), Logger::LEVEL_FATAL, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_TEMPLATE);
            throw $exception;
        }

        if (CONFIG_CACHE && !$this->checkDir(CONFIG_CACHE_PATH)) {
            // TODO: need custom exception with multi-language message
            $exception = new \Exception('Cache dir "' . Config::get('settings')->template->cache->dir . '" is not writable');
            Logger::Log('Bootstrap/Template', $exception->getMessage(), Logger::LEVEL_FATAL, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_TEMPLATE);
            throw $exception;
        }

        ob_start();
        Logger::Log('Bootstrap/Template', 'Template system initialized and thus ob_start()', Logger::LEVEL_INFO, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_TEMPLATE);

        set_error_handler(array('MPF\Template', 'errorHandler'), E_USER_ERROR | E_USER_NOTICE | E_USER_WARNING);
    }

    public function shutdown() {
        Logger::Log('ENV/Boostrap/Template', 'shutting down template', Logger::LEVEL_DEBUG, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_ENVIRONMENT);
    }

}
