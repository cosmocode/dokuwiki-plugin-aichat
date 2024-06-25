<?php

use dokuwiki\File\PageResolver;

/**
 * Renderer for preparing data for embedding
 *
 * BAsed on the text and markdown renderers
 *
 * @author Michael Hamann <michael@content-space.de>
 * @author Todd Augsburger <todd@rollerorgans.com>
 * @author  i-net software <tools@inetsoftware.de>
 * @link https://www.dokuwiki.org/plugin:text
 * @link https://www.dokuwiki.org/plugin:dw2markdown
 */
class renderer_plugin_aichat extends Doku_Renderer_xhtml
{


    /** @inheritdoc */
    function getFormat()
    {
        return 'aichat';
    }

    /** @inheritdoc */
    public function startSectionEdit($start, $data, $title = null)
    {
    }

    /** @inheritdoc */
    public function finishSectionEdit($end = null, $hid = null)
    {
    }

    /**
     * @inheritdoc
     * Use specific text support if available, otherwise use xhtml renderer and strip tags
     */
    public function plugin($name, $data, $state = '', $match = '')
    {
        /** @var DokuWiki_Syntax_Plugin $plugin */
        $plugin = plugin_load('syntax', $name);
        if ($plugin === null) return;

        if (
            !$plugin->render($this->getFormat(), $this, $data) &&
            !$plugin->render('text', $this, $data) &&
            !$plugin->render('markdown', $this, $data)
        ) {
            // plugin does not support any of the text formats, so use stripped-down xhtml
            $tmpData = $this->doc;
            $this->doc = '';
            if ($plugin->render('xhtml', $this, $data) && ($this->doc != '')) {
                $pluginoutput = $this->doc;
                $this->doc = $tmpData . DOKU_LF . trim(strip_tags($pluginoutput)) . DOKU_LF;
            } else {
                $this->doc = $tmpData;
            }
        }
    }


    /** @inheritdoc */
    public function document_start()
    {
        global $ID;


        $this->doc = '';
        $metaheader = array();
        $metaheader['Content-Type'] = 'text/plain; charset=utf-8';
        $meta = array();
        $meta['format']['aichat'] = $metaheader;
        p_set_metadata($ID, $meta);
    }

    /** @inheritdoc */
    public function document_end()
    {
        $this->doc = preg_replace("/(\r?\n){3,}/", "\n\n", $this->doc);
        $this->doc = ltrim($this->doc); // remove leading space and empty lines
    }

    /** @inheritdoc */
    public function header($text, $level, $pos, $returnonly = false)
    {
        $this->doc .= str_repeat("#", $level) . ' ' . $text . DOKU_LF;
    }

    /** @inheritdoc */
    public function section_open($level)
    {
        $this->doc .= DOKU_LF;
    }

    /** @inheritdoc */
    public function section_close()
    {
        $this->doc .= DOKU_LF;
    }

    /** @inheritdoc */
    public function cdata($text)
    {
        $this->doc .= $text;
    }

    /** @inheritdoc */
    public function p_open()
    {
        $this->doc .= DOKU_LF;
    }

    /** @inheritdoc */
    public function p_close()
    {
        $this->doc .= DOKU_LF;
    }

    /** @inheritdoc */
    public function linebreak()
    {
        $this->doc .= DOKU_LF . DOKU_LF;
    }

    /** @inheritdoc */
    public function hr()
    {
        $this->doc .= '----' . DOKU_LF;
    }

    /** @inheritdoc */
    public function strong_open()
    {
    }

    /** @inheritdoc */
    public function strong_close()
    {
    }

    /** @inheritdoc */
    public function emphasis_open()
    {
    }

    /** @inheritdoc */
    public function emphasis_close()
    {
    }

    /** @inheritdoc */
    public function underline_open()
    {
    }

    /** @inheritdoc */
    public function underline_close()
    {
    }

    /** @inheritdoc */
    public function monospace_open()
    {
    }

    /** @inheritdoc */
    public function monospace_close()
    {
    }

    /** @inheritdoc */
    public function subscript_open()
    {
    }

    /** @inheritdoc */
    public function subscript_close()
    {
    }

    /** @inheritdoc */
    public function superscript_open()
    {
    }

    /** @inheritdoc */
    public function superscript_close()
    {
    }

    /** @inheritdoc */
    public function deleted_open()
    {
    }

    /** @inheritdoc */
    public function deleted_close()
    {
    }

    /** @inheritdoc */
    public function footnote_open()
    {
        $this->doc .= ' ((';
    }

