<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Module;

use Dotclear\App;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Network\HttpClient;
use Dotclear\Interface\Module\ModulesInterface;
use Exception;

/**
 * @brief   Repository modules XML feed reader.
 *
 * Provides an object to parse XML feed of modules from repository.
 *
 * @since   2.6
 */
class Store
{
    /**
     * Modules fields to search on and their weight.
     *
     * @var     array<string,int>   $weighting
     */
    public static array $weighting = [
        'id'     => 10,
        'name'   => 8,
        'tags'   => 6,
        'desc'   => 4,
        'author' => 2,
    ];

    /**
     * User agent used to query repository.
     */
    protected string $user_agent = 'Dotclear.org RepoBrowser/0.1';

    /**
     * Array of new/update modules from repository.
     *
     * @var     array<string, array<string, array<string, mixed> > >   $data
     */
    protected array $data = [
        'new'    => [],
        'update' => [],
    ];

    /**
     * Array of new/update modules Define from repository.
     *
     * @var     array<string, array<int, ModuleDefine> >   $defines
     */
    protected array $defines = [
        'new'    => [],
        'update' => [],
    ];

    /**
     * Repositories new updates status.
     */
    private bool $has_new_update = false;

    /**
     * Constructor.
     *
     * @param   ModulesInterface    $modules            Modules instance
     * @param   string              $xml_url            XML feed URL
     * @param   null|bool           $force              Force query repository
     * @param   bool                $use_host_cache     Take care of hosts timeouts if any
     * @param   bool                $use_cache_only     Check for updates using cache only (core/3rd party)
     */
    public function __construct(
        public ModulesInterface $modules,
        protected ?string $xml_url,
        ?bool $force = false,
        bool $use_host_cache = true,
        bool $use_cache_only = true,
    ) {
        $this->user_agent = sprintf('Dotclear/%s)', App::config()->dotclearVersion());

        $this->check($force, $use_host_cache, $use_cache_only);
    }

