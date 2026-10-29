<?php

/**
 * @package     Dotclear
 * @subpackage  Upgrade
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Upgrade\GrowUp;

use Dotclear\App;
use Dotclear\Database\Statement\UpdateStatement;

/**
 * @brief   Upgrade step.
 */
class GrowUp_2_12_2_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // SQlite Clearbricks driver does not allow using single quote at beginning or end of a field value
        // so we have to use neutral values (localhost and 127.0.0.1) for some CSP directives
        $csp_prefix = App::db()->con()->syntax() === 'sqlite' ? 'localhost ' : ''; // Hack for SQlite Clearbricks driver

        # Update CSP img-src default directive
        $sql = new UpdateStatement();
        $sql
            ->ref(App::db()->con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME)
            ->columns('setting_value')
            ->values($csp_prefix . "'self' data: http://media.dotaddict.org blob:")
            ->where('setting_id = ' . $sql->quote('csp_admin_img'))
            ->and('setting_ns = ' . $sql->quote('system'))
            ->and('setting_value = ' . $sql->quote($csp_prefix . "'self' data: media.dotaddict.org blob:"))
            ->update();

        return $cleanup_sessions;
    }
}
