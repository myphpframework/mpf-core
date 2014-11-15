<?php

namespace MPF;

// TODO: all bootstraps should be able to emit the shutdown event. ENV::bootstrap(ENV::DATABASE)->on('shutdown', function (){});

/**
 * Represent a table structure in the database
 */
class Bootstrap
{

    protected $initialized = false;

    /**
     * Checks if the system has already been initialized
     *
     * @return bool
     */
    public function isInitialized()
    {
        return $this->initialized;
    }

    /**
     * Verifies if a directory exists and is writable.
     * Tries to create it if it does not.
     *
     * @param  string $dir
     * @return bool
     */
    protected function checkDir($dir)
    {
        if ($dir && (!is_dir($dir) || !is_writable($dir))) {
            if (!@mkdir($dir, 0775, true)) {
                return false;
            }
        }

        return true;
    }

}
