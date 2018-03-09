<?php
/**
 * @brief dcCKEditor, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

class dcCKEditorBehaviors
{
    protected static $p_url      = 'index.php?pf=dcCKEditor';
    protected static $config_url = 'plugin.php?p=dcCKEditor&config=1';

    /**
     * adminPostEditor add javascript to the DOM to load ckeditor depending on context
     *
     * @param editor   <b>string</b> wanted editor
     * @param context  <b>string</b> page context (post,page,comment,event,...)
     * @param tags     <b>array</b>  array of ids into inject editor
     * @param syntax   <b>string</b> wanted syntax (xhtml)
     */
    public static function adminPostEditor($editor = '', $context = '', array $tags = array(), $syntax = 'xhtml')
    {
        if (empty($editor) || $editor != 'dcCKEditor' || $syntax != 'xhtml') {return;}

        $config_js = self::$config_url;
        if (!empty($context)) {
            $config_js .= '&context=' . $context;
        }

        $res =
        '<script type="text/javascript">' . "\n" .
        dcPage::jsVar('dotclear.ckeditor_context', $context) .
        'dotclear.ckeditor_tags_context = ' . sprintf('{%s:["%s"]};' . "\n", $context, implode('","', $tags)) .
        'var CKEDITOR_BASEPATH = "' . DC_ADMIN_URL . self::$p_url . '/js/ckeditor/";' . "\n" .
        dcPage::jsVar('dotclear.admin_base_url', DC_ADMIN_URL) .
        dcPage::jsVar('dotclear.base_url', $GLOBALS['core']->blog->host) .
        dcPage::jsVar('dotclear.dcckeditor_plugin_url', DC_ADMIN_URL . self::$p_url) .
        'CKEDITOR_GETURL = function(resource) {
                // If this is not a full or absolute path.
                if ( resource.indexOf(":/") == -1 && resource.indexOf("/") !== 0 ) {
                    resource = this.basePath + resource;
                }
                return resource;
             };' . "\n" .
        "dotclear.msg.img_select_title = '" . html::escapeJS(__('Media chooser')) . "'; " . "\n" .
        "dotclear.msg.img_select_accesskey = '" . html::escapeJS(__('m')) . "'; " . "\n" .
        "dotclear.msg.post_link_title = '" . html::escapeJS(__('Link to an entry')) . "'; " . "\n" .
        "dotclear.msg.link_title = '" . html::escapeJS(__('Link')) . "'; " . "\n" .
        "dotclear.msg.link_accesskey = '" . html::escapeJS(__('l')) . "'; " . "\n" .
        "dotclear.msg.img_title = '" . html::escapeJS(__('External image')) . "'; " . "\n" .
        "dotclear.msg.url_cannot_be_empty = '" . html::escapeJS(__('URL field cannot be empty.')) . "';" . "\n" .
        "</script>\n" .
        dcPage::jsLoad(self::$p_url . '/js/ckeditor/ckeditor.js') .
        dcPage::jsLoad(self::$p_url . '/js/ckeditor/adapters/jquery.js') .
        dcPage::jsLoad($config_js);

        if ($GLOBALS['core']->auth->user_prefs->interface->htmlfontsize) {
            $res .=
            '<script type="text/javascript">' . "\n" .
            dcPage::jsVar('dotclear_htmlFontSize', $GLOBALS['core']->auth->user_prefs->interface->htmlfontsize) . "\n" .
                "</script>\n";
        }

        return $res;
    }

    public static function adminPopupMedia($editor = '')
    {
        if (empty($editor) || $editor != 'dcCKEditor') {return;}

        return dcPage::jsLoad(self::$p_url . '/js/popup_media.js');
    }

    public static function adminPopupLink($editor = '')
    {
        if (empty($editor) || $editor != 'dcCKEditor') {return;}

        return dcPage::jsLoad(self::$p_url . '/js/popup_link.js');
    }

    public static function adminPopupPosts($editor = '')
    {
        if (empty($editor) || $editor != 'dcCKEditor') {return;}

        return dcPage::jsLoad(self::$p_url . '/js/popup_posts.js');
    }

    public static function adminMediaURLParams($p)
    {
        if (!empty($_GET['editor'])) {
            $p['editor'] = html::sanitizeURL($_GET['editor']);
        }
    }

    public static function getTagsContext()
    {
        return self::$tagsContext;
    }
}
