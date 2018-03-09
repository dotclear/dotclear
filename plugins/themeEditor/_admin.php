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

if (!defined('DC_CONTEXT_ADMIN')) {return;}

if (!isset($__resources['help']['themeEditor'])) {
    $__resources['help']['themeEditor'] = dirname(__FILE__) . '/help.html';
}

$core->addBehavior('adminCurrentThemeDetails', array('themeEditorBehaviors', 'theme_editor_details'));

$core->addBehavior('adminBeforeUserOptionsUpdate', array('themeEditorBehaviors', 'adminBeforeUserUpdate'));
$core->addBehavior('adminPreferencesForm', array('themeEditorBehaviors', 'adminPreferencesForm'));

class themeEditorBehaviors
{
    public static function theme_editor_details($core, $id)
    {
        if ($id != 'default' && $core->auth->isSuperAdmin()) {
            return '<p><a href="' . $core->adminurl->get('admin.plugin.themeEditor') . '" class="button">' . __('Edit theme files') . '</a></p>';
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
        $themes_combo = array(__('Default') => '');
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
                array(
                    'default'    => $core->auth->user_prefs->interface->colorsyntax_theme,
                    'extra_html' => 'onchange="selectTheme()"')) .
                '</p>';
        } else {
            echo form::hidden('colorsyntax_theme', '');
        }
        echo '</div>';
        echo '<div class="col">';
        echo dcPage::jsLoadCodeMirror('', false, array('javascript'));
        foreach ($themes_list as $theme) {
            echo dcPage::cssLoad('js/codemirror/theme/' . $theme . '.css');
        }
        echo '
<textarea id="codemirror" name="codemirror">
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
            '<script>
            var input = document.getElementById("colorsyntax_theme");
            var theme = input.options[input.selectedIndex].textContent;
            var editor = CodeMirror.fromTextArea(document.getElementById("codemirror"), {
                mode: "javascript",
                tabMode: "indent",
                lineWrapping: 1,
                lineNumbers: 1,
                matchBrackets: 1,
                autoCloseBrackets: 1,
                theme: "' . ($core->auth->user_prefs->interface->colorsyntax_theme != '' ? $core->auth->user_prefs->interface->colorsyntax_theme : 'default') . '"
            });
            function selectTheme() {
                var input = document.getElementById("colorsyntax_theme");
                var theme = input.options[input.selectedIndex].value;
                if (theme == "") theme = "default";
                editor.setOption("theme", theme);
                editor.refresh();
            }
        </script>';
        echo '</div>';
        echo '</div>';
    }
}