    /**
     * Check repository.
     *
     * @param   bool    $force              Force query repository
     * @param   bool    $use_host_cache     Use host cache in StoreReader
     * @param   bool    $use_cache_only     Use only cache to check updates
     *
     * @return  bool    True if get feed or cache
     */
    public function check(?bool $force = false, bool $use_host_cache = true, bool $use_cache_only = true): bool
    {
        if (!$this->xml_url) {
            return false;
        }

        if ($force === true) {
            // Reset hosts cache
            StoreReader::resetHostsList();
        }

        try {
            $str_parser = App::config()->storeNotUpdate()
                ? false
                : StoreReader::quickParse($this->xml_url, App::config()->cacheRoot(), $force, $use_host_cache, $use_cache_only);
        } catch (Exception) {
            return false;
        }

        $new_defines  = [];
        $upd_defines  = [];
        $upd_versions = [];

        // check update/new from main repository
        if ($str_parser !== false) {
            foreach ($str_parser->getDefines() as $str_define) {
                // is installed ?
                $cur_define = $this->modules->getDefine($str_define->getId());
                if ($cur_define->isDefined()) {
                    if (is_string($str_define->get('version')) && is_string($cur_define->get('version')) && $this->modules->versionsCompare($str_define->get('version'), $cur_define->get('version'), '>')) {
                        // is update
                        $str_define->set('root', $cur_define->get('root'));
                        $str_define->set('root_writable', $cur_define->get('root_writable'));
                        $str_define->set('current_version', $cur_define->get('version'));

                        // set memo for third party updates
                        $upd_versions[$str_define->getId()] = [count($upd_defines), $str_define->get('version')];

                        $upd_defines[] = $str_define;

                        // This update is new from main repository
                        if (StoreReader::readCode() === StoreReader::READ_FROM_SOURCE) {
                            $this->has_new_update = true;
                        }
                    }
                } else {
                    // it's new
                    $new_defines[] = $str_define;
                }
            }
        }

        if (App::config()->allowRepositories()) {
            // check update from third party repositories

            $modules = $this->modules->getDefines();
            foreach ($modules as $cur_define) {
                if ($cur_define->get('repository') != '') {
                    try {
                        $repository = is_string($repository = $cur_define->get('repository')) ? $repository : '';
                        $str_url    = str_ends_with($repository, '/dcstore.xml')
                            ? $repository
                            : Http::concatURL($repository, 'dcstore.xml');

                        $str_parser = StoreReader::quickParse($str_url, App::config()->cacheRoot(), $force, $use_host_cache, $use_cache_only);
                        if ($str_parser === false) {
                            continue;
                        }

                        $cur_version = is_string($cur_version = $cur_define->get('version')) ? $cur_version : '0';

                        foreach ($str_parser->getDefines() as $str_define) {
                            if ($str_define->getId() === $cur_define->getId()) {
                                $str_version = is_string($str_version = $str_define->get('version')) ? $str_version : '0';
                                if ($this->modules->versionsCompare($str_version, $cur_version, '>')) {
                                    $str_define->set('repository', true);
                                    $str_define->set('root', $cur_define->get('root'));
                                    $str_define->set('root_writable', $cur_define->get('root_writable'));
                                    $str_define->set('current_version', $cur_version);

                                    // if no update from main repository, add third party update
                                    if (!isset($upd_versions[$str_define->getId()])) {
                                        $upd_defines[] = $str_define;
                                        // if update from third party repo is more recent than main repo, replace this last one
                                    } elseif ($this->modules->versionsCompare($str_version, $upd_versions[$str_define->getId()][1], '>')) {
                                        $upd_defines[$upd_versions[$str_define->getId()][0]] = $str_define;

                                        // This update is new from third party repository
                                        if (StoreReader::readCode() === StoreReader::READ_FROM_SOURCE) {
                                            $this->has_new_update = true;
                                        }
                                    }
                                }
                            }
                        }
                    } catch (Exception) {
                        // Ignore exceptions
                    }
                }
            }
        }

        // sort results by id
        uasort($new_defines, fn ($a, $b): int => strtolower($a->getId()) <=> strtolower($b->getId()));
        uasort($upd_defines, fn ($a, $b): int => strtolower($a->getId()) <=> strtolower($b->getId()));

        $this->defines = [
            'new'    => $new_defines,
            'update' => $upd_defines,
        ];

        // old style data
        foreach ($this->defines['new'] as $define) {
            $this->data['new'][$define->getId()] = $define->dump();
        }

        foreach ($this->defines['update'] as $define) {
            // keep only higher vesion
            $def_version = is_string($def_version = $define->get('version')) ? $def_version : '0';
            if (!isset($this->data['update'][$define->getId()])) {
                $this->data['update'][$define->getId()] = $define->dump();
            } else {
                $upd_version = is_string($upd_version = $this->data['update'][$define->getId()]['version']) ? $upd_version : '0';
                if ($this->modules->versionsCompare($def_version, $upd_version, '>')) {
                    $this->data['update'][$define->getId()] = $define->dump();
                }
            }
        }

        return true;
    }

    /**
     * Check if repositories have new updates.
     *
     * @return  bool    True on new updates
     */
    public function hasNewUdpates(): bool
    {
        return $this->has_new_update;
    }

    /**
     * Get a list of modules.
     *
     * @param   bool    $update     True to get update modules, false for new ones
     *
     * @return  array<int,ModuleDefine>   List of update/new modules defines
     */
    public function getDefines(bool $update = false): array
    {
        return $this->defines[$update ? 'update' : 'new'];
    }

    /**
     * Get a list of modules.
     *
     * @deprecated since 2.26, use self::getDefines() instead
     *
     * @param   bool    $update     True to get update modules, false for new ones
     *
     * @return  array<string, array<string, mixed>>   List of update/new modules
     */
    public function get(bool $update = false): array
    {
        App::deprecated()->set(self::class . '::getDefines()', '2.26');

        return $this->data[$update ? 'update' : 'new'];
    }

