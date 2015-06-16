<?php

namespace MPF\Bootstrap;

use MPF\ENV;
use MPF\Log\Category;

class Session extends \MPF\Bootstrap implements Intheface
{

    public function init($args = array())
    {
        ENV::bootstrap(ENV::DATABASE);

        ini_set('session.cookie_domain', SESSION_COOKIE_DOMAIN);
        ini_set('session.cookie_path', SESSION_COOKIE_PATH);
        ini_set('session.cookie_lifetime', (time() + (60 * 15)));
        
        if (array_key_exists('token', $args) && $args['token']) {
            session_id($args['token']);
        }
        @session_start();
        $this->initialized = true;
    }

    public function shutdown()
    {
        $this->getLogger()->info('Shutting down session', array(
            'category' => Category::FRAMEWORK | Category::ENVIRONMENT, 
            'className' => 'ENV/Boostrap/Session'
        ));

        @session_write_close();
        //session_regenerate_id(FALSE);
        @session_unset();
        $_SESSION = array();
        @session_commit();
    }

}
