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
 * @brief Dotclear credential record helpers
 *
 * This class adds new methods to database credential results.
 * You can call them on every record comming from App::credential()->getCredentials() methods.
 *
 * @warning You should not give the first argument (usualy $rs) of every described function.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class Credential
{
    /**
     * Gets the user common name.
     *
     * @param   MetaRecord  $rs     Invisible parameter
     *
     * @return  string  The user common name.
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

    /**
     * Returns a data.
     *
     * @param   MetaRecord  $rs     Invisible parameter
     * @param   string      $name   The name of the data
     *
     * @return  mixed   A data
     */
    public static function getData(MetaRecord $rs, string $name = 'data')
    {
        return self::getAllData($rs)[$name] ?? null;
    }

    /**
     * Returns all data.
     *
     * @param   MetaRecord  $rs     Invisible parameter
     *
     * @return  array<string, mixed>    All the data
     */
    public static function getAllData(MetaRecord $rs): array
    {
        $credential_data = is_string($credential_data = $rs->credential_data) ? $credential_data : '';

        return App::credential()->decryptData($credential_data);
    }
}
