<?php

namespace MPF\Bootstrap;

use MPF\ENV;
use MPF\Logger;

class Session extends \MPF\Bootstrap implements Intheface {

    public function init($args=array()) {
        ENV::bootstrap(ENV::DATABASE);

        ini_set('session.cookie_domain', '.'.filter_input(\INPUT_SERVER, 'SERVER_NAME', FILTER_SANITIZE_STRING));
        ini_set('session.cookie_path', '/');
        ini_set('session.cookie_lifetime', (time() + (60 * 15)));

        session_start();
        $this->initialized = true;
    }

    public function shutdown() {
        Logger::Log('ENV/Boostrap/Session', 'shutting down session', Logger::LEVEL_DEBUG, Logger::CATEGORY_FRAMEWORK | Logger::CATEGORY_ENVIRONMENT);
        session_write_close();
    }

}
