<?php
namespace MPF\Template;

abstract class Marker implements Marker\Intheface
{
   protected $args = array();
   protected $marker = '';
   protected $content = '';

   /**
    * Makes sure the marker marker doesnt already exists,
    * it will replace all instances of that marker anyway, no need to run it twice
    *
    * @param string $marker
    * @param Marker[] $markers
    * @return boolean
    */
   public static function isMarkerUnique($marker, $markers)
   {
      $isUnique = true;
      foreach ($markers as $marker)
      {
         if (($marker instanceof Marker\Intheface) && $marker->getMarker() == $marker)
         {
            $isUnique = false;
         }
      }
      return $isUnique;
   }

   /**
    *
    * @param string $marker
    * @param string $rawArgs
    * @param string $content // If the marker as a closing tag this will hold the contents between the opening and closing tags
    */
   public function __construct($marker, $rawArgs, $content='')
   {
      $this->marker = $marker;
      $this->args = $this->parseArgs($rawArgs);
      $this->content = $content;
   }

   /**
    * The marker that resided within the template that defined this marker
    *
    * @return string
    */
   public function getMarker()
   {
      return $this->marker;
   }

   /**
    * Verifies if the current marker is in the array
    *
    * @param Marker[] $markers
    * @return boolean
    */
   public function inArray($markers)
   {
       foreach ($markers as $marker)
       {
           if (($marker instanceof Marker\Intheface) && $marker->getMarker() == $this->getMarker())
           {
               return true;
           }
       }
       return false;
   }
   /**
    * Parses the raw arguments of the template
    *
    * @param string $rawArgs
    * @return array
    */
   protected function parseArgs($rawArgs)
   {
      $args = array();
      foreach (@explode(' ', $rawArgs) as $arg)
      {
         if ($arg)
         {
             @list($name, $value) = @explode('=', $arg);
             $args[ $name ] = (!$value ? true : str_replace("'", "", str_replace('"', '', $value)));
         }
      }
      return $args;
   }
}
