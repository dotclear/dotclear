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
     * adminPostEditor add javascript to the DOM to load legacy editor depending on context
     *
     * @param      string  $editor   The wanted editor
     * @param      string  $context  The page context (post,page,comment,event,...)
     * @param      array   $tags     The array of ids to inject editor
     * @param      string  $syntax   The wanted syntax (wiki,markdown,...)
     *
     * @return     mixed
     */
    public static function adminPostEditor($editor = '', $context = '', array $tags = [], $syntax = '')
    {
        if (empty($editor) || $editor != 'dcLegacyEditor') {
            return;
        }

        $js = [
            'legacy_editor_context'      => $context,
            'legacy_editor_syntax'       => $syntax,
            'legacy_editor_tags_context' => [$context => $tags],
        ];

        return
        self::jsToolBar() .
        dcPage::jsJson('legacy_editor_ctx', $js) .
        dcPage::jsModuleLoad('dcLegacyEditor/js/_post_editor.js');
    }

    public static function adminPopupMedia($editor = '')
    {
        if (empty($editor) || $editor != 'dcLegacyEditor') {
            return;
        }

        return dcPage::jsModuleLoad('dcLegacyEditor/js/jsToolBar/popup_media.js');
    }

    public static function adminPopupLink($editor = '')
    {
        if (empty($editor) || $editor != 'dcLegacyEditor') {
            return;
        }

        return dcPage::jsModuleLoad('dcLegacyEditor/js/jsToolBar/popup_link.js');
    }

    public static function adminPopupPosts($editor = '')
    {
        if (empty($editor) || $editor != 'dcLegacyEditor') {
            return;
        }

        return dcPage::jsModuleLoad('dcLegacyEditor/js/jsToolBar/popup_posts.js');
    }

    protected static function jsToolBar()
    {
        $js = [
            'dialog_url'            => 'popup.php',
            'base_url'              => dcCore::app()->blog->host,
            'switcher_visual_title' => __('visual'),
            'switcher_source_title' => __('source'),
            'legend_msg'            => __('You can use the following shortcuts to format your text.'),
            'elements'              => [
                'blocks' => [
                    'title'   => __('Block format'),
                    'options' => [
                        'none'    => __('-- none --'),
                        'nonebis' => __('-- block format --'),
                        'p'       => __('Paragraph'),
                        'h1'      => __('Level 1 header'),
                        'h2'      => __('Level 2 header'),
                        'h3'      => __('Level 3 header'),
                        'h4'      => __('Level 4 header'),
                        'h5'      => __('Level 5 header'),
                        'h6'      => __('Level 6 header'),
                    ], ],

                'strong'     => ['title' => __('Strong emphasis')],
                'em'         => ['title' => __('Emphasis')],
                'ins'        => ['title' => __('Inserted')],
                'del'        => ['title' => __('Deleted')],
                'quote'      => ['title' => __('Inline quote')],
                'code'       => ['title' => __('Code')],
                'mark'       => ['title' => __('Mark')],
                'br'         => ['title' => __('Line break')],
                'blockquote' => ['title' => __('Blockquote')],
                'pre'        => ['title' => __('Preformated text')],
                'ul'         => ['title' => __('Unordered list')],
                'ol'         => ['title' => __('Ordered list')],

                'link' => [
                    'title'           => __('Link'),
                    'accesskey'       => __('l'),
                    'href_prompt'     => __('URL?'),
                    'hreflang_prompt' => __('Language?'),
                ],

                'img' => [
                    'title'      => __('External image'),
                    'src_prompt' => __('URL?'),
                ],

                'img_select' => [
                    'title'     => __('Media chooser'),
                    'accesskey' => __('m'),
                ],

                'post_link'    => ['title' => __('Link to an entry')],
                'removeFormat' => ['title' => __('Remove text formating')],
                'preview'      => ['title' => __('Preview')],
            ],
            'toolbar_bottom' => (bool) isset(dcCore::app()->auth) && dcCore::app()->auth->getOption('toolbar_bottom'),
        ];

        // Tricky code to avoid xgettext bug on indented end heredoc identifier (see https://savannah.gnu.org/bugs/?62158)
        $rtl = l10n::getTextDirection($GLOBALS['_lang']) == 'rtl' ? 'direction: rtl;' : '';
        $css = <<<EOT
            body {
                color: #000;
                background: #f9f9f9;
                margin: 0;
                padding: 2px;
                border: none;
                $rtl
            }
            code {
                color: #666;
                font-weight: bold;
            }
            body > p:first-child {
                margin-top: 0;
            }
            EOT;
        $js['iframe_css'] = $css;
        // End of tricky code

        if (!dcCore::app()->auth->check('media,media_admin', dcCore::app()->blog->id)) {
            $js['elements']['img_select']['disabled'] = true;
        }

        $res = dcPage::jsJson('legacy_editor', $js) .
        dcPage::cssModuleLoad('dcLegacyEditor/css/jsToolBar/jsToolBar.css') .
        dcPage::jsModuleLoad('dcLegacyEditor/js/jsToolBar/jsToolBar.js');

        if (isset(dcCore::app()->auth) && dcCore::app()->auth->getOption('enable_wysiwyg')) {
            $res .= dcPage::jsModuleLoad('dcLegacyEditor/js/jsToolBar/jsToolBar.wysiwyg.js');
        }

        $res .= dcPage::jsModuleLoad('dcLegacyEditor/js/jsToolBar/jsToolBar.dotclear.js') .
        dcPage::jsModuleLoad('dcLegacyEditor/js/jsToolBar/jsToolBar.config.js');

        return $res;
    }
}
