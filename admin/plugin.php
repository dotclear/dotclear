<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
require __DIR__ . '/../inc/admin/prepend.php';

dcPage::check(dcCore::app()->auth->makePermissions([
    dcAuth::PERMISSION_USAGE,
    dcAuth::PERMISSION_CONTENT_ADMIN,
]));

$p_file = '';
$p      = !empty($_REQUEST['p']) ? $_REQUEST['p'] : null;
$popup  = (int) !empty($_REQUEST['popup']);

if ($popup) {
    $open_f  = ['dcPage', 'openPopup'];
    $close_f = ['dcPage', 'closePopup'];
} else {
    $open_f  = ['dcPage', 'open'];
    $close_f = ['dcPage', 'close'];
}

if (dcCore::app()->plugins->moduleExists($p)) {
    $p_file = dcCore::app()->plugins->moduleRoot($p) . '/index.php';
}

if (file_exists($p_file)) {
    # Loading plugin
    $p_info = dcCore::app()->plugins->getModules($p);

    $p_name = $p;
    dcCore::app()->admin->setPluginURL('plugin.php?p=' . $p);

    $p_title   = 'no content - plugin';
    $p_head    = '';
    $p_content = '<p>' . __('No content found on this plugin.') . '</p>';

    ob_start();
    include $p_file;
    $res = ob_get_contents();
    ob_end_clean();

    if (preg_match('|<head>(.*?)</head|ms', $res, $m)) {
        if (preg_match('|<title>(.*?)</title>|ms', $m[1], $mt)) {
            $p_title = $mt[1];
        }

        if (preg_match_all('|(<script.*?>.*?</script>)|ms', $m[1], $ms)) {
            foreach ($ms[1] as $v) {
                $p_head .= $v . "\n";
            }
        }

        if (preg_match_all('|(<style.*?>.*?</style>)|ms', $m[1], $ms)) {
            foreach ($ms[1] as $v) {
                $p_head .= $v . "\n";
            }
        }

        if (preg_match_all('|(<link.*?/>)|ms', $m[1], $ms)) {
            foreach ($ms[1] as $v) {
                $p_head .= $v . "\n";
            }
        }
    }

    if (preg_match('|<body.*?>(.+)</body>|ms', $res, $m)) {
        $p_content = $m[1];
    }

    call_user_func($open_f, $p_title, $p_head);
    echo $p_content;
    if (!$popup) {
        // Add direct links to plugin settings if any
        $settings = adminModulesList::getSettingsUrls((string) $p, true, false);
        if (!empty($settings)) {
            echo '<hr class="clear"/><p class="right modules">' . implode(' - ', $settings) . '</p>';
        }
    }
    call_user_func($close_f);
} else {
    call_user_func(
        $open_f,
        __('Plugin not found'),
        '',
        dcPage::breadcrumb(
            [
                __('System')           => '',
                __('Plugin not found') => '',
            ]
        )
    );

    echo '<p>' . __('The plugin you reached does not exist or does not have an admin page.') . '</p>';

    call_user_func($close_f);
}
