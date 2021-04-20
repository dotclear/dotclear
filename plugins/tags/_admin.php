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
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

$_menu['Blog']->addItem(__('Tags'),
    $core->adminurl->get('admin.plugin.tags', ['m' => 'tags']),
    dcPage::getPF('tags/icon.png'),
    preg_match('/' . preg_quote($core->adminurl->get('admin.plugin.tags')) . '&m=tag(s|_posts)?(&.*)?$/', $_SERVER['REQUEST_URI']),
    $core->auth->check('usage,contentadmin', $core->blog->id));

require dirname(__FILE__) . '/_widgets.php';

$core->addBehavior('adminPostFormItems', ['tagsBehaviors', 'tagsField']);

$core->addBehavior('adminAfterPostCreate', ['tagsBehaviors', 'setTags']);
$core->addBehavior('adminAfterPostUpdate', ['tagsBehaviors', 'setTags']);

$core->addBehavior('adminPostHeaders', ['tagsBehaviors', 'postHeaders']);

$core->addBehavior('adminPostsActionsPage', ['tagsBehaviors', 'adminPostsActionsPage']);

$core->addBehavior('adminPreferencesForm', ['tagsBehaviors', 'adminUserForm']);
$core->addBehavior('adminBeforeUserOptionsUpdate', ['tagsBehaviors', 'setTagListFormat']);

$core->addBehavior('adminUserForm', ['tagsBehaviors', 'adminUserForm']);
$core->addBehavior('adminBeforeUserCreate', ['tagsBehaviors', 'setTagListFormat']);
$core->addBehavior('adminBeforeUserUpdate', ['tagsBehaviors', 'setTagListFormat']);

$core->addBehavior('adminDashboardFavorites', ['tagsBehaviors', 'dashboardFavorites']);

$core->addBehavior('adminPageHelpBlock', ['tagsBehaviors', 'adminPageHelpBlock']);

$core->addBehavior('adminPostEditor', ['tagsBehaviors', 'adminPostEditor']);
$core->addBehavior('ckeditorExtraPlugins', ['tagsBehaviors', 'ckeditorExtraPlugins']);
