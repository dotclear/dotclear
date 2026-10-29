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
class GrowUp_2_14_3_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        # Update flie exclusion upload regex
        $sql = new UpdateStatement();
        $sql
            ->ref(App::db()->con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME)
            ->columns('setting_value')
            ->values('/\.(phps?|pht(ml)?|phl|.?html?|xml|js|htaccess)[0-9]*$/i')
            ->where('setting_id = ' . $sql->quote('media_exclusion'))
            ->and('setting_ns = ' . $sql->quote('system'))
            ->and('(setting_value = ' . $sql->quote('/\.php[0-9]*$/i') . ' OR setting_value = ' . $sql->quote('/\.php$/i') . ')')
            ->or('setting_value = ' . $sql->quote('/\.(phps?|pht(ml)?|phl)[0-9]*$/i'))
            ->or('setting_value = ' . $sql->quote('/\.(phps?|pht(ml)?|phl|s?html?|js)[0-9]*$/i'))
            ->or('setting_value = ' . $sql->quote('/\.(phps?|pht(ml)?|phl|s?html?|js|htaccess)[0-9]*$/i'))
            ->update();

        return $cleanup_sessions;
    }
}
