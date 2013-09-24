<?php


class dcMaintenanceBuildtools extends dcMaintenanceTask
{
	protected $tab = 'dev';
	protected $group = 'l10n';

	protected function init()
	{
		$this->task 		= __('Generate fake l10n');
		$this->success 		= __('fake l10n file generated.');
		$this->error 		= __('Failed to generate fake l10n file.');
		$this->description	= __('Generate a php file that contents strings to translate that are not be done with core tools.');
	}

	public function execute()
	{
		global $core;
		$widget = $this->core->plugins->getModules("widgets");
		include $widget['root'].'/_default_widgets.php';
		
		$faker = new l10nFaker($GLOBALS['core']);
		$faker->generate_file();
		return true;
	}
}

class l10nFaker {
	protected $core;
	protected $bundled_plugins;
	
	public function __construct($core) {
		$this->core = $core;
		$this->bundled_plugins = array(
			"aboutConfig","akismet","antispam","attachments","blogroll",
			"blowupConfig","daInstaller","fairTrackbacks","importExport",
			"maintenance","pages","pings","simpleMenu","tags","themeEditor",
			"userPref","widgets"
		);
		$this->core->media = new dcMedia($this->core);
	}

	protected function fake_l10n($str) {
		return sprintf('__("%s");'."\n",str_replace('"','\\"',$str));
	}
	public function generate_file() {
		global $__widgets;
		global $__autoload;
		
		$main = "<?php\n";
		$plugin = "<?php\n";
		$main .= "# Media sizes\n\n";
		foreach ($this->core->media->thumb_sizes as $k=> $v) {
			$main .= $this->fake_l10n($v[2]);
		}
		$post_types = $this->core->getPostTypes();
		$main .= "\n# Post types \n\n";
		foreach ($post_types as $k => $v) {
			$main .= $this->fake_l10n($v['label']);
		}
		$ws = $this->core->auth->user_prefs->favorites;
		$main .= "\n# Favorites \n\n";
		foreach ($ws->dumpPrefs() as $k => $v) {
			$fav = unserialize($v['value']);
			$main .= $this->fake_l10n($fav['title']);
		}
		file_put_contents(dirname($__autoload['dcCore']).'/_fake_l10n.php', $main);
		$plugin .= "\n# Plugin names \n\n";
		foreach ($this->bundled_plugins as $id) {
			$p = $this->core->plugins->getModules($id);
			$plugin .= $this->fake_l10n($p['desc']);
		}
		$plugin .= "\n# Widget settings names \n\n";
		$widgets = $__widgets->elements();
		foreach ($widgets as $w) {
			$plugin .= $this->fake_l10n($w->desc());
		}
		mkdir(dirname(__FILE__)."/../_fake_plugin");
		file_put_contents(dirname(__FILE__).'/../_fake_plugin/_fake_l10n.php', $plugin);
	}
}
