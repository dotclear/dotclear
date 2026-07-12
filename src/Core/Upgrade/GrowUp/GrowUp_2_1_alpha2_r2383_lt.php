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

/**
 * @brief   Upgrade step.
 *
 * @todo switch to SqlStatement
 */
class GrowUp_2_1_alpha2_r2383_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        if (str_contains(App::db()->con()->driver(), 'sqlite')) {
            return $cleanup_sessions;
        }

        $schema = App::db()->con()->schema();
        $schema->dropUnique(App::db()->con()->prefix() . App::blog()->categories()::CATEGORY_TABLE_NAME, App::db()->con()->prefix() . 'uk_cat_title');

        # Reindex categories
        $rs = new MetaRecord(App::db()->con()->select(
            'SELECT cat_id, cat_title, blog_id ' .
            'FROM ' . App::db()->con()->prefix() . App::blog()->categories()::CATEGORY_TABLE_NAME . ' ' .
            'ORDER BY blog_id ASC , cat_position ASC '
        ));
        $cat_blog = $rs->strField('blog_id');
        $cat_id   = $rs->intField('cat_id');
        $i        = 2;
        while ($rs->fetch()) {
            if ($cat_blog !== $rs->strField('blog_id')) {
                $i = 2;
            }

            App::db()->con()->execute(
                'UPDATE ' . App::db()->con()->prefix() . App::blog()->categories()::CATEGORY_TABLE_NAME . ' ' .
                'SET ' . 'cat_lft = ' . ($i++) . ', cat_rgt = ' . ($i++) . ' ' .
                'WHERE cat_id = ' . $cat_id
            );
            $cat_blog = $rs->strField('blog_id');
        }

        return $cleanup_sessions;
    }
}
