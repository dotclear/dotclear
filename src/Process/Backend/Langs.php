<?php

/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Process\Backend;

use Dotclear\App;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Zip\Unzip;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\File;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Option;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Password;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Span;
use Dotclear\Helper\Html\Form\Strong;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Tbody;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Th;
use Dotclear\Helper\Html\Form\Thead;
use Dotclear\Helper\Html\Form\Tr;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Feed\Reader;
use Dotclear\Helper\Network\HttpClient;
use Dotclear\Helper\Process\TraitProcess;
use Exception;
use stdClass;

/**
 * @since 2.27 Before as admin/langs.php
 */
class Langs
{
    use TraitProcess;

    // Local constants

    private const LANG_INSTALLED = 1;

    private const LANG_UPDATED = 2;

    protected static bool $is_writable;

    /**
     * @var array<string, string> $iso_codes
     */
    protected static array $iso_codes;

    /**
     * @var stdClass[] $available_languages
     */
    protected static array $available_languages;

    public static function init(): bool
    {
        App::backend()->page()->checkSuper();

        self::$is_writable = is_dir(App::config()->l10nRoot()) && is_writable(App::config()->l10nRoot());
        self::$iso_codes   = App::lang()->getISOcodes();

        # Get languages list from dotclear server
        $feed_reader = new Reader();

        $feed_reader->setCacheDir(App::config()->cacheRoot());
        $feed_reader->setTimeout(5);
        $feed_reader->setUserAgent('Dotclear - https://dotclear.org/');

        try {
            $parse = $feed_reader->parse(sprintf(App::config()->l10nUpdateUrl(), App::config()->dotclearVersion()));
            if ($parse !== false) {
                self::$available_languages = $parse->items;
            }
        } catch (Exception) {
            // Ignore exceptions
        }

        return self::status(true);
    }

