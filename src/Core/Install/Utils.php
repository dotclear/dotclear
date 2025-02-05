<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */

namespace Dotclear\Core\Install;

use Dotclear\App;
use Dotclear\Database\Structure;
use Dotclear\Interface\Core\ConnectionInterface;
use Dotclear\Schema\Schema;

/**
 * @brief   Installation helpers
 */
class Utils
{
    /**
     * Check server support.
     *
     * @param   ConnectionInterface     $con    The db handler instance
     * @param   array<int,string>       $err    The errors
     *
     * @return  bool    False on error
     */
    public static function check(ConnectionInterface $con, array &$err): bool
    {
        $err = [];

        if (version_compare(phpversion(), App::config()->minRequiredPhp(), '<')) {
            $err[] = sprintf(__('PHP version is %s (%s or earlier needed).'), phpversion(), App::config()->minRequiredPhp());
        }

        if (!function_exists('mb_detect_encoding')) {
            $err[] = __('Multibyte string module (mbstring) is not available.');
        }

        if (!function_exists('iconv')) {
            $err[] = __('Iconv module is not available.');
        }

        if (!function_exists('ob_start')) {
            $err[] = __('Output control functions are not available.');
        }

        if (!function_exists('simplexml_load_string')) {
            $err[] = __('SimpleXML module is not available.');
        }

        if (!function_exists('dom_import_simplexml')) {
            $err[] = __('DOM XML module is not available.');
        }

        $pcre_str = base64_decode('w6nDqMOgw6o=');
        if (!@preg_match('/' . $pcre_str . '/u', $pcre_str)) {
            $err[] = __('PCRE engine does not support UTF-8 strings.');
        }

        if (!function_exists('spl_classes')) {
            $err[] = __('SPL module is not available.');
        }

        if ($con->syntax() === 'mysql') {
            if (version_compare($con->version(), App::config()->minRequiredMysql(), '<')) {
                $err[] = sprintf(__('MySQL version is %s (%s or earlier needed).'), $con->version(), App::config()->minRequiredMysql());
            } else {
                $rs     = $con->select('SHOW ENGINES');
                $innodb = false;
                while ($rs->fetch()) {
                    if (strtolower((string) $rs->f(0)) === 'innodb' && strtolower((string) $rs->f(1)) !== 'disabled' && strtolower((string) $rs->f(1)) !== 'no') {
                        $innodb = true;

                        break;
                    }
                }

                if (!$innodb) {
                    $err[] = __('MySQL InnoDB engine is not available.');
                }
            }
        } elseif ($con->driver() === 'pgsql') {
            if (version_compare($con->version(), App::config()->minRequiredPgsql(), '<')) {
                $err[] = sprintf(__('PostgreSQL version is %s (%s or earlier needed).'), $con->version(), App::config()->minRequiredPgsql());
            }
        }

        return $err === [];
    }

    /**
     * Fill database structure.
     *
     * @param   Structure   $_s     The database structure handler instance
     *
     * @deprecated  Since 2.33  Use Schema::fillStructure()
     */
    public static function dbSchema(Structure $_s): void
    {
        App::deprecated()->set('Utils::dbSchema()', '2.33');

        Schema::fillStructure($_s);
    }

    /**
     * Creates default settings for active blog.
     *
     * Optionnal parameter <var>defaults</var> replaces default params while needed.
     *
     * @param   null|array<array{0:string, 1:string, 2:mixed, 3:string}>  $defaults   The defaults settings
     *
     * @deprecated  Since 2.33  Use App::blogs()->blogDefaults() instead
     */
    public static function blogDefaults(?array $defaults = null): void
    {
        App::deprecated()->set('Utils::blogDefaults()', '2.33');

        App::blogs()->blogDefaults($defaults);
    }
}
