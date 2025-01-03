<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Upgrade\GrowUp;

use Dotclear\App;
use Dotclear\Helper\File\Path;

/**
 * @brief   Upgrade step.
 */
class GrowUp_2_5_lt_eq
{
    public static function init(bool $cleanup_sessions): bool
    {
        # Try to disable daInstaller plugin if it has been installed outside the default plugins directory
        $path    = explode(PATH_SEPARATOR, App::config()->pluginsRoot());
        $default = Path::real(__DIR__ . '/../../plugins/');
        foreach ($path as $root) {
            if (!is_dir($root) || !is_readable($root)) {
                continue;
            }
            if (!str_ends_with($root, '/')) {
                $root .= '/';
            }
            if (($p = @dir($root)) === false) {
                continue;
            }
            if (Path::real($root) === $default) {
                continue;
            }
            if (($d = @dir($root . 'daInstaller')) === false) {
                continue;
            }
            $f = $root . '/daInstaller/_disabled';
            if (!file_exists($f)) {
                @file_put_contents($f, '');
            }
        }

        return $cleanup_sessions;
    }
}
