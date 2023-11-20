<?php
/**
 * @package     Dotclear
 * @subpackage  Upgrade
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Upgrade;

use Dotclear\Module\ModuleDefine;
use Dotclear\Module\StoreParser;

/**
 * @brief   Unversionned Store parser.
 *
 * @since   2.29
 */
class NextStoreParser extends StoreParser
{
    /**
     * Overwrite StoreParser to bypasse current dotclear version.
     */
    protected function _parse(): void
    {
        if (empty($this->xml->module)) {
            return;
        }

        foreach ($this->xml->module as $i) {
            $attrs = $i->attributes();
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
            $define->set('dc_min', (string) $i->children(self::$bloc)->dcmin);
            $define->set('details', (string) $i->children(self::$bloc)->details);
            $define->set('section', (string) $i->children(self::$bloc)->section);
            $define->set('support', (string) $i->children(self::$bloc)->support);
            $define->set('sshot', (string) $i->children(self::$bloc)->sshot);

            $tags = [];
            foreach ($i->children(self::$bloc)->tags as $t) {
                $tags[] = (string) $t->tag;
            }
            $define->set('tags', implode(', ', $tags));

            // No more filters here, return all modules
            $this->defines[] = $define;
        }
    }
}
