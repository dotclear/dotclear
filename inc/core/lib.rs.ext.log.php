<?php
/**
 * Core notice handler.
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

use Dotclear\Database\MetaRecord;

/**
 * Extent log Record class.
 */
class rsExtLog
{
    /**
     * Gets the user common name.
     *
     * @param      MetaRecord  $rs     Invisible parameter
     *
     * @return     string  The user common name.
     */
    public static function getUserCN(MetaRecord $rs): string
    {
        $user = dcUtils::getUserCN(
            $rs->user_id,
            $rs->user_name,
            $rs->user_firstname,
            $rs->user_displayname
        );

        if ($user === 'unknown') {
            $user = __('unknown');
        }

        return $user;
    }
}
