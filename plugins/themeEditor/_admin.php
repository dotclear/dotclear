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

if (!isset($__resources['help']['themeEditor'])) {
    $__resources['help']['themeEditor'] = dirname(__FILE__) . '/help.html';
}

$core->addBehavior('adminCurrentThemeDetails', ['themeEditorBehaviors', 'theme_editor_details']);

$core->addBehavior('adminBeforeUserOptionsUpdate', ['themeEditorBehaviors', 'adminBeforeUserUpdate']);
$core->addBehavior('adminPreferencesForm', ['themeEditorBehaviors', 'adminPreferencesForm']);

class themeEditorBehaviors
{
    public static function theme_editor_details($core, $id)
    {
        if ($id != 'default' && $core->auth->isSuperAdmin()) {
            // Check if it's not an officially distributed theme
            if ($core->blog->settings->system->themes_path !== $core->blog->settings->system->getGlobal('themes_path') || !adminThemesList::isDistributedModule($id)) {
                return '<p><a href="' . $core->adminurl->get('admin.plugin.themeEditor') . '" class="button">' . __('Edit theme files') . '</a></p>';
            }
        }
    }

    public static function adminBeforeUserUpdate($cur, $userID)
    {
        global $core;

        // Get and store user's prefs for plugin options
        $core->auth->user_prefs->addWorkspace('interface');

        try {
            $core->auth->user_prefs->interface->put('colorsyntax', !empty($_POST['colorsyntax']), 'boolean');
            $core->auth->user_prefs->interface->put('colorsyntax_theme',
                (!empty($_POST['colorsyntax_theme']) ? $_POST['colorsyntax_theme'] : ''));
        } catch (Exception $e) {
            $core->error->add($e->getMessage());
        }
    }

    public static function adminPreferencesForm($core)
    {
        // Add fieldset for plugin options
        $core->auth->user_prefs->addWorkspace('interface');

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
        form::checkbox('colorsyntax', 1, $core->auth->user_prefs->interface->colorsyntax) . '</label>' .
        __('Syntax highlighting in theme editor') .
            '</p>';
        if (count($themes_combo) > 1) {
            echo
            '<p><label for="colorsyntax_theme" class="classic">' . __('Theme:') . '</label> ' .
            form::combo('colorsyntax_theme', $themes_combo,
                [
                    'default' => $core->auth->user_prefs->interface->colorsyntax_theme
                ]) .
                '</p>';
        } else {
            echo form::hidden('colorsyntax_theme', '');
        }
        echo '</div>';
        echo '<div class="col">';
        echo dcPage::jsLoadCodeMirror('', false, ['javascript']);
        foreach ($themes_list as $theme) {
            echo dcPage::cssLoad('js/codemirror/theme/' . $theme . '.css');
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
        dcPage::jsJson('theme_editor_current', ['theme' => $core->auth->user_prefs->interface->colorsyntax_theme != '' ? $core->auth->user_prefs->interface->colorsyntax_theme : 'default']) .
        dcPage::jsLoad(dcPage::getPF('themeEditor/js/theme.js'));
        echo '</div>';
        echo '</div>';
    }
}
