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

namespace Dotclear\Upgrade\GrowUp;

use dcCore;
use dcNamespace;
use Dotclear\Upgrade\Upgrade;

class GrowUp_2_9_lt_eq
{
    public static function init(bool $cleanup_sessions): bool
    {
        # Some new settings should be initialized, prepare db queries
        $strReq = 'INSERT INTO ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME .
            ' (setting_id,setting_ns,setting_value,setting_type,setting_label)' .
            ' VALUES(\'%s\',\'system\',\'%s\',\'%s\',\'%s\')';
        dcCore::app()->con->execute(
            sprintf($strReq, 'media_video_width', '400', 'integer', 'Media video insertion width')
        );
        dcCore::app()->con->execute(
            sprintf($strReq, 'media_video_height', '300', 'integer', 'Media video insertion height')
        );
        dcCore::app()->con->execute(
            sprintf($strReq, 'media_flash_fallback', '1', 'boolean', 'Flash player fallback for audio and video media')
        );

        # Some settings and prefs should be moved from string to array
        Upgrade::settings2array('system', 'date_formats');
        Upgrade::settings2array('system', 'time_formats');
        Upgrade::settings2array('antispam', 'antispam_filters');
        Upgrade::settings2array('pings', 'pings_uris');
        Upgrade::settings2array('system', 'simpleMenu');
        Upgrade::prefs2array('dashboard', 'favorites');

        return $cleanup_sessions;
    }
}
