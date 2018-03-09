<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

require dirname(__FILE__) . '/../inc/admin/prepend.php';

dcPage::checkSuper();

$is_writable = is_dir(DC_L10N_ROOT) && is_writable(DC_L10N_ROOT);
$iso_codes   = l10n::getISOCodes();

# Get languages list on Dotclear.net
$dc_langs    = false;
$feed_reader = new feedReader;
$feed_reader->setCacheDir(DC_TPL_CACHE);
$feed_reader->setTimeout(5);
$feed_reader->setUserAgent('Dotclear - http://www.dotclear.org/');
try {
    $dc_langs = $feed_reader->parse(sprintf(DC_L10N_UPDATE_URL, DC_VERSION));
    if ($dc_langs !== false) {
        $dc_langs = $dc_langs->items;
    }
} catch (Exception $e) {}

# Language installation function
$lang_install = function ($file) {
    $zip = new fileUnzip($file);
    $zip->getList(false, '#(^|/)(__MACOSX|\.svn|\.DS_Store|\.directory|Thumbs\.db)(/|$)#');

    if (!preg_match('/^[a-z]{2,3}(-[a-z]{2})?$/', $zip->getRootDir())) {
        throw new Exception(__('Invalid language zip file.'));
    }

    if ($zip->isEmpty() || !$zip->hasFile($zip->getRootDir() . '/main.po')) {
        throw new Exception(__('The zip file does not appear to be a valid Dotclear language pack.'));
    }

    $target      = dirname($file);
    $destination = $target . '/' . $zip->getRootDir();
    $res         = 1;

    if (is_dir($destination)) {
        if (!files::deltree($destination)) {
            throw new Exception(__('An error occurred during language upgrade.'));
        }
        $res = 2;
    }

    $zip->unzipAll($target);
    return $res;
};