    /** @inheritdoc */
    public function footnote_close()
    {
        $this->doc .= '))';
    }

    private $listMode = [];

    /**
     * Open an unordered list
     */
    function listu_open($classes = null)
    {
        if (empty($this->listMode)) {
            $this->doc .= DOKU_LF;
        }
        $this->listMode[] = '*';
    }

    /**
     * Close an unordered list
     */
    function listu_close()
    {
        array_pop($this->listMode);
        if (empty($this->listMode)) {
            $this->doc .= DOKU_LF;
        }
    }

    /**
     * Open an ordered list
     */
    function listo_open($classes = null)
    {
        if (empty($this->listMode)) {
            $this->doc .= DOKU_LF;
        }
        $this->listMode[] = '1.';
    }

    /**
     * Close an ordered list
     */
    function listo_close()
    {
        array_pop($this->listMode);
        if (empty($this->listMode)) {
            $this->doc .= DOKU_LF;
        }
    }

    /**
     * Open a list item
     *
     * @param int $level the nesting level
     * @param bool $node true when a node; false when a leaf
     */
    function listitem_open($level, $node = false)
    {
        $this->doc .= str_repeat(' ', $level * 2) . $this->listMode[count($this->listMode) - 1];
    }

    /**
     * Close a list item
     */
    function listitem_close()
    {
    }


    /** @inheritdoc */
    public function listcontent_open()
    {
    }

    /** @inheritdoc */
    public function listcontent_close()
    {
        $this->doc .= DOKU_LF;
    }

    /** @inheritdoc */
    public function unformatted($text)
    {
        $this->doc .= $text;
    }

    /** @inheritdoc */
    public function quote_open()
    {
        $this->doc .= '>>>';
    }

    /** @inheritdoc */
    public function quote_close()
    {
        $this->doc .= '<<<' . DOKU_LF;
    }

    /** @inheritdoc */
    public function preformatted($text)
    {
        $this->code($text);
    }

    /** @inheritdoc */
    public function file($text, $language = null, $filename = null, $options = null)
    {
        $this->code($text, $language, $filename, $options);
    }

    /** @inheritdoc */
    public function code($text, $language = null, $filename = null, $options = null)
    {
        $this->doc .= DOKU_LF . '```' . ($language ?? '') . DOKU_LF . trim($text) . DOKU_LF . '```' . DOKU_LF;
    }

    /** @inheritdoc */
    public function acronym($acronym)
    {
        if (array_key_exists($acronym, $this->acronyms)) {
            $title = $this->acronyms[$acronym];
            $this->doc .= $acronym . ' (' . $title . ')';
        } else {
            $this->doc .= $acronym;
        }
    }

    /** @inheritdoc */
    public function smiley($smiley)
    {
        $this->doc .= $smiley;
    }

    /** @inheritdoc */
    public function entity($entity)
    {
        if (array_key_exists($entity, $this->entities)) {
            $this->doc .= $this->entities[$entity];
        } else {
            $this->doc .= $entity;
        }
    }

    /** @inheritdoc */
    public function multiplyentity($x, $y)
    {
        $this->doc .= $x . 'x' . $y;
    }

    /** @inheritdoc */
    public function singlequoteopening()
    {
        global $lang;
        $this->doc .= $lang['singlequoteopening'];
    }

    /** @inheritdoc */
    public function singlequoteclosing()
    {
        global $lang;
        $this->doc .= $lang['singlequoteclosing'];
    }

    /** @inheritdoc */
    public function apostrophe()
    {
        global $lang;
        $this->doc .= $lang['apostrophe'];
    }

    /** @inheritdoc */
    public function doublequoteopening()
    {
        global $lang;
        $this->doc .= $lang['doublequoteopening'];
    }

    /** @inheritdoc */
    public function doublequoteclosing()
    {
        global $lang;
        $this->doc .= $lang['doublequoteclosing'];
    }

    /** @inheritdoc */
    public function camelcaselink($link, $returnonly = false)
    {
        $this->internallink($link, $link);
    }

    /** @inheritdoc */
    public function locallink($hash, $name = null, $returnonly = false)
    {
        $name = $this->_getLinkTitle($name, $hash, $isImage);
        $this->doc .= $name;
    }

    /** @inheritdoc */
    public function internallink($id, $name = null, $search = null, $returnonly = false, $linktype = 'content')
    {
        global $ID;
        // default name is based on $id as given
        $default = $this->_simpleTitle($id);
        $resolver = new PageResolver($ID);
        $id = $resolver->resolveId($id);

        $name = $this->_getLinkTitle($name, $default, $isImage, $id, $linktype);
        if ($returnonly) {
            return $name;
        }
        $this->doc .= $name;
        return null;
    }

