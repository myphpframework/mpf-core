<?php

namespace MPF;

use MPF\ENV;

class Locale
{

    /**
     *  The locale
     *
     * @var string
     */
    protected $code = '';

    /**
     * Returns the locale of the environement
     *
     * @return Locale
     */
    public static function byENV()
    {
        return ENV::getLocale();
    }

    public function __construct($locale)
    {
        $this->code = $locale;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function __toString()
    {
        return $this->getCode();
    }

    /**
     * Returns the language
     *
     * @return string
     */
    public function getLanguageCode()
    {
        list($languageCode, $countryCode) = explode('_', $this->getCode());
        return $languageCode;
    }

    /**
     * Returns the country
     *
     * @return string
     */
    public function getCountryCode()
    {
        list($languageCode, $countryCode) = explode('_', $this->getCode());
        return $countryCode;
    }

}
