<?php

namespace \MPF\Finance\Currency;

class Intheface {
    /**
     * @throws \MPF\Finance\Exception\MissingAPIKey
     */
    abstract public static function updateDb();
}
