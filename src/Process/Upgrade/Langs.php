<?php

/**
 * @package     Dotclear
 * @subpackage  Upgrade
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Process\Upgrade;

use stdClass;
use Dotclear\App;
use Dotclear\Core\Upgrade\Notices;
use Dotclear\Core\Upgrade\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Zip\Unzip;
use Dotclear\Helper\Html\Form\{
    Div,
    File,
    Form,
    Hidden,
    Label,
    Note,
    Para,
    Password,
    Select,
    Submit,
    Table,
    Td,
    Th,
    Text,
    Tr
};
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\L10n;
use Dotclear\Helper\Network\Feed\Reader;
use Dotclear\Helper\Network\HttpClient;
use Exception;

/**
 * @brief   Langs management page.
 *
 * @since   2.29
 */
class Langs extends Process
{
    // Local constants

    private const LANG_INSTALLED = 1;
    private const LANG_UPDATED   = 2;

    private static bool $is_writable = false;
    /**
     * @var     array<string, string>   $iso_codes
     */
    private static array $iso_codes = [];

    /**
     * @var     array<int, stdClass>     $dc_langs
     */
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

        if (!empty($_GET['removed'])) {
            Notices::AddSuccessNotice(__('Language has been successfully deleted.'));
        }

        if (!empty($_GET['added'])) {
            Notices::AddSuccessNotice(($_GET['added'] == 2 ? __('Language has been successfully upgraded') : __('Language has been successfully installed.')));
        }

        if (!self::$is_writable) {
            Notices::addWarningNotice(sprintf(
                __('You can install or remove a language by adding or removing the relevant directory in your %s folder.'),
                '<strong>locales</strong>'
            ));
        }

        return true;
    }

    public static function render(): void
    {
        $items = [];

        Page::open(
            __('Languages management'),
            Page::jsLoad('js/_langs.js'),
            Page::breadcrumb(
                [
                    __('Dotclear update')      => '',
                    __('Languages management') => '',
                ]
            )
        );

        $items = [];

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

        if ($langs_list !== []) {
            $options = [];
            $i       = 0;
            foreach ($langs_list as $lang_code => $lang) {
                $actions = [];
                if (self::$is_writable && is_writable($lang)) {
                    $actions[] = (new Form('options_' . $i++))
                        ->method('post')
                        ->action(App::upgrade()->url()->get('upgrade.langs'))
                        ->fields([
                            (new Div())
                                ->items([
                                    App::nonce()->formNonce(),
                                    (new Hidden(['locale_id'], Html::escapeHTML($lang_code))),
                                    (new Submit(['delete'], __('Delete')))
                                        ->class('delete'),
                                ]),
                        ]);
                }

                $options[] = (new Tr())
                    ->class('line wide')
                    ->items([
                        (new Td())
                            ->class('maximal nowrap')
                            ->lang($lang_code)
                            ->separator(' ')
                            ->items([
                                (new Text('', '(' . $lang_code . ')')),
                                (new Text('strong', Html::escapeHTML(self::$iso_codes[$lang_code]))),
                            ]),
                        (new Td())
                            ->class('action nowrap')
                            ->items($actions),
                    ]);
            }

            $items[] = (new Div())
                ->class('fieldset')
                ->items([
                    (new Text('h4', __('Installed languages'))),
                    (new Div())
                        ->class('table-outer clear')
                        ->items([
                            (new Table())
                                ->class('plugins')
                                ->items([
                                    (new Tr())
                                        ->items([
                                            (new Th())
                                                ->text(__('Language')),
                                            (new Th())
                                                ->class('nowrap')
                                                ->text(__('Action')),
                                        ]),
                                    ...$options,
                                ]),
                        ]),
                ]);
        }

        if (is_array(self::$dc_langs) && self::$dc_langs !== [] && self::$is_writable) {
            $dc_langs_combo = [];
            foreach (self::$dc_langs as $lang) {
                if ($lang->link && isset(self::$iso_codes[$lang->title])) {
                    $dc_langs_combo[Html::escapeHTML('(' . $lang->title . ') ' . self::$iso_codes[$lang->title])] = Html::escapeHTML($lang->link);
                }
            }

            $items[] = (new Form('langavailable'))
                ->class('fieldset')
                ->method('post')
                ->action(App::upgrade()->url()->get('upgrade.langs'))
                ->enctype('multipart/form-data')
                ->fields([
                    (new Text('h4', __('Install or upgrade languages from available languages'))),
                    (new Text('p', sprintf(
                        __('You can download and install a additional language directly from Dotclear.net. Proposed languages are based on your version: %s.'),
                        '<strong>' . App::config()->dotclearVersion() . '</strong>'
                    ))),
                    (new Para())
                        ->class('field')
                        ->items([
                            (new Label(__('Language:')))
                                ->class('classic')
                                ->for('pkg_url'),
                            (new Select(['pkg_url']))
                                ->items($dc_langs_combo),
                        ]),
                    (new Para())
                        ->class('field')
                        ->items([
                            (new Password(['your_pwd', 'your_pwd1']))
                                ->placeholder(__('Your password:'))
                                ->size(20)
                                ->maxlength(255)
                                ->required(true)
                                ->autocomplete('current-password')
                                ->label((new Label(
                                    __('Your password:'),
                                    Label::OUTSIDE_LABEL_BEFORE
                                ))),
                        ]),
                    App::nonce()->formNonce(),
                    (new Submit(['install'], __('Install language'))),

                ]);
        }

        if (self::$is_writable) {
            # 'Upload language pack' form
            $items[] = (new Form('langupload'))
                ->class('fieldset')
                ->method('post')
                ->action(App::upgrade()->url()->get('upgrade.langs'))
                ->enctype('multipart/form-data')
                ->fields([
                    (new Text('h4', __('Install or upgrade languages from an upload a zip file'))),
                    (new Note())
                        ->class('form-note')
                        ->text(sprintf(__('Fields preceded by %s are mandatory.'), '<span class="required">*</span>')),
                    (new Text('p', __('You can install languages by uploading zip files.'))),
                    (new Para())
                        ->class('field')
                        ->items([
                            (new File('pkg_file'))
                                ->size(30)
                                ->required(true)
                                ->label(
                                    (new Label(
                                        '<span>*</span> ' . __('Language zip file:'),
                                        Label::OUTSIDE_LABEL_BEFORE
                                    ))
                                    ->class('classic required')
                                ),
                        ]),
                    (new Para())
                        ->class('field')
                        ->items([
                            (new Password(['your_pwd', 'your_pwd2']))
                                ->size(20)
                                ->maxlength(255)
                                ->required(true)
                                ->placeholder(__('Password'))
                                ->autocomplete('current-password')
                                ->label(
                                    (new Label(
                                        '<span>*</span> ' . __('Your password:'),
                                        Label::OUTSIDE_LABEL_BEFORE
                                    ))
                                    ->class('classic required')
                                ),
                        ]),

                    (new Para())
                        ->items([
                            App::nonce()->formNonce(),
                            (new Submit(['upload_pkg'], __('Upload language'))),
                        ]),
                ]);
        }

        echo (new Div())
            ->items([
                (new Note())
                    ->class('static-msg')
                    ->text(__('Here you can install, upgrade or remove languages for your Dotclear installation.')),
                ...$items,
            ])
            ->render();

        Page::helpBlock('core_langs');
        Page::close();
    }
}