# Delete a language pack
if ($is_writable && !empty($_POST['delete']) && !empty($_POST['locale_id'])) {
    try
    {
        $locale_id = $_POST['locale_id'];
        if (!isset($iso_codes[$locale_id]) || !is_dir(DC_L10N_ROOT . '/' . $locale_id)) {
            throw new Exception(__('No such installed language'));
        }

        if ($locale_id == 'en') {
            throw new Exception(__("You can't remove English language."));
        }

        if (!files::deltree(DC_L10N_ROOT . '/' . $locale_id)) {
            throw new Exception(__('Permissions to delete language denied.'));
        }

        dcPage::addSuccessNotice(__('Language has been successfully deleted.'));
        $core->adminurl->redirect("admin.langs");
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

# Download a language pack
if ($is_writable && !empty($_POST['pkg_url'])) {
    try
    {
        if (empty($_POST['your_pwd']) || !$core->auth->checkPassword($_POST['your_pwd'])) {
            throw new Exception(__('Password verification failed'));
        }

        $url  = html::escapeHTML($_POST['pkg_url']);
        $dest = DC_L10N_ROOT . '/' . basename($url);
        if (!preg_match('#^http://[^.]+\.dotclear\.(net|org)/.*\.zip$#', $url)) {
            throw new Exception(__('Invalid language file URL.'));
        }

        $client = netHttp::initClient($url, $path);
        $client->setUserAgent('Dotclear - http://www.dotclear.org/');
        $client->useGzip(false);
        $client->setPersistReferers(false);
        $client->setOutput($dest);
        $client->get($path);

        try {
            $ret_code = $lang_install($dest);
        } catch (Exception $e) {
            @unlink($dest);
            throw $e;
        }

        @unlink($dest);
        if ($ret_code == 2) {
            dcPage::addSuccessNotice(__('Language has been successfully upgraded'));
        } else {
            dcPage::addSuccessNotice(__('Language has been successfully installed.'));
        }
        $core->adminurl->redirect("admin.langs");
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

# Upload a language pack
if ($is_writable && !empty($_POST['upload_pkg'])) {
    try
    {
        if (empty($_POST['your_pwd']) || !$core->auth->checkPassword($_POST['your_pwd'])) {
            throw new Exception(__('Password verification failed'));
        }

        files::uploadStatus($_FILES['pkg_file']);
        $dest = DC_L10N_ROOT . '/' . $_FILES['pkg_file']['name'];
        if (!move_uploaded_file($_FILES['pkg_file']['tmp_name'], $dest)) {
            throw new Exception(__('Unable to move uploaded file.'));
        }

        try {
            $ret_code = $lang_install($dest);
        } catch (Exception $e) {
            @unlink($dest);
            throw $e;
        }

        @unlink($dest);
        if ($ret_code == 2) {
            dcPage::addSuccessNotice(__('Language has been successfully upgraded'));
        } else {
            dcPage::addSuccessNotice(__('Language has been successfully installed.'));
        }
        $core->adminurl->redirect("admin.langs");
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

/* DISPLAY Main page
-------------------------------------------------------- */
dcPage::open(__('Languages management'),
    dcPage::jsLoad('js/_langs.js'),
    dcPage::breadcrumb(
        array(
            __('System')               => '',
            __('Languages management') => ''
        ))
);

if (!empty($_GET['removed'])) {
    dcPage::success(__('Language has been successfully deleted.'));
}

if (!empty($_GET['added'])) {
    dcPage::success(($_GET['added'] == 2 ? __('Language has been successfully upgraded') : __('Language has been successfully installed.')));
}

echo
'<p>' . __('Here you can install, upgrade or remove languages for your Dotclear ' .
    'installation.') . '</p>' .
'<p>' . sprintf(__('You can change your user language in your <a href="%1$s">preferences</a> or ' .
    'change your blog\'s main language in your <a href="%2$s">blog settings</a>.'),
    $core->adminurl->get("admin.user.preferences"), $core->adminurl->get("admin.blog.pref")) . '</p>';

echo
'<h3>' . __('Installed languages') . '</h3>';

$locales_content = scandir(DC_L10N_ROOT);
$tmp             = array();
foreach ($locales_content as $v) {
    $c = ($v == '.' || $v == '..' || $v == 'en' || !is_dir(DC_L10N_ROOT . '/' . $v) || !isset($iso_codes[$v]));

    if (!$c) {
        $tmp[$v] = DC_L10N_ROOT . '/' . $v;
    }
}
$locales_content = $tmp;

if (empty($locales_content)) {
    echo '<p><strong>' . __('No additional language is installed.') . '</strong></p>';
} else {
    echo
    '<div class="table-outer clear">' .
    '<table class="plugins"><tr>' .
    '<th>' . __('Language') . '</th>' .
    '<th class="nowrap">' . __('Action') . '</th>' .
        '</tr>';

    foreach ($locales_content as $k => $v) {
        $is_deletable = $is_writable && is_writable($v);

        echo
        '<tr class="line wide">' .
        '<td class="maximal nowrap">(' . $k . ') ' .
        '<strong>' . html::escapeHTML($iso_codes[$k]) . '</strong></td>' .
            '<td class="nowrap action">';

        if ($is_deletable) {
            echo
            '<form action="' . $core->adminurl->get("admin.langs") . '" method="post">' .
            '<div>' .
            $core->formNonce() .
            form::hidden(array('locale_id'), html::escapeHTML($k)) .
            '<input type="submit" class="delete" name="delete" value="' . __('Delete') . '" /> ' .
                '</div>' .
                '</form>';
        }

        echo '</td></tr>';
    }
    echo '</table></div>';
}

echo '<h3>' . __('Install or upgrade languages') . '</h3>';

if (!$is_writable) {
    echo '<p>' . sprintf(__('You can install or remove a language by adding or ' .
        'removing the relevant directory in your %s folder.'), '<strong>locales</strong>') . '</p>';
}

if (!empty($dc_langs) && $is_writable) {
    $dc_langs_combo = array();
    foreach ($dc_langs as $k => $v) {
        if ($v->link && isset($iso_codes[$v->title])) {
            $dc_langs_combo[html::escapeHTML('(' . $v->title . ') ' . $iso_codes[$v->title])] = html::escapeHTML($v->link);
        }
    }

    echo
    '<form method="post" action="' . $core->adminurl->get("admin.langs") . '" enctype="multipart/form-data" class="fieldset">' .
    '<h4>' . __('Available languages') . '</h4>' .
    '<p>' . sprintf(__('You can download and install a additional language directly from Dotclear.net. ' .
        'Proposed languages are based on your version: %s.'), '<strong>' . DC_VERSION . '</strong>') . '</p>' .
    '<p class="field"><label for="pkg_url" class="classic">' . __('Language:') . '</label> ' .
    form::combo(array('pkg_url'), $dc_langs_combo) . '</p>' .
    '<p class="field"><label for="your_pwd1" class="classic required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Your password:') . '</label> ' .
    form::password(array('your_pwd', 'your_pwd1'), 20, 255,
        array(
            'extra_html'   => 'required placeholder="' . __('Password') . '"',
            'autocomplete' => 'current-password')
    ) . '</p>' .
    '<p><input type="submit" value="' . __('Install language') . '" />' .
    $core->formNonce() .
        '</p>' .
        '</form>';
}

if ($is_writable) {
    # 'Upload language pack' form
    echo
    '<form method="post" action="' . $core->adminurl->get("admin.langs") . '" enctype="multipart/form-data" class="fieldset">' .
    '<h4>' . __('Upload a zip file') . '</h4>' .
    '<p>' . __('You can install languages by uploading zip files.') . '</p>' .
    '<p class="field"><label for="pkg_file" class="classic required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Language zip file:') . '</label> ' .
    '<input type="file" id="pkg_file" name="pkg_file" required /></p>' .
    '<p class="field"><label for="your_pwd2" class="classic required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Your password:') . '</label> ' .
    form::password(array('your_pwd', 'your_pwd2'), 20, 255,
        array(
            'extra_html'   => 'required placeholder="' . __('Password') . '"',
            'autocomplete' => 'current-password')
    ) . '</p>' .
    '<p><input type="submit" name="upload_pkg" value="' . __('Upload language') . '" />' .
    $core->formNonce() .
        '</p>' .
        '</form>';
}
dcPage::helpBlock('core_langs');
dcPage::close();
