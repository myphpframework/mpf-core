<?php
namespace MPF\Db\Exception;
use MPF\Text;

class ModelMissingStatuses extends \Exception
{
    public function  __construct()
    {
        parent::__construct(Text::byXml('mpf_exception')->get('dbModelMissingStatuses'));
    }
}