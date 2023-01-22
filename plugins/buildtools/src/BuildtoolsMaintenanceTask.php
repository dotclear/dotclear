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
    /**
     * Maintenance Tab name
     *
     * @var        string
     */
    protected $tab = 'dev';

    /**
     * Maintenance group name
     *
     * @var        string
     */
    protected $group = 'l10n';

    /**
     * Initializes the task.
     */
    protected function init(): void
    {
        $this->task        = __('Generate fake l10n');
        $this->success     = __('fake l10n file generated.');
        $this->error       = __('Failed to generate fake l10n file.');
        $this->description = __('Generate a php file that contents strings to translate that are not be done with core tools.');
    }

    /**
     * Execute the task.
     *
     * @return     bool
     */
    public function execute(): bool
    {
        if (class_exists('defaultWidgets')) {
            defaultWidgets::init();
        }

        $faker = new l10nFaker();
        $faker->generate_file();

        return true;
    }
}

class l10nFaker
{
    /**
     * List of bundled plugins
     *
     * @var array
     */
    protected $bundled_plugins;

    /**
     * Constructs a new instance.
     */
    public function __construct()
    {
        $this->bundled_plugins = explode(',', DC_DISTRIB_PLUGINS);
        dcCore::app()->media   = new dcMedia();
    }

    /**
     * Get a fake l10n
     *
     * @param      string  $str    The string
     *
     * @return     string
     */
    protected function fake_l10n(string $str)
    {
        return sprintf('__("%s");' . "\n", str_replace('"', '\\"', $str));
    }

    /**
     * Generate files
     *
     * - /_fake_l10n.php                        Main locales
     * - /plugins/_fake_plugin/_fake_l10n.php   Plugins and widgets locales
     */
    public function generate_file(): void
    {
        $main = $plugin = "<?php\n" . '// Generated on ' . dt::dt2str('%Y-%m-%d %H:%M %z', (string) time(), dcCore::app()->auth->getInfo('user_tz')) . "\n";

        $main .= "\n// Media sizes\n\n";
        foreach (dcCore::app()->media->thumb_sizes as $v) {
            $main .= $this->fake_l10n($v[2]);
        }

        $post_types = dcCore::app()->getPostTypes();
        $main .= "\n// Post types\n\n";
        foreach ($post_types as $v) {
            $main .= $this->fake_l10n($v['label']);
        }
        file_put_contents(dirname(Clearbricks::lib()->autoloadSource('dcCore')) . '/_fake_l10n.php', $main);

        $plugin .= "\n// Plugin names\n\n";
        foreach ($this->bundled_plugins as $id) {
            $p = dcCore::app()->plugins->getModules($id);
            $plugin .= $this->fake_l10n($p['desc']);
        }

        $plugin .= "\n// Widget settings names\n\n";
        $widgets = dcCore::app()->widgets->elements();
        foreach ($widgets as $w) {
            $plugin .= $this->fake_l10n($w->desc());
        }

        if (!is_dir(__DIR__ . '/../../_fake_plugin')) {
            mkdir(__DIR__ . '/../../_fake_plugin');
        }
        file_put_contents(__DIR__ . '/../../_fake_plugin/_fake_l10n.php', $plugin);
    }
}
