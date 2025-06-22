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
     * @param   string  $resource   The resource file
     */
    public static function fileURL(string $resource, bool $frontend = false): string
    {
        if ($resource !== '' && str_starts_with($resource, '/')) {
            $resource = ltrim($resource, '/');
        }

        if (App::task()->checkContext('BACKEND') && !$frontend) {
            return urldecode(App::backend()->url()->get('load.theme.file', ['tf' => $resource], '&'));
        }

        return App::blog()->isDefined() ? urldecode(App::blog()->getQmarkURL() . 'tf=' . $resource) : '';
    }
}
