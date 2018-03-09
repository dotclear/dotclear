#!/usr/bin/env php
<?php
/**
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

try
{
    $js = (!empty($_SERVER['argv'][1])) ? $_SERVER['argv'][1] : null;

    if (!$js || !is_file($js)) {
        throw new Exception(sprintf("File %s does not exist", $js));
    }

    require dirname(__FILE__) . '/jsmin-1.1.1.php';

    $content = file_get_contents($js);
    $res     = JSMin::minify($content);

    if (($fp = fopen($js, 'wb')) === false) {
        throw new Exception(sprintf('Unable to open file %s', $js));
    }
    fwrite($fp, $res);
    fclose($fp);
} catch (Exception $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
?>
