<?php

namespace MPF;

use MPF\Locale;

class Session {

    /**
     * Returns the locale of the session
     *
     * @return Locale
     */
    public static function getLocale() {
        if (!array_key_exists('mpf_locale', $_COOKIE)) {
            return new Locale('en_CA');
        }

        return new Locale($_COOKIE['mpf_locale']);
    }

    /**
     * Returns the user for the session if any
     *
     * @return \MPF\User
     */
    public static function getUser() {
        return \MPF\User::bySession();
    }

    /**
     * Returns a variable in the session
     *
     * @param type $varName
     * @return mixed
     */
    public static function get($varName) {
        if (!array_key_exists($varName, (array)$_SESSION)) {
            return null;
        }
        return $_SESSION[ $varName ];
    }

    /**
     * Makes sure there is a user logged in,
     * if not it redirects to the given path.
     *
     * Preferably the login path
     * @return \MPF\User
     */
    public static function mustBeLoggedIn($loginPath) {
        $user = Session::getUser();
        if (!$user) {
            header('Location: '. $loginPath);
            exit;
        }

        return Session::getUser();
    }

    /**
     * Destroy the session
     */
    public static function destroy() {
        unset($_SESSION['userId']);
        session_write_close();
        session_unset();
        $_SESSION = array();
    }
}