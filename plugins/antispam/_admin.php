<?php
/**
 * @brief antispam, a plugin for Dotclear 2
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

if (!defined('DC_ANTISPAM_CONF_SUPER')) {
    define('DC_ANTISPAM_CONF_SUPER', false);
}

dcCore::app()->menu[dcAdmin::MENU_PLUGINS]->addItem(
    __('Antispam'),
    dcCore::app()->adminurl->get('admin.plugin.antispam'),
    [dcPage::getPF('antispam/icon.svg'), dcPage::getPF('antispam/icon-dark.svg')],
    preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.antispam')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
    dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
        dcAuth::PERMISSION_ADMIN,
    ]), dcCore::app()->blog->id)
);

dcCore::app()->addBehavior('coreAfterCommentUpdate', ['dcAntispam', 'trainFilters']);
dcCore::app()->addBehavior('adminAfterCommentDesc', ['dcAntispam', 'statusMessage']);
dcCore::app()->addBehavior('adminDashboardHeaders', ['dcAntispam', 'dashboardHeaders']);

dcCore::app()->addBehavior(
    'adminDashboardFavoritesV2',
    function ($favs) {
        $favs->register(
            'antispam',
            [
                'title'       => __('Antispam'),
                'url'         => dcCore::app()->adminurl->get('admin.plugin.antispam'),
                'small-icon'  => [dcPage::getPF('antispam/icon.svg'), dcPage::getPF('antispam/icon-dark.svg')],
                'large-icon'  => [dcPage::getPF('antispam/icon.svg'), dcPage::getPF('antispam/icon-dark.svg')],
                'permissions' => 'admin', ]
        );
    }
);
dcCore::app()->addBehavior(
    'adminDashboardFavsIconV2',
    function ($name, $icon) {
        // Check if it is comments favs
        if ($name == 'comments') {
            // Hack comments title if there is at least one spam
            $str = dcAntispam::dashboardIconTitle(dcCore::app());
            if ($str != '') {
                $icon[0] .= $str;
            }
        }
    }
);

if (!DC_ANTISPAM_CONF_SUPER || dcCore::app()->auth->isSuperAdmin()) {
    dcCore::app()->addBehavior('adminBlogPreferencesFormV2', ['antispamBehaviors', 'adminBlogPreferencesForm']);
    dcCore::app()->addBehavior('adminBeforeBlogSettingsUpdate', ['antispamBehaviors', 'adminBeforeBlogSettingsUpdate']);
    dcCore::app()->addBehavior('adminCommentsSpamFormV2', ['antispamBehaviors', 'adminCommentsSpamForm']);
    dcCore::app()->addBehavior('adminPageHelpBlock', ['antispamBehaviors', 'adminPageHelpBlock']);
}

class antispamBehaviors
{
    public static function adminPageHelpBlock($blocks)
    {
        $found = false;
        foreach ($blocks as $block) {
            if ($block == 'core_comments') {
                $found = true;

                break;
            }
        }
        if (!$found) {
            return;
        }
        $blocks[] = 'antispam_comments';
    }

    public static function adminCommentsSpamForm()
    {
        $ttl = dcCore::app()->blog->settings->antispam->antispam_moderation_ttl;
        if ($ttl != null && $ttl >= 0) {
            echo '<p>' . sprintf(__('All spam comments older than %s day(s) will be automatically deleted.'), $ttl) . ' ' .
            sprintf(__('You can modify this duration in the %s'), '<a href="' . dcCore::app()->adminurl->get('admin.blog.pref') .
                '#antispam_moderation_ttl"> ' . __('Blog settings') . '</a>') .
                '.</p>';
        }
    }

    public static function adminBlogPreferencesForm($settings)
    {
        $ttl = $settings->antispam->antispam_moderation_ttl;
        echo
        '<div class="fieldset"><h4 id="antispam_params">Antispam</h4>' .
        '<p><label for="antispam_moderation_ttl" class="classic">' . __('Delete junk comments older than') . ' ' .
        form::number('antispam_moderation_ttl', -1, 999, $ttl) .
        ' ' . __('days') .
        '</label></p>' .
        '<p class="form-note">' . __('Set -1 to disabled this feature ; Leave empty to use default 7 days delay.') . '</p>' .
        '<p><a href="' . dcCore::app()->adminurl->get('admin.plugin.antispam') . '">' . __('Set spam filters.') . '</a></p>' .
            '</div>';
    }

    public static function adminBeforeBlogSettingsUpdate($settings)
    {
        $settings->addNamespace('antispam');
        $settings->antispam->put('antispam_moderation_ttl', (int) $_POST['antispam_moderation_ttl']);
    }
}
