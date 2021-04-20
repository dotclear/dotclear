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
            $as->addAction($stub_actions, ['dcLegacyPosts', 'onActionLegacy']);
        }
    }

    public static function onActionLegacy($core, dcPostsActionsPage $as, $post)
    {
        $core->callBehavior('adminPostsActions', $core, $as->getRS(), $as->getAction(), $as->getRedirection());
        $as->beginPage('',
            dcPage::jsLoad('js/jquery/jquery.autocomplete.js') .
            dcPage::jsMetaEditor() .
            $core->callBehavior('adminPostsActionsHeaders'), '');
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
            $as->addAction($stub_actions, ['dcLegacyComments', 'onActionLegacy']);
        }
    }

    public static function onActionLegacy($core, dcCommentsActionsPage $as, $Comment)
    {
        $core->callBehavior('adminCommentsActions', $core, $as->getRS(), $as->getAction(), $as->getRedirection());
        $as->beginPage('',
            dcPage::jsLoad('js/jquery/jquery.autocomplete.js') .
            dcPage::jsMetaEditor() .
            $core->callBehavior('adminCommentsActionsHeaders'), '');
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
            $as->addAction($stub_actions, ['dcLegacyPages', 'onActionLegacy']);
        }
    }

    public static function onActionLegacy($core, dcPagesActionsPage $as, $post)
    {
        $core->callBehavior('adminPostsActions', $core, $as->getRS(), $as->getAction(), $as->getRedirection());
        $as->beginPage('',
            dcPage::jsLoad('js/jquery/jquery.autocomplete.js') .
            dcPage::jsMetaEditor() .
            $core->callBehavior('adminPostsActionsHeaders'), '');
        ob_start();
        $core->callBehavior('adminPostsActionsContent', $core, $as->getAction(), $as->getHiddenFields(true));
        $res = ob_get_contents();
        ob_end_clean();
        $res = str_replace('posts_actions.php', 'plugin.php', $res);
        echo $res;
        $as->endPage();
    }
}
