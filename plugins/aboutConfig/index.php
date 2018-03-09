<?php
/**
 * @brief aboutConfig, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_CONTEXT_ADMIN')) {return;}

# Local navigation
if (!empty($_POST['gs_nav'])) {
    http::redirect($p_url . $_POST['gs_nav']);
    exit;
}
if (!empty($_POST['ls_nav'])) {
    http::redirect($p_url . $_POST['ls_nav']);
    exit;
}

# Local settings update
if (!empty($_POST['s']) && is_array($_POST['s'])) {
    try
    {
        foreach ($_POST['s'] as $ns => $s) {
            $core->blog->settings->addNamespace($ns);
            foreach ($s as $k => $v) {
                if ($_POST['s_type'][$ns][$k] == 'array') {
                    $v = json_decode($v, true);
                }
                $core->blog->settings->$ns->put($k, $v);
            }
            $core->blog->triggerBlog();
        }

        dcPage::addSuccessNotice(__('Configuration successfully updated'));
        http::redirect($p_url);
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

# Global settings update
if (!empty($_POST['gs']) && is_array($_POST['gs'])) {
    try
    {
        foreach ($_POST['gs'] as $ns => $s) {
            $core->blog->settings->addNamespace($ns);
            foreach ($s as $k => $v) {
                if ($_POST['gs_type'][$ns][$k] == 'array') {
                    $v = json_decode($v, true);
                }
                $core->blog->settings->$ns->put($k, $v, null, null, true, true);
            }
            $core->blog->triggerBlog();
        }

        dcPage::addSuccessNotice(__('Configuration successfully updated'));
        http::redirect($p_url . '&part=global');
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

$part = !empty($_GET['part']) && $_GET['part'] == 'global' ? 'global' : 'local';

function settingLine($id, $s, $ns, $field_name, $strong_label)
{
    if ($s['type'] == 'boolean') {
        $field = form::combo(array($field_name . '[' . $ns . '][' . $id . ']', $field_name . '_' . $ns . '_' . $id),
            array(__('yes') => 1, __('no') => 0), $s['value'] ? 1 : 0);
    } else {
        if ($s['type'] == 'array') {
            $field = form::field(array($field_name . '[' . $ns . '][' . $id . ']', $field_name . '_' . $ns . '_' . $id), 40, null,
                html::escapeHTML(json_encode($s['value'])));
        } else {
            $field = form::field(array($field_name . '[' . $ns . '][' . $id . ']', $field_name . '_' . $ns . '_' . $id), 40, null,
                html::escapeHTML($s['value']));
        }
    }
    $type = form::hidden(array($field_name . '_type' . '[' . $ns . '][' . $id . ']', $field_name . '_' . $ns . '_' . $id . '_type'),
        html::escapeHTML($s['type']));

    $slabel = $strong_label ? '<strong>%s</strong>' : '%s';

    return
    '<tr class="line">' .
    '<td scope="row"><label for="' . $field_name . '_' . $ns . '_' . $id . '">' . sprintf($slabel, html::escapeHTML($id)) . '</label></td>' .
    '<td>' . $field . '</td>' .
    '<td>' . $s['type'] . $type . '</td>' .
    '<td>' . html::escapeHTML($s['label']) . '</td>' .
        '</tr>';
}
?>
<html>
<head>
  <title>about:config</title>
  <?php echo dcPage::jsPageTabs($part) . dcPage::jsLoad(dcPage::getPF('aboutConfig/js/index.js')); ?>
</head>

<body>
<?php
echo dcPage::breadcrumb(
    array(
        __('System')                        => '',
        html::escapeHTML($core->blog->name) => '',
        __('about:config')                  => ''
    )) .
dcPage::notices();
?>

<div id="local" class="multi-part" title="<?php echo sprintf(__('Settings for %s'), html::escapeHTML($core->blog->name)); ?>">
<h3 class="out-of-screen-if-js"><?php echo sprintf(__('Settings for %s'), html::escapeHTML($core->blog->name)); ?></h3>

<?php
$table_header = '<div class="table-outer"><table class="settings" id="%s"><caption class="as_h3">%s</caption>' .
'<thead>' .
'<tr>' . "\n" .
'  <th class="nowrap">' . __('Setting ID') . '</th>' . "\n" .
'  <th>' . __('Value') . '</th>' . "\n" .
'  <th>' . __('Type') . '</th>' . "\n" .
'  <th class="maximalx">' . __('Description') . '</th>' . "\n" .
    '</tr>' . "\n" .
    '</thead>' . "\n" .
    '<tbody>';
$table_footer = '</tbody></table></div>';

$settings = array();
foreach ($core->blog->settings->dumpNamespaces() as $ns => $namespace) {
    foreach ($namespace->dumpSettings() as $k => $v) {
        $settings[$ns][$k] = $v;
    }
}
ksort($settings);
if (count($settings) > 0) {
    $ns_combo = array();
    foreach ($settings as $ns => $s) {
        $ns_combo[$ns] = '#l_' . $ns;
    }
    echo
    '<form action="' . $core->adminurl->get('admin.plugin') . '" method="post">' .
    '<p class="anchor-nav">' .
    '<label for="ls_nav" class="classic">' . __('Goto:') . '</label> ' . form::combo('ls_nav', $ns_combo) .
    ' <input type="submit" value="' . __('Ok') . '" id="ls_submit" />' .
    '<input type="hidden" name="p" value="aboutConfig" />' .
    $core->formNonce() . '</p></form>';
}
?>

<form action="<?php echo $core->adminurl->get('admin.plugin'); ?>" method="post">

<?php
foreach ($settings as $ns => $s) {
    ksort($s);
    echo sprintf($table_header, 'l_' . $ns, $ns);
    foreach ($s as $k => $v) {
        echo settingLine($k, $v, $ns, 's', !$v['global']);
    }
    echo $table_footer;
}
?>

<p><input type="submit" value="<?php echo __('Save'); ?>" />
<input type="hidden" name="p" value="aboutConfig" />
<?php echo $core->formNonce(); ?></p>
</form>
</div>

<div id="global" class="multi-part" title="<?php echo __('Global settings'); ?>">
<h3 class="out-of-screen-if-js"><?php echo __('Global settings'); ?></h3>

<?php
$settings = array();

foreach ($core->blog->settings->dumpNamespaces() as $ns => $namespace) {
    foreach ($namespace->dumpGlobalSettings() as $k => $v) {
        $settings[$ns][$k] = $v;
    }
}

ksort($settings);

if (count($settings) > 0) {
    $ns_combo = array();
    foreach ($settings as $ns => $s) {
        $ns_combo[$ns] = '#g_' . $ns;
    }
    echo
    '<form action="' . $core->adminurl->get('admin.plugin') . '" method="post">' .
    '<p class="anchor-nav">' .
    '<label for="gs_nav" class="classic">' . __('Goto:') . '</label> ' . form::combo('gs_nav', $ns_combo) . ' ' .
    '<input type="submit" value="' . __('Ok') . '" id="gs_submit" />' .
    '<input type="hidden" name="p" value="aboutConfig" />' .
    $core->formNonce() . '</p></form>';
}
?>

<form action="<?php echo $core->adminurl->get('admin.plugin'); ?>" method="post">

<?php
foreach ($settings as $ns => $s) {
    ksort($s);
    echo sprintf($table_header, 'g_' . $ns, $ns);
    foreach ($s as $k => $v) {
        echo settingLine($k, $v, $ns, 'gs', false);
    }
    echo $table_footer;
}
?>

<p><input type="submit" value="<?php echo __('Save'); ?>" />
<input type="hidden" name="p" value="aboutConfig" />
<?php echo $core->formNonce(); ?></p>
</form>
</div>

<?php dcPage::helpBlock('aboutConfig');?>

</body>
</html>
