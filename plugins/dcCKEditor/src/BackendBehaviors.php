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
declare(strict_types=1);

namespace Dotclear\Plugin\dcCKEditor;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Page;

class BackendBehaviors
{
    /**
     * adminPostEditor add javascript to the DOM to load ckeditor depending on context
     *
     * @param      string  $editor   The wanted editor
     * @param      string  $context  The page context (post,page,comment,event,...)
     * @param      array   $tags     The array of ids to inject editor
     * @param      string  $syntax   The wanted syntax (wiki,markdown,...)
     *
     * @return     string
     */
    public static function adminPostEditor(string $editor = '', string $context = '', array $tags = [], string $syntax = 'xhtml'): string
    {
        if (empty($editor) || $editor !== 'dcCKEditor' || $syntax !== 'xhtml') {
            return '';
        }

        $config_js = ['config' => '1'];
        if (!empty($context)) {
            $config_js['context'] = $context;
        }

        $alt_tags = new ArrayObject($tags);
        # --BEHAVIOR-- adminPostEditorTags -- string, string, string, ArrayObject, string
        App::behavior()->callBehavior('adminPostEditorTags', $editor, $context, $alt_tags, 'xhtml');

        return
        Page::jsJson('ck_editor_ctx', [
            'ckeditor_context'      => $context,
            'ckeditor_tags_context' => [$context => (array) $alt_tags],
            'admin_base_url'        => App::config()->adminUrl(),
            'base_url'              => App::blog()->host(),
            'dcckeditor_plugin_url' => App::config()->adminUrl() . My::fileURL(''),
            'user_language'         => App::auth()->getInfo('user_lang'),
        ]) .
        Page::jsJson('ck_editor_var', [
            'CKEDITOR_BASEPATH' => App::config()->adminUrl() . My::fileURL('js/ckeditor/'),
        ]) .
        Page::jsJson('ck_editor_msg', [
            'img_select_title'     => __('Media chooser'),
            'img_select_accesskey' => __('m'),
            'post_link_title'      => __('Link to an entry'),
            'link_title'           => __('Link'),
            'link_accesskey'       => __('l'),
            'img_title'            => __('External image'),
            'url_cannot_be_empty'  => __('URL field cannot be empty.'),
        ]) .
        My::jsLoad('_post_editor') .
        My::jsLoad('ckeditor/ckeditor') .
        My::jsLoad('ckeditor/adapters/jquery') .
        Page::jsLoad(My::manageURL($config_js, '&'));
    }

    /**
     * Load additional script for media insertion popup
     *
     * @param      string  $editor  The editor
     *
     * @return     string
     */
    public static function adminPopupMedia(string $editor = ''): string
    {
        if (empty($editor) || $editor !== 'dcCKEditor') {
            return '';
        }

        return
        Page::jsJson('ck_editor_media', [
            'left'   => 'media-left',
            'center' => 'media-center',
            'right'  => 'media-right',
        ]) .
        My::jsLoad('popup_media');
    }

    /**
     * Load additional script for link insertion popup
     *
     * @param      string  $editor  The editor
     *
     * @return     string
     */
    public static function adminPopupLink(string $editor = ''): string
    {
        if (empty($editor) || $editor !== 'dcCKEditor') {
            return '';
        }

        return My::jsLoad('popup_link');
    }

    /**
     * Load additional script for entry link insertion popup
     *
     * @param      string  $editor  The editor
     *
     * @return     string
     */
    public static function adminPopupPosts(string $editor = ''): string
    {
        if (empty($editor) || $editor !== 'dcCKEditor') {
            return '';
        }

        return My::jsLoad('popup_posts');
    }

    /**
     * Add some CSP headers
     *
     * CKEditor uses inline CSS styles, inline JS scripts and even uses eval() javascript function, so…
     *
     * @param      ArrayObject  $csp    The csp
     */
    public static function adminPageHTTPHeaderCSP(ArrayObject $csp): void
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
