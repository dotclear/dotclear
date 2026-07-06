<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\themeEditor;

use Dotclear\App;
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
use Dotclear\Helper\Html\Form\Strong;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Textarea;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Module\ModuleDefine;
use Exception;

/**
 * @brief   The module backend manage process.
 * @ingroup themeEditor
 */
class Manage
{
    use TraitProcess;

    // Local static properties

    /**
     * Current edited theme (Module)
     */
    private static ModuleDefine $theme;

    /**
     * Theme editor instance
     */
    private static ThemeEditor $editor;

    /**
     * Use syntaxic color?
     */
    private static bool $colorsyntax;

    /**
     * Syntaxic color theme
     */
    private static string $colorsyntax_theme;

    /**
     * Current edited file descriptor
     *
     * @var array{c: string|null, w: bool, type: string, f: string} $file
     */
    private static array $file;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::MANAGE));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        $file_default = [
            'c'    => null,
            'w'    => false,
            'type' => '',
            'f'    => '',
        ];

        self::$file = $file_default;

        // Get interface setting
        self::$colorsyntax       = App::auth()->prefs()->get('interface')->getBool('colorsyntax', false);
        self::$colorsyntax_theme = App::auth()->prefs()->get('interface')->getStr('colorsyntax_theme') ?? '';

        if (App::themes()->isEmpty()) {
            App::themes()->loadModules(App::blog()->themesPath(), 'admin', App::lang()->getLang());
        }

        $system_theme = is_string($system_theme = App::blog()->settings()->system->theme) ? $system_theme : '';
        self::$theme  = App::themes()->getDefine($system_theme);
        self::$editor = new ThemeEditor();

        try {
            try {
                if (!empty($_REQUEST['tpl']) && is_string($_REQUEST['tpl'])) {
                    self::$file = self::$editor->getFileContent('tpl', $_REQUEST['tpl']);
                } elseif (!empty($_REQUEST['css']) && is_string($_REQUEST['css'])) {
                    self::$file = self::$editor->getFileContent('css', $_REQUEST['css']);
                } elseif (!empty($_REQUEST['js']) && is_string($_REQUEST['js'])) {
                    self::$file = self::$editor->getFileContent('js', $_REQUEST['js']);
                } elseif (!empty($_REQUEST['po']) && is_string($_REQUEST['po'])) {
                    self::$file = self::$editor->getFileContent('po', $_REQUEST['po']);
                } elseif (!empty($_REQUEST['php']) && is_string($_REQUEST['php'])) {
                    self::$file = self::$editor->getFileContent('php', $_REQUEST['php']);
                }
            } catch (Exception $e) {
                self::$file = $file_default;

                throw $e;
            }

            if (App::auth()->isSuperAdmin()
                && !empty($_POST['lock'])
                && is_string(self::$theme->get('root'))
            ) {
                file_put_contents(self::$theme->get('root') . DIRECTORY_SEPARATOR . App::themes()::MODULE_FILE_LOCKED, '');
                App::backend()->notices()->addSuccessNotice(__('The theme update has been locked.'));
            }
            if (App::auth()->isSuperAdmin()
                && !empty($_POST['unlock'])
                && is_string(self::$theme->get('root'))
                && file_exists(self::$theme->get('root') . DIRECTORY_SEPARATOR . App::themes()::MODULE_FILE_LOCKED)
            ) {
                unlink(self::$theme->get('root') . DIRECTORY_SEPARATOR . App::themes()::MODULE_FILE_LOCKED);
                App::backend()->notices()->addSuccessNotice(__('The theme update has been unocked.'));
            }

            if (!empty($_POST['write'])) {
                // Write file

                // Overwrite content with new one
                self::$file['c'] = is_string($file_content = $_POST['file_content']) ? $file_content : '';

                self::$editor->writeFile(
                    self::$file['type'],
                    self::$file['f'],
                    self::$file['c']
                );
            }

            if (!empty($_POST['delete'])) {
                // Delete file

                $type = self::$file['type'];
                $file = self::$file['f'];

                self::$editor->deleteFile($type, $file);
                App::backend()->notices()->addSuccessNotice(__('The file has been reset.'));
                // @phpstan-ignore argument.type
                My::redirect([
                    $type => $file,
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
                                        [self::$theme->updLocked() ? 'unlock' : 'lock'],
                                        self::$theme->updLocked() ? html::escapeHTML(__('Unlock update')) : html::escapeHTML(__('Lock update'))
                                    )),
                                ]),
                            (new Note())
                                ->class('info')
                                ->text(__('Lock theme update disables theme update, but allows theme files to be modified.')),
                        ]),
                ]) :
            (new None());

        if (self::$editor->devMode()) {
            App::backend()->notices()->addWarningNotice(__('The theme editor is in development mode, theme files will be overwritten!'));
        }

        $head = '';
        if (self::$colorsyntax) {
            $head .= App::backend()->page()->jsJson('dotclear_colorsyntax', ['colorsyntax' => self::$colorsyntax]);
        }
        $head .= App::backend()->page()->jsJson('theme_editor_msg', [
            'saving_document'    => __('Saving document...'),
            'document_saved'     => __('Document saved'),
            'error_occurred'     => __('An error occurred:'),
            'confirm_reset_file' => __('Are you sure you want to reset this file?'),
        ]) .
            My::jsLoad('script') .
            App::backend()->page()->jsConfirmClose('file-form');
        if (self::$colorsyntax) {
            $head .= App::backend()->page()->jsLoadCodeMirror(self::$colorsyntax_theme);
        }
        $head .= My::cssLoad('style');

        App::backend()->page()->openModule(__('Edit theme files'), $head);

        echo
        App::backend()->page()->breadcrumb(
            [
                Html::escapeHTML(App::blog()->name()) => '',
                __('Blog appearance')                 => App::backend()->url()->get('admin.blog.theme'),
                __('Edit theme files')                => '',
            ]
        ) .
        App::backend()->notices()->getNotices();

        $theme_name = is_string($theme_name = self::$theme->get('name')) ? $theme_name : self::$theme->getId();

        echo (new Para())
            ->items([
                (new Text(null, sprintf(
                    __('Your current theme on this blog is "%s".'),
                    (new Strong(Html::escapeHTML($theme_name)))->render()
                ))),
            ])
        ->render();

        $editorMode = self::$colorsyntax ? self::getEditorMode() : '';

        if (self::$file['c'] === null) {
            $items = [
                (new Note())->text(__('Please select a file to edit.')),
                $lock_form,
            ];
        } else {
            $deletable = self::$editor->deletableFile(self::$file['type'], self::$file['f']);
            if (self::$editor->devMode() && !$deletable) {
                $deleteButton = (new None());
            } else {
                $deleteButton = (new Submit(['delete'], __('Reset')))
                    ->class(['delete', $deletable ? '' : 'hide']);
            }
            $items = [
                (new Form())
                    ->method('post')
                    ->action(App::backend()->getPageURL())
                    ->id('file-form')
                    ->fields([
                        (new Text('h3', __('File editor'))),
                        (new Para())
                            ->items([
                                (new Textarea('file_content', Html::escapeHTML(self::$file['c'])))
                                    ->cols(72)
                                    ->rows(25)
                                    ->class('maximal')
                                    ->disabled(!self::$file['w'])
                                    ->label((new Label(sprintf(
                                        __('Editing file %s'),
                                        (new Strong(self::$file['f']))->render()
                                    ), Label::OL_TF))),
                            ]),
                        (new Para())
                            ->class(self::$file['w'] ? 'form-buttons' : '')
                            ->items(self::$file['w'] ? [
                                ...My::hiddenFields(),
                                (new Submit(['write'], __('Save') . ' (s)'))
                                    ->accesskey('s'),
                                $deleteButton,
                                self::$file['type'] ?
                                    (new Hidden([self::$file['type']], self::$file['f'])) :
                                    (new None()),
                                (new Note())
                                    ->class('info')
                                    ->text(__('If you use <code>url(...)</code> in your CSS files, be sure to use <code>url(index.php?tf=...)</code> to correctly load theme resources (imported CSS, images, etc.), except for URL types in the form <code>data:image</code>.<br>Example: do <code>@import url(index.php?tf=css/layout.css);</code> instead of <code>@import url(css/layout.css);</code>.')),
                            ] : [
                                (new Note())
                                    ->class('warning')
                                    ->text(__('This file is not overloadable. Please check your var folder permissions.')),
                            ]),
                    ]),
                $lock_form,
                self::$colorsyntax ?
                    (new Text(null, App::backend()->page()->jsJson('theme_editor_mode', ['mode' => $editorMode]) . My::jsLoad('mode') . App::backend()->page()->jsRunCodeMirror('editor', 'file_content', 'dotclear', self::$colorsyntax_theme))) :
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
                        (new Text(null, self::$editor->filesList(
                            'tpl',
                            (new Link())->href(App::backend()->getPageURL() . '&tpl=%2$s')->text('%1$s')->class('tpl-link')->render()
                        ))),
                        (new Text('h3', __('CSS files'))),
                        (new Text(null, self::$editor->filesList(
                            'css',
                            (new Link())->href(App::backend()->getPageURL() . '&css=%2$s')->text('%1$s')->class('css-link')->render()
                        ))),
                        (new Text('h3', __('JavaScript files'))),
                        (new Text(null, self::$editor->filesList(
                            'js',
                            (new Link())->href(App::backend()->getPageURL() . '&js=%2$s')->text('%1$s')->class('js-link')->render()
                        ))),
                        (new Text('h3', __('Locales files'))),
                        (new Text(null, self::$editor->filesList(
                            'po',
                            (new Link())->href(App::backend()->getPageURL() . '&po=%2$s')->text('%1$s')->class('po-link')->render()
                        ))),
                        (new Text('h3', __('PHP files'))),
                        (new Text(null, self::$editor->filesList(
                            'php',
                            (new Link())->href(App::backend()->getPageURL() . '&php=%2$s')->text('%1$s')->class('php-link')->render()
                        ))),
                    ]),
            ])
        ->render();

        App::backend()->page()->helpBlock(My::id());

        App::backend()->page()->closeModule();
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
