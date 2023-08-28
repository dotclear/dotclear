<?php
/**
 * @since 2.27 Before as admin/search.php
 *
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Process\Backend;

use Dotclear\Core\Backend\Action\ActionsComments;
use Dotclear\Core\Backend\Action\ActionsPosts;
use Dotclear\Core\Backend\Listing\ListingComments;
use Dotclear\Core\Backend\Listing\ListingPosts;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Backend\UserPref;
use Dotclear\Core\Core;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Html;
use Exception;
use form;

class Search extends Process
{
    // Local properties (used by behavior callbacks)

    protected static $count   = null;
    protected static $list    = null;
    protected static $actions = null;

    public static function init(): bool
    {
        Page::check(Core::auth()->makePermissions([
            Core::auth()::PERMISSION_USAGE,
            Core::auth()::PERMISSION_CONTENT_ADMIN,
        ]));

        Core::behavior()->addBehaviors([
            'adminSearchPageComboV2' => [static::class,'typeCombo'],
            'adminSearchPageHeadV2'  => [static::class,'pageHead'],
            // posts search
            'adminSearchPageProcessV2' => [static::class,'processPosts'],
            'adminSearchPageDisplayV2' => [static::class,'displayPosts'],
        ]);
        Core::behavior()->addBehaviors([
            // comments search
            'adminSearchPageProcessV2' => [static::class,'processComments'],
            'adminSearchPageDisplayV2' => [static::class,'displayComments'],
        ]);

        $qtype_combo = [];
        # --BEHAVIOR-- adminSearchPageCombo -- array<int,array>
        Core::behavior()->callBehavior('adminSearchPageComboV2', [& $qtype_combo]);
        Core::backend()->qtype_combo = $qtype_combo;

        return self::status(true);
    }

    public static function process(): bool
    {
        Core::backend()->q     = !empty($_REQUEST['q']) ? $_REQUEST['q'] : (!empty($_REQUEST['qx']) ? $_REQUEST['qx'] : null);
        Core::backend()->qtype = !empty($_REQUEST['qtype']) ? $_REQUEST['qtype'] : 'p';

        if (!empty(Core::backend()->q) && !in_array(Core::backend()->qtype, Core::backend()->qtype_combo)) {
            Core::backend()->qtype = 'p';
        }

        Core::backend()->page = !empty($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        Core::backend()->nb   = UserPref::getUserFilters('search', 'nb');
        if (!empty($_GET['nb']) && (int) $_GET['nb'] > 0) {
            Core::backend()->nb = (int) $_GET['nb'];
        }

        return true;
    }

    public static function render(): void
    {
        $args = ['q' => Core::backend()->q, 'qtype' => Core::backend()->qtype, 'page' => Core::backend()->page, 'nb' => Core::backend()->nb];

        # --BEHAVIOR-- adminSearchPageHead -- array<string,string>
        $starting_scripts = Core::backend()->q ? Core::behavior()->callBehavior('adminSearchPageHeadV2', $args) : '';

        if (Core::backend()->q) {
            # --BEHAVIOR-- adminSearchPageProcess -- array<string,string>
            Core::behavior()->callBehavior('adminSearchPageProcessV2', $args);
        }

        Page::open(
            __('Search'),
            $starting_scripts,
            Page::breadcrumb(
                [
                    Html::escapeHTML(Core::blog()->name) => '',
                    __('Search')                         => '',
                ]
            )
        );

        echo
        '<form action="' . Core::backend()->url->get('admin.search') . '" method="get" role="search">' .
        '<div class="fieldset"><h3>' . __('Search options') . '</h3>' .
        '<p><label for="q">' . __('Query:') . ' </label>' .
        form::field('q', 30, 255, Html::escapeHTML(Core::backend()->q)) . '</p>' .
        '<p><label for="qtype">' . __('In:') . '</label> ' .
        form::combo('qtype', Core::backend()->qtype_combo, Core::backend()->qtype) . '</p>' .
        '<p><input type="submit" value="' . __('Search') . '" />' .
        ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
        form::hidden('process', 'Search') .
        '</p>' .
        '</div>' .
        '</form>';

        if (Core::backend()->q && !Core::error()->flag()) {
            ob_start();

            # --BEHAVIOR-- adminSearchPageDisplay -- array<string,string>
            Core::behavior()->callBehavior('adminSearchPageDisplayV2', $args);

            $res = ob_get_contents();
            ob_end_clean();
            echo $res ?: '<p>' . __('No results found') . '</p>';
        }

        Page::helpBlock('core_search');
        Page::close();
    }

    /**
     * Behaviors callbacks
     */
    public static function typeCombo(array $combo)
    {
        $combo[0][__('Search in entries')]  = 'p';
        $combo[0][__('Search in comments')] = 'c';
    }

    public static function pageHead(array $args)
    {
        if ($args['qtype'] == 'p') {
            return Page::jsLoad('js/_posts_list.js');
        } elseif ($args['qtype'] == 'c') {
            return Page::jsLoad('js/_comments.js');
        }
    }

    public static function processPosts(array $args)
    {
        if ($args['qtype'] != 'p') {
            return null;
        }

        $params = [
            'search'     => $args['q'],
            'limit'      => [(($args['page'] - 1) * $args['nb']), $args['nb']],
            'no_content' => true,
            'order'      => 'post_dt DESC',
        ];

        try {
            self::$count   = (int) Core::blog()->getPosts($params, true)->f(0);
            self::$list    = new ListingPosts(Core::blog()->getPosts($params), self::$count);
            self::$actions = new ActionsPosts(Core::backend()->url->get('admin.search'), $args);
            if (self::$actions->process()) {
                return;
            }
        } catch (Exception $e) {
            Core::error()->add($e->getMessage());
        }
    }

    public static function displayPosts(array $args)
    {
        if ($args['qtype'] != 'p' || self::$count === null) {
            return null;
        }

        if (self::$count > 0) {
            printf('<h3>' . __('one entry found', '%d entries found', self::$count) . '</h3>', self::$count);
        }

        self::$list->display(
            $args['page'],
            $args['nb'],
            '<form action="' . Core::backend()->url->get('admin.search') . '" method="post" id="form-entries">' .

            '%s' .

            '<div class="two-cols">' .
            '<p class="col checkboxes-helpers"></p>' .

            '<p class="col right"><label for="action" class="classic">' . __('Selected entries action:') . '</label> ' .
            form::combo('action', self::$actions->getCombo()) .
            '<input id="do-action" type="submit" value="' . __('ok') . '" /></p>' .
            Core::nonce()->getFormNonce() .
            str_replace('%', '%%', self::$actions->getHiddenFields()) .
            '</div>' .
            '</form>'
        );
    }

    public static function processComments(array $args)
    {
        if ($args['qtype'] != 'c') {
            return null;
        }

        $params = [
            'search'     => $args['q'],
            'limit'      => [(($args['page'] - 1) * $args['nb']), $args['nb']],
            'no_content' => true,
            'order'      => 'comment_dt DESC',
        ];

        try {
            self::$count   = Core::blog()->getComments($params, true)->f(0);
            self::$list    = new ListingComments(Core::blog()->getComments($params), self::$count);
            self::$actions = new ActionsComments(Core::backend()->url->get('admin.search'), $args);
            if (self::$actions->process()) {
                return;
            }
        } catch (Exception $e) {
            Core::error()->add($e->getMessage());
        }
    }

    public static function displayComments(array $args)
    {
        if ($args['qtype'] != 'c' || self::$count === null) {
            return null;
        }

        if (self::$count > 0) {
            printf('<h3>' . __('one comment found', '%d comments found', (int) self::$count) . '</h3>', self::$count);
        }

        // IP are available only for super-admin and admin
        $show_ip = Core::auth()->check(
            Core::auth()->makePermissions([
                Core::auth()::PERMISSION_CONTENT_ADMIN,
            ]),
            Core::blog()->id
        );

        self::$list->display(
            $args['page'],
            $args['nb'],
            '<form action="' . Core::backend()->url->get('admin.search') . '" method="post" id="form-comments">' .

            '%s' .

            '<div class="two-cols">' .
            '<p class="col checkboxes-helpers"></p>' .

            '<p class="col right"><label for="action" class="classic">' . __('Selected comments action:') . '</label> ' .
            form::combo('action', self::$actions->getCombo()) .
            '<input id="do-action" type="submit" value="' . __('ok') . '" /></p>' .
            Core::nonce()->getFormNonce() .
            str_replace('%', '%%', self::$actions->getHiddenFields()) .
            '</div>' .
            '</form>',
            false,
            false,
            $show_ip
        );
    }
}
