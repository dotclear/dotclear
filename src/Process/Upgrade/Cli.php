<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Process\Upgrade;

use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Core\Upgrade\Upgrade;
use Dotclear\Exception\ProcessException;
use Throwable;

/**
 * @brief   CLI upgrade process.
 *
 * @since   2.27 Before as inc/dbschema/upgrade-cli.php
 */
class Cli extends Process
{
    public static function init(): bool
    {
        if (!self::status(App::task()->checkContext('UPGRADE') && App::config()->cliMode())) {
            throw new ProcessException('Application is not in CLI mode', 550);
        }

        return self::status();
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        try {
            echo "Starting upgrade process\n";
            App::db()->con()->begin();

            try {
                $changes = (int) Upgrade::dotclearUpgrade();
            } catch (Throwable $e) {
                App::db()->con()->rollback();

                throw $e;
            }
            App::db()->con()->commit();
            echo 'Upgrade process successfully completed (' . $changes . "). \n";
            dotclear_exit(0);
        } catch (Throwable $e) {
            echo $e->getMessage() . "\n";
            dotclear_exit(1);
        }
    }
}
