<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Module;

use Dotclear\App;
use Exception;
use SimpleXMLElement;

/**
 * @brief   Repository modules XML feed parser.
 *
 * Provides an object to parse XML feed of modules from a repository.
 *
 * @since   2.6
 */
class StoreParser
{
    /**
     * XML object of feed contents.
     *
     * @var     false|SimpleXMLElement  $xml
     */
    protected $xml;

    /**
     * Array of feed contents.
     *
     * @var     array<string, array<string, mixed>>   $items
     */
    protected $items = [];

    /**
     * Array of Define instances of feed contents.
     *
     * @var     array<int,ModuleDefine>     $defines
     */
    protected $defines = [];

    /**
     * XML bloc tag.
     *
     * @var     string  $bloc
     */
    protected static string $bloc = 'http://dotaddict.org/da/';

    /**
     * Constructor.
     *
     * @param   string  $data   Feed content
     */
    public function __construct(string $data)
    {
        $this->xml = simplexml_load_string($data);

        if ($this->xml === false) {
            throw new Exception(__('Wrong data feed'));
        }

        $this->_parse();

        $this->xml = false;
        unset($data);
    }

    /**
     * Parse XML into array.
     */
    protected function _parse(): void
    {
        if (!$this->xml || empty($this->xml->module)) {
            return;
        }

        foreach ($this->xml->module as $i) {
            if ($attrs = $i->attributes()) {
                if (!isset($attrs['id'])) {
                    continue;
                }

                $define = new ModuleDefine((string) $attrs['id']);

                # DC/DA shared markers
                $define->set('file', (string) $i->file);
                $define->set('label', (string) $i->name); // deprecated
                $define->set('name', (string) $i->name);
                $define->set('version', (string) $i->version);
                $define->set('author', (string) $i->author);
                $define->set('desc', (string) $i->desc);

                # DA specific markers
                if ($children = $i->children(self::$bloc)) {
                    $define->set('dc_min', (string) $children->dcmin);
                    $define->set('details', (string) $children->details);
                    $define->set('section', (string) $children->section);
                    $define->set('support', (string) $children->support);
                    $define->set('sshot', (string) $children->sshot);

                    $tags = [];
                    if (is_countable($children->tags)) {
                        foreach ($children->tags as $t) {
                            $tags[] = (string) $t->tag;
                        }
                    }
                    $define->set('tags', implode(', ', $tags));
                }

                # First filter right now. If DC_DEV is set all modules are parse
                if (App::config()->devMode() === true || App::plugins()->versionsCompare(App::config()->dotclearVersion(), $define->get('dc_min'), '>=', false)) {
                    $this->defines[] = $define;
                }
            }
        }
    }

    /**
     * Get modules Defines.
     *
     * @return  array<int,ModuleDefine>     Modules Define list
     */
    public function getDefines(): array
    {
        return $this->defines;
    }

    /**
     * Get modules.
     *
     * @deprecated  since 2.26, use self::getDefines() instead
     *
     * @return  array<string, array<string, mixed>>   Modules list
     */
    public function getModules(): array
    {
        App::deprecated()->set(self::class . '::getDefines()', '2.26');

        // fill property once on demand
        if (empty($this->items) && !empty($this->defines)) {
            foreach ($this->defines as $define) {
                $this->items[$define->getId()] = $define->dump();
            }
        }

        return $this->items;
    }
}
