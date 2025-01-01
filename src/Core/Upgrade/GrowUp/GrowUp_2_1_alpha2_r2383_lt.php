<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Upgrade\GrowUp;

use Dotclear\App;

/**
 * @brief   Upgrade step.
 */
class GrowUp_2_1_alpha2_r2383_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        if (App::con()->driver() === 'sqlite') {
            return $cleanup_sessions;
        }

        $schema = App::con()->schema();
        $schema->dropUnique(App::con()->prefix() . App::blog()->categories()::CATEGORY_TABLE_NAME, App::con()->prefix() . 'uk_cat_title');

        # Reindex categories
        $rs = App::con()->select(
            'SELECT cat_id, cat_title, blog_id ' .
            'FROM ' . App::con()->prefix() . App::blog()->categories()::CATEGORY_TABLE_NAME . ' ' .
            'ORDER BY blog_id ASC , cat_position ASC '
        );
        $cat_blog = $rs->blog_id;
        $i        = 2;
        while ($rs->fetch()) {
            if ($cat_blog != $rs->blog_id) {
                $i = 2;
            }
            App::con()->execute(
                'UPDATE ' . App::con()->prefix() . App::blog()->categories()::CATEGORY_TABLE_NAME . ' SET '
                . 'cat_lft = ' . ($i++) . ', cat_rgt = ' . ($i++) . ' ' .
                'WHERE cat_id = ' . (int) $rs->cat_id
            );
            $cat_blog = $rs->blog_id;
        }

        return $cleanup_sessions;
    }
}
