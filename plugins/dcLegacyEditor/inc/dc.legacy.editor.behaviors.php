<?php
/**
 * @brief dcLegacyEditor, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

class dcLegacyEditorBehaviors
{
    protected static $p_url = 'index.php?pf=dcLegacyEditor';

    /**
     * adminPostEditor add javascript to the DOM to load ckeditor depending on context
     *
     * @param editor   <b>string</b> wanted editor
     * @param context  <b>string</b> page context (post,page,comment,event,...)
     * @param tags     <b>array</b>  array of ids to inject editor
     * @param syntax   <b>string</b> wanted syntax (wiki,markdown,...)
     */
    public static function adminPostEditor($editor = '', $context = '', array $tags = array(), $syntax = '')
    {
        if (empty($editor) || $editor != 'dcLegacyEditor') {return;}

        return
        self::jsToolBar() .
        dcPage::jsLoad(dcPage::getPF('dcLegacyEditor/js/_post_editor.js')) .
        '<script type="text/javascript">' . "\n" .
        dcPage::jsVar('dotclear.legacy_editor_context', $context) .
        dcPage::jsVar('dotclear.legacy_editor_syntax', $syntax) .
        'dotclear.legacy_editor_tags_context = ' . sprintf('{%s:["%s"]};' . "\n", $context, implode('","', $tags)) . "\n" .
            "</script>\n";
    }

    public static function adminPopupMedia($editor = '')
    {
        if (empty($editor) || $editor != 'dcLegacyEditor') {return;}

        return dcPage::jsLoad(dcPage::getPF('dcLegacyEditor/js/jsToolBar/popup_media.js'));
    }

    public static function adminPopupLink($editor = '')
    {
        if (empty($editor) || $editor != 'dcLegacyEditor') {return;}

        return dcPage::jsLoad(dcPage::getPF('dcLegacyEditor/js/jsToolBar/popup_link.js'));
    }

    public static function adminPopupPosts($editor = '')
    {
        if (empty($editor) || $editor != 'dcLegacyEditor') {return;}

        return dcPage::jsLoad(dcPage::getPF('dcLegacyEditor/js/jsToolBar/popup_posts.js'));
    }

    protected static function jsToolBar()
    {
        $res =
        dcPage::cssLoad(dcPage::getPF('dcLegacyEditor/css/jsToolBar/jsToolBar.css')) .
        dcPage::jsLoad(dcPage::getPF('dcLegacyEditor/js/jsToolBar/jsToolBar.js'));

        if (isset($GLOBALS['core']->auth) && $GLOBALS['core']->auth->getOption('enable_wysiwyg')) {
            $res .= dcPage::jsLoad(dcPage::getPF('dcLegacyEditor/js/jsToolBar/jsToolBar.wysiwyg.js'));
        }

        $res .=
        dcPage::jsLoad(dcPage::getPF('dcLegacyEditor/js/jsToolBar/jsToolBar.dotclear.js')) .
        '<script type="text/javascript">' . "\n" .
        "jsToolBar.prototype.dialog_url = 'popup.php'; " . "\n" .
        "jsToolBar.prototype.iframe_css = '" .
        'body {' .
        '   color: #000;' .
        '   background: #f9f9f9;' .
        '   margin: 0;' .
        '   padding: 2px;' .
        '   border: none;' .
        (l10n::getTextDirection($GLOBALS['_lang']) == 'rtl' ? ' direction: rtl;' : '') .
        '}' .
        'code {' .
        '   color: #666;' .
        '   font-weight: bold;' .
        '}' .
        'body > p:first-child {' .
        '   margin-top: 0;' .
        '}' .
        "'; " . "\n" .
        "jsToolBar.prototype.base_url = '" . html::escapeJS($GLOBALS['core']->blog->host) . "'; " . "\n" .
        "jsToolBar.prototype.switcher_visual_title = '" . html::escapeJS(__('visual')) . "'; " . "\n" .
        "jsToolBar.prototype.switcher_source_title = '" . html::escapeJS(__('source')) . "'; " . "\n" .
        "jsToolBar.prototype.legend_msg = '" .
        html::escapeJS(__('You can use the following shortcuts to format your text.')) . "'; " . "\n" .
        "jsToolBar.prototype.elements.blocks.options.none = '" . html::escapeJS(__('-- none --')) . "'; " . "\n" .
        "jsToolBar.prototype.elements.blocks.options.nonebis = '" . html::escapeJS(__('-- block format --')) . "'; " . "\n" .
        "jsToolBar.prototype.elements.blocks.options.p = '" . html::escapeJS(__('Paragraph')) . "'; " . "\n" .
        "jsToolBar.prototype.elements.blocks.options.h1 = '" . html::escapeJS(__('Level 1 header')) . "'; " . "\n" .
        "jsToolBar.prototype.elements.blocks.options.h2 = '" . html::escapeJS(__('Level 2 header')) . "'; " . "\n" .
        "jsToolBar.prototype.elements.blocks.options.h3 = '" . html::escapeJS(__('Level 3 header')) . "'; " . "\n" .
        "jsToolBar.prototype.elements.blocks.options.h4 = '" . html::escapeJS(__('Level 4 header')) . "'; " . "\n" .
        "jsToolBar.prototype.elements.blocks.options.h5 = '" . html::escapeJS(__('Level 5 header')) . "'; " . "\n" .
        "jsToolBar.prototype.elements.blocks.options.h6 = '" . html::escapeJS(__('Level 6 header')) . "'; " . "\n" .
        "jsToolBar.prototype.elements.strong.title = '" . html::escapeJS(__('Strong emphasis')) . "'; " . "\n" .
        "jsToolBar.prototype.elements.em.title = '" . html::escapeJS(__('Emphasis')) . "'; " . "\n" .
        "jsToolBar.prototype.elements.ins.title = '" . html::escapeJS(__('Inserted')) . "'; " . "\n" .
        "jsToolBar.prototype.elements.del.title = '" . html::escapeJS(__('Deleted')) . "'; " . "\n" .
        "jsToolBar.prototype.elements.quote.title = '" . html::escapeJS(__('Inline quote')) . "'; " . "\n" .
        "jsToolBar.prototype.elements.code.title = '" . html::escapeJS(__('Code')) . "'; " . "\n" .
        "jsToolBar.prototype.elements.mark.title = '" . html::escapeJS(__('Mark')) . "'; " . "\n" .
        "jsToolBar.prototype.elements.br.title = '" . html::escapeJS(__('Line break')) . "'; " . "\n" .
        "jsToolBar.prototype.elements.blockquote.title = '" . html::escapeJS(__('Blockquote')) . "'; " . "\n" .
        "jsToolBar.prototype.elements.pre.title = '" . html::escapeJS(__('Preformated text')) . "'; " . "\n" .
        "jsToolBar.prototype.elements.ul.title = '" . html::escapeJS(__('Unordered list')) . "'; " . "\n" .
        "jsToolBar.prototype.elements.ol.title = '" . html::escapeJS(__('Ordered list')) . "'; " . "\n" .

        "jsToolBar.prototype.elements.link.title = '" . html::escapeJS(__('Link')) . "'; " . "\n" .
        "jsToolBar.prototype.elements.link.accesskey = '" . html::escapeJS(__('l')) . "'; " . "\n" .
        "jsToolBar.prototype.elements.link.href_prompt = '" . html::escapeJS(__('URL?')) . "'; " . "\n" .
        "jsToolBar.prototype.elements.link.hreflang_prompt = '" . html::escapeJS(__('Language?')) . "'; " . "\n" .

        "jsToolBar.prototype.elements.img.title = '" . html::escapeJS(__('External image')) . "'; " . "\n" .
        "jsToolBar.prototype.elements.img.src_prompt = '" . html::escapeJS(__('URL?')) . "'; " . "\n" .

        "jsToolBar.prototype.elements.img_select.title = '" . html::escapeJS(__('Media chooser')) . "'; " . "\n" .
        "jsToolBar.prototype.elements.img_select.accesskey = '" . html::escapeJS(__('m')) . "'; " . "\n" .
        "jsToolBar.prototype.elements.post_link.title = '" . html::escapeJS(__('Link to an entry')) . "'; " . "\n" .
        "jsToolBar.prototype.elements.removeFormat = jsToolBar.prototype.elements.removeFormat || {}; " . "\n" .
        "jsToolBar.prototype.elements.removeFormat.title = '" . html::escapeJS(__('Remove text formating')) . "'; " . "\n";

        if (!$GLOBALS['core']->auth->check('media,media_admin', $GLOBALS['core']->blog->id)) {
            $res .= "jsToolBar.prototype.elements.img_select.disabled = true;\n";
        }

        $res .= "jsToolBar.prototype.toolbar_bottom = " .
            (isset($GLOBALS['core']->auth) && $GLOBALS['core']->auth->getOption('toolbar_bottom') ? 'true' : 'false') . ";\n";

        $res .=
            "</script>\n";

        if ($GLOBALS['core']->auth->user_prefs->interface->htmlfontsize) {
            $res .=
            '<script type="text/javascript">' . "\n" .
            dcPage::jsVar('dotclear_htmlFontSize', $GLOBALS['core']->auth->user_prefs->interface->htmlfontsize) . "\n" .
                "</script>\n";
        }

        return $res;
    }
}
