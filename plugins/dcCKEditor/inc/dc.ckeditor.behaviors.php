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
     * @param      string  $editor   The wanted editor
     * @param      string  $context  The page context (post,page,comment,event,...)
     * @param      array   $tags     The array of ids to inject editor
     * @param      string  $syntax   The wanted syntax (wiki,markdown,...)
     *
     * @return     mixed
     */
    public static function adminPostEditor($editor = '', $context = '', array $tags = [], $syntax = 'xhtml')
    {
        if (empty($editor) || $editor != 'dcCKEditor' || $syntax != 'xhtml') {
            return;
        }

        $config_js = self::$config_url;
        if (!empty($context)) {
            $config_js .= '&context=' . $context;
        }

        $res = dcPage::jsJson('ck_editor_ctx', [
            'ckeditor_context'      => $context,
            'ckeditor_tags_context' => [$context => $tags],
            'admin_base_url'        => DC_ADMIN_URL,
            'base_url'              => $GLOBALS['core']->blog->host,
            'dcckeditor_plugin_url' => DC_ADMIN_URL . self::$p_url,
            'user_language'         => $GLOBALS['core']->auth->getInfo('user_lang')
        ]) .
        dcPage::jsJson('ck_editor_var', [
            'CKEDITOR_BASEPATH' => DC_ADMIN_URL . self::$p_url . '/js/ckeditor/'
        ]) .
        dcPage::jsJson('ck_editor_msg', [
            'img_select_title'     => __('Media chooser'),
            'img_select_accesskey' => __('m'),
            'post_link_title'      => __('Link to an entry'),
            'link_title'           => __('Link'),
            'link_accesskey'       => __('l'),
            'img_title'            => __('External image'),
            'url_cannot_be_empty'  => __('URL field cannot be empty.')
        ]) .
        dcPage::jsLoad(self::$p_url . '/js/_post_editor.js') .
        dcPage::jsLoad(self::$p_url . '/js/ckeditor/ckeditor.js') .
        dcPage::jsLoad(self::$p_url . '/js/ckeditor/adapters/jquery.js') .
        dcPage::jsLoad($config_js);

        return $res;
    }

    public static function adminPopupMedia($editor = '')
    {
        if (empty($editor) || $editor != 'dcCKEditor') {
            return;
        }

        return dcPage::jsLoad(self::$p_url . '/js/popup_media.js');
    }

    public static function adminPopupLink($editor = '')
    {
        if (empty($editor) || $editor != 'dcCKEditor') {
            return;
        }

        return dcPage::jsLoad(self::$p_url . '/js/popup_link.js');
    }

    public static function adminPopupPosts($editor = '')
    {
        if (empty($editor) || $editor != 'dcCKEditor') {
            return;
        }

        return dcPage::jsLoad(self::$p_url . '/js/popup_posts.js');
    }

    public static function adminMediaURLParams($p)
    {
        if (!empty($_GET['editor'])) {
            $p['editor'] = html::sanitizeURL($_GET['editor']);
        }
    }

    public static function adminPageHTTPHeaderCSP($csp)
    {
        // add 'unsafe-inline' for CSS, add 'unsafe-eval' for scripts as far as CKEditor 4.x is used
        if (strpos($csp['style-src'], 'unsafe-inline') === false) {
            $csp['style-src'] .= " 'unsafe-inline'";
        }
        if (strpos($csp['script-src'], 'unsafe-inline') === false) {
            $csp['script-src'] .= " 'unsafe-inline'";
        }
        if (strpos($csp['script-src'], 'unsafe-eval') === false) {
            $csp['script-src'] .= " 'unsafe-eval'";
        }
    }
}
