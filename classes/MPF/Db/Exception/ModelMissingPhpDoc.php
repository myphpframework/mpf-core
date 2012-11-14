<?php
namespace MPF\Db\Exception;
use MPF\Text;

class ModelMissingPhpDoc extends \Exception
{
    public function  __construct($modelName, $phpdoc)
    {
        parent::__construct(Text::byXml('mpf_exception')->get('dbModelMissingPhpDoc', array('Replace' => array('phpdoc' => $phpdoc, 'modelName' => $modelName))));
    }
}