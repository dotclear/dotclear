<?php
/**
 * @package Dotclear
 * @subpackage Install
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

namespace Dotclear\Install;

class Utils
{
    public const PHP_MIN   = '7.4';
    public const MYSQL_MIN = '4.1';
    public const PGSQL_MIN = '8.0';

    public static function check($con, &$err)
    {
        $err = [];

        if (version_compare(phpversion(), self::PHP_MIN, '<')) {
            $err[] = sprintf(__('PHP version is %s (%s or earlier needed).'), phpversion(), self::PHP_MIN);
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

        if ($con->syntax() == 'mysql') {
            if (version_compare($con->version(), self::MYSQL_MIN, '<')) {
                $err[] = sprintf(__('MySQL version is %s (%s or earlier needed).'), $con->version(), self::MYSQL_MIN);
            } else {
                $rs     = $con->select('SHOW ENGINES');
                $innodb = false;
                while ($rs->fetch()) {
                    if (strtolower($rs->f(0)) == 'innodb' && strtolower($rs->f(1)) != 'disabled' && strtolower($rs->f(1)) != 'no') {
                        $innodb = true;

                        break;
                    }
                }

                if (!$innodb) {
                    $err[] = __('MySQL InnoDB engine is not available.');
                }
            }
        } elseif ($con->driver() == 'pgsql') {
            if (version_compare($con->version(), self::PGSQL_MIN, '<')) {
                $err[] = sprintf(__('PostgreSQL version is %s (%s or earlier needed).'), $con->version(), self::PGSQL_MIN);
            }
        }

        return !count($err);
    }
}
