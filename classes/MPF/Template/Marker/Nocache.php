<?php
namespace MPF\Template\Marker;
use MPF\Template;

/*
 * TODO: MARKER_CACHE: add a expiry feature. Goes in INIT? so it'll fetch catch first or RECREATE if expiry is now. init() is now called in constructor and after the loaded cached in Template->rawTemplate, just put it to ''/null if it expired..
 **/
class Nocache extends \MPF\Template\Marker
{
   public function init(Template $template, &$rawTemplate)
   {
   }

   public function execute(Template $template, &$templateOutput, &$templateCache)
   {
       preg_match('#\{([\w]+).*?\}#is', $this->getMarker(), $matchs);
       $templateOutput = preg_replace('#\{'.$matchs[1].'.*?\}#s', '', $templateOutput);
       $templateOutput = str_replace('{/'.$matchs[1].'}', '', $templateOutput);

       // We put back what was originally between the cache wrapper, since we do not cache it
       $templateCache = preg_replace('#\{('.$matchs[1].').*?\}.*?\{/\\1\}#s', $this->getMarker(), $templateCache);

       // if we have a parent we must return the cache, only way it'll make it back to the parent thus its cache?
       if ($template->parent)
       {
          $templateOutput = $templateCache;

          // We must add this marker to the parent's markers so it can handle the cache tag we are returning to it
          $template->parent->markers[] = $this;
       }
   }

}
