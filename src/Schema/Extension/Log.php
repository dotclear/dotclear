<?php
/**
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
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
        $user = App::users()->getUserCN(
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
