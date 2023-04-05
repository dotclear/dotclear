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
use adminThemesList;
use dcCore;
use dcNsProcess;
use dcPage;
use dcThemes;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use form;

class Manage extends dcNsProcess
{
    public static function init(): bool
    {
        if (!defined('DC_CONTEXT_ADMIN')) {
            return false;
        }

        static::$init = true;

        dcCore::app()->admin->file_default = dcCore::app()->admin->file = new ArrayObject([
            'c'            => null,
            'w'            => false,
            'type'         => null,
            'f'            => null,
            'default_file' => false,
        ]);

        # Get interface setting
        dcCore::app()->admin->user_ui_colorsyntax       = dcCore::app()->auth->user_prefs->interface->colorsyntax;
        dcCore::app()->admin->user_ui_colorsyntax_theme = dcCore::app()->auth->user_prefs->interface->colorsyntax_theme;

        # Loading themes // deprecated since 2.26
        adminThemesList::$distributed_modules = explode(',', DC_DISTRIB_THEMES);

        if (!is_a(dcCore::app()->themes, 'dcThemes')) {
            dcCore::app()->themes = new dcThemes();
            dcCore::app()->themes->loadModules(dcCore::app()->blog->themes_path, null);
        }

        dcCore::app()->admin->theme  = dcCore::app()->themes->getDefine(dcCore::app()->blog->settings->system->theme);
        dcCore::app()->admin->editor = new ThemeEditor();

        return static::$init;
    }

