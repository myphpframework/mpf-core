<?php

namespace MPF;

use MPF\Log\Logger;

class Base
{
    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $logger = null;
 
    /**
     * Sets the logger.
     *
     * @param \Psr\Log\LoggerInterface $logger
     *
     * @return void
     */
    public function setLogger(\Psr\Log\LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Returns the Logger object.
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger()
    {
        if (null === $this->logger) {
            $this->setLogger(new Logger(
                Config::get('settings')->logger->level, 
                Config::get('settings')->logger->category, 
                Config::get('settings')->logger->storage
            ));
        }

        return $this->logger;
    }

}
