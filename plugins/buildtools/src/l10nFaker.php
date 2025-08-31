<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\buildtools;

use Dotclear\App;
use Dotclear\Helper\Date;

/**
 * @brief   The module l10n faker handler.
 * @ingroup buildtools
 */
class l10nFaker
{
    /**
     * Get a fake l10n.
     *
     * @param   string  $str    The string
     */
    protected function fake_l10n(string $str): string
    {
        return trim($str) !== '' ? sprintf('__(\'%s\');', addslashes($str)) . "\n" : '';
    }

    /**
     * Generate files.
     *
     * - /_fake_l10n.php                        Main locales
     * - /plugins/_fake_plugin/_fake_l10n.php   Plugins and widgets locales
     */
    public function generate_file(): void
    {
        $main = $plugin = "<?php\n\n" . '// Generated on ' . Date::dt2str('%Y-%m-%d %H:%M %z', (string) time(), App::auth()->getInfo('user_tz')) . "\n";

        // Main file

        // Get list of media thumb sizes
        $main .= "\n// Media sizes\n\n";
        foreach (App::media()->getThumbSizes() as $v) {
            if (isset($v[3])) {
                $main .= $this->fake_l10n($v[3]);
            }
        }

        // Get list of post types
        $post_types = App::postTypes()->dump();
        $main .= "\n// Post types\n\n";
        foreach ($post_types as $v) {
            $main .= $this->fake_l10n($v->get('label'));
        }

        // Get list of DB drivers names
        $main .= "\n// DB drivers names\n\n";
        foreach (array_keys(App::db()->combo()) as $driver_name) {
            $main .= $this->fake_l10n($driver_name);
        }

        file_put_contents(implode(DIRECTORY_SEPARATOR, [App::config()->dotclearRoot(), 'inc', 'core', '_fake_l10n.php']), $main);

        // Plugin file

        // Get list of plugins names
        $plugin .= "\n// Plugin names\n\n";
        foreach (App::plugins()->getDefines() as $define) {
            if ($define->get('distributed')) {
                $plugin .= $this->fake_l10n($define->get('desc'));
            }
        }

        // Get list of widgets settings names
        if (class_exists(\Dotclear\Plugin\widgets\Widgets::class)) {
            $plugin .= "\n// Widget settings names\n\n";
            $widgets = \Dotclear\Plugin\widgets\Widgets::$widgets->elements();
            foreach ($widgets as $w) {
                $plugin .= $this->fake_l10n($w->desc());
            }
        }

        if (!is_dir(implode(DIRECTORY_SEPARATOR, [App::config()->dotclearRoot(), 'plugins', '_fake_plugin']))) {
            mkdir(implode(DIRECTORY_SEPARATOR, [App::config()->dotclearRoot(), 'plugins', '_fake_plugin']));
        }
        file_put_contents(implode(DIRECTORY_SEPARATOR, [App::config()->dotclearRoot(), 'plugins', '_fake_plugin', '_fake_l10n.php']), $plugin);
    }
}
