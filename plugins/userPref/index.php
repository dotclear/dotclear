<?php
/**
 * @brief userPref, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_CONTEXT_ADMIN')) {return;}

# Local navigation
if (!empty($_POST['gp_nav'])) {
    http::redirect($p_url . $_POST['gp_nav']);
    exit;
}
if (!empty($_POST['lp_nav'])) {
    http::redirect($p_url . $_POST['lp_nav']);
    exit;
}

# Local prefs update
if (!empty($_POST['s']) && is_array($_POST['s'])) {
    try
    {
        foreach ($_POST['s'] as $ws => $s) {
            $core->auth->user_prefs->addWorkspace($ws);
            foreach ($s as $k => $v) {
                if ($_POST['s_type'][$ws][$k] == 'array') {
                    $v = json_decode($v, true);
                }
                $core->auth->user_prefs->$ws->put($k, $v);
            }
        }

        dcPage::addSuccessNotice(__('Preferences successfully updated'));
        http::redirect($p_url);
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

# Global prefs update
if (!empty($_POST['gs']) && is_array($_POST['gs'])) {
    try
    {
        foreach ($_POST['gs'] as $ws => $s) {
            $core->auth->user_prefs->addWorkspace($ws);
            foreach ($s as $k => $v) {
                if ($_POST['gs_type'][$ws][$k] == 'array') {
                    $v = json_decode($v, true);
                }
                $core->auth->user_prefs->$ws->put($k, $v, null, null, true, true);
            }
        }

        dcPage::addSuccessNotice(__('Preferences successfully updated'));
        http::redirect($p_url . '&part=global');
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

$part = !empty($_GET['part']) && $_GET['part'] == 'global' ? 'global' : 'local';

function prefLine($id, $s, $ws, $field_name, $strong_label)
{
    if ($s['type'] == 'boolean') {
        $field = form::combo(array($field_name . '[' . $ws . '][' . $id . ']', $field_name . '_' . $ws . '_' . $id),
            array(__('yes') => 1, __('no') => 0), $s['value'] ? 1 : 0);
    } else {
        if ($s['type'] == 'array') {
            $field = form::field(array($field_name . '[' . $ws . '][' . $id . ']', $field_name . '_' . $ws . '_' . $id), 40, null,
                html::escapeHTML(json_encode($s['value'])));
        } else {
            $field = form::field(array($field_name . '[' . $ws . '][' . $id . ']', $field_name . '_' . $ws . '_' . $id), 40, null,
                html::escapeHTML($s['value']));
        }
    }
    $type = form::hidden(array($field_name . '_type' . '[' . $ws . '][' . $id . ']', $field_name . '_' . $ws . '_' . $id . '_type'),
        html::escapeHTML($s['type']));

    $slabel = $strong_label ? '<strong>%s</strong>' : '%s';

    return
    '<tr class="line">' .
    '<td scope="row"><label for="' . $field_name . '_' . $ws . '_' . $id . '">' . sprintf($slabel, html::escapeHTML($id)) . '</label></td>' .
    '<td>' . $field . '</td>' .
    '<td>' . $s['type'] . $type . '</td>' .
    '<td>' . html::escapeHTML($s['label']) . '</td>' .
        '</tr>';
}
?>
<html>
<head>
  <title>user:preferences</title>
  <?php echo dcPage::jsPageTabs($part) . dcPage::jsLoad(dcPage::getPF('userPref/js/index.js')); ?>
</head>

<body>
<?php
echo dcPage::breadcrumb(
    array(
        __('System')                            => '',
        html::escapeHTML($core->auth->userID()) => '',
        __('user:preferences')                  => ''
    )) .
dcPage::notices();

?>

<div id="local" class="multi-part" title="<?php echo __('User preferences'); ?>">
<h3 class="out-of-screen-if-js"><?php echo __('User preferences'); ?></h3>

<?php
$table_header = '<div class="table-outer"><table class="prefs" id="%s"><caption class="as_h3">%s</caption>' .
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

$prefs = array();
foreach ($core->auth->user_prefs->dumpWorkspaces() as $ws => $workspace) {
    foreach ($workspace->dumpPrefs() as $k => $v) {
        $prefs[$ws][$k] = $v;
    }
}
ksort($prefs);
if (count($prefs) > 0) {
    $ws_combo = array();
    foreach ($prefs as $ws => $s) {
        $ws_combo[$ws] = '#l_' . $ws;
    }
    echo
    '<form action="' . $core->adminurl->get('admin.plugin') . '" method="post">' .
    '<p class="anchor-nav">' .
    '<label for="lp_nav" class="classic">' . __('Goto:') . '</label> ' .
    form::combo('lp_nav', $ws_combo, array('class' => 'navigation')) .
    ' <input type="submit" value="' . __('Ok') . '" id="lp_submit" />' .
    '<input type="hidden" name="p" value="userPref" />' .
    $core->formNonce() . '</p></form>';
}
?>

<form action="<?php echo $core->adminurl->get('admin.plugin'); ?>" method="post">

<?php
foreach ($prefs as $ws => $s) {
    ksort($s);
    echo sprintf($table_header, 'l_' . $ws, $ws);
    foreach ($s as $k => $v) {
        echo prefLine($k, $v, $ws, 's', !$v['global']);
    }
    echo $table_footer;
}
?>

<p><input type="submit" value="<?php echo __('Save'); ?>" />
<input type="hidden" name="p" value="userPref" />
<?php echo $core->formNonce(); ?></p>
</form>
</div>

<div id="global" class="multi-part" title="<?php echo __('Global preferences'); ?>">
<h3 class="out-of-screen-if-js"><?php echo __('Global preferences'); ?></h3>

<?php
$prefs = array();

foreach ($core->auth->user_prefs->dumpWorkspaces() as $ws => $workspace) {
    foreach ($workspace->dumpGlobalPrefs() as $k => $v) {
        $prefs[$ws][$k] = $v;
    }
}

ksort($prefs);

if (count($prefs) > 0) {
    $ws_combo = array();
    foreach ($prefs as $ws => $s) {
        $ws_combo[$ws] = '#g_' . $ws;
    }
    echo
    '<form action="' . $core->adminurl->get('admin.plugin') . '" method="post">' .
    '<p class="anchor-nav">' .
    '<label for="gp_nav" class="classic">' . __('Goto:') . '</label> ' .
    form::combo('gp_nav', $ws_combo, array('class' => 'navigation')) .
    ' <input type="submit" value="' . __('Ok') . '" id="gp_submit" />' .
    '<input type="hidden" name="p" value="userPref" />' .
    $core->formNonce() . '</p></form>';
}
?>

<form action="<?php echo $core->adminurl->get('admin.plugin'); ?>" method="post">

<?php
foreach ($prefs as $ws => $s) {
    ksort($s);
    echo sprintf($table_header, 'g_' . $ws, $ws);
    foreach ($s as $k => $v) {
        echo prefLine($k, $v, $ws, 'gs', false);
    }
    echo $table_footer;
}
?>

<p><input type="submit" value="<?php echo __('Save'); ?>" />
<input type="hidden" name="p" value="userPref" />
<?php echo $core->formNonce(); ?></p>
</form>
</div>

<?php dcPage::helpBlock('userPref');?>

</body>
</html>
