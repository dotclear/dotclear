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
declare(strict_types=1);

namespace Dotclear\Plugin\antispam;

use ArrayObject;
use dcAdmin;
use dcAuth;
use dcCore;
use dcFavorites;
use dcNsProcess;
use dcPage;

class Backend extends dcNsProcess
{
    public static function init(): bool
    {
        if (defined('DC_CONTEXT_ADMIN')) {
            self::$init = true;
        }

        if (!defined('DC_ANTISPAM_CONF_SUPER')) {
            define('DC_ANTISPAM_CONF_SUPER', false);
        }

        return self::$init;
    }

    public static function process(): bool
    {
        if (!self::$init) {
            return false;
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

        dcCore::app()->addBehaviors([
            'coreAfterCommentUpdate'    => [Antispam::class, 'trainFilters'],
            'adminAfterCommentDesc'     => [Antispam::class, 'statusMessage'],
            'adminDashboardHeaders'     => [Antispam::class, 'dashboardHeaders'],
            'adminDashboardFavoritesV2' => function (dcFavorites $favs) {
                $favs->register(
                    'antispam',
                    [
                        'title'       => __('Antispam'),
                        'url'         => dcCore::app()->adminurl->get('admin.plugin.antispam'),
                        'small-icon'  => [dcPage::getPF('antispam/icon.svg'), dcPage::getPF('antispam/icon-dark.svg')],
                        'large-icon'  => [dcPage::getPF('antispam/icon.svg'), dcPage::getPF('antispam/icon-dark.svg')],
                        'permissions' => dcCore::app()->auth->makePermissions([
                            dcAuth::PERMISSION_ADMIN,
                        ]), ]
                );
            },
            'adminDashboardFavsIconV2'  => function (string $name, ArrayObject $icon) {
                // Check if it is comments favs
                if ($name === 'comments') {
                    // Hack comments title if there is at least one spam
                    $str = Antispam::dashboardIconTitle();
                    if ($str !== '') {
                        $icon[0] .= $str;
                    }
                }
            },
        ]);

        if (!DC_ANTISPAM_CONF_SUPER || dcCore::app()->auth->isSuperAdmin()) {
            dcCore::app()->addBehaviors([
                'adminBlogPreferencesFormV2'    => [BackendBehaviors::class, 'adminBlogPreferencesForm'],
                'adminBeforeBlogSettingsUpdate' => [BackendBehaviors::class, 'adminBeforeBlogSettingsUpdate'],
                'adminCommentsSpamFormV2'       => [BackendBehaviors::class, 'adminCommentsSpamForm'],
                'adminPageHelpBlock'            => [BackendBehaviors::class, 'adminPageHelpBlock'],
            ]);
        }

        return true;
    }
}
