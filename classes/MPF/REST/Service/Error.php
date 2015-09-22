<?php

namespace MPF\REST\Service;

class Error extends \MPF\REST\Service
{

    protected function options($id, $action)
    {
        $this->setResponseCode(self::HTTPCODE_NOT_IMPLEMENTED);
        return array();
    }

    protected function update($id, $data)
    {
        $this->setResponseCode(self::HTTPCODE_NOT_IMPLEMENTED);
        return array();
    }

    protected function create($id, $data)
    {
        $this->setResponseCode(self::HTTPCODE_NOT_IMPLEMENTED);
        return array();
    }

    protected function delete($id, $data)
    {
        $this->setResponseCode(self::HTTPCODE_NOT_IMPLEMENTED);
        return array();
    }

    protected function retrieve($id, $data)
    {
        $this->setResponseCode(self::HTTPCODE_NOT_IMPLEMENTED);
        return array();
    }

}
