<?php
namespace MPF\Text\Exception;
use MPF\Text;

class IdNotFound extends \Exception
{
    public function __construct($id, $ids)
    {
        $ids = implode(",", $ids)."\n";
        parent::__construct(Text::byXml('mpf_exception')->get('textIdNotFound', array('Replace' => array(
            'id' => $id,
            'availableIds' => $ids,
        ))));
    }
}