    public static function process(): bool
    {
        // Post data helpers
        $_Str = fn (string $name, string $default = ''): string => isset($_POST[$name]) && is_string($val = $_POST[$name]) ? $val : $default;

        // Delete a language pack
        if (self::$is_writable && !empty($_POST['delete']) && !empty($_POST['locale_id'])) {
            try {
                $locale_id = $_Str('locale_id');
                if ($locale_id === ''
                    || !isset(self::$iso_codes[$locale_id])
                    || !is_dir(App::config()->l10nRoot() . '/' . $locale_id)
                ) {
                    throw new Exception(__('No such installed language.'));
                }

                if ($locale_id === 'en') {
                    throw new Exception(__("You can't remove English language."));
                }

                if (!Files::deltree(App::config()->l10nRoot() . '/' . $locale_id)) {
                    throw new Exception(__('Permissions to delete language denied.'));
                }

                App::backend()->notices()->addSuccessNotice(__('Language has been successfully deleted.'));
                App::backend()->url()->redirect('admin.langs');
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        // Download a language pack
        if (self::$is_writable && !empty($_POST['pkg_url'])) {
            try {
                $your_pwd = $_Str('your_pwd');
                if ($your_pwd === '' || !App::auth()->checkPassword($your_pwd)) {
                    throw new Exception(__('Password verification failed.'));
                }

                $url  = Html::escapeHTML($_Str('pkg_url'));
                $dest = App::config()->l10nRoot() . '/' . basename($url);
                if ($url === '' || !preg_match('#^https://[^.]+\.dotclear\.(net|org)/.*\.zip$#', $url)) {
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
                    $ret_code = self::installLanguage($dest);
                } catch (Exception $e) {
                    throw $e;
                } finally {
                    // Remove temporary file
                    @unlink($dest);
                }

                if ($ret_code === self::LANG_UPDATED) {
                    App::backend()->notices()->addSuccessNotice(__('Language has been successfully upgraded.'));
                } else {
                    App::backend()->notices()->addSuccessNotice(__('Language has been successfully installed.'));
                }

                App::backend()->url()->redirect('admin.langs');
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        // Upload a language pack
        if (self::$is_writable && !empty($_POST['upload_pkg'])) {
            try {
                $your_pwd = $_Str('your_pwd');
                if ($your_pwd === '' || !App::auth()->checkPassword($your_pwd)) {
                    throw new Exception(__('Password verification failed.'));
                }

                /**
                 * @var array{name: string, type: string, size: int, tmp_name: string, error?: int, full_path: string}  $file
                 */
                $file = $_FILES['pkg_file'];
                Files::uploadStatus($file);
                $dest = App::config()->l10nRoot() . '/' . $file['name'];
                if (!move_uploaded_file($file['tmp_name'], $dest)) {
                    throw new Exception(__('Unable to move uploaded file.'));
                }

                try {
                    $ret_code = self::installLanguage($dest);
                } catch (Exception $e) {
                    throw $e;
                } finally {
                    // Remove temporary file
                    @unlink($dest);
                }

                if ($ret_code === self::LANG_UPDATED) {
                    App::backend()->notices()->addSuccessNotice(__('Language has been successfully upgraded.'));
                } else {
                    App::backend()->notices()->addSuccessNotice(__('Language has been successfully installed.'));
                }

                App::backend()->url()->redirect('admin.langs');
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        return true;
    }

    public static function render(): void
    {
        // Get current list of installed languages
        $langs      = scandir(App::config()->l10nRoot());
        $langs_list = [];
        if ($langs) {
            foreach ($langs as $language) {
                $check = (in_array($language, ['.', '..', 'en'], true) || !is_dir(App::config()->l10nRoot() . '/' . $language) || !isset(self::$iso_codes[$language]));

                if (!$check) {
                    $langs_list[$language] = App::config()->l10nRoot() . '/' . $language;
                }
            }
        }

        $rows = [];
        foreach ($langs_list as $lang_code => $language) {
            $is_deletable = self::$is_writable && is_writable($language);

            $rows[] = (new Tr())
                ->class(['line', 'wide'])
                ->items([
                    (new Td())
                        ->class(['maximal', 'nowrap'])
                        ->lang($lang_code)
                        ->translate(false)
                        ->text('(' . $lang_code . ') ' . (new Strong(Html::escapeHTML(self::$iso_codes[$lang_code])))->render()),
                    (new Td())
                        ->class(['action', 'nowrap'])
                        ->items([
                            $is_deletable ?
                            (new Form('delete-' . Html::escapeHTML($lang_code)))
                                ->method('post')
                                ->action(App::backend()->url()->get('admin.langs'))
                                ->fields([
                                    (new Para())
                                        ->class('form-buttons')
                                        ->items([
                                            App::nonce()->formNonce(),
                                            (new Hidden(['locale_id'], Html::escapeHTML($lang_code))),
                                            (new Submit(['delete'], __('Delete')))
                                                ->class('delete'),
                                        ]),
                                ]) :
                            (new None()),
                        ]),
                ]);
        }

        // Display

        App::backend()->page()->open(
            __('Languages management'),
            App::backend()->page()->jsLoad('js/_langs.js'),
            App::backend()->page()->breadcrumb(
                [
                    __('System')               => '',
                    __('Languages management') => '',
                ]
            )
        );

        $parts = [];

        $parts[] = (new Set())
            ->items([
                (new Note())
                    ->text(__('Here you can install, upgrade or remove languages for your Dotclear installation.')),
                (new Note())
                    ->text(sprintf(
                        __('You can change your user language in your <a href="%1$s">preferences</a> or change your blog\'s main language in your <a href="%2$s">blog settings</a>.'),
                        App::backend()->url()->get('admin.user.preferences'),
                        App::backend()->url()->get('admin.blog.pref')
                    )),
                (new Note())
                    ->class(['form-note', 'info'])
                    ->text(__('Languages other than French and English have been automatically translated. If you spot any incorrect translations, please let us know.')),
                (new Text('h3', __('Installed languages'))),
            ]);

        if ($langs_list === []) {
            $parts[] = (new Para())
                ->items([
                    (new Strong(__('No additional language is installed.'))),
                ]);
        } else {
            $parts[] = (new Div())
                ->class(['table-outer', 'clear'])
                ->items([
                    (new Table())
                        ->class('langs')
                        ->thead((new Thead())
                            ->rows([
                                (new Tr())
                                    ->items([
                                        (new Th())
                                            ->text(__('Language')),
                                        (new Th())
                                            ->class('nowrap')
                                            ->text(__('Action')),
                                    ]),
                            ]))
                        ->tbody((new Tbody())
                            ->rows($rows)),
                ]);
        }

        $parts[] = (new Text('h3', __('Install or upgrade languages')));

        if (!self::$is_writable) {
            $parts[] = (new Note())
                ->text(sprintf(__('You can install or remove a language by adding or removing the relevant directory in your %s folder.'), '<strong>locales</strong>'));
        }

        if (self::$available_languages !== [] && self::$is_writable) {
            // Prepare list of available languages
            $languages_combo = [];
            foreach (self::$available_languages as $language) {
                $link  = is_string($link = $language->link) ? $link : '';
                $title = is_string($title = $language->title) ? $title : '';
                if ($link     !== ''
                    && $title !== ''
                    && isset(self::$iso_codes[$title])
                ) {
                    $languages_combo[] = new Option(
                        Html::escapeHTML('(' . $title . ') ' . self::$iso_codes[$title]),
                        Html::escapeHTML($link)
                    );
                }
            }

            if ($languages_combo !== []) {
                // 'Install language pack' form
                $parts[] = (new Form('install'))
                    ->method('post')
                    ->action(App::backend()->url()->get('admin.langs'))
                    ->class('fieldset')
                    ->enctype('multipart/form-data')
                    ->fields([
                        (new Text('h4', __('Available languages'))),
                        (new Note())
                            ->class('form-note')
                            ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Span('*'))->class('required')->render())),
                        (new Note())
                            ->text(sprintf(__('You can download and install a additional language directly from Dotclear server. Proposed languages are based on your version: %s.'), '<strong>' . App::config()->dotclearVersion() . '</strong>')),
                        (new Para())
                            ->class('field')
                            ->items([
                                (new Select('pkg_url'))
                                    ->items($languages_combo)
                                    ->required(true)
                                    ->label((new Label(
                                        (new Span('*'))->render() . __('Language:'),
                                        Label::OL_TF
                                    ))
                                        ->class('required')),
                            ]),
                        (new Para())
                            ->class('field')
                            ->items([
                                (new Password(['your_pwd', 'your_pwd1']))
                                        ->size(20)
                                        ->maxlength(255)
                                        ->required(true)
                                        ->placeholder(__('Password'))
                                        ->autocomplete('current-password')
                                        ->label((new Label(
                                            (new Span('*'))->render() . __('Your password:'),
                                            Label::OL_TF
                                        ))
                                            ->class('required')),
                            ]),
                        (new Para())
                            ->items([
                                (new Submit('upload_pkg', __('Install language'))),
                                App::nonce()->formNonce(),
                            ]),
                    ]);
            }
        }

        if (self::$is_writable) {
            // 'Upload language pack' form
            $parts[] = (new Form('upload'))
                ->method('post')
                ->action(App::backend()->url()->get('admin.langs'))
                ->class('fieldset')
                ->enctype('multipart/form-data')
                ->fields([
                    (new Text('h4', __('Upload a zip file'))),
                    (new Note())
                        ->class('form-note')
                        ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Span('*'))->class('required')->render())),
                    (new Note())
                        ->text(__('You can install languages by uploading zip files.')),
                    (new Para())
                        ->class('field')
                        ->items([
                            (new File('pkg_file'))
                                ->required(true)
                                ->label((new Label(
                                    (new Span('*'))->render() . __('Language zip file:'),
                                    Label::OL_TF
                                ))
                                    ->class('required')),
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
                                ->translate(false)
                                ->label((new Label(
                                    (new Span('*'))->render() . __('Your password:'),
                                    Label::OL_TF
                                ))
                                    ->class('required')),
                        ]),
                    (new Para())
                        ->items([
                            (new Submit('upload_pkg', __('Upload language'))),
                            App::nonce()->formNonce(),
                        ]),
                ]);
        }

        echo (new Set())
            ->items($parts)
        ->render();

        App::backend()->page()->helpBlock('core_langs');
        App::backend()->page()->close();
    }

    /**
     * Language installation function
     *
     * @param      string      $file   The language file to install
     *
     * @throws     Exception
     *
     * @return     int        self::LANG_INSTALLED = installation ok, self::LANG_UPDATED = update ok
     */
    protected static function installLanguage(string $file): int
    {
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
    }
}
