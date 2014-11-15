<?php

namespace MPF\Text\Plugin;

class BBCode extends \MPF\Text\Plugin
{

    private $priorityWeight = 200;
    private static $bbcode = array(
        '[p]', '[/p]',
        '[h1]', '[/h1]', '[h2]', '[/h2]', '[h3]', '[/h3]', '[h4]', '[/h4]', '[h5]', '[/h5]',
        '[hgroup]', '[/hgroup]',
        '[list]', '[*]', '[/*]', '[/list]',
        '[img]', '[/img]',
        '[label]', '[/label]',
        '[b]', '[/b]',
        '[strong]', '[/strong]',
        '[u]', '[/u]',
        '[i]', '[/i]',
        '[em]', '[/em]',
        '[class="', '[/class]',
        '[url="', '[/url]',
        '[a title="', '[/a]',
        '[mail="', '[/mail]',
        '[code]', '[/code]',
        '[quote]', '[/quote]',
        '[header]', '[/header]',
        '[footer]', '[/footer]',
        '[article]', '[/article]',
        '[small]', '[/small]',
        '[time]', '[/time]',
        '[summary]', '[/summary]',
        '[section]', '[/section]',
        '[div]', '[/div]',
        '[span]', '[/span]',
        '[aside]', '[/aside]',
        '[br]',
        '"]');
    private static $htmlcode = array(
        '<p>', '</p>',
        '<h1>', '</h1>', '<h2>', '</h2>', '<h3>', '</h3>', '<h4>', '</h4>', '<h5>', '</h5>',
        '<hgroup>', '</hgroup>',
        "<ul>\n", '<li>', "</li>\n", "</ul>\n",
        '<img src="', '" alt="BBCode img" />',
        '<label>', '</label>',
        '<b>', '</b>',
        '<strong>', '</strong>',
        '<span style="text-decoration: underline">', '</span>',
        '<i>', '</i>',
        '<em>', '</em>',
        '<span class="', '</span>',
        '<a href="', '</a>',
        '<a title="', '</a>',
        '<a href="mailto:', '</a>',
        '<code>', '</code>',
        '<blockquote>', '</blockquote>',
        '<header>', '</header>',
        '<footer>', '</footer>',
        '<article>', '</article>',
        '<small>', '</small>',
        '<time>', '</time>',
        '<summary>', '</summary>',
        '<section>', '</section>',
        '<div>', '</div>',
        '<span>', '</span>',
        '<aside>', '</aside>',
        '<br />',
        '">');

    public function parse($text, $args)
    {
        // we do the more "complex" ones with regexp first
        $text = $this->parseComplexTags($text);

        // The simple bbcode replace needs to be done after the complex ones
        // because it replaces all "] by "> which makes the complex ones unmatched
        $text = str_ireplace(self::$bbcode, self::$htmlcode, $text);

        return $text;
    }

    /**
     * Parses the more complex BBCode tags.
     *
     * @param string $text
     * @return string
     */
    private function parseComplexTags($text)
    {
        preg_match_all("/\[([a-z0-9]+)(.*?)\](.*?)\[\/\\1\]/is", $text, $matches);
        if (0 < count($matches[0])) {
            foreach ($matches[1] as $index => $match) {
                $replacement = null;
                $action = trim(strtolower($match));

                // if there is embed tags within the match we try to parse them
                if (false !== strpos($matches[3][$index], '[') && false !== strpos($matches[3][$index], ']')) {
                    $replacement = $this->parseComplexTags($matches[3][$index]);

                    // If we had a change
                    if ($matches[3][$index] != $replacement) {
                        $matches[3][$index] = $replacement;
                        $text = str_replace($matches[0][$index], $replacement, $text);
                    }
                }

                switch ($action) {
                    case 'date':
                        $text = str_replace($matches[0][$index], date('Y-m-d', strtotime($matches[3][$index])), $text);
                        break;
                    case 'div':
                    case 'span':
                        $text = str_replace($matches[0][$index], '<' . $action . $matches[2][$index] . '>' . $matches[3][$index] . '</' . $action . '>', $text);
                        break;
                }
            }
        }

        return $text;
    }

    public function detect($text)
    {
        return preg_match('/\[[a-z0-9 \=\"\']+/', $text);
    }

    public function getPriorityWeight()
    {
        return $this->priorityWeight;
    }

}
