<?php
/**
 * @brief dclegacy, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

$GLOBALS['core']->addBehavior('adminPostsActionsPage', ['dcLegacyPosts', 'adminPostsActionsPage']);
$GLOBALS['core']->addBehavior('adminPagesActionsPage', ['dcLegacyPages', 'adminPagesActionsPage']);
$GLOBALS['core']->addBehavior('adminCommentsActionsPage', ['dcLegacyComments', 'adminCommentsActionsPage']);
$GLOBALS['core']->addBehavior('adminFiltersLists', ['dcLegacyPreferences', 'adminFiltersLists']);

/* Handle deprecated behaviors :
 * adminPostsActionsCombo
 * adminPostsActionsHeaders
 * adminPostsActionsContent
 */
class dcLegacyPosts
{
    public static function adminPostsActionsPage($core, dcPostsActionsPage $as)
    {
        $stub_actions = new ArrayObject();
        $core->callBehavior('adminPostsActionsCombo', [$stub_actions]);
        if (!empty($stub_actions)) {
            $as->addAction($stub_actions->getArrayCopy(), ['dcLegacyPosts', 'onActionLegacy']);
        }
    }

    public static function onActionLegacy($core, dcPostsActionsPage $as, $post)
    {
        $core->callBehavior('adminPostsActions', $core, $as->getRS(), $as->getAction(), $as->getRedirection());
        $as->beginPage('',
            dcPage::jsLoad('js/jquery/jquery.autocomplete.js') .
            dcPage::jsMetaEditor() .
            $core->callBehavior('adminPostsActionsHeaders'));
        $core->callBehavior('adminPostsActionsContent', $core, $as->getAction(), $as->getHiddenFields(true));
        $as->endPage();
    }
}

/* Handle deprecated behaviors :
 * adminCommentsActionsCombo
 * adminCommentsActionsHeaders
 * adminCommentsActionsContent
 */
class dcLegacyComments
{
    public static function adminCommentsActionsPage($core, dcCommentsActionsPage $as)
    {
        $stub_actions = new ArrayObject();
        $core->callBehavior('adminCommentsActionsCombo', [$stub_actions]);
        if (!empty($stub_actions)) {
            $as->addAction($stub_actions->getArrayCopy(), ['dcLegacyComments', 'onActionLegacy']);
        }
    }

    public static function onActionLegacy($core, dcCommentsActionsPage $as, $Comment)
    {
        $core->callBehavior('adminCommentsActions', $core, $as->getRS(), $as->getAction(), $as->getRedirection());
        $as->beginPage('',
            dcPage::jsLoad('js/jquery/jquery.autocomplete.js') .
            dcPage::jsMetaEditor() .
            $core->callBehavior('adminCommentsActionsHeaders'));
        ob_start();
        $core->callBehavior('adminCommentsActionsContent', $core, $as->getAction(), $as->getHiddenFields(true));
        $res = ob_get_contents();
        ob_end_clean();
        $res = str_replace('comments_actions.php', $as->getURI(), $res);
        echo $res;
        $as->endPage();
    }
}
/* Handle deprecated behaviors :
 * adminPagesActionsCombo
 * adminPagesActionsHeaders
 * adminPagesActionsContent
 */
class dcLegacyPages
{
    public static function adminPagesActionsPage($core, dcPagesActionsPage $as)
    {
        $stub_actions = new ArrayObject();
        $core->callBehavior('adminPagesActionsCombo', [$stub_actions]);
        if (!empty($stub_actions)) {
            $as->addAction($stub_actions->getArrayCopy(), ['dcLegacyPages', 'onActionLegacy']);
        }
    }

    public static function onActionLegacy($core, dcPagesActionsPage $as, $post)
    {
        $core->callBehavior('adminPostsActions', $core, $as->getRS(), $as->getAction(), $as->getRedirection());
        $as->beginPage('',
            dcPage::jsLoad('js/jquery/jquery.autocomplete.js') .
            dcPage::jsMetaEditor() .
            $core->callBehavior('adminPostsActionsHeaders'));
        ob_start();
        $core->callBehavior('adminPostsActionsContent', $core, $as->getAction(), $as->getHiddenFields(true));
        $res = ob_get_contents();
        ob_end_clean();
        $res = str_replace('posts_actions.php', 'plugin.php', $res);
        echo $res;
        $as->endPage();
    }
}

/* Handle deprecated 2.20 filter-controls user preferences :
 * Now all in $core->auth->user_prefs->interface->sorts
 */
class dcLegacyPreferences
{
    public static function adminFiltersLists($core, $sorts)
    {
        $core->auth->user_prefs->addWorkspace('interface');

        $sorts['posts'][2] = $core->auth->user_prefs->interface->posts_sortby ?: 'post_dt';
        $sorts['posts'][3] = $core->auth->user_prefs->interface->posts_order ?: 'desc';
        if (0 < ($nb = $core->auth->user_prefs->interface->nb_posts_per_page)) {
            $sorts['posts'][4][1] = $nb;
        }

        $sorts['comments'][2] = $core->auth->user_prefs->interface->comments_sortby ?: 'comment_dt';
        $sorts['comments'][3] = $core->auth->user_prefs->interface->comments_order ?: 'desc';
        if (0 < ($nb = $core->auth->user_prefs->interface->nb_comments_per_page)) {
            $sorts['comments'][4][1] = $nb;
        }

        $sorts['blogs'][2] = $core->auth->user_prefs->interface->blogs_sortby ?: 'blog_upddt';
        $sorts['blogs'][3] = $core->auth->user_prefs->interface->blogs_order ?: 'desc';
        if (0 < ($nb = $core->auth->user_prefs->interface->nb_blogs_per_page)) {
            $sorts['blogs'][4][1] = $nb;
        }

        if ($core->auth->isSuperAdmin()) {
            $sorts['users'][2] = $core->auth->user_prefs->interface->users_sortby ?: 'user_id';
            $sorts['users'][3] = $core->auth->user_prefs->interface->users_order ?: 'asc';
            if (0 < ($nb = $core->auth->user_prefs->interface->nb_users_per_page)) {
                $sorts['users'][4][1] = $nb;
            }
        }

        if (0 < ($nb = $core->auth->user_prefs->interface->media_by_page)) {
            $sorts['media'][4][1] = $nb;
        }

        if (0 < ($nb = $core->auth->user_prefs->interface->nb_searchresults_per_page)) {
            $sorts['search'][4][1] = $nb;
        }
    }
}
