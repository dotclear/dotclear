<?php
/**
 * @since 2.27 Before as inc/dbschema/upgrade-cli.php
 *
 * @package Dotclear
 * @subpackage Upgrade
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Process\Upgrade;

use dcCore;
use Dotclear\Core\Process;
use Dotclear\Core\Upgrade\Upgrade;
use Exception;

class Cli extends Process
{
    public static function init(): bool
    {
        if (!self::status(defined('DC_CONTEXT_UPGRADE') && defined('PHP_SAPI') && PHP_SAPI == 'cli')) {
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
            dcCore::app()->con->begin();

            try {
                $changes = Upgrade::dotclearUpgrade();
            } catch (Exception $e) {
                dcCore::app()->con->rollback();

                throw $e;
            }
            dcCore::app()->con->commit();
            echo 'Upgrade process successfully completed (' . $changes . "). \n";
            exit(0);
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
            exit(1);
        }
    }
}
