<?php

$license_block = <<<EOF
    /**
     * @package Dotclear
     *
     * @copyright Olivier Meunier & Association Dotclear
     * @copyright GPL-2.0-only
     */
    EOF;

$dc_base  = __DIR__ . '/..';
$php_exec = $_SERVER['_'];

$opts = [
    'http' => [],
];
if (getenv('http_proxy') !== false) {
    $opts['http']['proxy'] = 'tcp://' . getenv('http_proxy');
}
$context = stream_context_create($opts);

if (!file_exists($dc_base . '/composer.phar')) {
    echo "Downloading composer.phar\n";
    $composer_installer = file_get_contents('https://getcomposer.org/composer.phar', false, $context);
    file_put_contents($dc_base . '/composer.phar', $composer_installer);
}
chdir($dc_base);
echo 'Running ' . $php_exec . ' composer.phar install' . "\n";
passthru($php_exec . ' composer.phar install');
