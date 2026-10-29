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
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Database\Statement\UpdateStatement;

/**
 * @brief   Upgrade step.
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
        $rs = (new SelectStatement())
            ->columns(['cat_id', 'cat_title', 'blog_id'])
            ->from(App::db()->con()->prefix() . App::blog()->categories()::CATEGORY_TABLE_NAME)
            ->order(['blog_id ASC', 'cat_position ASC'])
            ->select();
        if (!$rs instanceof MetaRecord) {
            return $cleanup_sessions;
        }

        $cat_blog = $rs->strField('blog_id');
        $cat_id   = $rs->intField('cat_id');
        $i        = 2;
        while ($rs->fetch()) {
            if ($cat_blog !== $rs->strField('blog_id')) {
                $i = 2;
            }

            $sql = new UpdateStatement();
            $sql
                ->ref(App::db()->con()->prefix() . App::blog()->categories()::CATEGORY_TABLE_NAME)
                ->columns(['cat_lft', 'cat_rgt'])
                ->values([$i++, $i++])
                ->where('cat_id = ' . $cat_id)
                ->update();
            $cat_blog = $rs->strField('blog_id');
        }

        return $cleanup_sessions;
    }
}
