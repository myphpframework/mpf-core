<?php

namespace MPF\Service;

class Text extends \MPF\Service {
    protected function update($id, $data) {
        $this->setResponseCode(self::HTTPCODE_NOT_IMPLEMENTED);
    }
    
    protected function create($data) {
        $this->setResponseCode(self::HTTPCODE_NOT_IMPLEMENTED);
    }

    protected function delete($id, $data) {
        $this->setResponseCode(self::HTTPCODE_NOT_IMPLEMENTED);
    }

    protected function retrieve($id, $data) {
        $this->setResponseCode(self::HTTPCODE_OK);
        echo \MPF\Text::byXml($id)->toJson();
    }
}
