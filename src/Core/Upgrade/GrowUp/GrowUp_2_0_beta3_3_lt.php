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

namespace Dotclear\Core\Upgrade\GrowUp;

use dcCore;
use dcMedia;

class GrowUp_2_0_beta3_3_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // Populate media_dir field (since 2.0-beta3.3)
        $strReq = 'SELECT media_id, media_file FROM ' . dcCore::app()->prefix . dcMedia::MEDIA_TABLE_NAME . ' ';
        $rs_m   = dcCore::app()->con->select($strReq);
        while ($rs_m->fetch()) {
            $cur            = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcMedia::MEDIA_TABLE_NAME);
            $cur->media_dir = dirname($rs_m->media_file);
            $cur->update('WHERE media_id = ' . (int) $rs_m->media_id);
        }

        return $cleanup_sessions;
    }
}
