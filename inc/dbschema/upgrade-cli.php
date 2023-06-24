#!/usr/bin/env php
<?php
/**
 * @brief Dotclear upgrade procedure (CLI)
 *
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_RC_PATH')) {
    return;
}

try {
    if (isset($_SERVER['argv'][1])) {
        $dc_conf = $_SERVER['argv'][1];
    } elseif (isset($_SERVER['DC_RC_PATH'])) {
        $dc_conf = realpath($_SERVER['DC_RC_PATH']);
    } else {
        $dc_conf = __DIR__ . '/../config.php';
    }

    if (!is_file($dc_conf)) {
        throw new Exception(sprintf('%s is not a file', $dc_conf));
    }

    $_SERVER['DC_RC_PATH'] = $dc_conf;
    unset($dc_conf);

    require __DIR__ . '/../../src/App.php';

    Dotclear\App::bootstrap();

    echo "Starting upgrade process\n";
    dcCore::app()->con->begin();

    try {
        $changes = dcUpgrade::dotclearUpgrade();
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
