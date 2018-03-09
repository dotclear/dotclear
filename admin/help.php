<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

require dirname(__FILE__) . '/../inc/admin/prepend.php';

dcPage::check('usage,contentadmin');

$helpPage = function () {
    $ret = array('content' => '', 'title' => '');

    $args = func_get_args();
    if (empty($args)) {
        return $ret;
    }
    ;

    global $__resources;
    if (empty($__resources['help'])) {
        return $ret;
    }

    $content = '';
    $title   = '';
    foreach ($args as $v) {
        if (is_object($v) && isset($v->content)) {
            $content .= $v->content;
            continue;
        }

        if (!isset($__resources['help'][$v])) {
            continue;
        }
        $f = $__resources['help'][$v];
        if (!file_exists($f) || !is_readable($f)) {
            continue;
        }

        $fc = file_get_contents($f);
        if (preg_match('|<body[^>]*?>(.*?)</body>|ms', $fc, $matches)) {
            $content .= $matches[1];
            if (preg_match('|<title[^>]*?>(.*?)</title>|ms', $fc, $matches)) {
                $title = $matches[1];
            }
        } else {
            $content .= $fc;
        }
    }

    if (trim($content) == '') {
        return $ret;
    }

    $ret['content'] = $content;
    if ($title != '') {
        $ret['title'] = $title;
    }
    return $ret;
};

$help_page     = !empty($_GET['page']) ? html::escapeHTML($_GET['page']) : 'index';
$content_array = $helpPage($help_page);
if (($content_array['content'] == '') || ($help_page == 'index')) {
    $content_array = $helpPage('index');
}
if ($content_array['title'] != '') {
    $breadcrumb = dcPage::breadcrumb(
        array(
            __('Global help')       => $core->adminurl->get("admin.help"),
            $content_array['title'] => ''
        ));
} else {
    $breadcrumb = dcPage::breadcrumb(
        array(
            __('Global help') => ''
        ));
}

/* DISPLAY
-------------------------------------------------------- */
dcPage::open(__('Global help'),
    dcPage::jsPageTabs('first-step'),
    $breadcrumb
);

echo $content_array['content'];

// Prevents global help link display
$GLOBALS['__resources']['ctxhelp'] = true;

dcPage::close();