    /**
     * Search a module.
     *
     * Search string is cleaned, split and compare to split:
     * - module id and clean id,
     * - module name, clean name,
     * - module desccription.
     *
     * Every time a part of query is find on module,
     * result accuracy grow. Result is sorted by accuracy.
     *
     * @param   string  $pattern    String to search
     *
     * @return  array<int,ModuleDefine>     Match modules defines
     */
    public function searchDefines(string $pattern): array
    {
        # Split query into small clean words
        if (!($patterns = $this->patternize($pattern))) {
            return [];
        }

        # For each modules
        $defines = [];
        foreach ($this->defines['new'] as $define) {
            # Loop through required module fields
            foreach (self::$weighting as $field => $weight) {
                # Skip fields which not exsist on module
                if ($define->get($field) === null) {
                    continue;
                }

                # Split field value into small clean word
                $value = is_string($value = $define->get($field)) ? $value : '';
                if (!($subjects = $this->patternize($value))) {
                    continue;
                }

                # Check contents
                if (!($nb = preg_match_all('/(' . implode('|', $patterns) . ')/', implode(' ', $subjects), $_))) {
                    continue;
                }

                # Increment score by matches count * field weight
                $score = is_numeric($score = $define->get('score')) ? (int) $score : 0;
                $define->set('score', $score + $nb * $weight);
            }
            // return only scored modules
            if ($define->get('score')) {
                $defines[] = $define;
            }
        }
        # Sort response by matches count
        usort($defines, fn (ModuleDefine $a, ModuleDefine $b): int => is_numeric($score_b = $b->get('score')) && is_numeric($score_a = $a->get('score')) ? (int) $score_b <=> (int) $score_a : 0);

        return $defines;
    }

    /**
     * Search a module.
     *
     * @deprecated  since 2.26, use self::searchDefines() instead
     *
     * @param   string  $pattern    String to search
     *
     * @return  array<string, array<string, mixed>>   Match modules
     */
    public function search(string $pattern): array
    {
        App::deprecated()->set(self::class . '::searchDefines()', '2.26');

        $result = [];
        foreach ($this->searchDefines($pattern) as $define) {
            $result[$define->getId()] = $define->dump();
        }

        return $result;
    }

    /**
     * Quick download and install module.
     *
     * @param    string    $url    Module package URL
     * @param    string    $dest    Path to install module
     *
     * @return  int     Modules::PACKAGE_INSTALLED (1), Modules::PACKAGE_UPDATED (2)
     */
    public function process(string $url, string $dest): int
    {
        $this->download($url, $dest);

        return $this->install($dest);
    }

    /**
     * Download a module.
     *
     * @param   string  $url    Module package URL
     * @param   string  $dest   Path to put module package
     */
    public function download(string $url, string $dest): void
    {
        // Check and add default protocol if necessary
        if (!preg_match('%^https?:\/\/%', $url)) {
            $url = 'http://' . $url;
        }
        // Download package
        $path = '';
        if ($client = HttpClient::initClient($url, $path)) {
            try {
                $client->setUserAgent($this->user_agent);
                $client->useGzip(false);
                $client->setPersistReferers(false);
                $client->setOutput($dest);
                $client->get($path);
                unset($client);
            } catch (Exception) {
                unset($client);

                throw new Exception(__('An error occurred while downloading the file.'));
            }
        } else {
            throw new Exception(__('An error occurred while downloading the file.'));
        }
    }

    /**
     * Install a previously downloaded module.
     *
     * @param   string  $path   Path to module package
     *
     * @return  int     1 = installed, 2 = update
     */
    public function install(string $path): int
    {
        return $this->modules::installPackage($path, $this->modules);
    }

    /**
     * User Agent String.
     *
     * @param   string  $str    User agent string
     */
    public function agent(string $str): void
    {
        $this->user_agent = $str;
    }

    /**
     * Split and clean pattern.
     *
     * @param   string  $str    String to sanitize
     *
     * @return  array<int, string>|false     Array of cleaned pieces of string or false if none
     */
    private function patternize(string $str): false|array
    {
        $arr = [];

        foreach (explode(' ', $str) as $_) {
            $_ = strtolower((string) preg_replace('/[^A-Za-z0-9]/', '', $_));
            if (strlen($_) >= 2) {
                $arr[] = $_;
            }
        }

        return $arr === [] ? false : $arr;
    }
}
