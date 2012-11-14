<?php
namespace MPF;

class Email
{
    /**
     * @static
     * @param $string
     * @return \MPF\Email
     */
    public static function byString($string)
    {
        return new Email($string);
    }

    protected $email = '';

    private function __construct($string)
    {
        $this->email = $string;
    }

    /**
     * Returns the domain part of the email
     *
     * @return string
     */
    public function getDomain()
    {
        return substr($this->email, strpos($this->email, '@'));
    }

    /**
     * Returns the username (first part) of the email
     *
     * @return string
     */
    public function getUserName()
    {
        return substr($this->email, 0, strpos($this->email, '@'));
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->email;
    }
}
