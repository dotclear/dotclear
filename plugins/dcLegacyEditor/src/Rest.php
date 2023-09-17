<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\dcLegacyEditor;

use Dotclear\App;

if (!App::task()->checkContext('BACKEND')) {
    return;
}

/**
 * @brief   The module backend REST service.
 * @ingroup dcLegacyEditor
 */
class Rest
{
    /**
     * Convert wiki to HTML REST service (JSON).
     *
     * @param   array   $get    The get
     * @param   array   $post   The post
     *
     * @return  array
     */
    public static function convert(array $get, array $post): array
    {
        $wiki = $post['wiki'] ?? '';
        $ret  = false;
        $html = '';
        if ($wiki !== '') {
            if (!App::filter()->wiki()) {
                App::filter()->initWikiPost();
            }
            $html = App::formater()->callEditorFormater(My::id(), 'wiki', $wiki);
            $ret  = strlen($html) > 0;

            if ($ret) {
                $media_root = App::blog()->host();
                $html       = preg_replace_callback('/src="([^\"]*)"/', function ($matches) use ($media_root) {
                    if (!preg_match('/^http(s)?:\/\//', $matches[1])) {
                        // Relative URL, convert to absolute
                        return 'src="' . $media_root . $matches[1] . '"';
                    }
                    // Absolute URL, do nothing
                    return $matches[0];
                }, $html);
            }
        }

        return [
            'ret' => $ret,
            'msg' => $html,
        ];
    }
}
