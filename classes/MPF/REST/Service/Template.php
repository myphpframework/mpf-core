<?php

namespace MPF\REST\Service;

class Template extends \MPF\REST\Service {
    protected function options($id, $action) {
        $this->setResponseCode(self::HTTPCODE_OK);

        $options = '';
        header('Allow: '.$options);
    }

    protected function update($id, $data) {
        $this->setResponseCode(self::HTTPCODE_NOT_IMPLEMENTED);
    }

    protected function create($id, $data) {
        $this->setResponseCode(self::HTTPCODE_NOT_IMPLEMENTED);
    }

    protected function delete($id) {
        $this->setResponseCode(self::HTTPCODE_NOT_IMPLEMENTED);
    }

    protected function retrieve($id, $data) {
        $this->setResponseCode(self::HTTPCODE_OK);
        return \MPF\Template::getFile(str_replace('::', '/', $id))->parse();
    }
}
