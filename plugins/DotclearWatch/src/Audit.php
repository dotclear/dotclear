<?php

/**
 * @brief       DotclearWatch compatibility audit.
 * @ingroup     DotclearWatch
 *
 * The distant server answers a report with the compatibility rules of the next Dotclear version
 * (what breaks or is deprecated). They are kept in the var directory. On demand of a super admin,
 * the installed modules (plugins and themes, distributed ones excepted) are checked against these rules
 * with RulesScanner, and the result is kept too. Nothing about modules code is sent.
 * See CONCEPTION.md of DotclearWatchServer and https://github.com/JcDenis/gh2da/blob/master/dotclear/RULES.md
 *
 * @author      Jean-Christian Denis
 *
 * @copyright   Jean-Christian Denis
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\DotclearWatch;

use Dotclear\App;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Module\ModuleDefine;
use Throwable;

class Audit
{
    public const ALERT = 'alert';

    public const NOTICE = 'notice';

    /**
     * Save rules received from the distant server.
     *
     * @param   array<mixed>    $rules  The rules (dotclear header and rules list)
     */
    public static function saveRules(array $rules): bool
    {
        if (!isset($rules['rules']) || !is_array($rules['rules']) || !isset($rules['dotclear']) || !is_array($rules['dotclear'])) {
            return false;
        }

        return self::writeFile('rules.json', [
            'date'     => time(),
            'dotclear' => $rules['dotclear'],
            'rules'    => array_values($rules['rules']),
        ]);
    }

    /**
     * Get the saved rules, null if none.
     *
     * @return  null|array{date: int, dotclear: array<string, string>, rules: array<int, array<mixed>>}
     */
    public static function getRules(): ?array
    {
        $data = self::readFile('rules.json');
        if ($data === null || !isset($data['rules']) || !is_array($data['rules'])) {
            return null;
        }

        $header = [];
        foreach ((array) ($data['dotclear'] ?? []) as $k => $v) {
            $header[(string) $k] = is_scalar($v) ? (string) $v : '';
        }

        $rules = [];
        foreach ($data['rules'] as $rule) {
            if (is_array($rule)) {
                $rules[] = $rule;
            }
        }

        return [
            'date'     => is_numeric($data['date'] ?? null) ? (int) $data['date'] : 0,
            'dotclear' => $header,
            'rules'    => $rules,
        ];
    }

    /**
     * Get the finding of the PHP version, null if the PHP version is enough or unknown.
     *
     * @return  null|array<string, mixed>
     */
    public static function checkPhp(): ?array
    {
        $rules = self::getRules();

        return $rules === null ? null : (new RulesScanner($rules))->checkPhp(PHP_VERSION);
    }

    /**
     * Get the modules to check: installed plugins and themes of the blog, without distributed ones.
     *
     * @return  array<string, array{id: string, type: string, name: string, version: string, enabled: bool, root: string}>   By type:id
     */
    public static function getModules(): array
    {
        if (App::themes()->isEmpty()) {
            App::themes()->loadModules(App::blog()->themesPath());
        }

        $modules = [];
        foreach ([
            'plugin' => [App::plugins(), explode(',', App::config()->distributedPlugins())],
            'theme'  => [App::themes(), explode(',', App::config()->distributedThemes())],
        ] as $type => [$handler, $distributed]) {
            foreach ($handler->getDefines() as $define) {
                $id   = $define->getId();
                $root = $define->get('root');
                if (in_array($id, $distributed, true) || !is_string($root) || $root === '' || !is_dir($root)) {
                    continue;
                }

                $modules[$type . ':' . $id] = [
                    'id'      => $id,
                    'type'    => $type,
                    'name'    => is_string($name = $define->get('name')) && $name !== '' ? $name : $id,
                    'version' => is_string($version = $define->get('version')) ? $version : '',
                    'enabled' => $define->get('state') === ModuleDefine::STATE_ENABLED,
                    'root'    => $root,
                ];
            }
        }

        ksort($modules);

        return $modules;
    }

    /**
     * Check the modules against the rules and save the result.
     *
     * @return  bool    False if there is no rules
     */
    public static function run(): bool
    {
        $rules = self::getRules();
        if ($rules === null) {
            return false;
        }

        // Reading all files of all modules may take a while
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }

        $scanner = new RulesScanner($rules, App::config()->dotclearVersion());
        $results = [];
        foreach (self::getModules() as $key => $module) {
            try {
                $report = $scanner->scan($module['root']);
            } catch (Throwable) {
                continue;
            }

            unset($module['root']);
            $results[$key] = array_merge($module, [
                'level'    => $report['level'],
                'alerts'   => $report['alerts'],
                'notices'  => $report['notices'],
                'findings' => $report['findings'],
            ]);
        }

        return self::writeFile('audit.json', [
            'date'     => time(),
            'rules'    => self::signature($rules),
            'modules'  => self::modulesSignature(),
            'dotclear' => App::config()->dotclearVersion(),
            'next'     => $rules['dotclear']['next'] ?? '',
            'results'  => $results,
        ]);
    }

    /**
     * Get the saved result, null if none.
     *
     * @return  null|array{date: int, outdated: bool, next: string, alerts: int, notices: int, results: array<string, array<mixed>>}
     */
    public static function getResult(): ?array
    {
        $data = self::readFile('audit.json');
        if ($data === null || !isset($data['results']) || !is_array($data['results'])) {
            return null;
        }

        $rules   = self::getRules();
        $results = [];
        $alerts  = 0;
        $notices = 0;
        foreach ($data['results'] as $key => $result) {
            if (!is_array($result)) {
                continue;
            }

            $results[(string) $key] = $result;
            if (($result['level'] ?? '') === self::ALERT) {
                $alerts++;
            } elseif (($result['level'] ?? '') === self::NOTICE) {
                $notices++;
            }
        }

        return [
            'date' => is_numeric($data['date'] ?? null) ? (int) $data['date'] : 0,
            // New rules, other modules or another Dotclear version since the check
            'outdated' => $rules === null
                || ($data['rules'] ?? '')    !== self::signature($rules)
                || ($data['modules'] ?? '')  !== self::modulesSignature()
                || ($data['dotclear'] ?? '') !== App::config()->dotclearVersion(),
            'next'    => is_string($data['next'] ?? null) ? $data['next'] : '',
            'alerts'  => $alerts,
            'notices' => $notices,
            'results' => $results,
        ];
    }

    /**
     * Delete saved rules and result.
     */
    public static function clear(): void
    {
        foreach (['rules.json', 'audit.json'] as $file) {
            if (is_file(self::path($file))) {
                @unlink(self::path($file));
            }
        }
    }

    /**
     * Get a signature of rules.
     *
     * @param   array{date: int, dotclear: array<string, string>, rules: array<int, array<mixed>>}  $rules
     */
    private static function signature(array $rules): string
    {
        return md5((string) json_encode([$rules['dotclear'], $rules['rules']]));
    }

    /**
     * Get a signature of modules to check (ids and versions).
     */
    private static function modulesSignature(): string
    {
        $list = [];
        foreach (self::getModules() as $key => $module) {
            $list[] = $key . ':' . $module['version'];
        }

        return md5(implode(',', $list));
    }

    /**
     * Get the path of a file of the plugin in the var directory.
     */
    private static function path(string $file): string
    {
        return implode(DIRECTORY_SEPARATOR, [(string) Path::real(App::config()->varRoot()), My::id(), $file]);
    }

    /**
     * Read a JSON file of the var directory.
     *
     * @return  null|array<mixed>
     */
    private static function readFile(string $file): ?array
    {
        $path = self::path($file);
        $data = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;

        return is_array($data) ? $data : null;
    }

    /**
     * Write a JSON file in the var directory.
     *
     * @param   array<mixed>    $data
     */
    private static function writeFile(string $file, array $data): bool
    {
        try {
            $path = self::path($file);
            if (!is_dir(dirname($path))) {
                Files::makeDir(dirname($path), true);
            }

            return file_put_contents($path, (string) json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) !== false;
        } catch (Throwable) {
            return false;
        }
    }
}
