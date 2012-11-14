<?php
namespace MPF\Text\Plugin;

class Replace extends \MPF\Text\Plugin
{
   private $priorityWeight = 100;

   public function parse($text, $replaceKeyValues)
   {
       $keys = array_keys($replaceKeyValues);
       foreach ($keys as $i => $key)
       {
           $keys[ $i ] = '@'.$key.'@';
       }
       $values = array_values($replaceKeyValues);
       return str_replace($keys, $values, $text);
   }

   public function detect($text)
   {
       return preg_match('/@[a-zA-Z0-9_]+@/', $text);
   }

   public function getPriorityWeight()
   {
       return $this->priorityWeight;
   }
}