    /** @inheritdoc */
    public function externallink($url, $name = null, $returnonly = false)
    {
        $title = $this->_getLinkTitle($name, $url, $isImage);
        if ($title != $url) {
            $this->doc .= "[$title]($url)";
        } else {
            $this->doc .= $title;
        }
    }

    /** @inheritdoc */
    public function interwikilink($match, $name, $wikiName, $wikiUri, $returnonly = false)
    {
        $this->doc .= $this->_getLinkTitle($name, $wikiUri, $isImage);
    }

    /** @inheritdoc */
    public function windowssharelink($url, $name = null, $returnonly = false)
    {
        $this->doc .= $this->_getLinkTitle($name, $url, $isImage);
    }

    /** @inheritdoc */
    public function emaillink($address, $name = null, $returnonly = false)
    {
        $name = $this->_getLinkTitle($name, '', $isImage);
        $address = html_entity_decode(obfuscate($address), ENT_QUOTES, 'UTF-8');
        if (empty($name)) {
            $name = $address;
        }
        $this->doc .= $name;
    }

    /** @inheritdoc */
    public function internalmedia($src, $title = null, $align = null, $width = null,
                                  $height = null, $cache = null, $linking = null, $return = false)
    {
        $this->doc .= $title;
    }

    /** @inheritdoc */
    public function externalmedia($src, $title = null, $align = null, $width = null,
                                  $height = null, $cache = null, $linking = null, $return = false)
    {
        $this->doc .= $title;
    }

    /** @inheritdoc */
    public function rss($url, $params)
    {
    }

    /** @inheritdoc */
    public function table_open($maxcols = null, $numrows = null, $pos = null, $classes = null)
    {
    }

    /** @inheritdoc */
    public function table_close($pos = null)
    {
        $this->doc .= DOKU_LF;
    }

    private $tableColumns = 0;

    /**
     * Open a table header
     */
    function tablethead_open()
    {
        $this->tableColumns = 0;
        $this->doc .= DOKU_LF; // . '|';
    }

    /**
     * Close a table header
     */
    function tablethead_close()
    {
        $this->doc .= '|' . str_repeat('---|', $this->tableColumns) . DOKU_LF;
    }

    /**
     * Open a table body
     */
    function tabletbody_open()
    {
    }

    /**
     * Close a table body
     */
    function tabletbody_close()
    {
    }

    /**
     * Open a table row
     */
    function tablerow_open($classes = null)
    {
    }

    /**
     * Close a table row
     */
    function tablerow_close()
    {
        $this->doc .= '|' . DOKU_LF;
    }

    /**
     * Open a table header cell
     *
     * @param int $colspan
     * @param string $align left|center|right
     * @param int $rowspan
     */
    function tableheader_open($colspan = 1, $align = null, $rowspan = 1, $classes = null)
    {
        $this->doc .= str_repeat('|', $colspan);
        $this->tableColumns += $colspan;
    }

    /**
     * Close a table header cell
     */
    function tableheader_close()
    {
    }

    /**
     * Open a table cell
     *
     * @param int $colspan
     * @param string $align left|center|right
     * @param int $rowspan
     */
    function tablecell_open($colspan = 1, $align = null, $rowspan = 1, $classes = null)
    {
        $this->doc .= str_repeat('|', $colspan);
    }

    /**
     * Close a table cell
     */
    function tablecell_close()
    {
    }

    /** @inheritdoc */
    public function _getLinkTitle($title, $default, &$isImage, $id = null, $linktype = 'content')
    {
        $isImage = false;
        if (is_array($title)) {
            $isImage = true;
            if (!is_null($default) && ($default != $title['title']))
                return $default . " " . $title['title'];
            else
                return $title['title'];
        } elseif (is_null($title) || trim($title) == '') {
            if (useHeading($linktype) && $id) {
                $heading = p_get_first_heading($id);
                if ($heading) {
                    return $this->_xmlEntities($heading);
                }
            }
            return $this->_xmlEntities($default);
        } else {
            return $this->_xmlEntities($title);
        }
    }

    /** @inheritdoc */
    public function _xmlEntities($string)
    {
        return $string; // nothing to do for text
    }

    /** @inheritdoc */
    public function _formatLink($link)
    {
        if (!empty($link['name'])) {
            return $link['name'];
        } elseif (!empty($link['title'])) {
            return $link['title'];
        }
        return $link['url'];
    }
}
