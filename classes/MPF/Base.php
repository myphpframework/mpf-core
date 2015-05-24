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
            $this->setLogger(new Logger());
        }

        return $this->logger;
    }

}
