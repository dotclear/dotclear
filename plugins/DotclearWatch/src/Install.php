<?php

/**
 * @brief       DotclearWatch install class.
 * @ingroup     DotclearWatch
 *
 * @author      Jean-Christian Denis
 *
 * @copyright   Jean-Christian Denis
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\DotclearWatch;

use Dotclear\Helper\Process\TraitProcess;

class Install
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (self::status()) {
            // Current distant API URL, before default settings
            $api_url = My::settings()->getGlobal('distant_api_url');

            My::settings()->put(
                'hidden_modules',
                'DotclearWatch',
                'string',
                'Hidden modules from report',
                false,
                true
            );
            My::settings()->put(
                'distant_api_url',
                Utils::DISTANT_API_URL,
                'string',
                'Distant API URL',
                false,
                true
            );

            // Statistics server moved from dotclear.watch to dotclear.org: update the old default URL, keep a custom one
            if (is_string($api_url) && in_array(rtrim(trim($api_url), '/'), Utils::OLD_API_URLS, true)) {
                My::settings()->put('distant_api_url', Utils::DISTANT_API_URL, 'string', 'Distant API URL', true, true);
                // Send next report of this blog to the new server without waiting for the delay
                Utils::clearCache();
            }
        }

        return self::status();
    }
}
