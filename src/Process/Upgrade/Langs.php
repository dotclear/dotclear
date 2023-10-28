<?php
/**
 * @package Dotclear
 * @subpackage Upgrade
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Process\Upgrade;

use Dotclear\App;
use Dotclear\Core\Upgrade\Notices;
use Dotclear\Core\Upgrade\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Zip\Unzip;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\L10n;
use Dotclear\Helper\Network\Feed\Reader;
use Dotclear\Helper\Network\HttpClient;
use Exception;
use form;

/**
 * @since 2.27 Before as admin/langs.php
 */
class Langs extends Process
{
    // Local constants

    private const LANG_INSTALLED = 1;
    private const LANG_UPDATED   = 2;

    private static bool $is_writable    = false;
    private static array $iso_codes     = [];
    private static bool|array $dc_langs = false;

    public static function init(): bool
    {
        Page::checkSuper();

        self::$is_writable = is_dir(App::config()->l10nRoot()) && is_writable(App::config()->l10nRoot());
        self::$iso_codes   = L10n::getISOCodes();

        # Get languages list on Dotclear.net
        self::$dc_langs = false;

        $feed_reader = new Reader();

        $feed_reader->setCacheDir(App::config()->cacheRoot());
        $feed_reader->setTimeout(5);
        $feed_reader->setUserAgent('Dotclear - https://dotclear.org/');

        try {
            $parse = $feed_reader->parse(sprintf(App::config()->l10nUpdateUrl(), App::config()->dotclearVersion()));
            if ($parse !== false) {
                self::$dc_langs = $parse->items;
            }
        } catch (Exception) {
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
        if (self::$is_writable && !empty($_POST['delete']) && !empty($_POST['locale_id'])) {
            try {
                $locale_id = $_POST['locale_id'];
                if (!isset(self::$iso_codes[$locale_id]) || !is_dir(App::config()->l10nRoot() . '/' . $locale_id)) {
                    throw new Exception(__('No such installed language'));
                }

                if ($locale_id == 'en') {
                    throw new Exception(__("You can't remove English language."));
                }

                if (!Files::deltree(App::config()->l10nRoot() . '/' . $locale_id)) {
                    throw new Exception(__('Permissions to delete language denied.'));
                }

                Notices::addSuccessNotice(__('Language has been successfully deleted.'));
                App::upgrade()->url()->redirect('upgrade.langs');
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        # Download a language pack
        if (self::$is_writable && !empty($_POST['pkg_url'])) {
            try {
                if (empty($_POST['your_pwd']) || !App::auth()->checkPassword($_POST['your_pwd'])) {
                    throw new Exception(__('Password verification failed'));
                }

                $url  = Html::escapeHTML($_POST['pkg_url']);
                $dest = App::config()->l10nRoot() . '/' . basename($url);
                if (!preg_match('#^https://[^.]+\.dotclear\.(net|org)/.*\.zip$#', $url)) {
                    throw new Exception(__('Invalid language file URL.'));
                }

                $path   = '';
                $client = HttpClient::initClient($url, $path);
                if ($client) {
                    $client->setUserAgent('Dotclear - https://dotclear.org/');
                    $client->useGzip(false);
                    $client->setPersistReferers(false);
                    $client->setOutput($dest);
                    $client->get($path);
                } else {
                    throw new Exception(__('Unable to make a HTTP request.'));
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
                App::upgrade()->url()->redirect('upgrade.langs');
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        # Upload a language pack
        if (self::$is_writable && !empty($_POST['upload_pkg'])) {
            try {
                if (empty($_POST['your_pwd']) || !App::auth()->checkPassword($_POST['your_pwd'])) {
                    throw new Exception(__('Password verification failed'));
                }

                Files::uploadStatus($_FILES['pkg_file']);
                $dest = App::config()->l10nRoot() . '/' . $_FILES['pkg_file']['name'];
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
                App::upgrade()->url()->redirect('upgrade.langs');
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
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
        '<p>' . __('Here you can install, upgrade or remove languages for your Dotclear installation.') . '</p>';

        echo
        '<h3>' . __('Installed languages') . '</h3>';

        $langs      = scandir(App::config()->l10nRoot());
        $langs_list = [];
        if ($langs) {
            foreach ($langs as $lang) {
                $check = ($lang === '.' || $lang === '..' || $lang === 'en' || !is_dir(App::config()->l10nRoot() . '/' . $lang) || !isset(self::$iso_codes[$lang]));

                if (!$check) {
                    $langs_list[$lang] = App::config()->l10nRoot() . '/' . $lang;
                }
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
                $is_deletable = self::$is_writable && is_writable($lang);

                echo
                '<tr class="line wide">' .
                '<td class="maximal nowrap" lang="' . $lang_code . '">(' . $lang_code . ') ' .
                '<strong>' . Html::escapeHTML(self::$iso_codes[$lang_code]) . '</strong></td>' .
                '<td class="nowrap action">';

                if ($is_deletable) {
                    echo
                    '<form action="' . App::upgrade()->url()->get('upgrade.langs') . '" method="post">' .
                    '<div>' .
                    App::nonce()->getFormNonce() .
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

        if (!self::$is_writable) {
            echo '<p>' . sprintf(__('You can install or remove a language by adding or ' .
        'removing the relevant directory in your %s folder.'), '<strong>locales</strong>') . '</p>';
        }

        if (!empty(self::$dc_langs) && self::$is_writable) {
            $dc_langs_combo = [];
            foreach (self::$dc_langs as $lang) {
                if ($lang->link && isset(self::$iso_codes[$lang->title])) {
                    $dc_langs_combo[Html::escapeHTML('(' . $lang->title . ') ' . self::$iso_codes[$lang->title])] = Html::escapeHTML($lang->link);
                }
            }

            echo
            '<form method="post" action="' . App::upgrade()->url()->get('upgrade.langs') . '" enctype="multipart/form-data" class="fieldset">' .
            '<h4>' . __('Available languages') . '</h4>' .
            '<p>' . sprintf(__('You can download and install a additional language directly from Dotclear.net. ' .
                'Proposed languages are based on your version: %s.'), '<strong>' . App::config()->dotclearVersion() . '</strong>') . '</p>' .
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
            App::nonce()->getFormNonce() .
            '</p>' .
            '</form>';
        }

        if (self::$is_writable) {
            # 'Upload language pack' form
            echo
            '<form method="post" action="' . App::upgrade()->url()->get('upgrade.langs') . '" enctype="multipart/form-data" class="fieldset">' .
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
            App::nonce()->getFormNonce() .
            '</p>' .
            '</form>';
        }

        Page::close();
    }
}
