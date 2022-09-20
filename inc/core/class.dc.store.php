<?php
/**
 * @brief Repository modules XML feed reader
 *
 * Provides an object to parse XML feed of modules from repository.
 *
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 *
 * @since 2.6
 */
if (!defined('DC_RC_PATH')) {
    return;
}

class dcStore
{
    /**
     * dcModules instance
     *
     * @var    object
     */
    public $modules;

    /**
     * Modules fields to search on and their weight
     *
     * @var    array
     */
    public static $weighting = [
        'id'     => 10,
        'name'   => 8,
        'tags'   => 6,
        'desc'   => 4,
        'author' => 2,
    ];

    /**
     * User agent used to query repository
     *
     * @var    string
     */
    protected $user_agent = 'DotClear.org RepoBrowser/0.1';

    /**
     * XML feed URL
     *
     * @var    string
     */
    protected $xml_url;

    /**
     * Array of new/update modules from repository
     *
     * @var    array
     */
    protected $data = [];

    /**
     * Constructor.
     *
     * @param    dcModules $modules        dcModules instance
     * @param    string    $xml_url        XML feed URL
     * @param    bool      $force          Force query repository
     */
    public function __construct(dcModules $modules, string $xml_url, bool $force = false)
    {
        $this->modules    = $modules;
        $this->xml_url    = $xml_url;
        $this->user_agent = sprintf('Dotclear/%s)', DC_VERSION);

        $this->check($force);
    }

    /**
     * Check repository.
     *
     * @param    bool    $force        Force query repository
     *
     * @return    bool    True if get feed or cache
     */
    public function check(bool $force = false): bool
    {
        if (!$this->xml_url) {
            return false;
        }

        try {
            $parser = DC_STORE_NOT_UPDATE ? false : dcStoreReader::quickParse($this->xml_url, DC_TPL_CACHE, $force);
        } catch (Exception $e) {
            return false;
        }

        $raw_datas = !$parser ? [] : $parser->getModules();

        uasort($raw_datas, fn ($a, $b) => strtolower($a['id']) <=> strtolower($b['id']));

        $skipped = array_keys($this->modules->getDisabledModules());
        foreach ($skipped as $p_id) {
            if (isset($raw_datas[$p_id])) {
                unset($raw_datas[$p_id]);
            }
        }

        $updates = [];
        $current = $this->modules->getModules();
        foreach ($current as $p_id => $p_infos) {
            # non privileged user has no info
            if (!is_array($p_infos)) {
                continue;
            }
            # main repository
            if (isset($raw_datas[$p_id])) {
                if (dcUtils::versionsCompare($raw_datas[$p_id]['version'], $p_infos['version'], '>')) {
                    $updates[$p_id]                    = $raw_datas[$p_id];
                    $updates[$p_id]['root']            = $p_infos['root'];
                    $updates[$p_id]['root_writable']   = $p_infos['root_writable'];
                    $updates[$p_id]['current_version'] = $p_infos['version'];
                }
                unset($raw_datas[$p_id]);
            }
            # per module third-party repository
            if (!empty($p_infos['repository']) && DC_ALLOW_REPOSITORIES) {
                try {
                    $dcs_url    = substr($p_infos['repository'], -12, 12) == '/dcstore.xml' ? $p_infos['repository'] : http::concatURL($p_infos['repository'], 'dcstore.xml');
                    $dcs_parser = dcStoreReader::quickParse($dcs_url, DC_TPL_CACHE, $force);
                    if ($dcs_parser !== false) {
                        $dcs_raw_datas = $dcs_parser->getModules();
                        if (isset($dcs_raw_datas[$p_id]) && dcUtils::versionsCompare($dcs_raw_datas[$p_id]['version'], $p_infos['version'], '>')) {
                            if (!isset($updates[$p_id]) || dcUtils::versionsCompare($dcs_raw_datas[$p_id]['version'], $updates[$p_id]['version'], '>')) {
                                $dcs_raw_datas[$p_id]['repository'] = true;
                                $updates[$p_id]                     = $dcs_raw_datas[$p_id];
                                $updates[$p_id]['root']             = $p_infos['root'];
                                $updates[$p_id]['root_writable']    = $p_infos['root_writable'];
                                $updates[$p_id]['current_version']  = $p_infos['version'];
                            }
                        }
                    }
                } catch (Exception $e) {
                    // Ignore exceptions
                }
            }
        }

        $this->data = [
            'new'    => $raw_datas,
            'update' => $updates,
        ];

        return true;
    }

