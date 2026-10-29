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
class GrowUp_2_16_9_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        // Fix 87,5% which should be 87.5% in pref for htmlfontsize
        $sql = new UpdateStatement();
        $sql
            ->ref(App::db()->con()->prefix() . App::userWorkspace()::WS_TABLE_NAME)
            ->set('pref_value = REPLACE(pref_value, ' . $sql->quote('87,5%') . ', ' . $sql->quote('87.5%') . ')')
            ->where('pref_id = ' . $sql->quote('htmlfontsize'))
            ->and('pref_ws = ' . $sql->quote('interface'))
            ->update();

        return $cleanup_sessions;
    }
}
