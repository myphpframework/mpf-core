<?php

namespace MPF\Template\Marker;

use MPF\Template;

interface Intheface
{

    /**
     * This function is called before the template's php code executed
     *
     * NOTE: $rawTemplate is the code from the .phtml, or cached template. Thus only the Markers that have a tag in a raw.phtml or in a cached template will have their ->init() called. This happens in the __constructor of the template. The init() function may be called twice if the cache is invalidated by another marker then the markers are re-initiated on the rawTemplate
     *
     * @param Template $template
     * @param string $rawTemplate
     */
    public function init(Template $template, &$rawTemplate);

    /**
     * This function is called after the template's php code executed
     *
     * NOTE: $templateOutput and $templateCache are equals at this point. Thus only the markers that have a tag after the first eval of the raw template will have their ->execute() called. This happens in the parse() of the template.
     *
     * @param Template $template
     * @param string $templateOutput
     * @param string $templateCache
     */
    public function execute(Template $template, &$templateOutput, &$templateCache);
}
