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

require dirname(__FILE__) . '/class.themeEditor.php';

$file_default = $file = array('c' => null, 'w' => false, 'type' => null, 'f' => null, 'default_file' => false);

# Get interface setting
$core->auth->user_prefs->addWorkspace('interface');
$user_ui_colorsyntax       = $core->auth->user_prefs->interface->colorsyntax;
$user_ui_colorsyntax_theme = $core->auth->user_prefs->interface->colorsyntax_theme;

# Loading themes
$core->themes = new dcThemes($core);
$core->themes->loadModules($core->blog->themes_path, null);
$T = $core->themes->getModules($core->blog->settings->system->theme);
$o = new dcThemeEditor($core);

try
{
    try
    {
        if (!empty($_REQUEST['tpl'])) {
            $file = $o->getFileContent('tpl', $_REQUEST['tpl']);
        } elseif (!empty($_REQUEST['css'])) {
            $file = $o->getFileContent('css', $_REQUEST['css']);
        } elseif (!empty($_REQUEST['js'])) {
            $file = $o->getFileContent('js', $_REQUEST['js']);
        } elseif (!empty($_REQUEST['po'])) {
            $file = $o->getFileContent('po', $_REQUEST['po']);
        }
    } catch (Exception $e) {
        $file = $file_default;
        throw $e;
    }

    # Write file
    if (!empty($_POST['write'])) {
        $file['c'] = $_POST['file_content'];
        $o->writeFile($file['type'], $file['f'], $file['c']);
    }

    # Delete file
    if (!empty($_POST['delete'])) {
        $o->deleteFile($file['type'], $file['f']);
        dcPage::addSuccessNotice(__('The file has been reset.'));
        http::redirect($p_url . '&' . $file['type'] . '=' . $file['f']);
    }
} catch (Exception $e) {
    $core->error->add($e->getMessage());
}
?>

<html>
<head>
    <title><?php echo __('Edit theme files'); ?></title>
    <?php
echo dcPage::jsVars(array(
    'dotclear.msg.saving_document'    => __("Saving document..."),
    'dotclear.msg.document_saved'     => __("Document saved"),
    'dotclear.msg.error_occurred'     => __("An error occurred:"),
    'dotclear.msg.confirm_reset_file' => __("Are you sure you want to reset this file?")
)) .
dcPage::jsConfirmClose('file-form') .
dcPage::jsLoad(dcPage::getPF('themeEditor/js/script.js'));
if ($user_ui_colorsyntax) {
    echo dcPage::jsVars(array('dotclear.colorsyntax' => $user_ui_colorsyntax));
    echo dcPage::jsLoadCodeMirror($user_ui_colorsyntax_theme);
}
echo dcPage::cssLoad(dcPage::getPF('themeEditor/style.css'));
?>
</head>

<body>
<?php
echo dcPage::breadcrumb(
    array(
        html::escapeHTML($core->blog->name) => '',
        __('Blog appearance')               => $core->adminurl->get('admin.blog.theme'),
        __('Edit theme files')              => ''
    )) .
dcPage::notices();
?>

<p><strong><?php echo sprintf(__('Your current theme on this blog is "%s".'), html::escapeHTML($T['name'])); ?></strong></p>

<?php if ($core->blog->settings->system->theme == 'default') {?>
    <div class="error"><p><?php echo __("You can't edit default theme."); ?></p></div>
    </body></html>
<?php }?>

<div id="file-box">
<div id="file-editor">
<?php
if ($file['c'] === null) {
    echo '<p>' . __('Please select a file to edit.') . '</p>';
} else {
    echo
    '<form id="file-form" action="' . $p_url . '" method="post">' .
    '<div class="fieldset"><h3>' . __('File editor') . '</h3>' .
    '<p><label for="file_content">' . sprintf(__('Editing file %s'), '<strong>' . $file['f']) . '</strong></label></p>' .
    '<p>' . form::textarea('file_content', 72, 25, array(
        'default'  => html::escapeHTML($file['c']),
        'class'    => 'maximal',
        'disabled' => !$file['w']
    )) . '</p>';

    if ($file['w']) {
        echo
        '<p><input type="submit" name="write" value="' . __('Save') . ' (s)" accesskey="s" /> ' .
        ($o->deletableFile($file['type'], $file['f']) ? '<input type="submit" name="delete" class="delete" value="' . __('Reset') . '" />' : '') .
        $core->formNonce() .
            ($file['type'] ? form::hidden(array($file['type']), $file['f']) : '') .
            '</p>';
    } else {
        echo '<p>' . __('This file is not writable. Please check your theme files permissions.') . '</p>';
    }

    echo
        '</div></form>';

    if ($user_ui_colorsyntax) {
        $editorMode =
            (!empty($_REQUEST['css']) ? "css" :
            (!empty($_REQUEST['js']) ? "javascript" :
                (!empty($_REQUEST['po']) ? "text/plain" : "text/html")));
        echo
            '<script type="text/javascript">
            window.CodeMirror.defineMode("dotclear", function(config) {
                return CodeMirror.multiplexingMode(
                    CodeMirror.getMode(config, "' . $editorMode . '"),
                    {open: "{{tpl:", close: "}}",
                     mode: CodeMirror.getMode(config, "text/plain"),
                     delimStyle: "delimit"},
                    {open: "<tpl:", close: ">",
                     mode: CodeMirror.getMode(config, "text/plain"),
                     delimStyle: "delimit"},
                    {open: "</tpl:", close: ">",
                     mode: CodeMirror.getMode(config, "text/plain"),
                     delimStyle: "delimit"}
                    );
            });
        </script>';
        echo dcPage::jsRunCodeMirror('editor', 'file_content', 'dotclear', $user_ui_colorsyntax_theme);
    }
}
?>
</div>
</div>

<div id="file-chooser">
<h3><?php echo __('Templates files'); ?></h3>
<?php echo $o->filesList('tpl', '<a href="' . $p_url . '&amp;tpl=%2$s" class="tpl-link">%1$s</a>'); ?>

<h3><?php echo __('CSS files'); ?></h3>
<?php echo $o->filesList('css', '<a href="' . $p_url . '&amp;css=%2$s" class="css-link">%1$s</a>'); ?>

<h3><?php echo __('JavaScript files'); ?></h3>
<?php echo $o->filesList('js', '<a href="' . $p_url . '&amp;js=%2$s" class="js-link">%1$s</a>'); ?>

<h3><?php echo __('Locales files'); ?></h3>
<?php echo $o->filesList('po', '<a href="' . $p_url . '&amp;po=%2$s" class="po-link">%1$s</a>'); ?>
</div>

<?php dcPage::helpBlock('themeEditor');?>
</body>
</html>
