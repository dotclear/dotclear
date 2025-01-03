<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\themeEditor;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Backend\ThemesList;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Textarea;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @brief   The module backend manage process.
 * @ingroup themeEditor
 */
class Manage extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::MANAGE));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::backend()->file_default = App::backend()->file = new ArrayObject([
            'c'            => null,
            'w'            => false,
            'type'         => null,
            'f'            => null,
            'default_file' => false,
        ]);

        # Get interface setting
        App::backend()->user_ui_colorsyntax       = App::auth()->prefs()->interface->colorsyntax;
        App::backend()->user_ui_colorsyntax_theme = App::auth()->prefs()->interface->colorsyntax_theme;

        # Loading themes // deprecated since 2.26
        ThemesList::$distributed_modules = explode(',', App::config()->distributedThemes());

        if (App::themes()->isEmpty()) {
            App::themes()->loadModules(App::blog()->themesPath(), 'admin', App::lang()->getLang());
        }

        App::backend()->theme  = App::themes()->getDefine(App::blog()->settings()->system->theme);
        App::backend()->editor = new ThemeEditor();

        try {
            try {
                if (!empty($_REQUEST['tpl'])) {
                    App::backend()->file = new ArrayObject(App::backend()->editor->getFileContent('tpl', $_REQUEST['tpl']));
                } elseif (!empty($_REQUEST['css'])) {
                    App::backend()->file = new ArrayObject(App::backend()->editor->getFileContent('css', $_REQUEST['css']));
                } elseif (!empty($_REQUEST['js'])) {
                    App::backend()->file = new ArrayObject(App::backend()->editor->getFileContent('js', $_REQUEST['js']));
                } elseif (!empty($_REQUEST['po'])) {
                    App::backend()->file = new ArrayObject(App::backend()->editor->getFileContent('po', $_REQUEST['po']));
                } elseif (!empty($_REQUEST['php'])) {
                    App::backend()->file = new ArrayObject(App::backend()->editor->getFileContent('php', $_REQUEST['php']));
                }
            } catch (Exception $e) {
                App::backend()->file = App::backend()->file_default;

                throw $e;
            }

            if (App::auth()->isSuperAdmin()
                && !empty($_POST['lock'])
                && is_string(App::backend()->theme->get('root'))
            ) {
                file_put_contents(App::backend()->theme->get('root') . DIRECTORY_SEPARATOR . App::themes()::MODULE_FILE_LOCKED, '');
                Notices::addSuccessNotice(__('The theme update has been locked.'));
            }
            if (App::auth()->isSuperAdmin()
                && !empty($_POST['unlock'])
                && is_string(App::backend()->theme->get('root'))
                && file_exists(App::backend()->theme->get('root') . DIRECTORY_SEPARATOR . App::themes()::MODULE_FILE_LOCKED)
            ) {
                unlink(App::backend()->theme->get('root') . DIRECTORY_SEPARATOR . App::themes()::MODULE_FILE_LOCKED);
                Notices::addSuccessNotice(__('The theme update has been unocked.'));
            }

            if (!empty($_POST['write'])) {
                // Write file

                // Overwrite content with new one
                App::backend()->file['c'] = $_POST['file_content'];

                App::backend()->editor->writeFile(
                    (string) App::backend()->file['type'],
                    (string) App::backend()->file['f'],
                    (string) App::backend()->file['c']
                );
            }

            if (!empty($_POST['delete'])) {
                // Delete file

                App::backend()->editor->deleteFile(
                    (string) App::backend()->file['type'],
                    (string) App::backend()->file['f']
                );
                Notices::addSuccessNotice(__('The file has been reset.'));
                My::redirect([  // @phpstan-ignore-line
                    (string) App::backend()->file['type'] => (string) App::backend()->file['f'],
                ]);
            }
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        $lock_form = (App::auth()->isSuperAdmin()) ?
            (new Form())
                ->method('post')
                ->action(App::backend()->getPageURL())
                ->id('lock-update')
                ->fields([
                    (new Fieldset())
                        ->id('lock-form')
                        ->legend(new Legend(__('Update')))
                        ->items([
                            (new Para())
                                ->items([
                                    ...My::hiddenFields(),
                                    (new Submit(
                                        [App::backend()->theme->updLocked() ? 'unlock' : 'lock'],
                                        App::backend()->theme->updLocked() ? html::escapeHTML(__('Unlock update')) : html::escapeHTML(__('Lock update'))
                                    )),
                                ]),
                            (new Note())
                                ->class('info')
                                ->text(__('Lock theme update disables theme update, but allows theme files to be modified.')),
                        ]),
                ]) :
            (new None());

        $head = '';
        if (App::backend()->user_ui_colorsyntax) {
            $head .= Page::jsJson('dotclear_colorsyntax', ['colorsyntax' => App::backend()->user_ui_colorsyntax]);
        }
        $head .= Page::jsJson('theme_editor_msg', [
            'saving_document'    => __('Saving document...'),
            'document_saved'     => __('Document saved'),
            'error_occurred'     => __('An error occurred:'),
            'confirm_reset_file' => __('Are you sure you want to reset this file?'),
        ]) .
            My::jsLoad('script') .
            Page::jsConfirmClose('file-form');
        if (App::backend()->user_ui_colorsyntax) {
            $head .= Page::jsLoadCodeMirror(App::backend()->user_ui_colorsyntax_theme);
        }
        $head .= My::cssLoad('style');

        Page::openModule(__('Edit theme files'), $head);

        echo
        Page::breadcrumb(
            [
                Html::escapeHTML(App::blog()->name()) => '',
                __('Blog appearance')                 => App::backend()->url()->get('admin.blog.theme'),
                __('Edit theme files')                => '',
            ]
        ) .
        Notices::getNotices();

        echo (new Para())
            ->items([
                (new Text(null, sprintf(
                    __('Your current theme on this blog is "%s".'),
                    (new Text('strong', Html::escapeHTML(App::backend()->theme->get('name'))))->render()
                ))),
            ])
        ->render();

        if (App::blog()->settings()->system->themes_path !== App::blog()->settings()->system->getGlobal('themes_path')
            || !App::themes()->getDefine(App::blog()->settings()->system->theme)->get('distributed')
        ) {
            $editorMode = App::backend()->user_ui_colorsyntax ? self::getEditorMode() : '';

            if (App::backend()->file['c'] === null) {
                $items = [
                    (new Note())->text(__('Please select a file to edit.')),
                    $lock_form,
                ];
            } else {
                $items = [
                    (new Form())
                        ->method('post')
                        ->action(App::backend()->getPageURL())
                        ->id('file-form')
                        ->fields([
                            (new Text('h3', __('File editor'))),
                            (new Para())
                                ->items([
                                    (new Textarea('file_content', Html::escapeHTML(App::backend()->file['c'])))
                                        ->cols(72)
                                        ->rows(25)
                                        ->class('maximal')
                                        ->disabled(!App::backend()->file['w'])
                                        ->label((new Label(sprintf(
                                            __('Editing file %s'),
                                            (new Text('strong', App::backend()->file['f']))->render()
                                        ), Label::OL_TF))),
                                ]),
                            (new Para())
                                ->class(App::backend()->file['w'] ? 'form-buttons' : '')
                                ->items(App::backend()->file['w'] ? [
                                    ...My::hiddenFields(),
                                    (new Submit(['write'], __('Save') . ' (s)'))->accesskey('s'),
                                    App::backend()->editor->deletableFile(App::backend()->file['type'], App::backend()->file['f']) ?
                                        (new Submit(['delete'], __('Reset')))->class('delete') :
                                        (new None()),
                                    App::backend()->file['type'] ?
                                        (new Hidden([App::backend()->file['type']], App::backend()->file['f'])) :
                                        (new None()),
                                ] : [
                                    (new Note())
                                        ->text(__('This file is not writable. Please check your theme files permissions.')),
                                ]),
                        ]),
                    $lock_form,
                    App::backend()->user_ui_colorsyntax ?
                        (new Text(null, Page::jsJson('theme_editor_mode', ['mode' => $editorMode]) . My::jsLoad('mode') . Page::jsRunCodeMirror('editor', 'file_content', 'dotclear', App::backend()->user_ui_colorsyntax_theme))) :
                        (new None()),
                ];
            }

            echo (new Div())
                ->id('file-box')
                ->items([
                    (new Div())
                        ->id('file-editor')
                        ->items($items),
                    (new Div())
                        ->id('file-chooser')
                        ->items([
                            (new Text('h3', __('Templates files'))),
                            (new Text(null, App::backend()->editor->filesList(
                                'tpl',
                                (new Link())->href(App::backend()->getPageURL() . '&tpl=%2$s')->text('%1$s')->class('tpl-link')->render()
                            ))),
                            (new Text('h3', __('CSS files'))),
                            (new Text(null, App::backend()->editor->filesList(
                                'css',
                                (new Link())->href(App::backend()->getPageURL() . '&css=%2$s')->text('%1$s')->class('css-link')->render()
                            ))),
                            (new Text('h3', __('JavaScript files'))),
                            (new Text(null, App::backend()->editor->filesList(
                                'js',
                                (new Link())->href(App::backend()->getPageURL() . '&js=%2$s')->text('%1$s')->class('js-link')->render()
                            ))),
                            (new Text('h3', __('Locales files'))),
                            (new Text(null, App::backend()->editor->filesList(
                                'po',
                                (new Link())->href(App::backend()->getPageURL() . '&po=%2$s')->text('%1$s')->class('po-link')->render()
                            ))),
                            (new Text('h3', __('PHP files'))),
                            (new Text(null, App::backend()->editor->filesList(
                                'php',
                                (new Link())->href(App::backend()->getPageURL() . '&php=%2$s')->text('%1$s')->class('php-link')->render()
                            ))),
                        ]),
                ])
            ->render();

            Page::helpBlock(My::id());
        } else {
            echo (new Div())
                ->class('error')
                ->items([
                    (new Note())->text(__("You can't edit a distributed theme.")),
                ])
            ->render();
        }

        Page::closeModule();
    }

    private static function getEditorMode(): string
    {
        $modes = [
            'css' => 'css',
            'js'  => 'javascript',
            'po'  => 'text/plain',
            'php' => 'php',
        ];

        foreach ($modes as $request => $mode) {
            if (isset($_REQUEST[$request]) && !empty($_REQUEST[$request])) {
                return $mode;
            }
        }

        return 'text/html';
    }
}
