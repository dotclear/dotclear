<?php
/**
 * @brief themeEditor, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\themeEditor;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Backend\ThemesList;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Html;
use Exception;
use form;

class Manage extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::MANAGE));
    }

    /**
     * Processes the request(s).
     */
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
            App::themes()->loadModules(App::blog()->themesPath(), 'admin', App::task()->getLang());
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
                My::redirect([
                    (string) App::backend()->file['type'] => (string) App::backend()->file['f'],
                ]);
            }
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        return true;
    }

    /**
     * Renders the page.
     */
    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        $lock_form = (App::auth()->isSuperAdmin()) ?
            '<fieldset id="lock-form"><legend>' . __('Update') . '</legend>' .
            '<form id="lock-update" method="post" action="' . App::backend()->getPageURL() . '">' .
                '<p>' .
                (App::backend()->theme->updLocked() ?
                '<input type="submit" name="unlock" value="' . html::escapeHTML(__('Unlock update')) . '" />' :
                '<input type="submit" name="lock" value="' . html::escapeHTML(__('Lock update')) . '" />') .
                App::nonce()->getFormNonce() .
                '</p>' .
                '<p class="info">' .
                __('Lock update of the theme does not prevent to modify its files, only to update it globally.') .
                '</p>' .
            '</form>' .
            '</fieldset>' :
            ''
        ;

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
                __('Blog appearance')                 => App::backend()->url->get('admin.blog.theme'),
                __('Edit theme files')                => '',
            ]
        ) .
        Notices::getNotices();

        echo
        '<p><strong>' . sprintf(__('Your current theme on this blog is "%s".'), Html::escapeHTML(App::backend()->theme->get('name'))) . '</strong></p>';

        if (App::blog()->settings()->system->themes_path !== App::blog()->settings()->system->getGlobal('themes_path')
            || !App::themes()->getDefine(App::blog()->settings()->system->theme)->get('distributed')
        ) {
            echo
            '<div id="file-box">' .
            '<div id="file-editor">';

            if (App::backend()->file['c'] === null) {
                echo
                '<p>' . __('Please select a file to edit.') . '</p>';
                echo $lock_form;
            } else {
                echo
                '<form id="file-form" action="' . App::backend()->getPageURL() . '" method="post">' .
                '<h3>' . __('File editor') . '</h3>' .
                '<p><label for="file_content">' . sprintf(__('Editing file %s'), '<strong>' . App::backend()->file['f']) . '</strong></label></p>' .
                '<p>' . form::textarea('file_content', 72, 25, [
                    'default'  => Html::escapeHTML(App::backend()->file['c']),
                    'class'    => 'maximal',
                    'disabled' => !App::backend()->file['w'],
                ]) . '</p>';

                if (App::backend()->file['w']) {
                    echo
                    '<p><input type="submit" name="write" value="' . __('Save') . ' (s)" accesskey="s" /> ' .
                    (App::backend()->editor->deletableFile(App::backend()->file['type'], App::backend()->file['f']) ? '<input type="submit" name="delete" class="delete" value="' . __('Reset') . '" />' : '') .
                    App::nonce()->getFormNonce() .
                        (App::backend()->file['type'] ? form::hidden([App::backend()->file['type']], App::backend()->file['f']) : '') .
                        '</p>';
                } else {
                    echo
                    '<p>' . __('This file is not writable. Please check your theme files permissions.') . '</p>';
                }
                echo
                '</form>';
                echo $lock_form;

                if (App::backend()->user_ui_colorsyntax) {
                    $editorMode = (!empty($_REQUEST['css']) ?
                        'css' :
                        (!empty($_REQUEST['js']) ?
                            'javascript' :
                            (!empty($_REQUEST['po']) ?
                                'text/plain' :
                                (!empty($_REQUEST['php']) ?
                                    'php' :
                                    'text/html'))));
                    echo
                    Page::jsJson('theme_editor_mode', ['mode' => $editorMode]) .
                    My::jsLoad('mode') .
                    Page::jsRunCodeMirror('editor', 'file_content', 'dotclear', App::backend()->user_ui_colorsyntax_theme);
                }
            }

            echo
            '</div>' .

            '<div id="file-chooser">' .
            '<h3>' . __('Templates files') . '</h3>' .
            App::backend()->editor->filesList('tpl', '<a href="' . App::backend()->getPageURL() . '&amp;tpl=%2$s" class="tpl-link">%1$s</a>') .

            '<h3>' . __('CSS files') . '</h3>' .
            App::backend()->editor->filesList('css', '<a href="' . App::backend()->getPageURL() . '&amp;css=%2$s" class="css-link">%1$s</a>') .

            '<h3>' . __('JavaScript files') . '</h3>' .
            App::backend()->editor->filesList('js', '<a href="' . App::backend()->getPageURL() . '&amp;js=%2$s" class="js-link">%1$s</a>') .

            '<h3>' . __('Locales files') . '</h3>' .
            App::backend()->editor->filesList('po', '<a href="' . App::backend()->getPageURL() . '&amp;po=%2$s" class="po-link">%1$s</a>') .

            '<h3>' . __('PHP files') . '</h3>' .
            App::backend()->editor->filesList('php', '<a href="' . App::backend()->getPageURL() . '&amp;php=%2$s" class="php-link">%1$s</a>') .

            '</div>' .
            '</div>';

            Page::helpBlock(My::id());
        } else {
            echo
            '<div class="error"><p>' . __("You can't edit a distributed theme.") . '</p></div>';
        }

        echo
        '</body>' .
        '</html>';
    }
}
