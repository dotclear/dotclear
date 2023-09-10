<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear;

/**
 * Config handler.
 *
 * Simple write once container for 
 * unmutable configuration values.
 *
 * All methods are typed and return
 * also default values in same type.
 */
class Config
{
	private float $start_time;
	private bool $cli_mode;
	private bool $debug_mode;
	private string $dotclear_root;
	private string $dotclear_version;
	private string $dotclear_name;
	private string $config_path;
	private string $digests_root;
	private string $l10n_root;
	private string $l10n_url;
	private string $distributed_plugins;
	private string $distributed_themes;

	private function set($key, $value = null)
	{
		return $this->{$key} ?? (is_null($value) ? null : $this->{$key} = $value);
	}

	public function startTime(float $value = null): float
	{
		return $this->set('start_time', $value) ?? 0;
	}

	public function cliMode(bool $value = null): bool
	{
		return $this->set('cli_mode', $value) ?? false;
	}

	public function debugMode(bool $value = null): bool
	{
		return $this->set('debug_mode', $value) ?? false;
	}

	public function dotclearRoot(string $value = null): string
	{
		return $this->set('dotclear_root', $value) ?? '';
	}

	public function dotclearVersion(string $value = null): string
	{
		return $this->set('dotclear_version', $value) ?? '';
	}

	public function dotclearName(string $value = null): string
	{
		return $this->set('dotclear_name', $value) ?? '';
	}

	public function configPath(string $value = null): string
	{
		return $this->set('config_path', $value) ?? '';
	}

	public function digestsRoot(string $value = null): string
	{
		return $this->set('digests_root', $value) ?? '';
	}

	public function l10nRoot(string $value = null): string
	{
		return $this->set('l10n_root', $value) ?? '';
	}

	public function l10nUrl(string $value = null): string
	{
		return $this->set('l10n_url', $value) ?? '';
	}

	public function distributedPlugins(string $value = null): string
	{
		return $this->set('distributed_plugins', $value) ?? '';
	}

	public function distributedThemes(string $value = null): string
	{
		return $this->set('distributed_themes', $value) ?? '';
	}
	
}