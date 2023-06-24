<?php
/**
 * @brief Blowup, a theme for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Themes
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

namespace Dotclear\Theme\blowup;

use dcCore;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;

class Prepend extends Process
{
    public static function init(): bool
    {
        if (My::checkContext(My::PREPEND)) {
            static::$init = dcCore::app()->blog->settings->system->theme === My::id();
        }

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        dcCore::app()->addBehavior('publicHeadContent', function () {
            $url = Blowup::publicCssUrlHelper();
            if ($url) {
                echo '<link rel="stylesheet" href="' . $url . '" type="text/css" />';
            }
        });

        if (!defined('DC_CONTEXT_ADMIN')) {
            return true;
        }

        dcCore::app()->addBehavior('adminPageHTMLHead', function () {
            echo "\n" . '<!-- Header directives for Blowup configuration -->' . "\n" .
            Page::jsJson('blowup', [
                'blowup_public_url' => Blowup::imagesURL(),
                'blowup_theme_url'  => Blowup::themeURL(),
                'msg'               => [
                    'predefined_styles'      => __('Predefined styles'),
                    'apply_code'             => __('Apply code'),
                    'predefined_style_title' => __('Choose a predefined style'),
                ],
            ]) .
            Page::jsLoad(Blowup::themeURL() . '/js/config.js');
        });

        return true;
    }
}
