<?php
/**
 * @since 2.27 Before as admin/langs.php
 *
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Process\Backend;

use dcCore;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Core;
use Dotclear\Core\Process;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Zip\Unzip;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\L10n;
use Dotclear\Helper\Network\Feed\Reader;
use Dotclear\Helper\Network\HttpClient;
use Exception;
use form;

class Langs extends Process
{
    // Local constants

    private const LANG_INSTALLED = 1;
    private const LANG_UPDATED   = 2;

    public static function init(): bool
    {
        Page::checkSuper();

        Core::backend()->is_writable = is_dir(DC_L10N_ROOT) && is_writable(DC_L10N_ROOT);
        Core::backend()->iso_codes   = L10n::getISOCodes();

        # Get languages list on Dotclear.net
        Core::backend()->dc_langs = false;

        $feed_reader = new Reader();

        $feed_reader->setCacheDir(DC_TPL_CACHE);
        $feed_reader->setTimeout(5);
        $feed_reader->setUserAgent('Dotclear - https://dotclear.org/');

        try {
            $parse = $feed_reader->parse(sprintf(DC_L10N_UPDATE_URL, DC_VERSION));
            if ($parse !== false) {
                Core::backend()->dc_langs = $parse->items;
            }
        } catch (Exception $e) {
            // Ignore exceptions
        }

        return self::status(true);
    }

    public static function process(): bool
    {
        /**
         * Language installation function
         *
         * @param      mixed      $file   The file
         *
         * @throws     Exception
         *
         * @return     int        1 = installation ok, 2 = update ok
         */
        $lang_install = function ($file): int {
            // Language installation function
            $zip = new Unzip($file);
            $zip->getList(false, '#(^|/)(__MACOSX|\.svn|\.hg.*|\.git.*|\.DS_Store|\.directory|Thumbs\.db)(/|$)#');

            if (!preg_match('/^[a-z]{2,3}(-[a-z]{2})?$/', (string) $zip->getRootDir())) {
                throw new Exception(__('Invalid language zip file.'));
            }

            if ($zip->isEmpty() || !$zip->hasFile($zip->getRootDir() . '/main.po')) {
                throw new Exception(__('The zip file does not appear to be a valid Dotclear language pack.'));
            }

            $target      = dirname($file);
            $destination = $target . '/' . $zip->getRootDir();
            $res         = self::LANG_INSTALLED;

            if (is_dir($destination)) {
                if (!Files::deltree($destination)) {
                    throw new Exception(__('An error occurred during language upgrade.'));
                }
                $res = self::LANG_UPDATED;
            }

            $zip->unzipAll($target);

            return $res;
        };

        # Delete a language pack
        if (Core::backend()->is_writable && !empty($_POST['delete']) && !empty($_POST['locale_id'])) {
            try {
                $locale_id = $_POST['locale_id'];
                if (!isset(Core::backend()->iso_codes[$locale_id]) || !is_dir(DC_L10N_ROOT . '/' . $locale_id)) {
                    throw new Exception(__('No such installed language'));
                }

                if ($locale_id == 'en') {
                    throw new Exception(__("You can't remove English language."));
                }

                if (!Files::deltree(DC_L10N_ROOT . '/' . $locale_id)) {
                    throw new Exception(__('Permissions to delete language denied.'));
                }

                Notices::addSuccessNotice(__('Language has been successfully deleted.'));
                Core::backend()->url->redirect('admin.langs');
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        # Download a language pack
        if (Core::backend()->is_writable && !empty($_POST['pkg_url'])) {
            try {
                if (empty($_POST['your_pwd']) || !Core::auth()->checkPassword($_POST['your_pwd'])) {
                    throw new Exception(__('Password verification failed'));
                }

                $url  = Html::escapeHTML($_POST['pkg_url']);
                $dest = DC_L10N_ROOT . '/' . basename($url);
                if (!preg_match('#^https://[^.]+\.dotclear\.(net|org)/.*\.zip$#', $url)) {
                    throw new Exception(__('Invalid language file URL.'));
                }

                $path   = '';
                $client = HttpClient::initClient($url, $path);
                $client->setUserAgent('Dotclear - https://dotclear.org/');
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
                if ($ret_code === self::LANG_UPDATED) {
                    Notices::addSuccessNotice(__('Language has been successfully upgraded'));
                } else {
                    Notices::addSuccessNotice(__('Language has been successfully installed.'));
                }
                Core::backend()->url->redirect('admin.langs');
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        # Upload a language pack
        if (Core::backend()->is_writable && !empty($_POST['upload_pkg'])) {
            try {
                if (empty($_POST['your_pwd']) || !Core::auth()->checkPassword($_POST['your_pwd'])) {
                    throw new Exception(__('Password verification failed'));
                }

                Files::uploadStatus($_FILES['pkg_file']);
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
                if ($ret_code === self::LANG_UPDATED) {
                    Notices::addSuccessNotice(__('Language has been successfully upgraded'));
                } else {
                    Notices::addSuccessNotice(__('Language has been successfully installed.'));
                }
                Core::backend()->url->redirect('admin.langs');
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        return true;
    }

    public static function render(): void
    {
        Page::open(
            __('Languages management'),
            Page::jsLoad('js/_langs.js'),
            Page::breadcrumb(
                [
                    __('System')               => '',
                    __('Languages management') => '',
                ]
            )
        );

        if (!empty($_GET['removed'])) {
            Notices::success(__('Language has been successfully deleted.'));
        }

        if (!empty($_GET['added'])) {
            Notices::success(($_GET['added'] == 2 ? __('Language has been successfully upgraded') : __('Language has been successfully installed.')));
        }

        echo
        '<p>' . __('Here you can install, upgrade or remove languages for your Dotclear installation.') . '</p>' .
        '<p>' . sprintf(
            __('You can change your user language in your <a href="%1$s">preferences</a> or change your blog\'s main language in your <a href="%2$s">blog settings</a>.'),
            Core::backend()->url->get('admin.user.preferences'),
            Core::backend()->url->get('admin.blog.pref')
        ) . '</p>';

        echo
        '<h3>' . __('Installed languages') . '</h3>';

        $langs      = scandir(DC_L10N_ROOT);
        $langs_list = [];
        foreach ($langs as $lang) {
            $check = ($lang === '.' || $lang === '..' || $lang === 'en' || !is_dir(DC_L10N_ROOT . '/' . $lang) || !isset(Core::backend()->iso_codes[$lang]));

            if (!$check) {
                $langs_list[$lang] = DC_L10N_ROOT . '/' . $lang;
            }
        }

        if (empty($langs_list)) {
            echo
            '<p><strong>' . __('No additional language is installed.') . '</strong></p>';
        } else {
            echo
            '<div class="table-outer clear">' .
            '<table class="plugins"><tr>' .
            '<th>' . __('Language') . '</th>' .
            '<th class="nowrap">' . __('Action') . '</th>' .
            '</tr>';

            foreach ($langs_list as $lang_code => $lang) {
                $is_deletable = Core::backend()->is_writable && is_writable($lang);

                echo
                '<tr class="line wide">' .
                '<td class="maximal nowrap" lang="' . $lang_code . '">(' . $lang_code . ') ' .
                '<strong>' . Html::escapeHTML(Core::backend()->iso_codes[$lang_code]) . '</strong></td>' .
                '<td class="nowrap action">';

                if ($is_deletable) {
                    echo
                    '<form action="' . Core::backend()->url->get('admin.langs') . '" method="post">' .
                    '<div>' .
                    Core::nonce()->getFormNonce() .
                    form::hidden(['locale_id'], Html::escapeHTML($lang_code)) .
                    '<input type="submit" class="delete" name="delete" value="' . __('Delete') . '" /> ' .
                    '</div>' .
                    '</form>';
                }

                echo
                '</td></tr>';
            }
            echo
            '</table></div>';
        }

        echo '<h3>' . __('Install or upgrade languages') . '</h3>';

        if (!Core::backend()->is_writable) {
            echo '<p>' . sprintf(__('You can install or remove a language by adding or ' .
        'removing the relevant directory in your %s folder.'), '<strong>locales</strong>') . '</p>';
        }

        if (!empty(Core::backend()->dc_langs) && Core::backend()->is_writable) {
            $dc_langs_combo = [];
            foreach (Core::backend()->dc_langs as $lang) {
                if ($lang->link && isset(Core::backend()->iso_codes[$lang->title])) {
                    $dc_langs_combo[Html::escapeHTML('(' . $lang->title . ') ' . Core::backend()->iso_codes[$lang->title])] = Html::escapeHTML($lang->link);
                }
            }

            echo
            '<form method="post" action="' . Core::backend()->url->get('admin.langs') . '" enctype="multipart/form-data" class="fieldset">' .
            '<h4>' . __('Available languages') . '</h4>' .
            '<p>' . sprintf(__('You can download and install a additional language directly from Dotclear.net. ' .
                'Proposed languages are based on your version: %s.'), '<strong>' . DC_VERSION . '</strong>') . '</p>' .
            '<p class="field"><label for="pkg_url" class="classic">' . __('Language:') . '</label> ' .
            form::combo(['pkg_url'], $dc_langs_combo) . '</p>' .
            '<p class="field"><label for="your_pwd1" class="classic required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Your password:') . '</label> ' .
            form::password(
                ['your_pwd', 'your_pwd1'],
                20,
                255,
                [
                    'extra_html'   => 'required placeholder="' . __('Password') . '"',
                    'autocomplete' => 'current-password', ]
            ) . '</p>' .
            '<p><input type="submit" value="' . __('Install language') . '" />' .
            Core::nonce()->getFormNonce() .
            '</p>' .
            '</form>';
        }

        if (Core::backend()->is_writable) {
            # 'Upload language pack' form
            echo
            '<form method="post" action="' . Core::backend()->url->get('admin.langs') . '" enctype="multipart/form-data" class="fieldset">' .
            '<h4>' . __('Upload a zip file') . '</h4>' .
            '<p>' . __('You can install languages by uploading zip files.') . '</p>' .
            '<p class="field"><label for="pkg_file" class="classic required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Language zip file:') . '</label> ' .
            '<input type="file" id="pkg_file" name="pkg_file" required /></p>' .
            '<p class="field"><label for="your_pwd2" class="classic required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Your password:') . '</label> ' .
            form::password(
                ['your_pwd', 'your_pwd2'],
                20,
                255,
                [
                    'extra_html'   => 'required placeholder="' . __('Password') . '"',
                    'autocomplete' => 'current-password', ]
            ) . '</p>' .
            '<p><input type="submit" name="upload_pkg" value="' . __('Upload language') . '" />' .
            Core::nonce()->getFormNonce() .
            '</p>' .
            '</form>';
        }
        Page::helpBlock('core_langs');
        Page::close();
    }
}
