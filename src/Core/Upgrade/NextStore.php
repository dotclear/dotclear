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

use Dotclear\App;
use Dotclear\Module\Store;
use Dotclear\Helper\Network\Http;
use Exception;

/**
 * @brief   Unversionned Store manager.
 *
 * @since   2.29
 */
class NextStore extends Store
{
    /**
     * Overwrite Store::check to remove cache and use NextStoreReader and check disabled modules.
     */
    public function check(?bool $force = true): bool
    {
        if (!$this->xml_url) {
            return false;
        }

        try {
            $str_parser = App::config()->storeNotUpdate() ? false : NextStoreReader::quickParse($this->xml_url, App::config()->cacheRoot(), $force);
        } catch (Exception) {
            return false;
        }

        $upd_defines  = [];
        $upd_versions = [];

        // check update from main repository
        if (!is_bool($str_parser)) {
            foreach ($this->modules->getDefines() as $cur_define) {
                foreach ($str_parser->getDefines() as $str_define) {
                    if ($str_define->getId() == $cur_define->getId() && $this->modules->versionsCompare($str_define->get('version'), $cur_define->get('version'), '>=')) {
                        $str_define->set('root', $cur_define->get('root'));
                        $str_define->set('root_writable', $cur_define->get('root_writable'));
                        $str_define->set('current_version', $cur_define->get('version'));

                        // set memo for third party updates
                        $upd_versions[$str_define->getId()] = [count($upd_defines), $str_define->get('version')];

                        $upd_defines[] = $str_define;
                    }
                }
            }
        }

        // check update from third party repositories
        foreach ($this->modules->getDefines() as $cur_define) {
            if ($cur_define->get('repository') != '' && App::config()->allowRepositories()) {
                try {
                    $str_url    = str_ends_with((string) $cur_define->get('repository'), '/dcstore.xml') ? $cur_define->get('repository') : Http::concatURL($cur_define->get('repository'), 'dcstore.xml');
                    $str_parser = NextStoreReader::quickParse($str_url, App::config()->cacheRoot(), $force);
                    if (is_bool($str_parser)) {
                        continue;
                    }

                    foreach ($str_parser->getDefines() as $str_define) {
                        if ($str_define->getId() == $cur_define->getId() && $this->modules->versionsCompare($str_define->get('version'), $cur_define->get('version'), '>=')) {
                            $str_define->set('repository', true);
                            $str_define->set('root', $cur_define->get('root'));
                            $str_define->set('root_writable', $cur_define->get('root_writable'));
                            $str_define->set('current_version', $cur_define->get('version'));

                            // if no update from main repository, add third party update
                            if (!isset($upd_versions[$str_define->getId()])) {
                                $upd_defines[] = $str_define;
                                // if update from third party repo is more recent than main repo, replace this last one
                            } elseif ($this->modules->versionsCompare($str_define->get('version'), $upd_versions[$str_define->getID()][1], '>')) {
                                $upd_defines[$upd_versions[$str_define->getId()][0]] = $str_define;
                            }
                        }
                    }
                } catch (Exception) {
                    // Ignore exceptions
                }
            }
        }

        uasort($upd_defines, fn ($a, $b) => strtolower($a->getId()) <=> strtolower($b->getId()));

        $this->defines = [
            'new'    => [],
            'update' => $upd_defines,
        ];

        return true;
    }
}
