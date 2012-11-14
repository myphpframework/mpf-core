<?php
namespace MPF\Db\Connection;

interface Intheface
{
    /**
     * Determines if we have everything we need to be able to connect to the
     * database
     *
     * @return bool
     */
    public function isInfoValid();
    
    /**
     * Tries to connect to the database and sets the resource if successfull
     * 
     * @return bool
     */
    public function connect();

    /**
     * Closes the connection with the database if need be.
     */
    public function disconnect();
}