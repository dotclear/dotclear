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
use Exception;

/**
 * @brief   CLI upgrade process.
 *
 * @since   2.27 Before as inc/dbschema/upgrade-cli.php
 */
class Cli extends Process
{
    public static function init(): bool
    {
        if (!self::status(App::task()->checkContext('UPGRADE') && defined('PHP_SAPI') && PHP_SAPI === 'cli')) {
            throw new Exception('Application is not in CLI mode', 550);
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
            App::con()->begin();

            try {
                $changes = (int) Upgrade::dotclearUpgrade();
            } catch (Exception $e) {
                App::con()->rollback();

                throw $e;
            }
            App::con()->commit();
            echo 'Upgrade process successfully completed (' . $changes . "). \n";
            exit(0);
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
            exit(1);
        }
    }
}
