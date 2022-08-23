<?php
/**
 * @brief importExport, a plugin for Dotclear 2
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

function listImportExportModules(dcCore $core, $modules)
{
    $res = '';
    foreach ($modules as $id) {
        $o = new $id(dcCore::app());

        $res .= '<dt><a href="' . $o->getURL(true) . '">' . html::escapeHTML($o->name) . '</a></dt>' .
        '<dd>' . html::escapeHTML($o->description) . '</dd>';

        unset($o);
    }

    return '<dl class="modules">' . $res . '</dl>';
}

$modules = new ArrayObject(['import' => [], 'export' => []]);

# --BEHAVIOR-- importExportModules
dcCore::app()->callBehavior('importExportModules', $modules, dcCore::app());

$type = null;
if (!empty($_REQUEST['type']) && in_array($_REQUEST['type'], ['export', 'import'])) {
    $type = $_REQUEST['type'];
}

$module = null;
if ($type && !empty($_REQUEST['module']) && isset($modules[$type]) && in_array($_REQUEST['module'], $modules[$type])) {
    $module = new $_REQUEST['module'](dcCore::app());
    $module->init();
}

if ($type && $module !== null && !empty($_REQUEST['do'])) {
    try {
        $module->process($_REQUEST['do']);
    } catch (Exception $e) {
        dcCore::app()->error->add($e->getMessage());
    }
}

$title = __('Import/Export');

echo '
<html>
<head>
    <title>' . $title . '</title>' .
dcPage::cssModuleLoad('importExport/style.css') .
dcPage::jsJson('ie_msg', ['please_wait' => __('Please wait...')]) .
dcPage::jsModuleLoad('importExport/js/script.js') .
'</head>
<body>';

if ($type && $module !== null) {
    echo dcPage::breadcrumb(
        [
            __('Plugins')                   => '',
            $title                          => $p_url,
            html::escapeHTML($module->name) => '',
        ]
    ) .
    dcPage::notices();

    echo
        '<div id="ie-gui">';

    $module->gui();

    echo '</div>';
} else {
    echo dcPage::breadcrumb(
        [
            __('Plugins') => '',
            $title        => '',
        ]
    ) .
    dcPage::notices();

    echo '<h3>' . __('Import') . '</h3>' . listImportExportModules(dcCore::app(), $modules['import']);

    echo
    '<h3>' . __('Export') . '</h3>' .
    '<p class="info">' . sprintf(
        __('Export functions are in the page %s.'),
        '<a href="' . dcCore::app()->adminurl->get('admin.plugin.maintenance', ['tab' => 'backup']) . '#backup">' . __('Maintenance') . '</a>'
    ) . '</p>';
}

dcPage::helpBlock('import');

echo '</body></html>';
