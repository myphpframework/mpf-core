<?php

namespace MPF\Service;

use MPF\ENV;
use MPF\Text;
use MPF\Logger;

class Bucket extends \MPF\Service {
    protected function update($id, $data) {
        $this->setResponseCode(self::HTTPCODE_NOT_IMPLEMENTED);
    }

    protected function delete($id, $data) {
        $this->setResponseCode(self::HTTPCODE_NOT_IMPLEMENTED);
    }

    protected function retrieve($id, $data) {
        $this->setResponseCode(self::HTTPCODE_NOT_IMPLEMENTED);
    }

    protected function create($data) {
        $this->setResponseCode(self::HTTPCODE_NOT_IMPLEMENTED);
    }

}
