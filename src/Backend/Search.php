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

namespace Dotclear\Backend;

use adminCommentList;
use adminPostList;
use adminUserPref;
use dcAuth;
use dcCommentsActions;
use dcCore;
use dcPage;
use dcPostsActions;
use Dotclear\Helper\Html\Html;
use Exception;
use form;

class Search
{
    // Local properties (used by behavior callbacks)

    protected static $count   = null;
    protected static $list    = null;
    protected static $actions = null;

    public static function init(): bool
    {
        dcPage::check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_USAGE,
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]));

        dcCore::app()->addBehaviors([
            'adminSearchPageComboV2' => [static::class,'typeCombo'],
            'adminSearchPageHeadV2'  => [static::class,'pageHead'],
            // posts search
            'adminSearchPageProcessV2' => [static::class,'processPosts'],
            'adminSearchPageDisplayV2' => [static::class,'displayPosts'],
        ]);
        dcCore::app()->addBehaviors([
            // comments search
            'adminSearchPageProcessV2' => [static::class,'processComments'],
            'adminSearchPageDisplayV2' => [static::class,'displayComments'],
        ]);

        $qtype_combo = [];
        # --BEHAVIOR-- adminSearchPageCombo -- array<int,array>
        dcCore::app()->callBehavior('adminSearchPageComboV2', [& $qtype_combo]);
        dcCore::app()->admin->qtype_combo = $qtype_combo;

        return (static::$init = true);
    }

    public static function process(): bool
    {
        dcCore::app()->admin->q     = !empty($_REQUEST['q']) ? $_REQUEST['q'] : (!empty($_REQUEST['qx']) ? $_REQUEST['qx'] : null);
        dcCore::app()->admin->qtype = !empty($_REQUEST['qtype']) ? $_REQUEST['qtype'] : 'p';

        if (!empty(dcCore::app()->admin->q) && !in_array(dcCore::app()->admin->qtype, dcCore::app()->admin->qtype_combo)) {
            dcCore::app()->admin->qtype = 'p';
        }

        dcCore::app()->admin->page = !empty($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        dcCore::app()->admin->nb   = adminUserPref::getUserFilters('search', 'nb');
        if (!empty($_GET['nb']) && (int) $_GET['nb'] > 0) {
            dcCore::app()->admin->nb = (int) $_GET['nb'];
        }

        return true;
    }

    public static function render()
    {
        $args = ['q' => dcCore::app()->admin->q, 'qtype' => dcCore::app()->admin->qtype, 'page' => dcCore::app()->admin->page, 'nb' => dcCore::app()->admin->nb];

        # --BEHAVIOR-- adminSearchPageHead -- array<string,string>
        $starting_scripts = dcCore::app()->admin->q ? dcCore::app()->callBehavior('adminSearchPageHeadV2', $args) : '';

        if (dcCore::app()->admin->q) {
            # --BEHAVIOR-- adminSearchPageProcess -- array<string,string>
            dcCore::app()->callBehavior('adminSearchPageProcessV2', $args);
        }

        dcPage::open(
            __('Search'),
            $starting_scripts,
            dcPage::breadcrumb(
                [
                    Html::escapeHTML(dcCore::app()->blog->name) => '',
                    __('Search')                                => '',
                ]
            )
        );

        echo
        '<form action="' . dcCore::app()->adminurl->get('admin.search') . '" method="get" role="search">' .
        '<div class="fieldset"><h3>' . __('Search options') . '</h3>' .
        '<p><label for="q">' . __('Query:') . ' </label>' .
        form::field('q', 30, 255, Html::escapeHTML(dcCore::app()->admin->q)) . '</p>' .
        '<p><label for="qtype">' . __('In:') . '</label> ' .
        form::combo('qtype', dcCore::app()->admin->qtype_combo, dcCore::app()->admin->qtype) . '</p>' .
        '<p><input type="submit" value="' . __('Search') . '" />' .
        ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
        form::hidden('process', 'Search') .
        '</p>' .
        '</div>' .
        '</form>';

        if (dcCore::app()->admin->q && !dcCore::app()->error->flag()) {
            ob_start();

            # --BEHAVIOR-- adminSearchPageDisplay -- array<string,string>
            dcCore::app()->callBehavior('adminSearchPageDisplayV2', $args);

            $res = ob_get_contents();
            ob_end_clean();
            echo $res ?: '<p>' . __('No results found') . '</p>';
        }

        dcPage::helpBlock('core_search');
        dcPage::close();
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
            return dcPage::jsLoad('js/_posts_list.js');
        } elseif ($args['qtype'] == 'c') {
            return dcPage::jsLoad('js/_comments.js');
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
            self::$count   = (int) dcCore::app()->blog->getPosts($params, true)->f(0);
            self::$list    = new adminPostList(dcCore::app()->blog->getPosts($params), self::$count);
            self::$actions = new dcPostsActions(dcCore::app()->adminurl->get('admin.search'), $args);
            if (self::$actions->process()) {
                return;
            }
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
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
            '<form action="' . dcCore::app()->adminurl->get('admin.search') . '" method="post" id="form-entries">' .

            '%s' .

            '<div class="two-cols">' .
            '<p class="col checkboxes-helpers"></p>' .

            '<p class="col right"><label for="action" class="classic">' . __('Selected entries action:') . '</label> ' .
            form::combo('action', self::$actions->getCombo()) .
            '<input id="do-action" type="submit" value="' . __('ok') . '" /></p>' .
            dcCore::app()->formNonce() .
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
            self::$count   = dcCore::app()->blog->getComments($params, true)->f(0);
            self::$list    = new adminCommentList(dcCore::app()->blog->getComments($params), self::$count);
            self::$actions = new dcCommentsActions(dcCore::app()->adminurl->get('admin.search'), $args);
            if (self::$actions->process()) {
                return;
            }
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }
    }

    public static function displayComments(array $args)
    {
        if ($args['qtype'] != 'c' || self::$count === null) {
            return null;
        }

        if (self::$count > 0) {
            printf('<h3>' . __('one comment found', '%d comments found', self::$count) . '</h3>', self::$count);
        }

        self::$list->display(
            $args['page'],
            $args['nb'],
            '<form action="' . dcCore::app()->adminurl->get('admin.search') . '" method="post" id="form-comments">' .

            '%s' .

            '<div class="two-cols">' .
            '<p class="col checkboxes-helpers"></p>' .

            '<p class="col right"><label for="action" class="classic">' . __('Selected comments action:') . '</label> ' .
            form::combo('action', self::$actions->getCombo()) .
            '<input id="do-action" type="submit" value="' . __('ok') . '" /></p>' .
            dcCore::app()->formNonce() .
            str_replace('%', '%%', self::$actions->getHiddenFields()) .
            '</div>' .
            '</form>'
        );
    }
}
