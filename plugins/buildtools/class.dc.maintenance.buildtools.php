<?php
/**
 * @brief buildtools, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class dcMaintenanceBuildtools extends dcMaintenanceTask
{
    protected $tab   = 'dev';
    protected $group = 'l10n';

    protected function init()
    {
        $this->task        = __('Generate fake l10n');
        $this->success     = __('fake l10n file generated.');
        $this->error       = __('Failed to generate fake l10n file.');
        $this->description = __('Generate a php file that contents strings to translate that are not be done with core tools.');
    }

    public function execute()
    {
        $widget = dcCore::app()->plugins->getModules('widgets');
        include $widget['root'] . '/_default_widgets.php';

        $faker = new l10nFaker();
        $faker->generate_file();

        return true;
    }
}

class l10nFaker
{
    protected $bundled_plugins;

    public function __construct()
    {
        $this->bundled_plugins = explode(',', DC_DISTRIB_PLUGINS);
        dcCore::app()->media   = new dcMedia(dcCore::app());
    }

    protected function fake_l10n($str)
    {
        return sprintf('__("%s");' . "\n", str_replace('"', '\\"', $str));
    }
    public function generate_file()
    {
        $main = $plugin = "<?php\n";
        $main .= "# Media sizes\n\n";
        foreach (dcCore::app()->media->thumb_sizes as $v) {
            $main .= $this->fake_l10n($v[2]);
        }
        $post_types = dcCore::app()->getPostTypes();
        $main .= "\n# Post types\n\n";
        foreach ($post_types as $v) {
            $main .= $this->fake_l10n($v['label']);
        }
        $ws = dcCore::app()->auth->user_prefs->favorites; // Favs old school !
        if ($ws) {
            $main .= "\n# Favorites\n\n";
            foreach ($ws->dumpPrefs() as $v) {
                $fav = unserialize($v['value']);
                $main .= $this->fake_l10n($fav['title']);
            }
        }
        file_put_contents(Clearbricks::lib()->autoloadSource('dcCore') . '/_fake_l10n.php', $main);
        $plugin .= "\n# Plugin names\n\n";
        foreach ($this->bundled_plugins as $id) {
            $p = dcCore::app()->plugins->getModules($id);
            $plugin .= $this->fake_l10n($p['desc']);
        }
        $plugin .= "\n# Widget settings names\n\n";
        $widgets = dcCore::app()->widgets->elements();
        foreach ($widgets as $w) {
            $plugin .= $this->fake_l10n($w->desc());
        }
        mkdir(__DIR__ . '/../_fake_plugin');
        file_put_contents(__DIR__ . '/../_fake_plugin/_fake_l10n.php', $plugin);
    }
}
