<?php

/**
 * Tags plugin for Pico CMS (@see https://github.com/picocms/Pico)
 *
 * Using this plugin, you can use the "Tags" and "Filter" headers in the page meta block
 * in order to modify the "pages" array for certain pages. This creates the possibility
 * to feature index pages which show only posts of a certain type.
 *
 * The "Tags" header accepts a comma-separated list of tags that apply to the current page.
 *
 * The "Filter" header also accepts a comma-separated list of tags, but instead specifies
 * which pages end up in the "pages" array. A page with no "Filter" header will have an
 * unfiltered list of pages, whereas a page that specifies the header "Filter: foo, bar"
 * will receive in its "pages" array only pages having at least one of those two tags.
 *
 * @author Pontus Horn
 * @link https://pontushorn.me
 * @repository https://github.com/PontusHorn/Pico-Tags
 * @license http://opensource.org/licenses/MIT
 */

class PicoTags extends AbstractPicoPlugin
{

    public $filtered_pages = array();
    public $page_tags = array();

    /**
     * Register the "Tags" and "Filter" meta header fields.
     *
     * @see    Pico::getMetaHeaders()
     * @param  array<string> &$headers list of known meta header fields
     * @return void
     */
    public function onMetaHeaders(&$headers)
    {
        $headers['tags'] = 'Tags';
        $headers['filter'] = 'Filter';
    }

    /**
     * Parse the current page's tags and/or filters into arrays.
     *
     * @see    Pico::getFileMeta()
     * @param  array &$meta parsed meta data
     * @return void
     */
    public function onMetaParsed(&$meta)
    {
        $meta['tags'] = PicoTags::parseTags($meta['tags']);
        $meta['filter'] = PicoTags::parseTags($meta['filter']);
    }

    /**
     * If the current page has a filter on tags, filter out the $pages array to
     * only contain pages having any of those tags.
     *
     * @see    Pico::getPages()
     * @see    Pico::getCurrentPage()
     * @see    Pico::getPreviousPage()
     * @see    Pico::getNextPage()
     * @param  array &$pages        data of all known pages
     * @param  array &$currentPage  data of the page being served
     * @param  array &$previousPage data of the previous page
     * @param  array &$nextPage     data of the next page
     * @return void
     */
    public function onPagesLoaded(&$pages, &$currentPage, &$previousPage, &$nextPage)
    {
     
        foreach($pages as &$page){
            if(!is_array($page['meta']['tags'])){
                $page['meta']['tags'] = PicoTags::parseTags($page['meta']['tags']);
            }
        }

        if ($currentPage && !empty($currentPage['meta']['filter'])) {
            $tagsToShow = $currentPage['meta']['filter'];

            $this->filtered_pages = array_filter($pages, function ($page) use ($tagsToShow) {
                $tags = $page['meta']['tags'];
                return count(array_intersect($tagsToShow, $tags)) > 0;
            });
        }
    }

    public function onPageRendering(&$twig, &$twigVariables, &$templateName)
    {
        if($this->filtered_pages)
            $twigVariables['filtered_pages'] = $this->filtered_pages;

        if($this->page_tags)
            $twigVariables['page_tags'] = $this->page_tags;
    }

    /**
     * Get array of tags from metadata string.
     *
     * @param $tags
     * @return array
     */
    private static function parseTags($tags)
    {
        if (!is_string($tags) || mb_strlen($tags) <= 0) {
            return array();
        }

        $tags = explode(',', $tags);

        return is_array($tags) ? array_map('trim', $tags) : array();
    }
}
