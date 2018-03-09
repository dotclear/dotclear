<?php
/**
 * @brief pings, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_CONTEXT_ADMIN')) {return;}

dcPage::checkSuper();

try
{
    $pings_uris = $core->blog->settings->pings->pings_uris;
    if (!$pings_uris) {
        $pings_uris = array();
    }

    if (isset($_POST['pings_srv_name'])) {
        $pings_srv_name = is_array($_POST['pings_srv_name']) ? $_POST['pings_srv_name'] : array();
        $pings_srv_uri  = is_array($_POST['pings_srv_uri']) ? $_POST['pings_srv_uri'] : array();
        $pings_uris     = array();

        foreach ($pings_srv_name as $k => $v) {
            if (trim($v) && trim($pings_srv_uri[$k])) {
                $pings_uris[trim($v)] = trim($pings_srv_uri[$k]);
            }
        }

        $core->blog->settings->addNamespace('pings');
        // Settings for all blogs
        $core->blog->settings->pings->put('pings_active', !empty($_POST['pings_active']), null, null, true, true);
        $core->blog->settings->pings->put('pings_uris', $pings_uris, null, null, true, true);
        // Settings for current blog only
        $core->blog->settings->pings->put('pings_auto', !empty($_POST['pings_auto']), null, null, true, false);

        dcPage::addSuccessNotice(__('Settings have been successfully updated.'));
        http::redirect($p_url);
    }
} catch (Exception $e) {
    $core->error->add($e->getMessage());
}
?>
<html>
<head>
  <title><?php echo __('Pings'); ?></title>
</head>

<body>
<?php

echo dcPage::breadcrumb(
    array(
        __('Plugins')             => '',
        __('Pings configuration') => ''
    ));

echo
'<form action="' . $p_url . '" method="post">' .
'<p><label for="pings_active" class="classic">' . form::checkbox('pings_active', 1, $core->blog->settings->pings->pings_active) .
__('Activate pings extension') . '</label></p>';

$i = 0;
foreach ($pings_uris as $n => $u) {
    echo
    '<p><label for="pings_srv_name-' . $i . '" class="classic">' . __('Service name:') . '</label> ' .
    form::field(array('pings_srv_name[]', 'pings_srv_name-' . $i), 20, 128, html::escapeHTML($n)) . ' ' .
    '<label for="pings_srv_uri-' . $i . '" class="classic">' . __('Service URI:') . '</label> ' .
    form::url(array('pings_srv_uri[]', 'pings_srv_uri-' . $i), array(
        'size'    => 40,
        'default' => html::escapeHTML($u)
    ));

    if (!empty($_GET['test'])) {
        try {
            pingsAPI::doPings($u, 'Example site', 'http://example.com');
            echo ' <img src="images/check-on.png" alt="OK" />';
        } catch (Exception $e) {
            echo ' <img src="images/check-off.png" alt="' . __('Error') . '" /> ' . $e->getMessage();
        }
    }

    echo '</p>';
    $i++;
}

echo
'<p><label for="pings_srv_name2" class="classic">' . __('Service name:') . '</label> ' .
form::field(array('pings_srv_name[]', 'pings_srv_name2'), 20, 128) . ' ' .
'<label for="pings_srv_uri2" class="classic">' . __('Service URI:') . '</label> ' .
form::url(array('pings_srv_uri[]', 'pings_srv_uri2'), 40) .
'</p>' .

'<p><label for="pings_auto" class="classic">' . form::checkbox('pings_auto', 1, $core->blog->settings->pings->pings_auto) .
__('Auto pings all services on first publication of entry (current blog only)') . '</label></p>' .

'<p><input type="submit" value="' . __('Save') . '" />' .
$core->formNonce() . '</p>' .
    '</form>';

echo '<p><a class="button" href="' . $p_url . '&amp;test=1">' . __('Test ping services') . '</a></p>';
?>

<?php dcPage::helpBlock('pings');?>

</body>
</html>
