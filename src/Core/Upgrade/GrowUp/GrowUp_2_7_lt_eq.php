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
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\InsertStatement;
use Dotclear\Database\Statement\SelectStatement;

/**
 * @brief   Upgrade step.
 */
class GrowUp_2_7_lt_eq
{
    public static function init(bool $cleanup_sessions): bool
    {
        # Some new settings should be initialized, prepare db queries
        $table = App::db()->con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME;

        # Add nb of posts for home (first page), copying nb of posts on every page
        $sql = new SelectStatement();
        $rs  = $sql
            ->columns('count(1)')
            ->from($table)
            ->where('setting_id = ' . $sql->quote('nb_post_for_home'))
            ->and('setting_ns = ' . $sql->quote('system'))
            ->and('blog_id IS NULL')
            ->select();
        if (!$rs instanceof MetaRecord) {
            return $cleanup_sessions;
        }

        if ($rs->cardinal() === 0) {
            $sql = new SelectStatement();
            $rs  = $sql
                ->columns('count(1)')
                ->from($table)
                ->where('setting_id = ' . $sql->quote('nb_post_per_page'))
                ->and('setting_ns = ' . $sql->quote('system'))
                ->and('blog_id IS NULL')
                ->select();
            if (!$rs instanceof MetaRecord) {
                return $cleanup_sessions;
            }

            $nb_page = $rs->cardinal();
            $insert  = new InsertStatement();
            $insert
                ->into($table)
                ->columns(['setting_id', 'setting_ns', 'setting_value', 'setting_type', 'setting_label'])
                ->values([['nb_post_for_home', 'system', $nb_page, 'string', 'Nb of posts on home (first page only)']])
                ->insert();
        }

        return $cleanup_sessions;
    }
}
