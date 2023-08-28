<?php
/**
 * @brief tags, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\tags;

use dcCore;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Core;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Html;

class Manage extends Process
{
    public static function init(): bool
    {
        if (My::checkContext(My::MANAGE)) {
            self::status(($_REQUEST['m'] ?? 'tags') === 'tag_posts' ? ManagePosts::init() : true);
        }

        return self::status();
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (($_REQUEST['m'] ?? 'tags') === 'tag_posts') {
            return ManagePosts::process();
        }

        Core::backend()->tags = Core::meta()->getMetadata(['meta_type' => 'tag']);
        Core::backend()->tags = Core::meta()->computeMetaStats(Core::backend()->tags);
        Core::backend()->tags->sort('meta_id_lower', 'asc');

        return true;
    }

    /**
     * Renders the page.
     */
    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        if (($_REQUEST['m'] ?? 'tags') === 'tag_posts') {
            ManagePosts::render();

            return;
        }

        Page::openModule(
            My::name(),
            My::cssLoad('style')
        );

        echo
        Page::breadcrumb(
            [
                Html::escapeHTML(Core::blog()->name) => '',
                My::name()                                  => '',
            ]
        ) .
        Notices::getNotices();

        $last_letter = null;
        $cols        = ['', ''];
        $col         = 0;
        while (Core::backend()->tags->fetch()) {
            $letter = mb_strtoupper(mb_substr(Core::backend()->tags->meta_id_lower, 0, 1));

            if ($last_letter != $letter) {
                if (Core::backend()->tags->index() >= round(Core::backend()->tags->count() / 2)) {
                    $col = 1;
                }
                $cols[$col] .= '<tr class="tagLetter"><td colspan="2"><span>' . $letter . '</span></td></tr>';
            }

            $cols[$col] .= '<tr class="line">' .
            '<td class="maximal"><a href="' . Core::backend()->getPageURL() .
            '&amp;m=tag_posts&amp;tag=' . rawurlencode(Core::backend()->tags->meta_id) . '">' . Core::backend()->tags->meta_id . '</a></td>' .
            '<td class="nowrap count"><strong>' . Core::backend()->tags->count . '</strong> ' .
                ((Core::backend()->tags->count == 1) ? __('entry') : __('entries')) . '</td>' .
                '</tr>';

            $last_letter = $letter;
        }

        $table = '<div class="col"><table class="tags">%s</table></div>';

        if ($cols[0]) {
            echo
            '<div class="two-cols">';
            printf($table, $cols[0]);
            if ($cols[1]) {
                printf($table, $cols[1]);
            }
            echo
            '</div>';
        } else {
            echo
            '<p>' . __('No tags on this blog.') . '</p>';
        }

        Page::helpBlock(My::id());

        Page::closeModule();
    }
}
