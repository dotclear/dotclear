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
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

if (!isset(dcCore::app()->resources['help']['themeEditor'])) {
    dcCore::app()->resources['help']['themeEditor'] = __DIR__ . '/help.html';
}

dcCore::app()->addBehavior('adminCurrentThemeDetails', ['themeEditorBehaviors', 'theme_editor_details']);

dcCore::app()->addBehavior('adminBeforeUserOptionsUpdate', ['themeEditorBehaviors', 'adminBeforeUserUpdate']);
dcCore::app()->addBehavior('adminPreferencesForm', ['themeEditorBehaviors', 'adminPreferencesForm']);

class themeEditorBehaviors
{
    public static function theme_editor_details(dcCore $core, $id)
    {
        if (dcCore::app()->auth->isSuperAdmin()) {
            // Check if it's not an officially distributed theme
            if (dcCore::app()->blog->settings->system->themes_path !== dcCore::app()->blog->settings->system->getGlobal('themes_path') || !adminThemesList::isDistributedModule($id)) {
                return '<p><a href="' . dcCore::app()->adminurl->get('admin.plugin.themeEditor') . '" class="button">' . __('Edit theme files') . '</a></p>';
            }
        }
    }

    public static function adminBeforeUserUpdate($cur, $userID)
    {
        // Get and store user's prefs for plugin options
        dcCore::app()->auth->user_prefs->addWorkspace('interface');

        try {
            dcCore::app()->auth->user_prefs->interface->put('colorsyntax', !empty($_POST['colorsyntax']), 'boolean');
            dcCore::app()->auth->user_prefs->interface->put(
                'colorsyntax_theme',
                (!empty($_POST['colorsyntax_theme']) ? $_POST['colorsyntax_theme'] : '')
            );
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }
    }

    public static function adminPreferencesForm(dcCore $core = null)
    {
        // Add fieldset for plugin options
        dcCore::app()->auth->user_prefs->addWorkspace('interface');
        $current_theme = dcCore::app()->auth->user_prefs->interface->colorsyntax_theme ?? 'default';

        $themes_list  = dcPage::getCodeMirrorThemes();
        $themes_combo = [__('Default') => ''];
        foreach ($themes_list as $theme) {
            $themes_combo[$theme] = $theme;
        }

        echo
        '<div class="fieldset two-cols clearfix">' .
        '<h5 id="themeEditor_prefs">' . __('Syntax highlighting') . '</h5>';
        echo
        '<div class="col">' .
        '<p><label for="colorsyntax" class="classic">' .
        form::checkbox('colorsyntax', 1, dcCore::app()->auth->user_prefs->interface->colorsyntax) . '</label>' .
        __('Syntax highlighting in theme editor') .
            '</p>';
        if (count($themes_combo) > 1) {
            echo
            '<p><label for="colorsyntax_theme" class="classic">' . __('Theme:') . '</label> ' .
            form::combo(
                'colorsyntax_theme',
                $themes_combo,
                [
                    'default' => $current_theme,
                ]
            ) .
                '</p>';
        } else {
            echo form::hidden('colorsyntax_theme', '');
        }
        echo '</div>';
        echo '<div class="col">';
        echo dcPage::jsLoadCodeMirror('', false, ['javascript']);
        if ($current_theme !== 'default') {
            echo dcPage::cssLoad('js/codemirror/theme/' . $current_theme . '.css');
        }
        echo '
<textarea id="codemirror" name="codemirror" readonly="true">
function findSequence(goal) {
  function find(start, history) {
    if (start == goal)
      return history;
    else if (start > goal)
      return null;
    else
      return find(start + 5, "(" + history + " + 5)") ||
             find(start * 3, "(" + history + " * 3)");
  }
  return find(1, "1");
}</textarea>';
        echo
        dcPage::jsJson('theme_editor_current', ['theme' => $current_theme]) .
        dcPage::jsModuleLoad('themeEditor/js/theme.js');
        echo '</div>';
        echo '</div>';
    }
}
