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
        $user = App::users()->getUserCN(
            $rs->user_id,
            $rs->user_name,
            $rs->user_firstname,
            $rs->user_displayname
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
        return App::credential()->decryptData((string) $rs->credential_data);
    }
}
