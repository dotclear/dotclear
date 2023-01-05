<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
require __DIR__ . '/../inc/admin/prepend.php';

class adminHelp
{
    /**
     * Initializes the page.
     */
    public static function init()
    {
        dcPage::check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_USAGE,
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]));
    }

    /**
     * Renders the page.
     */
    public static function render()
    {
        /**
         * $helpPage() get help content depending on context
         *
         * @param      mixed  ...$args  The arguments
         *
         * @return     array
         */
        $helpPage = function (...$args) {
            // Init return value
            $ret = [
                'content' => '',
                'title'   => '',
            ];

            if (empty($args)) {
                // No context given
                return $ret;
            }

            if (empty(dcCore::app()->resources['help'])) {
                // No available help
                return $ret;
            }

            $content = '';
            $title   = '';
            foreach ($args as $v) {
                if (is_object($v) && isset($v->content)) {
                    $content .= $v->content;

                    continue;
                }

                if (!isset(dcCore::app()->resources['help'][$v])) {
                    continue;
                }
                $f = dcCore::app()->resources['help'][$v];
                if (!file_exists($f) || !is_readable($f)) {
                    continue;
                }

                $fc = (string) file_get_contents($f);
                if (preg_match('|<body[^>]*?>(.*?)</body>|ms', $fc, $matches)) {
                    $content .= $matches[1];
                    if (preg_match('|<title[^>]*?>(.*?)</title>|ms', $fc, $matches)) {
                        $title = $matches[1];
                    }
                } else {
                    $content .= $fc;
                }
            }

            if (trim($content) == '') {
                return $ret;
            }

            $ret['content'] = $content;
            if ($title != '') {
                $ret['title'] = $title;
            }

            return $ret;
        };

        $help_page = !empty($_GET['page']) ? html::escapeHTML($_GET['page']) : 'index';

        $content_array = $helpPage($help_page);

        if (($content_array['content'] === '') || ($help_page === 'index')) {
            $content_array = $helpPage('index');
        }

        if ($content_array['title'] !== '') {
            $breadcrumb = dcPage::breadcrumb(
                [
                    __('Global help')       => dcCore::app()->adminurl->get('admin.help'),
                    $content_array['title'] => '',
                ]
            );
        } else {
            $breadcrumb = dcPage::breadcrumb(
                [
                    __('Global help') => '',
                ]
            );
        }

        dcPage::open(
            __('Global help'),
            dcPage::jsPageTabs('first-step'),
            $breadcrumb
        );

        echo $content_array['content'];

        // Prevents global help link display
        dcCore::app()->resources['ctxhelp'] = true;

        dcPage::close();
    }
}

adminHelp::init();
adminHelp::render();
