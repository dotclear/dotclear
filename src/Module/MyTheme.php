<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Module;

use Dotclear\App;
use Dotclear\Helper\Network\Http;

/**
 * @brief   Theme My module class.
 *
 * A theme My class must extend this class.
 *
 * @since   2.27
 */
abstract class MyTheme extends MyModule
{
    protected static function define(): ModuleDefine
    {
        // load once themes
        if (App::themes()->isEmpty() && App::blog()->isDefined()) {
            App::themes()->loadModules(App::blog()->themesPath());
        }

        return static::getDefineFromNamespace(App::themes());
    }

    protected static function checkCustomContext(int $context): ?bool
    {
        // themes specific context permissions
        return match ($context) {
            self::BACKEND, self::CONFIG => App::task()->checkContext('BACKEND')
                    // Check specific permission, allowed to blog admin for themes
                    && App::blog()->isDefined()
                    && App::auth()->check(App::auth()->makePermissions([
                        App::auth()::PERMISSION_ADMIN,
                    ]), App::blog()->id()),
            default => null,
        };
    }

    /**
     * Returns URL of a theme file.
     *
     * Always returns frontend (public) URL
     *
     * @param   string  $resource   The resource file
     * @param   bool    $frontend   (not used for themes)
     */
    public static function fileURL(string $resource, bool $frontend = false): string
    {
        if ($resource !== '' && !str_starts_with($resource, '/')) {
            $resource = '/' . $resource;
        }

        if (!App::blog()->isDefined()) {
            return '';
        }

        $base = preg_match('#^http(s)?://#', (string) App::blog()->settings()->system->themes_url) ?
            Http::concatURL(App::blog()->settings()->system->themes_url, '/' . self::id()) :
            Http::concatURL(App::blog()->url(), App::blog()->settings()->system->themes_url . '/' . self::id());

        return  $base . $resource;
    }
}