    /**
     * Processes the request(s).
     */
    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        try {
            try {
                if (!empty($_REQUEST['tpl'])) {
                    dcCore::app()->admin->file = new ArrayObject(dcCore::app()->admin->editor->getFileContent('tpl', $_REQUEST['tpl']));
                } elseif (!empty($_REQUEST['css'])) {
                    dcCore::app()->admin->file = new ArrayObject(dcCore::app()->admin->editor->getFileContent('css', $_REQUEST['css']));
                } elseif (!empty($_REQUEST['js'])) {
                    dcCore::app()->admin->file = new ArrayObject(dcCore::app()->admin->editor->getFileContent('js', $_REQUEST['js']));
                } elseif (!empty($_REQUEST['po'])) {
                    dcCore::app()->admin->file = new ArrayObject(dcCore::app()->admin->editor->getFileContent('po', $_REQUEST['po']));
                } elseif (!empty($_REQUEST['php'])) {
                    dcCore::app()->admin->file = new ArrayObject(dcCore::app()->admin->editor->getFileContent('php', $_REQUEST['php']));
                }
            } catch (Exception $e) {
                dcCore::app()->admin->file = dcCore::app()->admin->file_default;

                throw $e;
            }

            if (!empty($_POST['write'])) {
                // Write file

                // Overwrite content with new one
                dcCore::app()->admin->file['c'] = $_POST['file_content'];

                dcCore::app()->admin->editor->writeFile(
                    dcCore::app()->admin->file['type'], // @phpstan-ignore-line
                    dcCore::app()->admin->file['f'],    // @phpstan-ignore-line
                    dcCore::app()->admin->file['c']
                );
            }

            if (!empty($_POST['delete'])) {
                // Delete file

                dcCore::app()->admin->editor->deleteFile(
                    dcCore::app()->admin->file['type'],
                    dcCore::app()->admin->file['f']
                );
                dcPage::addSuccessNotice(__('The file has been reset.'));
                Http::redirect(dcCore::app()->admin->getPageURL() . '&' . dcCore::app()->admin->file['type'] . '=' . dcCore::app()->admin->file['f']);
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
        if (!static::$init) {
            return;
        }

        $head = '';
        if (dcCore::app()->admin->user_ui_colorsyntax) {
            $head .= dcPage::jsJson('dotclear_colorsyntax', ['colorsyntax' => dcCore::app()->admin->user_ui_colorsyntax]);
        }
        $head .= dcPage::jsJson('theme_editor_msg', [
            'saving_document'    => __('Saving document...'),
            'document_saved'     => __('Document saved'),
            'error_occurred'     => __('An error occurred:'),
            'confirm_reset_file' => __('Are you sure you want to reset this file?'),
        ]) .
            dcPage::jsModuleLoad('themeEditor/js/script.js') .
            dcPage::jsConfirmClose('file-form');
        if (dcCore::app()->admin->user_ui_colorsyntax) {
            $head .= dcPage::jsLoadCodeMirror(dcCore::app()->admin->user_ui_colorsyntax_theme);
        }
        $head .= dcPage::cssModuleLoad('themeEditor/css/style.css');

        dcPage::openModule(__('Edit theme files'), $head);

        echo
        dcPage::breadcrumb(
            [
                Html::escapeHTML(dcCore::app()->blog->name) => '',
                __('Blog appearance')                       => dcCore::app()->adminurl->get('admin.blog.theme'),
                __('Edit theme files')                      => '',
            ]
        ) .
        dcPage::notices() .
        '<p><strong>' . sprintf(__('Your current theme on this blog is "%s".'), Html::escapeHTML(dcCore::app()->admin->theme->get('name'))) . '</strong></p>';

        if (dcCore::app()->blog->settings->system->themes_path !== dcCore::app()->blog->settings->system->getGlobal('themes_path') 
            || !dcCore::app()->themes->getDefine(dcCore::app()->blog->settings->system->theme)->distributed
        ) {
            echo
            '<div id="file-box">' .
            '<div id="file-editor">';

            if (dcCore::app()->admin->file['c'] === null) {
                echo
                '<p>' . __('Please select a file to edit.') . '</p>';
            } else {
                echo
                '<form id="file-form" action="' . dcCore::app()->admin->getPageURL() . '" method="post">' .
                '<div class="fieldset"><h3>' . __('File editor') . '</h3>' .
                '<p><label for="file_content">' . sprintf(__('Editing file %s'), '<strong>' . dcCore::app()->admin->file['f']) . '</strong></label></p>' .
                '<p>' . form::textarea('file_content', 72, 25, [
                    'default'  => Html::escapeHTML(dcCore::app()->admin->file['c']),
                    'class'    => 'maximal',
                    'disabled' => !dcCore::app()->admin->file['w'],
                ]) . '</p>';

                if (dcCore::app()->admin->file['w']) {
                    echo
                    '<p><input type="submit" name="write" value="' . __('Save') . ' (s)" accesskey="s" /> ' .
                    (dcCore::app()->admin->editor->deletableFile(dcCore::app()->admin->file['type'], dcCore::app()->admin->file['f']) ? '<input type="submit" name="delete" class="delete" value="' . __('Reset') . '" />' : '') .
                    dcCore::app()->formNonce() .
                        (dcCore::app()->admin->file['type'] ? form::hidden([dcCore::app()->admin->file['type']], dcCore::app()->admin->file['f']) : '') .
                        '</p>';
                } else {
                    echo
                    '<p>' . __('This file is not writable. Please check your theme files permissions.') . '</p>';
                }

                echo
                '</div></form>';

                if (dcCore::app()->admin->user_ui_colorsyntax) {
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
                    dcPage::jsJson('theme_editor_mode', ['mode' => $editorMode]) .
                    dcPage::jsModuleLoad('themeEditor/js/mode.js') .
                    dcPage::jsRunCodeMirror('editor', 'file_content', 'dotclear', dcCore::app()->admin->user_ui_colorsyntax_theme);
                }
            }

            echo
            '</div>' .
            '</div>' .

            '<div id="file-chooser">' .
            '<h3>' . __('Templates files') . '</h3>' .
            dcCore::app()->admin->editor->filesList('tpl', '<a href="' . dcCore::app()->admin->getPageURL() . '&amp;tpl=%2$s" class="tpl-link">%1$s</a>') .

            '<h3>' . __('CSS files') . '</h3>' .
            dcCore::app()->admin->editor->filesList('css', '<a href="' . dcCore::app()->admin->getPageURL() . '&amp;css=%2$s" class="css-link">%1$s</a>') .

            '<h3>' . __('JavaScript files') . '</h3>' .
            dcCore::app()->admin->editor->filesList('js', '<a href="' . dcCore::app()->admin->getPageURL() . '&amp;js=%2$s" class="js-link">%1$s</a>') .

            '<h3>' . __('Locales files') . '</h3>' .
            dcCore::app()->admin->editor->filesList('po', '<a href="' . dcCore::app()->admin->getPageURL() . '&amp;po=%2$s" class="po-link">%1$s</a>') .

            '<h3>' . __('PHP files') . '</h3>' .
            dcCore::app()->admin->editor->filesList('php', '<a href="' . dcCore::app()->admin->getPageURL() . '&amp;php=%2$s" class="php-link">%1$s</a>') .
            '</div>';

            dcPage::helpBlock('themeEditor');
        } else {
            echo
            '<div class="error"><p>' . __("You can't edit a distributed theme.") . '</p></div>';
        }

        echo
        '</body>' .
        '</html>';
    }
}
