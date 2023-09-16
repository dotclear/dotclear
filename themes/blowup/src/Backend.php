<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */

namespace Dotclear\Theme\blowup;

use Dotclear\App;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;

/**
 * @brief   The module backend process.
 * @ingroup blowup
 */
class Backend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::behavior()->addBehavior('adminPageHTMLHead', function () {
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
