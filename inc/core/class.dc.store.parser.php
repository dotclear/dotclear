<?php
/**
 * @brief Repository modules XML feed parser
 *
 * Provides an object to parse XML feed of modules from a repository.
 *
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 *
 * @since 2.6
 */

if (!defined('DC_RC_PATH')) {return;}

class dcStoreParser
{
    /** @var    object    XML object of feed contents */
    protected $xml;
    /** @var    array    Array of feed contents */
    protected $items;
    /** @var    string    XML bloc tag */
    protected static $bloc = 'http://dotaddict.org/da/';

    /**
     * Constructor.
     *
     * @param    string    $data        Feed content
     */
    public function __construct($data)
    {
        if (!is_string($data)) {
            throw new Exception(__('Failed to read data feed'));
        }

        $this->xml   = simplexml_load_string($data);
        $this->items = array();

        if ($this->xml === false) {
            throw new Exception(__('Wrong data feed'));
        }

        $this->_parse();

        unset($data);
        unset($this->xml);
    }

    /**
     * Parse XML into array
     */
    protected function _parse()
    {
        if (empty($this->xml->module)) {
            return;
        }

        foreach ($this->xml->module as $i) {
            $attrs = $i->attributes();

            $item = array();

            # DC/DA shared markers
            $item['id']      = (string) $attrs['id'];
            $item['file']    = (string) $i->file;
            $item['label']   = (string) $i->name; // deprecated
            $item['name']    = (string) $i->name;
            $item['version'] = (string) $i->version;
            $item['author']  = (string) $i->author;
            $item['desc']    = (string) $i->desc;

            # DA specific markers
            $item['dc_min']  = (string) $i->children(self::$bloc)->dcmin;
            $item['details'] = (string) $i->children(self::$bloc)->details;
            $item['section'] = (string) $i->children(self::$bloc)->section;
            $item['support'] = (string) $i->children(self::$bloc)->support;
            $item['sshot']   = (string) $i->children(self::$bloc)->sshot;

            $tags = array();
            foreach ($i->children(self::$bloc)->tags as $t) {
                $tags[] = (string) $t->tag;
            }
            $item['tags'] = implode(', ', $tags);

            # First filter right now. If DC_DEV is set all modules are parse
            if (defined('DC_DEV') && DC_DEV === true || dcUtils::versionsCompare(DC_VERSION, $item['dc_min'], '>=', false)) {
                $this->items[$item['id']] = $item;
            }
        }
    }

    /**
     * Get modules.
     *
     * @return    array        Modules list
     */
    public function getModules()
    {
        return $this->items;
    }
}