    /**
     * Get a list of modules.
     *
     * @param    bool    $update    True to get update modules, false for new ones
     *
     * @return    array    List of update/new modules
     */
    public function get(bool $update = false): array
    {
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
     * @param    string    $pattern    String to search
     *
     * @return    array    Match modules
     */
    public function search(string $pattern): array
    {
        $result = [];
        $sorter = [];

        # Split query into small clean words
        if (!($patterns = self::patternize($pattern))) {
            return $result;
        }

        # For each modules
        foreach ($this->data['new'] as $id => $module) {
            $module['id'] = $id;

            # Loop through required module fields
            foreach (self::$weighting as $field => $weight) {

                # Skip fields which not exsist on module
                if (empty($module[$field])) {
                    continue;
                }

                # Split field value into small clean word
                if (!($subjects = self::patternize($module[$field]))) {
                    continue;
                }

                # Check contents
                if (!($nb = preg_match_all('/(' . implode('|', $patterns) . ')/', implode(' ', $subjects), $_))) {
                    continue;
                }

                # Add module to result
                if (!isset($sorter[$id])) {
                    $sorter[$id] = 0;
                    $result[$id] = $module;
                }

                # Increment score by matches count * field weight
                $sorter[$id] += $nb * $weight;
                $result[$id]['score'] = $sorter[$id];
            }
        }
        # Sort response by matches count
        if (!empty($result)) {
            array_multisort($sorter, SORT_DESC, $result);
        }

        return $result;
    }

    /**
     * Quick download and install module.
     *
     * @param    string    $url    Module package URL
     * @param    string    $dest    Path to install module
     *
     * @return    int      dcModules::PACKAGE_INSTALLED (1), dcModules::PACKAGE_UPDATED (2)
     */
    public function process(string $url, string $dest): int
    {
        $this->download($url, $dest);

        return $this->install($dest);
    }

    /**
     * Download a module.
     *
     * @param    string    $url    Module package URL
     * @param    string    $dest    Path to put module package
     */
    public function download(string $url, string $dest): void
    {
        // Check and add default protocol if necessary
        if (!preg_match('%^https?:\/\/%', $url)) {
            $url = 'http://' . $url;
        }
        // Download package
        $path = '';
        if ($client = netHttp::initClient($url, $path)) {
            try {
                $client->setUserAgent($this->user_agent);
                $client->useGzip(false);
                $client->setPersistReferers(false);
                $client->setOutput($dest);
                $client->get($path);
                unset($client);
            } catch (Exception $e) {
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
     * @param    string    $path    Path to module package
     *
     * @return    int        1 = installed, 2 = update
     */
    public function install(string $path): int
    {
        return dcModules::installPackage($path, $this->modules);
    }

    /**
     * User Agent String.
     *
     * @param    string    $str        User agent string
     */
    public function agent(string $str)
    {
        $this->user_agent = $str;
    }

    /**
     * Split and clean pattern.
     *
     * @param    string    $str        String to sanitize
     *
     * @return    array|false    Array of cleaned pieces of string or false if none
     */
    private static function patternize(string $str)
    {
        $arr = [];

        foreach (explode(' ', $str) as $_) {
            $_ = strtolower(preg_replace('/[^A-Za-z0-9]/', '', $_));
            if (strlen($_) >= 2) {
                $arr[] = $_;
            }
        }

        return empty($arr) ? false : $arr;
    }
}
