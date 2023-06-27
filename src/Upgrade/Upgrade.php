<?php
/**
 * @package Dotclear
 * @subpackage Upgrade
 *
 * Dotclear upgrade procedure.
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Upgrade;

use dcCore;
use dcNamespace;
use dcWorkspace;
use Dotclear\Upgrade\GrowUp\GrowUp_2_0_beta3_3_lt;
use Dotclear\Upgrade\GrowUp\GrowUp_2_0_beta7_3_lt;
use Dotclear\Upgrade\GrowUp\GrowUp_2_10_lt;
use Dotclear\Upgrade\GrowUp\GrowUp_2_11_lt;
use Dotclear\Upgrade\GrowUp\GrowUp_2_12_2_lt;
use Dotclear\Upgrade\GrowUp\GrowUp_2_12_lt;
use Dotclear\Upgrade\GrowUp\GrowUp_2_14_3_lt;
use Dotclear\Upgrade\GrowUp\GrowUp_2_14_lt;
use Dotclear\Upgrade\GrowUp\GrowUp_2_15_1_lt;
use Dotclear\Upgrade\GrowUp\GrowUp_2_15_lt;
use Dotclear\Upgrade\GrowUp\GrowUp_2_16_1_lt;
use Dotclear\Upgrade\GrowUp\GrowUp_2_16_9_lt;
use Dotclear\Upgrade\GrowUp\GrowUp_2_16_lt;
use Dotclear\Upgrade\GrowUp\GrowUp_2_17_lt;
use Dotclear\Upgrade\GrowUp\GrowUp_2_19_lt;
use Dotclear\Upgrade\GrowUp\GrowUp_2_1_6_lt_eq;
use Dotclear\Upgrade\GrowUp\GrowUp_2_1_alpha2_r2383_lt;
use Dotclear\Upgrade\GrowUp\GrowUp_2_21_2_lt;
use Dotclear\Upgrade\GrowUp\GrowUp_2_21_lt;
use Dotclear\Upgrade\GrowUp\GrowUp_2_23_lt;
use Dotclear\Upgrade\GrowUp\GrowUp_2_24_lt;
use Dotclear\Upgrade\GrowUp\GrowUp_2_25_1_lt;
use Dotclear\Upgrade\GrowUp\GrowUp_2_25_lt;
use Dotclear\Upgrade\GrowUp\GrowUp_2_26_lt;
use Dotclear\Upgrade\GrowUp\GrowUp_2_27_lt;
use Dotclear\Upgrade\GrowUp\GrowUp_2_2_alpha1_r3043_lt;
use Dotclear\Upgrade\GrowUp\GrowUp_2_3_1_lt;
use Dotclear\Upgrade\GrowUp\GrowUp_2_3_lt;
use Dotclear\Upgrade\GrowUp\GrowUp_2_5_1_lt_eq;
use Dotclear\Upgrade\GrowUp\GrowUp_2_5_lt_eq;
use Dotclear\Upgrade\GrowUp\GrowUp_2_6_lt_eq;
use Dotclear\Upgrade\GrowUp\GrowUp_2_7_lt_eq;
use Dotclear\Upgrade\GrowUp\GrowUp_2_8_1_lt_eq;
use Dotclear\Upgrade\GrowUp\GrowUp_2_9_1_lt_eq;
use Dotclear\Upgrade\GrowUp\GrowUp_2_9_lt_eq;
use Dotclear\Database\Structure;
use Dotclear\Helper\File\Files;
use Exception;

class Upgrade
{
    /**
     * Do Dotclear upgrade if necessary
     *
     * @throws     Exception
     *
     * @return     bool|Structure|int
     */
    public static function dotclearUpgrade()
    {
        $version = dcCore::app()->getVersion('core');

        if ($version === null) {
            return false;
        }

        if (version_compare($version, DC_VERSION, '<') == 1 || strpos(DC_VERSION, 'dev')) {
            try {
                if (dcCore::app()->con->driver() == 'sqlite') {
                    return false; // Need to find a way to upgrade sqlite database
                }

                # Database upgrade
                $_s = new Structure(dcCore::app()->con, dcCore::app()->prefix);
                require __DIR__ . '/db-schema.php';

                $si      = new Structure(dcCore::app()->con, dcCore::app()->prefix);
                $changes = $si->synchronize($_s);

                /* Some other upgrades
                ------------------------------------ */
                $cleanup_sessions = self::growUp($version);

                # Drop content from session table if changes or if needed
                if ($changes != 0 || $cleanup_sessions) {
                    dcCore::app()->con->execute('DELETE FROM ' . dcCore::app()->prefix . dcCore::SESSION_TABLE_NAME);
                }

                # Empty templates cache directory
                try {
                    dcCore::app()->emptyTemplatesCache();
                } catch (Exception $e) {
                }

                return $changes;
            } catch (Exception $e) {
                throw new Exception(__('Something went wrong with auto upgrade:') .
                    ' ' . $e->getMessage());
            }
        }

        # No upgrade?
        return false;
    }

    /**
     * Make necessary updates in DB and in filesystem
     *
     * @param      null|string  $version  The version
     *
     * @return     bool     true if a session cleanup is requested
     */
    public static function growUp(?string $version): bool
    {
        if ($version === null) {
            return false;
        }

        /**
         * Update it in a step that needed sessions to be removed
         *
         * @var        bool
         */
        $cleanup_sessions = false;

        if (version_compare($version, '2.0-beta3.3', '<')) {
            $cleanup_sessions = GrowUp_2_0_beta3_3_lt::init($cleanup_sessions);
        }

        if (version_compare($version, '2.0-beta7.3', '<')) {
            $cleanup_sessions = GrowUp_2_0_beta7_3_lt::init($cleanup_sessions);
        }

        if (version_compare($version, '2.1-alpha2-r2383', '<')) {
            $cleanup_sessions = GrowUp_2_1_alpha2_r2383_lt::init($cleanup_sessions);
        }

        if (version_compare($version, '2.1.6', '<=')) {
            $cleanup_sessions = GrowUp_2_1_6_lt_eq::init($cleanup_sessions);
        }

        if (version_compare($version, '2.2-alpha1-r3043', '<')) {
            $cleanup_sessions = GrowUp_2_2_alpha1_r3043_lt::init($cleanup_sessions);
        }

        if (version_compare($version, '2.3', '<')) {
            $cleanup_sessions = GrowUp_2_3_lt::init($cleanup_sessions);
        }

        if (version_compare($version, '2.3.1', '<')) {
            $cleanup_sessions = GrowUp_2_3_1_lt::init($cleanup_sessions);
        }

        if (version_compare($version, '2.5', '<=')) {
            $cleanup_sessions = GrowUp_2_5_lt_eq::init($cleanup_sessions);
        }

        if (version_compare($version, '2.5.1', '<=')) {
            $cleanup_sessions = GrowUp_2_5_1_lt_eq::init($cleanup_sessions);
        }

        if (version_compare($version, '2.6', '<=')) {
            $cleanup_sessions = GrowUp_2_6_lt_eq::init($cleanup_sessions);
        }

        if (version_compare($version, '2.7', '<=')) {
            $cleanup_sessions = GrowUp_2_7_lt_eq::init($cleanup_sessions);
        }

        if (version_compare($version, '2.8.1', '<=')) {
            $cleanup_sessions = GrowUp_2_8_1_lt_eq::init($cleanup_sessions);
        }

        if (version_compare($version, '2.9', '<=')) {
            $cleanup_sessions = GrowUp_2_9_lt_eq::init($cleanup_sessions);
        }

        if (version_compare($version, '2.9.1', '<=')) {
            $cleanup_sessions = GrowUp_2_9_1_lt_eq::init($cleanup_sessions);
        }

        if (version_compare($version, '2.10', '<')) {
            $cleanup_sessions = GrowUp_2_10_lt::init($cleanup_sessions);
        }

        if (version_compare($version, '2.11', '<')) {
            $cleanup_sessions = GrowUp_2_11_lt::init($cleanup_sessions);
        }

        if (version_compare($version, '2.12', '<')) {
            $cleanup_sessions = GrowUp_2_12_lt::init($cleanup_sessions);
        }

        if (version_compare($version, '2.12.2', '<')) {
            $cleanup_sessions = GrowUp_2_12_2_lt::init($cleanup_sessions);
        }

        if (version_compare($version, '2.14', '<')) {
            $cleanup_sessions = GrowUp_2_14_lt::init($cleanup_sessions);
        }

        if (version_compare($version, '2.14.3', '<')) {
            $cleanup_sessions = GrowUp_2_14_3_lt::init($cleanup_sessions);
        }

        if (version_compare($version, '2.15', '<')) {
            $cleanup_sessions = GrowUp_2_15_lt::init($cleanup_sessions);
        }

        if (version_compare($version, '2.15.1', '<')) {
            $cleanup_sessions = GrowUp_2_15_1_lt::init($cleanup_sessions);
        }

        if (version_compare($version, '2.16', '<')) {
            $cleanup_sessions = GrowUp_2_16_lt::init($cleanup_sessions);
        }

        if (version_compare($version, '2.16.1', '<')) {
            $cleanup_sessions = GrowUp_2_16_1_lt::init($cleanup_sessions);
        }

        if (version_compare($version, '2.16.9', '<')) {
            $cleanup_sessions = GrowUp_2_16_9_lt::init($cleanup_sessions);
        }

        if (version_compare($version, '2.17', '<')) {
            $cleanup_sessions = GrowUp_2_17_lt::init($cleanup_sessions);
        }

        if (version_compare($version, '2.19', '<')) {
            $cleanup_sessions = GrowUp_2_19_lt::init($cleanup_sessions);
        }

        if (version_compare($version, '2.21', '<')) {
            $cleanup_sessions = GrowUp_2_21_lt::init($cleanup_sessions);
        }

        if (version_compare($version, '2.21.2', '<')) {
            $cleanup_sessions = GrowUp_2_21_2_lt::init($cleanup_sessions);
        }

        if (version_compare($version, '2.23', '<')) {
            $cleanup_sessions = GrowUp_2_23_lt::init($cleanup_sessions);
        }

        if (version_compare($version, '2.24', '<')) {
            $cleanup_sessions = GrowUp_2_24_lt::init($cleanup_sessions);
        }

        if (version_compare($version, '2.25', '<')) {
            $cleanup_sessions = GrowUp_2_25_lt::init($cleanup_sessions);
        }

        if (version_compare($version, '2.25.1', '<')) {
            $cleanup_sessions = GrowUp_2_25_1_lt::init($cleanup_sessions);
        }

        if (version_compare($version, '2.26', '<')) {
            $cleanup_sessions = GrowUp_2_26_lt::init($cleanup_sessions);
        }

        if (version_compare($version, '2.27', '<')) {
            $cleanup_sessions = GrowUp_2_27_lt::init($cleanup_sessions);
        }

        dcCore::app()->setVersion('core', DC_VERSION);
        dcCore::app()->blogDefaults();

        return $cleanup_sessions;
    }

    /**
     * Convert old-fashion serialized array setting to new-fashion json encoded array
     *
     * @param      string  $ns        namespace name
     * @param      string  $setting   The setting ID
     */
    public static function settings2array(string $ns, string $setting)
    {
        $strReqSelect = 'SELECT setting_id,blog_id,setting_ns,setting_type,setting_value FROM ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME . ' ' .
            "WHERE setting_id = '%s' " .
            "AND setting_ns = '%s' " .
            "AND setting_type = 'string'";
        $rs = dcCore::app()->con->select(sprintf($strReqSelect, $setting, $ns));
        while ($rs->fetch()) {
            $value = @unserialize($rs->setting_value);
            if (!$value) {
                $value = [];
            }
            settype($value, 'array');
            $value = json_encode($value, JSON_THROW_ON_ERROR);
            $rs2   = 'UPDATE ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME . ' ' .
            "SET setting_type='array', setting_value = '" . dcCore::app()->con->escape($value) . "' " .
            "WHERE setting_id='" . dcCore::app()->con->escape($rs->setting_id) . "' " .
            "AND setting_ns='" . dcCore::app()->con->escape($rs->setting_ns) . "' ";
            if ($rs->blog_id == '') {
                $rs2 .= 'AND blog_id IS null';
            } else {
                $rs2 .= "AND blog_id = '" . dcCore::app()->con->escape($rs->blog_id) . "'";
            }
            dcCore::app()->con->execute($rs2);
        }
    }

    /**
     * Convert old-fashion serialized array pref to new-fashion json encoded array
     *
     * @param      string  $ws     workspace name
     * @param      string  $pref   The preference ID
     */
    public static function prefs2array(string $ws, string $pref)
    {
        $strReqSelect = 'SELECT pref_id,user_id,pref_ws,pref_type,pref_value FROM ' . dcCore::app()->prefix . dcWorkspace::WS_TABLE_NAME . ' ' .
            "WHERE pref_id = '%s' " .
            "AND pref_ws = '%s' " .
            "AND pref_type = 'string'";
        $rs = dcCore::app()->con->select(sprintf($strReqSelect, $pref, $ws));
        while ($rs->fetch()) {
            $value = @unserialize($rs->pref_value);
            if (!$value) {
                $value = [];
            }
            settype($value, 'array');
            $value = json_encode($value, JSON_THROW_ON_ERROR);
            $rs2   = 'UPDATE ' . dcCore::app()->prefix . dcWorkspace::WS_TABLE_NAME . ' ' .
            "SET pref_type='array', pref_value = '" . dcCore::app()->con->escape($value) . "' " .
            "WHERE pref_id='" . dcCore::app()->con->escape($rs->pref_id) . "' " .
            "AND pref_ws='" . dcCore::app()->con->escape($rs->pref_ws) . "' ";
            if ($rs->user_id == '') {
                $rs2 .= 'AND user_id IS null';
            } else {
                $rs2 .= "AND user_id = '" . dcCore::app()->con->escape($rs->user_id) . "'";
            }
            dcCore::app()->con->execute($rs2);
        }
    }

    /**
     * Remove files and/or folders
     *
     * @param      array|null  $files    The files
     * @param      array|null  $folders  The folders
     */
    public static function houseCleaning(?array $files = null, ?array $folders = null)
    {
        if (!defined('DC_ROOT') || (DC_ROOT === '')) {
            return;
        }

        if (is_array($files)) {
            foreach ($files as $f) {
                if (file_exists(DC_ROOT . '/' . $f)) {
                    @unlink(DC_ROOT . '/' . $f);
                }
            }
        }

        if (is_array($folders)) {
            foreach ($folders as $f) {
                if (file_exists(DC_ROOT . '/' . $f)) {
                    Files::deltree(DC_ROOT . '/' . $f);
                }
            }
        }
    }
}
