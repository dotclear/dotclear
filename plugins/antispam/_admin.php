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

if (!defined('DC_CONTEXT_ADMIN')) {return;}

if (!defined('DC_ANTISPAM_CONF_SUPER')) {
    define('DC_ANTISPAM_CONF_SUPER', false);
}

$_menu['Plugins']->addItem(__('Antispam'),
    $core->adminurl->get('admin.plugin.antispam'),
    dcPage::getPF('antispam/icon.png'),
    preg_match('/' . preg_quote($core->adminurl->get('admin.plugin.antispam')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
    $core->auth->check('admin', $core->blog->id));

$core->addBehavior('coreAfterCommentUpdate', array('dcAntispam', 'trainFilters'));
$core->addBehavior('adminAfterCommentDesc', array('dcAntispam', 'statusMessage'));
$core->addBehavior('adminDashboardIcons', array('dcAntispam', 'dashboardIcon'));
$core->addBehavior('adminDashboardHeaders', array('dcAntispam', 'dashboardHeaders'));

$core->addBehavior('adminDashboardFavorites', 'antispamDashboardFavorites');
$core->addBehavior('adminDashboardFavsIcon', 'antispamDashboardFavsIcon');

function antispamDashboardFavorites($core, $favs)
{
    $favs->register('antispam', array(
        'title'       => __('Antispam'),
        'url'         => $core->adminurl->get('admin.plugin.antispam'),
        'small-icon'  => dcPage::getPF('antispam/icon.png'),
        'large-icon'  => dcPage::getPF('antispam/icon-big.png'),
        'permissions' => 'admin')
    );
}

function antispamDashboardFavsIcon($core, $name, $icon)
{
    // Check if it is comments favs
    if ($name == 'comments') {
        // Hack comments title if there is at least one spam
        $str = dcAntispam::dashboardIconTitle($core);
        if ($str != '') {
            $icon[0] .= $str;
        }
    }
}

if (!DC_ANTISPAM_CONF_SUPER || $core->auth->isSuperAdmin()) {
    $core->addBehavior('adminBlogPreferencesForm', array('antispamBehaviors', 'adminBlogPreferencesForm'));
    $core->addBehavior('adminBeforeBlogSettingsUpdate', array('antispamBehaviors', 'adminBeforeBlogSettingsUpdate'));
    $core->addBehavior('adminCommentsSpamForm', array('antispamBehaviors', 'adminCommentsSpamForm'));
    $core->addBehavior('adminPageHelpBlock', array('antispamBehaviors', 'adminPageHelpBlock'));
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

    public static function adminCommentsSpamForm($core)
    {
        $ttl = $core->blog->settings->antispam->antispam_moderation_ttl;
        if ($ttl != null && $ttl >= 0) {
            echo '<p>' . sprintf(__('All spam comments older than %s day(s) will be automatically deleted.'), $ttl) . ' ' .
            sprintf(__('You can modify this duration in the %s'), '<a href="' . $core->adminurl->get('admin.blog.pref') .
                '#antispam_moderation_ttl"> ' . __('Blog settings') . '</a>') .
                '.</p>';
        }
    }

    public static function adminBlogPreferencesForm($core, $settings)
    {
        $ttl = $settings->antispam->antispam_moderation_ttl;
        echo
        '<div class="fieldset"><h4 id="antispam_params">Antispam</h4>' .
        '<p><label for="antispam_moderation_ttl" class="classic">' . __('Delete junk comments older than') . ' ' .
        form::number('antispam_moderation_ttl', -1, 999, $ttl) .
        ' ' . __('days') .
        '</label></p>' .
        '<p class="form-note">' . __('Set -1 to disabled this feature ; Leave empty to use default 7 days delay.') . '</p>' .
        '<p><a href="' . $core->adminurl->get('admin.plugin.antispam') . '">' . __('Set spam filters.') . '</a></p>' .
            '</div>';
    }

    public static function adminBeforeBlogSettingsUpdate($settings)
    {
        $settings->addNamespace('antispam');
        $settings->antispam->put('antispam_moderation_ttl', (integer) $_POST['antispam_moderation_ttl']);
    }
}
