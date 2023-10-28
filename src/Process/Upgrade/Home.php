<?php
/**
 * @package Dotclear
 * @subpackage Upgrade
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Process\Upgrade;

use Dotclear\App;
use Dotclear\Core\Upgrade\Notices;
use Dotclear\Core\Upgrade\Page;
use Dotclear\Core\Process;
use Exception;

/**
 * @since 2.27 Before as admin/index.php
 */
class Home extends Process
{
    public static function init(): bool
    {
        if (!App::task()->checkContext('UPGRADE')) {
            throw new Exception('Application is not in upgrade context.', 500);
        }

        Page::checkSuper();

        return self::status(true);
    }

    public static function process(): bool
    {
        return true;
    }

    public static function render(): void
    {
        Page::open(
            __('Dashboard'),
            Page::jsLoad('js/_index.js') .
            Page::jsAdsBlockCheck(),
            Page::breadcrumb(
                [
                    __('Dashboard') => '',
                ],
                ['home_link' => false]
            )
        );

        if (App::config()->adminUrl() == '') {
            Notices::message(
                sprintf(__('%s is not defined, you should edit your configuration file.'), 'DC_ADMIN_URL') . ' ' .
                __('See <a href="https://dotclear.org/documentation/2.0/admin/config">documentation</a> for more information.'),
                false
            );
        }

        if (App::config()->adminMailfrom() == 'dotclear@local') {
            Notices::message(
                sprintf(__('%s is not defined, you should edit your configuration file.'), 'DC_ADMIN_MAILFROM') . ' ' .
                __('See <a href="https://dotclear.org/documentation/2.0/admin/config">documentation</a> for more information.'),
                false
            );
        }

        $err = [];

        // Check cache directory
        if (!is_dir(App::config()->cacheRoot()) || !is_writable(App::config()->cacheRoot())) {
            $err[] = __('The cache directory does not exist or is not writable. You must create this directory with sufficient rights and affect this location to "DC_TPL_CACHE" in inc/config.php file.');
        }

        // Error list
        if (count($err)) {
            Notices::error(
                __('Error:') .
                '<ul><li>' . implode("</li>\n<li>", $err) . '</li></ul>',
                false,
                true
            );
            unset($err);
        }

        echo
        '<div id="dashboard-main">' .
        '<h2>' . __('Welcome to dotclear update dashboard') . '</h2>' .
        '<p class="info">' . __('Please select an item in sidebar menu') . '<p>' .
        '</div>';

        Page::close();
    }
}
