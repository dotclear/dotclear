<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\blogroll;

use Exception;
use Dotclear\App;
use Dotclear\Core\Url;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Plugin\blogroll\Status\Link;

/**
 * @brief   The module frontent URL.
 * @ingroup blogroll
 */
class FrontendUrl extends Url
{
    /**
     * Get blogroll XBEL.
     *
     * @param   array<string, mixed>   $args   The arguments
     */
    public static function xbel(?array $args): void
    {
        $blogroll = new Blogroll(App::blog());

        try {
            $links = $blogroll->getLinks([
                'link_status' => Link::ONLINE,
            ]);
        } catch (Exception) {
            self::p404();
        }

        if ($args) {
            // We don't expect any URL query
            self::p404();
        }

        Http::cache(App::cache()->getFiles(), App::cache()->getTimes());

        header('Content-Type: text/xml; charset=UTF-8');

        echo
        '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
        '<!DOCTYPE xbel PUBLIC "+//IDN python.org//DTD XML Bookmark Exchange Language 1.0//EN//XML"' . "\n" .
        '"http://www.python.org/topics/xml/dtds/xbel-1.0.dtd">' . "\n" .
        '<xbel version="1.0">' . "\n" .
        '<title>' . Html::escapeHTML(App::blog()->name()) . ' blogroll</title>' . "\n";

        $i = 1;
        foreach ($blogroll->getLinksHierarchy($links) as $cat_title => $links) {
            if ($cat_title !== '') {
                echo
                '<folder>' . "\n" .
                '<title>' . Html::escapeHTML($cat_title) . '</title>' . "\n";
            }

            foreach ($links as $link) {
                $title = is_string($title = $link['link_title']) ? $title : '';
                $href  = is_string($href = $link['link_href']) ? $href : '';

                if ($title !== '' && $href !== '') {
                    $desc = is_string($desc = $link['link_desc']) ? $desc : '';
                    $lang = is_string($lang = $link['link_lang']) ? $lang : '';
                    $xfn  = is_string($xfn = $link['link_xfn']) ? $xfn : '';

                    $lang = $lang !== '' ? ' xml:lang="' . $lang . '"' : '';

                    echo
                    '<bookmark href="' . $href . '"' . $lang . '>' . "\n" .
                    '<title>' . Html::escapeHTML($title) . '</title>' . "\n";

                    if ($desc !== '') {
                        echo
                        '<desc>' . Html::escapeHTML($desc) . '</desc>' . "\n";
                    }

                    if ($xfn !== '') {
                        echo
                        '<info>' . "\n" .
                        '<metadata owner="http://gmpg.org/xfn/">' . $xfn . '</metadata>' . "\n" .
                        '</info>' . "\n";
                    }

                    echo
                    '</bookmark>' . "\n";
                }
            }

            if ($cat_title !== '') {
                echo
                '</folder>' . "\n";
            }

            $i++;
        }

        echo
        '</xbel>';
    }
}
