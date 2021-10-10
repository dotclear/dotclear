<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 *
 * @var dcCore $core
 */
require dirname(__FILE__) . '/../inc/admin/prepend.php';

dcPage::check('usage,contentadmin');

$core->addBehavior('adminSearchPageCombo', ['adminSearchPageDefault','typeCombo']);
$core->addBehavior('adminSearchPageHead', ['adminSearchPageDefault','pageHead']);
// posts search
$core->addBehavior('adminSearchPageProcess', ['adminSearchPageDefault','processPosts']);
$core->addBehavior('adminSearchPageDisplay', ['adminSearchPageDefault','displayPosts']);
// comments search
$core->addBehavior('adminSearchPageProcess', ['adminSearchPageDefault','processComments']);
$core->addBehavior('adminSearchPageDisplay', ['adminSearchPageDefault','displayComments']);

$qtype_combo = [];

# --BEHAVIOR-- adminSearchPageCombo
$core->callBehavior('adminSearchPageCombo', $core, [& $qtype_combo]);

$q     = !empty($_REQUEST['q']) ? $_REQUEST['q'] : (!empty($_REQUEST['qx']) ? $_REQUEST['qx'] : null);
$qtype = !empty($_REQUEST['qtype']) ? $_REQUEST['qtype'] : 'p';
if (!empty($q) && !in_array($qtype, $qtype_combo)) {
    $qtype = 'p';
}

$core->auth->user_prefs->addWorkspace('interface');
$page = !empty($_GET['page']) ? max(1, (integer) $_GET['page']) : 1;
$nb = adminUserPref::getUserFilters('search', 'nb');
if (!empty($_GET['nb']) && (integer) $_GET['nb'] > 0) {
    $nb = (integer) $_GET['nb'];
}

$args = ['q' => $q, 'qtype' => $qtype, 'page' => $page, 'nb' => $nb];

# --BEHAVIOR-- adminSearchPageHead
$starting_scripts = $q ? $core->callBehavior('adminSearchPageHead', $core, $args) : '';

if ($q) {

    # --BEHAVIOR-- adminSearchPageProcess
    $core->callBehavior('adminSearchPageProcess', $core, $args);
}

dcPage::open(__('Search'), $starting_scripts,
    dcPage::breadcrumb(
        [
            html::escapeHTML($core->blog->name) => '',
            __('Search')                        => ''
        ])
);

echo
'<form action="' . $core->adminurl->get('admin.search') . '" method="get" role="search">' .
'<div class="fieldset"><h3>' . __('Search options') . '</h3>' .
'<p><label for="q">' . __('Query:') . ' </label>' .
form::field('q', 30, 255, html::escapeHTML($q)) . '</p>' .
'<p><label for="qtype">' . __('In:') . '</label> ' .
form::combo('qtype', $qtype_combo, $qtype) . '</p>' .
'<p><input type="submit" value="' . __('Search') . '" />' .
' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
'</p>' .
'</div>' .
'</form>';

if ($q && !$core->error->flag()) {
    ob_start();

    # --BEHAVIOR-- adminSearchPageDisplay
    $core->callBehavior('adminSearchPageDisplay', $core, $args);

    $res = ob_get_contents();
    ob_end_clean();
    echo $res ?: '<p>' . __('No results found') . '</p>';
}

dcPage::helpBlock('core_search');
dcPage::close();

class adminSearchPageDefault
{
    protected static $count   = null;
    protected static $list    = null;
    protected static $actions = null;

    public static function typeCombo(dcCore $core, array $combo)
    {
        $combo[0][__('Search in entries')]  = 'p';
        $combo[0][__('Search in comments')] = 'c';
    }

    public static function pageHead(dcCore $core, array $args)
    {
        if ($args['qtype'] == 'p') {
            return dcPage::jsLoad('js/_posts_list.js');
        } elseif ($args['qtype'] == 'c') {
            return dcPage::jsLoad('js/_comments.js');
        }
    }

    public static function processPosts(dcCore $core, array $args)
    {
        if ($args['qtype'] != 'p') {
            return null;
        }

        $params = [
            'search'     => $args['q'],
            'limit'      => [(($args['page'] - 1) * $args['nb']), $args['nb']],
            'no_content' => true,
            'order'      => 'post_dt DESC'
        ];

        try {
            self::$count   = $core->blog->getPosts($params, true)->f(0);
            self::$list    = new adminPostList($core, $core->blog->getPosts($params), self::$count);
            self::$actions = new dcPostsActionsPage($core, $core->adminurl->get('admin.search'), $args);
            if (self::$actions->process()) {
                return;
            }
        } catch (Exception $e) {
            $core->error->add($e->getMessage());
        }
    }

    public static function displayPosts(dcCore $core, array $args)
    {
        if ($args['qtype'] != 'p' || self::$count === null) {
            return null;
        }

        if (self::$count > 0) {
            printf('<h3>' . __('one result', __('%d results'), self::$count) . '</h3>', self::$count);
        }

        self::$list->display($args['page'], $args['nb'],
            '<form action="' . $core->adminurl->get('admin.search') . '" method="post" id="form-entries">' .

            '%s' .

            '<div class="two-cols">' .
            '<p class="col checkboxes-helpers"></p>' .

            '<p class="col right"><label for="action" class="classic">' . __('Selected entries action:') . '</label> ' .
            form::combo('action', self::$actions->getCombo()) .
            '<input id="do-action" type="submit" value="' . __('ok') . '" /></p>' .
            $core->formNonce() .
            preg_replace('/%/','%%', self::$actions->getHiddenFields()) .
            '</div>' .
            '</form>'
        );
    }

    public static function processComments(dcCore $core, array $args)
    {
        if ($args['qtype'] != 'c') {
            return null;
        }

        $params = [
            'search'     => $args['q'],
            'limit'      => [(($args['page'] - 1) * $args['nb']), $args['nb']],
            'no_content' => true,
            'order'      => 'comment_dt DESC'
        ];

        try {
            self::$count   = $core->blog->getComments($params, true)->f(0);
            self::$list    = new adminCommentList($core, $core->blog->getComments($params), self::$count);
            self::$actions = new dcCommentsActionsPage($core, $core->adminurl->get('admin.search'), $args);
            if (self::$actions->process()) {
                return;
            }
        } catch (Exception $e) {
            $core->error->add($e->getMessage());
        }
    }

    public static function displayComments(dcCore $core, array $args)
    {
        if ($args['qtype'] != 'c' || self::$count === null) {
            return null;
        }

        if (self::$count > 0) {
            printf('<h3>' . __('one comment found', __('%d comments found'), self::$count) . '</h3>', self::$count);
        }

        self::$list->display($args['page'], $args['nb'],
            '<form action="' . $core->adminurl->get('admin.search') . '" method="post" id="form-comments">' .

            '%s' .

            '<div class="two-cols">' .
            '<p class="col checkboxes-helpers"></p>' .

            '<p class="col right"><label for="action" class="classic">' . __('Selected comments action:') . '</label> ' .
            form::combo('action', self::$actions->getCombo()) .
            '<input id="do-action" type="submit" value="' . __('ok') . '" /></p>' .
            $core->formNonce() .
            preg_replace('/%/','%%', self::$actions->getHiddenFields()) .
            '</div>' .
            '</form>'
        );
    }
}
