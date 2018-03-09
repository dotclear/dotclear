<?php
/**
 * @brief Custom, a theme for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Themes
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_CONTEXT_ADMIN')) {return;}

l10n::set(dirname(__FILE__) . '/locales/' . $_lang . '/main');
$css_file = path::real($core->blog->public_path) . '/custom_style.css';

if (!is_file($css_file) && !is_writable(dirname($css_file))) {
    throw new Exception(
        sprintf(__('File %s does not exist and directory %s is not writable.'),
            $css_file, dirname($css_file))
    );
}

if (isset($_POST['css'])) {
    @$fp = fopen($css_file, 'wb');
    fwrite($fp, $_POST['css']);
    fclose($fp);

    dcPage::message(__('Style sheet upgraded.'), true, true);
}

$css_content = is_file($css_file) ? file_get_contents($css_file) : '';

echo
'<p class="area"><label>' . __('Style sheet:') . '</label> ' .
form::textarea('css', 60, 20, html::escapeHTML($css_content)) . '</p>';
