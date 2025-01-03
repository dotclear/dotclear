<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\dcLegacyEditor;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Page;
use Dotclear\Helper\L10n;

/**
 * @brief   The module backend behaviors.
 * @ingroup dcLegacyEditor
 */
class BackendBehaviors
{
    /**
     * adminPostEditor add javascript to the DOM to load legacy editor depending on context.
     *
     * @param   string          $editor     The wanted editor
     * @param   string          $context    The page context (post,page,comment,event,...)
     * @param   array<string>   $tags       The array of ids to inject editor
     * @param   string          $syntax     The wanted syntax (wiki,markdown,...)
     */
    public static function adminPostEditor($editor = '', $context = '', array $tags = [], $syntax = ''): string
    {
        if (empty($editor) || $editor != 'dcLegacyEditor') {
            return '';
        }

        $alt_tags = new ArrayObject($tags);
        # --BEHAVIOR-- adminPostEditorTags -- string, string, string, ArrayObject, string
        App::behavior()->callBehavior('adminPostEditorTags', $editor, $context, $alt_tags, $syntax);

        $js = [
            'legacy_editor_context'      => $context,
            'legacy_editor_syntax'       => $syntax,
            'legacy_editor_tags_context' => [$context => (array) $alt_tags],
        ];

        return
        self::jsToolBar() .
        Page::jsJson('legacy_editor_ctx', $js) .
        My::jsLoad('_post_editor');
    }

    /**
     * Add media popup JS if necessary
     *
     * @param      string  $editor  The editor
     */
    public static function adminPopupMedia($editor = ''): string
    {
        if (empty($editor) || $editor != 'dcLegacyEditor') {
            return '';
        }

        return My::jsLoad('jsToolBar/popup_media');
    }

    /**
     * Add link popup JS if necessary
     *
     * @param      string  $editor  The editor
     */
    public static function adminPopupLink($editor = ''): string
    {
        if (empty($editor) || $editor != 'dcLegacyEditor') {
            return '';
        }

        return My::jsLoad('jsToolBar/popup_link');
    }

    /**
     * Add posts popup JS if necessary
     *
     * @param      string  $editor  The editor
     */
    public static function adminPopupPosts($editor = ''): string
    {
        if (empty($editor) || $editor != 'dcLegacyEditor') {
            return '';
        }

        return My::jsLoad('jsToolBar/popup_posts');
    }

    /**
     * Add JS toolbar
     */
    protected static function jsToolBar(): string
    {
        $js = [
            'dialog_url'            => 'popup.php',
            'base_url'              => App::blog()->host(),
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
            'toolbar_bottom' => (App::task()->checkContext('BACKEND') && App::auth()->getOption('toolbar_bottom')),
            'dynamic_height' => (App::task()->checkContext('BACKEND') && My::settings()->dynamic),
            'style'          => [
                'left'   => 'media-left',
                'center' => 'media-center',
                'right'  => 'media-right',
            ],
            'img_link_title' => __('Open the media'),
        ];

        $rtl              = L10n::getLanguageTextDirection(App::lang()->getLang()) === 'rtl' ? 'direction: rtl;' : '';
        $js['iframe_css'] = self::css($rtl);
        // End of tricky code

        if (!App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_MEDIA,
            App::auth()::PERMISSION_MEDIA_ADMIN,
        ]), App::blog()->id())) {
            $js['elements']['img_select']['disabled'] = true;
        }

        $res = Page::jsJson('legacy_editor', $js) .
        My::cssLoad('jsToolBar/jsToolBar') .
        My::jsLoad('jsToolBar/jsToolBar');

        if (App::task()->checkContext('BACKEND') && App::auth()->getOption('enable_wysiwyg')) {
            $res .= My::jsLoad('jsToolBar/jsToolBar.wysiwyg');
        }

        return $res . (My::jsLoad('jsToolBar/jsToolBar.dotclear') . My::jsLoad('jsToolBar/jsToolBar.config'));
    }

    private static function css(string $rtl): string
    {
        // Tricky code to avoid xgettext bug on indented end heredoc identifier (see https://savannah.gnu.org/bugs/?62158)
        // Warning: don't use <<< if there is some __() l10n calls after as xgettext will not find them
        return <<<EOT
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
    }
}
