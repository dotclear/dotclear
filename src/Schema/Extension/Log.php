<?php

/**
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Schema\Extension;

use Dotclear\App;
use Dotclear\Database\MetaRecord;

/**
 * @brief Dotclear log record helpers
 *
 * This class adds new methods to database post results.
 * You can call them on every record comming from Blog::getPosts and similar
 * methods.
 *
 * @warning You should not give the first argument (usualy $rs) of every described function.
 */
class Log
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
        $user_id          = is_string($user_id = $rs->user_id) ? $user_id : '';
        $user_name        = is_string($user_name = $rs->user_name) ? $user_name : null;
        $user_firstname   = is_string($user_firstname = $rs->user_firstname) ? $user_firstname : null;
        $user_displayname = is_string($user_displayname = $rs->user_displayname) ? $user_displayname : null;

        $user = App::users()->getUserCN(
            $user_id,
            $user_name,
            $user_firstname,
            $user_displayname
        );

        if ($user === 'unknown') {
            return __('unknown');
        }

        return $user;
    }
}
