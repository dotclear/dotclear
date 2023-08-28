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
use Exception;
use dcCore;
use dcThemes;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Backend\ThemesList;
use Dotclear\Core\Core;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Html;
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

        Core::backend()->file_default = Core::backend()->file = new ArrayObject([
            'c'            => null,
            'w'            => false,
            'type'         => null,
            'f'            => null,
            'default_file' => false,
        ]);

        # Get interface setting
        Core::backend()->user_ui_colorsyntax       = dcCore::app()->auth->user_prefs->interface->colorsyntax;
        Core::backend()->user_ui_colorsyntax_theme = dcCore::app()->auth->user_prefs->interface->colorsyntax_theme;

        # Loading themes // deprecated since 2.26
        ThemesList::$distributed_modules = explode(',', DC_DISTRIB_THEMES);

        if (!is_a(dcCore::app()->themes, 'dcThemes')) {
            dcCore::app()->themes = new dcThemes();
            dcCore::app()->themes->loadModules(Core::blog()->themes_path, 'admin', dcCore::app()->lang);
        }

        Core::backend()->theme  = dcCore::app()->themes->getDefine(Core::blog()->settings->system->theme);
        Core::backend()->editor = new ThemeEditor();

        try {
            try {
                if (!empty($_REQUEST['tpl'])) {
                    Core::backend()->file = new ArrayObject(Core::backend()->editor->getFileContent('tpl', $_REQUEST['tpl']));
                } elseif (!empty($_REQUEST['css'])) {
                    Core::backend()->file = new ArrayObject(Core::backend()->editor->getFileContent('css', $_REQUEST['css']));
                } elseif (!empty($_REQUEST['js'])) {
                    Core::backend()->file = new ArrayObject(Core::backend()->editor->getFileContent('js', $_REQUEST['js']));
                } elseif (!empty($_REQUEST['po'])) {
                    Core::backend()->file = new ArrayObject(Core::backend()->editor->getFileContent('po', $_REQUEST['po']));
                } elseif (!empty($_REQUEST['php'])) {
                    Core::backend()->file = new ArrayObject(Core::backend()->editor->getFileContent('php', $_REQUEST['php']));
                }
            } catch (Exception $e) {
                Core::backend()->file = Core::backend()->file_default;

                throw $e;
            }

            if (dcCore::app()->auth->isSuperAdmin()
                && !empty($_POST['lock'])
                && is_string(Core::backend()->theme->get('root'))
            ) {
                file_put_contents(Core::backend()->theme->get('root') . DIRECTORY_SEPARATOR . dcThemes::MODULE_FILE_LOCKED, '');
                Notices::addSuccessNotice(__('The theme update has been locked.'));
            }
            if (dcCore::app()->auth->isSuperAdmin()
                && !empty($_POST['unlock'])
                && is_string(Core::backend()->theme->get('root'))
                && file_exists(Core::backend()->theme->get('root') . DIRECTORY_SEPARATOR . dcThemes::MODULE_FILE_LOCKED)
            ) {
                unlink(Core::backend()->theme->get('root') . DIRECTORY_SEPARATOR . dcThemes::MODULE_FILE_LOCKED);
                Notices::addSuccessNotice(__('The theme update has been unocked.'));
            }

            if (!empty($_POST['write'])) {
                // Write file

                // Overwrite content with new one
                Core::backend()->file['c'] = $_POST['file_content'];

                Core::backend()->editor->writeFile(
                    (string) Core::backend()->file['type'],
                    (string) Core::backend()->file['f'],
                    (string) Core::backend()->file['c']
                );
            }

            if (!empty($_POST['delete'])) {
                // Delete file

                Core::backend()->editor->deleteFile(
                    (string) Core::backend()->file['type'],
                    (string) Core::backend()->file['f']
                );
                Notices::addSuccessNotice(__('The file has been reset.'));
                My::redirect([
                    (string) Core::backend()->file['type'] => (string) Core::backend()->file['f'],
                ]);
            }
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
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

        $lock_form = (dcCore::app()->auth->isSuperAdmin()) ?
            '<fieldset id="lock-form"><legend>' . __('Update') . '</legend>' .
            '<form id="lock-update" method="post" action="' . Core::backend()->getPageURL() . '">' .
                '<p>' .
                (Core::backend()->theme->updLocked() ?
                '<input type="submit" name="unlock" value="' . html::escapeHTML(__('Unlock update')) . '" />' :
                '<input type="submit" name="lock" value="' . html::escapeHTML(__('Lock update')) . '" />') .
                Core::nonce()->getFormNonce() .
                '</p>' .
                '<p class="info">' .
                __('Lock update of the theme does not prevent to modify its files, only to update it globally.') .
                '</p>' .
            '</form>' .
            '</fieldset>' :
            ''
        ;

        $head = '';
        if (Core::backend()->user_ui_colorsyntax) {
            $head .= Page::jsJson('dotclear_colorsyntax', ['colorsyntax' => Core::backend()->user_ui_colorsyntax]);
        }
        $head .= Page::jsJson('theme_editor_msg', [
            'saving_document'    => __('Saving document...'),
            'document_saved'     => __('Document saved'),
            'error_occurred'     => __('An error occurred:'),
            'confirm_reset_file' => __('Are you sure you want to reset this file?'),
        ]) .
            My::jsLoad('script') .
            Page::jsConfirmClose('file-form');
        if (Core::backend()->user_ui_colorsyntax) {
            $head .= Page::jsLoadCodeMirror(Core::backend()->user_ui_colorsyntax_theme);
        }
        $head .= My::cssLoad('style');

        Page::openModule(__('Edit theme files'), $head);

        echo
        Page::breadcrumb(
            [
                Html::escapeHTML(Core::blog()->name) => '',
                __('Blog appearance')                       => Core::backend()->url->get('admin.blog.theme'),
                __('Edit theme files')                      => '',
            ]
        ) .
        Notices::getNotices();

        echo
        '<p><strong>' . sprintf(__('Your current theme on this blog is "%s".'), Html::escapeHTML(Core::backend()->theme->get('name'))) . '</strong></p>';

        if (Core::blog()->settings->system->themes_path !== Core::blog()->settings->system->getGlobal('themes_path')
            || !dcCore::app()->themes->getDefine(Core::blog()->settings->system->theme)->distributed
        ) {
            echo
            '<div id="file-box">' .
            '<div id="file-editor">';

            if (Core::backend()->file['c'] === null) {
                echo
                '<p>' . __('Please select a file to edit.') . '</p>';
                echo $lock_form;
            } else {
                echo
                '<form id="file-form" action="' . Core::backend()->getPageURL() . '" method="post">' .
                '<h3>' . __('File editor') . '</h3>' .
                '<p><label for="file_content">' . sprintf(__('Editing file %s'), '<strong>' . Core::backend()->file['f']) . '</strong></label></p>' .
                '<p>' . form::textarea('file_content', 72, 25, [
                    'default'  => Html::escapeHTML(Core::backend()->file['c']),
                    'class'    => 'maximal',
                    'disabled' => !Core::backend()->file['w'],
                ]) . '</p>';

                if (Core::backend()->file['w']) {
                    echo
                    '<p><input type="submit" name="write" value="' . __('Save') . ' (s)" accesskey="s" /> ' .
                    (Core::backend()->editor->deletableFile(Core::backend()->file['type'], Core::backend()->file['f']) ? '<input type="submit" name="delete" class="delete" value="' . __('Reset') . '" />' : '') .
                    Core::nonce()->getFormNonce() .
                        (Core::backend()->file['type'] ? form::hidden([Core::backend()->file['type']], Core::backend()->file['f']) : '') .
                        '</p>';
                } else {
                    echo
                    '<p>' . __('This file is not writable. Please check your theme files permissions.') . '</p>';
                }
                echo
                '</form>';
                echo $lock_form;

                if (Core::backend()->user_ui_colorsyntax) {
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
                    Page::jsRunCodeMirror('editor', 'file_content', 'dotclear', Core::backend()->user_ui_colorsyntax_theme);
                }
            }

            echo
            '</div>' .

            '<div id="file-chooser">' .
            '<h3>' . __('Templates files') . '</h3>' .
            Core::backend()->editor->filesList('tpl', '<a href="' . Core::backend()->getPageURL() . '&amp;tpl=%2$s" class="tpl-link">%1$s</a>') .

            '<h3>' . __('CSS files') . '</h3>' .
            Core::backend()->editor->filesList('css', '<a href="' . Core::backend()->getPageURL() . '&amp;css=%2$s" class="css-link">%1$s</a>') .

            '<h3>' . __('JavaScript files') . '</h3>' .
            Core::backend()->editor->filesList('js', '<a href="' . Core::backend()->getPageURL() . '&amp;js=%2$s" class="js-link">%1$s</a>') .

            '<h3>' . __('Locales files') . '</h3>' .
            Core::backend()->editor->filesList('po', '<a href="' . Core::backend()->getPageURL() . '&amp;po=%2$s" class="po-link">%1$s</a>') .

            '<h3>' . __('PHP files') . '</h3>' .
            Core::backend()->editor->filesList('php', '<a href="' . Core::backend()->getPageURL() . '&amp;php=%2$s" class="php-link">%1$s</a>') .

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
