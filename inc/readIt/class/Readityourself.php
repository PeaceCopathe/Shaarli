<?php

class Readityourself extends Readability {

    private $stripColonIntitle = false;
    private $reduceTitleLength = false;
    public $debug = false;

    /**
     * Get the article title as an H1.
     *
     * @return DOMElement
     *
     * All of the regular expressions in use within readability.
     * Defined up here so we don't instantiate them repeatedly in loops.
     * */
    public $regexps = array(
        'unlikelyCandidates' => '/list-operations-dialog|modal-body|combx|community|comment|disqus|extra|foot|header|menu|remark|rss|shoutbox|sidebar|sponsor|ad-break|agegate|pagination|pager|popup|debutDefinition_|sms_codes/i',
        'okMaybeItsACandidate' => '/tweet|permalink-tweet|js-actionable-user|js-actionable-tweet|js-original-tweet|and|post|article|body|column|main|shadow|js-tweet-text|tweet-text/i',
        'positive' => '/tweet|permalink-tweet|js-actionable-user|js-actionable-tweet|js-original-tweet|article|content|comic|body|content|entry|hentry|main|page|media|attachment|pagination|post|text|blog|story/i',
        'negative' => '/list-operations-dialog|modal-body|combx|com-|contact|comment|foot|footer|_nav|footnote|masthead|meta|outbrain|promo|related|scroll|shoutbox|sidebar|sponsor|shopping|tags|tool|widget|modal-container|sms_codes/i',
        'htmlPositiveTag' => '/content|article|media|video/',
        'htmlNegativeTag' => '/footer|header|button/',
        'divToPElements' => '/<(td|tr|a|blockquote|dl|div|img|ol|p|pre|table|ul|code)/i',
        'replaceBrs' => '/(<br[^>]*>[ \n\r\t]*){2,}/i',
        'replaceFonts' => '/<(\/?)font[^>]*>/i',
        // 'trimRe' => '/^\s+|\s+$/g', // PHP has trim()
        'normalize' => '/\s{2,}/',
        'killBreaks' => '/(<br\s*\/?>(\s|&nbsp;?)*){1,}/',
        'video' => '!//(player\.|www\.)?(youtube|vimeo|viddler)\.com!i',
        'skipFootnoteLink' => '/^\s*(\[?[a-z0-9]{1,2}\]?|^|edit|citation needed)\s*$/i'
    );

    public function setDebugMode($debug) {
        $this->debug = $debug;
    }

    /**
     * Get the article title as an H1.
     *
     * @return DOMElement
     * 
     * Fixes #1 by remove splt ior ":" in title text
     */
    protected function getArticleTitle() {
        $curTitle = '';
        $origTitle = '';

        try {
            $curTitle = $origTitle = $this->getInnerText($this->dom->getElementsByTagName('title')->item(0));
        } catch (Exception $e) {
            
        }

        if (preg_match('/ [\|\-] /', $curTitle)) {
            $curTitle = preg_replace('/(.*)[\|\-] .*/i', '$1', $origTitle);

            if (count(explode(' ', $curTitle)) < 3) {
                $curTitle = preg_replace('/[^\|\-]*[\|\-](.*)/i', '$1', $origTitle);
            }
        }

        // remove to correct split of title with ':' see https://github.com/memiks/readityourself/issues/1
        else if ($this->stripColonIntitle == true && strpos($curTitle, ': ') !== false) {
            $curTitle = preg_replace('/.*:(.*)/i', '$1', $origTitle);

            if (count(explode(' ', $curTitle)) < 3) {
                $curTitle = preg_replace('/[^:]*[:](.*)/i', '$1', $origTitle);
            }
        } else if ($this->reduceTitleLength == true && ( strlen($curTitle) > 150 || strlen($curTitle) < 15)) {
            $hOnes = $this->dom->getElementsByTagName('h1');
            if ($hOnes->length == 1) {
                $curTitle = $this->getInnerText($hOnes->item(0));
            }
        }

        $curTitle = trim($curTitle);

        if (count(explode(' ', $curTitle)) <= 4) {
            $curTitle = $origTitle;
        }

        $articleTitle = $this->dom->createElement('h1');
        $articleTitle->innerHTML = $curTitle;

        return $articleTitle;
    }

    /**
     * Get an elements class/id weight. Uses regular expressions to tell if this 
     * element looks good or bad.
     *
     * @param DOMElement $e
     * @return number (Integer)
     */
    public function getClassWeight($e) {
        if (!$this->flagIsActive(self::FLAG_WEIGHT_CLASSES)) {
            return 0;
        }

        $weight = 0;

        if (preg_match($this->regexps['negative'], $e->tagName)) {
            $weight -= 250;
        }
        if (preg_match($this->regexps['positive'], $e->tagName)) {
            $weight += 250;
        }

        /* Look for a special ID */
        if ($e->hasAttribute('id') && $e->getAttribute('id') != '') {
            if (preg_match($this->regexps['negative'], $e->getAttribute('id'))) {
                $weight -= 150;
            }
            if (preg_match($this->regexps['positive'], $e->getAttribute('id'))) {
                $weight += 150;
            }
        }

        /* Look for a special classname */
        if ($e->hasAttribute('class') && $e->getAttribute('class') != '') {
            $classes = explode(" ", $e->getAttribute('class'));

            foreach ($classes as $class) {
                if (preg_match($this->regexps['negative'], $class)) {
                    $weight -= 25;
                }
                if (preg_match($this->regexps['positive'], $class)) {
                    $weight += 25;
                }
            }
        }

        return $weight;
    }

}
