<?php

/**
 * @package     Dotclear
 * @subpackage  Upgrade
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
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

            if ($children = $i->children(self::$bloc)) {
                # DA specific markers
                $define->set('dc_min', (string) $children->dcmin);
                $define->set('details', (string) $children->details);
                $define->set('section', (string) $children->section);
                $define->set('support', (string) $children->support);
                $define->set('sshot', (string) $children->sshot);

                $tags = [];
                if ($children->tags) {
                    foreach ($children->tags as $t) {
                        $tags[] = (string) $t->tag;
                    }
                }
                $define->set('tags', implode(', ', $tags));
            }

            // No more filters here, return all modules
            $this->defines[] = $define;
        }
    }
}
