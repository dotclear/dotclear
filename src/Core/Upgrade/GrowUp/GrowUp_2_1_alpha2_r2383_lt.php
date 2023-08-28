<?php
/**
 * @package Dotclear
 * @subpackage Upgrade
 *
 * Dotclear upgrade procedure.
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Upgrade\GrowUp;

use dcCategories;
use dcCore;
use Dotclear\Core\Core;
use Dotclear\Database\AbstractSchema;

class GrowUp_2_1_alpha2_r2383_lt
{
    public static function init(bool $cleanup_sessions): bool
    {
        $schema = AbstractSchema::init(Core::con());
        $schema->dropUnique(Core::con()->prefix() . dcCategories::CATEGORY_TABLE_NAME, Core::con()->prefix() . 'uk_cat_title');

        # Reindex categories
        $rs = Core::con()->select(
            'SELECT cat_id, cat_title, blog_id ' .
            'FROM ' . Core::con()->prefix() . dcCategories::CATEGORY_TABLE_NAME . ' ' .
            'ORDER BY blog_id ASC , cat_position ASC '
        );
        $cat_blog = $rs->blog_id;
        $i        = 2;
        while ($rs->fetch()) {
            if ($cat_blog != $rs->blog_id) {
                $i = 2;
            }
            Core::con()->execute(
                'UPDATE ' . Core::con()->prefix() . dcCategories::CATEGORY_TABLE_NAME . ' SET '
                . 'cat_lft = ' . ($i++) . ', cat_rgt = ' . ($i++) . ' ' .
                'WHERE cat_id = ' . (int) $rs->cat_id
            );
            $cat_blog = $rs->blog_id;
        }

        return $cleanup_sessions;
    }
}
