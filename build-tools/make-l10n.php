#!/usr/bin/env php
<?php
$license_block = <<<EOF
    /**
     * @package Dotclear
     *
     * @copyright Olivier Meunier & Association Dotclear
     * @copyright GPL-2.0-only
     */
    EOF;

require __DIR__ . '/../src/Helper/L10n.php';

$path = (!empty($_SERVER['argv'][1])) ? $_SERVER['argv'][1] : getcwd();
$path = realpath($path);

$cmd = 'find ' . $path . ' -type f -name \'*.po\'';
exec($cmd, $eres, $ret);

$res = [];

foreach ($eres as $f) {
    $dest = dirname($f) . '/' . basename($f, '.po') . '.lang.php';
    echo 'l10n file ' . $dest . ': ';

    if (Dotclear\Helper\L10n::generatePhpFileFromPo(dirname($f) . '/' . basename($f, '.po'), $license_block)) {
        echo 'OK';
    } else {
        echo 'FAILED';
    }
    echo "\n";
}
