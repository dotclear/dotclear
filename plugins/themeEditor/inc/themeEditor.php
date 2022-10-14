<?php
/**
 * @brief themeEditor, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class dcThemeEditor
{
    /**
     * Current theme
     *
     * @var string
     */
    protected $user_theme;

    /**
     * Parent theme if any
     *
     * @var string
     */
    protected $parent_theme;

    /**
     * Theme template set
     *
     * @var string
     */
    protected $tplset_theme;

    /**
     * Parent theme name if any
     *
     * @var string
     */
    protected $parent_name;

    /**
     * Theme template set name
     *
     * @var string
     */
    protected $tplset_name;

    /**
     * List of file from parent theme if any and from theme template set
     *
     * @var        array
     */
    protected $tpl_model = [];

    /**
     * List of theme template files
     *
     * @var        array
     */
    public $tpl = [];

    /**
     * List of theme CSS files
     *
     * @var        array
     */
    public $css = [];

    /**
     * List of theme JS files
     *
     * @var        array
     */
    public $js = [];

    /**
     * List of theme translation files
     *
     * @var        array
     */
    public $po = [];

    /**
     * List of theme PHP files
     *
     * @var        array
     */
    public $php = [];

    /**
     * Constructs a new instance.
     */
    public function __construct()
    {
        $this->user_theme   = path::real(dcCore::app()->blog->themes_path . '/' . dcCore::app()->blog->settings->system->theme);
        $this->tplset_theme = DC_ROOT . '/inc/public/' . dcPublic::TPL_ROOT . '/' . DC_DEFAULT_TPLSET;
        $this->tplset_name  = DC_DEFAULT_TPLSET;
        if (null !== dcCore::app()->themes) {
            $parent_theme = dcCore::app()->themes->moduleInfo(dcCore::app()->blog->settings->system->theme, 'parent');
            if ($parent_theme) {
                $this->parent_theme = path::real(dcCore::app()->blog->themes_path . '/' . $parent_theme);
                $this->parent_name  = $parent_theme;
            }
            $tplset = dcCore::app()->themes->moduleInfo(dcCore::app()->blog->settings->system->theme, 'tplset');
            if ($tplset) {
                $this->tplset_theme = DC_ROOT . '/inc/public/' . dcPublic::TPL_ROOT . '/' . $tplset;
                $this->tplset_name  = $tplset;
            }
        }
        $this->findTemplates();
        $this->findStyles();
        $this->findScripts();
        $this->findLocales();
        $this->findCodes();
    }

    /**
     * Display a file list
     *
     * @param      string  $type   The type of file
     * @param      string  $item   The item pattern
     * @param      bool    $split  Split from source?
     *
     * @return     string
     */
    public function filesList(string $type, string  $item = '%1$s', bool $split = true): string
    {
        $files = $this->getFilesFromType($type);

        if (empty($files)) {
            return '<p>' . __('No file') . '</p>';
        }

        $list = '';
        if ($split) {
            $list_theme  = ''; // Files from current theme
            $list_parent = ''; // Files from parent of current theme
            $list_tpl    = ''; // Files from template set used by current theme
            foreach ($files as $k => $v) {
                if (strpos($v, $this->user_theme) === 0) {
                    $li = sprintf('<li class="default-file">%s</li>', $item);
                    $list_theme .= sprintf($li, $k, html::escapeHTML($k));
                } elseif ($this->parent_theme && strpos($v, $this->parent_theme) === 0) {
                    $li = sprintf('<li class="parent-file">%s</li>', $item);
                    $list_parent .= sprintf($li, $k, html::escapeHTML($k));
                } else {
                    $li = sprintf('<li>%s</li>', $item);
                    $list_tpl .= sprintf($li, $k, html::escapeHTML($k));
                }
            }
            $list .= ($list_theme != '' ? sprintf('<li class="group-file">' . __('From theme:') . '<ul>%s</ul></li>', $list_theme) : '');
            $list .= ($list_parent != '' ? sprintf(
                '<li class="group-file">' . __('From parent:') . ' %s<ul>%s</ul></li>',
                $this->parent_name,
                $list_parent
            ) : '');
            $list .= ($list_tpl != '' ? sprintf(
                '<li class="group-file">' . __('From template set:') . ' %s<ul>%s</ul></li>',
                $this->tplset_name,
                $list_tpl
            ) : '');
        } else {
            foreach ($files as $k => $v) {
                if (strpos($v, $this->user_theme) === 0) {
                    $li = sprintf('<li class="default-file">%s</li>', $item);
                } elseif ($this->parent_theme && strpos($v, $this->parent_theme) === 0) {
                    $li = sprintf('<li class="parent-file">%s</li>', $item);
                } else {
                    $li = sprintf('<li>%s</li>', $item);
                }
                $list .= sprintf($li, $k, html::escapeHTML($k));
            }
        }

        return sprintf('<ul>%s</ul>', $list);
    }

    /**
     * Gets the file content.
     *
     * @param      string     $type   The type
     * @param      string     $f      The file ID
     *
     * @throws     Exception
     *
     * @return     array      The file content.
     */
    public function getFileContent(string $type, string $f): array
    {
        $files = $this->getFilesFromType($type);

        if (!isset($files[$f])) {
            throw new Exception(__('File does not exist.'));
        }

        $F = $files[$f];
        if (!is_readable($F)) {
            throw new Exception(sprintf(__('File %s is not readable'), $f));
        }

        return [
            'c'    => file_get_contents($F),
            'w'    => $this->getDestinationFile($type, $f) !== false,
            'type' => $type,
            'f'    => $f,
        ];
    }

    /**
     * Writes a file.
     *
     * @param      string     $type     The type
     * @param      string     $f        The file ID
     * @param      string     $content  The content
     *
     * @throws     Exception
     */
    public function writeFile(string $type, string $f, string $content): void
    {
        $files = $this->getFilesFromType($type);

        if (!isset($files[$f])) {
            throw new Exception(__('File does not exist.'));
        }

        try {
            $dest = $this->getDestinationFile($type, $f);

            if ($dest == false) {
                throw new Exception();
            }

            if ($type == 'tpl' && !is_dir(dirname($dest))) {
                files::makeDir(dirname($dest));
            }

            if ($type == 'po' && !is_dir(dirname($dest))) {
                files::makeDir(dirname($dest));
            }

            $fp = @fopen($dest, 'wb');
            if (!$fp) {
                throw new Exception('tocatch');
            }

            $content = preg_replace('/(\r?\n)/m', "\n", $content);
            $content = preg_replace('/\r/m', "\n", $content);

            fwrite($fp, $content);
            fclose($fp);

            // Updating inner files list
            $this->updateFileInList($type, $f, $dest);
        } catch (Exception $e) {
            throw new Exception(sprintf(__('Unable to write file %s. Please check your theme files and folders permissions.'), $f));
        }
    }

    /**
     * Check if a file is deletable
     *
     * @param      string  $type   The type
     * @param      string  $f      The file ID
     *
     * @return     bool
     */
    public function deletableFile(string $type, string $f): bool
    {
        if ($type !== 'tpl') {
            // Only tpl files may be deleted
            return false;
        }

        $files = $this->getFilesFromType($type);
        if (isset($files[$f])) {
            $dest = $this->getDestinationFile($type, $f);
            if ($dest) {
                if (file_exists($dest) && is_writable($dest)) {
                    // Is there a model (parent theme or template set) ?
                    if (isset($this->tpl_model[$f])) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Delete a file
     *
     * @param      string     $type   The type
     * @param      string     $f      The file ID
     *
     * @throws     Exception
     */
    public function deleteFile($type, $f)
    {
        if ($type !== 'tpl') {
            // Only tpl files may be deleted
            return;
        }

        $files = $this->getFilesFromType($type);
        if (!isset($files[$f])) {
            throw new Exception(__('File does not exist.'));
        }

        try {
            $dest = $this->getDestinationFile($type, $f);
            if ($dest) {
                // File exists and may be deleted
                unlink($dest);

                // Updating template files list
                $this->findTemplates();
            }
        } catch (Exception $e) {
            throw new Exception(sprintf(__('Unable to delete file %s. Please check your theme files and folders permissions.'), $f));
        }
    }

    /**
     * Gets the destination file.
     *
     * @param      string  $type   The type
     * @param      string  $f      The file ID
     *
     * @return     bool|string    The destination file.
     */
    protected function getDestinationFile(string $type, string $f)
    {
        if ($type === 'tpl') {
            $dest = $this->user_theme . '/tpl/' . $f;
        } elseif ($type === 'po') {
            $dest = $this->user_theme . '/locales/' . $f;
        } else {
            $dest = $this->user_theme . '/' . $f;
        }

        if (file_exists($dest) && is_writable($dest)) {
            return $dest;
        }

        if ($type === 'tpl' && !is_dir(dirname($dest))) {
            if (is_writable($this->user_theme)) {
                return $dest;
            }
        }

        if ($type === 'po' && !is_dir(dirname($dest))) {
            if (is_writable($this->user_theme)) {
                return $dest;
            }
        }

        if (is_writable(dirname($dest))) {
            return $dest;
        }

        return false;
    }

    /**
     * Gets the files list from type.
     *
     * @param      string  $type   The type
     *
     * @return     array   The list.
     */
    protected function getFilesFromType(string $type): array
    {
        switch ($type) {
            case 'tpl':
                return $this->tpl;
            case 'css':
                return $this->css;
            case 'js':
                return $this->js;
            case 'po':
                return $this->po;
            case 'php':
                return $this->php;
            default:
                return [];
        }
    }

    /**
     * Update a file in a list
     *
     * @param      string  $type   The type
     * @param      string  $f      The file ID
     * @param      string  $file   The file
     */
    protected function updateFileInList(string $type, string $f, string $file): void
    {
        switch ($type) {
            case 'tpl':
                $list = &$this->tpl;

                break;
            case 'css':
                $list = &$this->css;

                break;
            case 'js':
                $list = &$this->js;

                break;
            case 'po':
                $list = &$this->po;

                break;
            case 'php':
                $list = &$this->php;

                break;
            default:
                return;
        }

        $list[$f] = $file;
    }

    /**
     * Get template files of theme.
     */
    protected function findTemplates(): void
    {
        $this->tpl = array_merge(
            $this->getFilesInDir($this->tplset_theme),
            $this->getFilesInDir($this->parent_theme . '/tpl')
        );
        $this->tpl_model = $this->tpl;

        $this->tpl = array_merge($this->tpl, $this->getFilesInDir($this->user_theme . '/tpl'));

        # Then we look in dcPublic::TPL_ROOT plugins directory
        $plugins = dcCore::app()->plugins->getModules();
        foreach ($plugins as $p) {
            // Looking in dcPublic::TPL_ROOT directory
            $this->tpl       = array_merge($this->getFilesInDir($p['root'] . '/' . dcPublic::TPL_ROOT), $this->tpl);
            $this->tpl_model = array_merge($this->getFilesInDir($p['root'] . '/' . dcPublic::TPL_ROOT), $this->tpl_model);
            // Looking in dcPublic::TPL_ROOT/tplset directory
            $this->tpl       = array_merge($this->getFilesInDir($p['root'] . '/' . dcPublic::TPL_ROOT . '/' . $this->tplset_name), $this->tpl);
            $this->tpl_model = array_merge($this->getFilesInDir($p['root'] . '/' . dcPublic::TPL_ROOT . '/' . $this->tplset_name), $this->tpl_model);
        }

        uksort($this->tpl, [$this, 'sortFilesHelper']);
    }

    /**
     * Get CSS files of theme.
     */
    protected function findStyles(): void
    {
        $this->css = $this->getFilesInDir($this->user_theme, 'css');
        $this->css = array_merge($this->css, $this->getFilesInDir($this->user_theme . '/style', 'css', 'style/'));
        $this->css = array_merge($this->css, $this->getFilesInDir($this->user_theme . '/css', 'css', 'css/'));

        uksort($this->css, [$this, 'sortFilesHelper']);
    }

    /**
     * Get Javascript files of theme.
     */
    protected function findScripts(): void
    {
        $this->js = $this->getFilesInDir($this->user_theme, 'js');
        $this->js = array_merge($this->js, $this->getFilesInDir($this->user_theme . '/js', 'js', 'js/'));

        uksort($this->js, [$this, 'sortFilesHelper']);
    }

    /**
     * Get translations files of theme.
     */
    protected function findLocales(): void
    {
        $langs = l10n::getISOcodes(true, true);
        foreach ($langs as $v) {
            if ($this->parent_theme) {
                $this->po = array_merge($this->po, $this->getFilesInDir($this->parent_theme . '/locales/' . $v, 'po', $v . '/'));
            }
            $this->po = array_merge($this->po, $this->getFilesInDir($this->user_theme . '/locales/' . $v, 'po', $v . '/'));
        }

        uksort($this->po, [$this, 'sortFilesHelper']);
    }

    /**
     * Get PHP files of theme.
     */
    protected function findCodes(): void
    {
        $this->php = $this->getFilesInDir($this->user_theme, 'php');

        uksort($this->php, [$this, 'sortFilesHelper']);
    }

    /**
     * Stack files sorting helper
     *
     *  Sort by file extension first, then by file name
     *
     * @param      string  $a      1st file
     * @param      string  $b      2nd file
     *
     * @return     int
     */
    protected function sortFilesHelper(string $a, string $b): int
    {
        if ($a === $b) {
            return 0;
        }

        return strcmp(files::getExtension($a) . '.' . $a, files::getExtension($b) . '.' . $b);
    }

    /**
     * Gets the files in dir.
     *
     * @param      string       $dir     The dir
     * @param      null|string  $ext     The search extension
     * @param      string       $prefix  The key prefix
     * @param      null|string  $model   The model
     *
     * @return     array        The files in dir.
     */
    protected function getFilesInDir(string $dir, ?string $ext = null, string $prefix = '', ?string $model = null): array
    {
        $dir = path::real($dir);
        if (!$dir || !is_dir($dir) || !is_readable($dir)) {
            return [];
        }

        $d   = dir($dir);
        $res = [];
        while (($f = $d->read()) !== false) {
            if (is_file($dir . '/' . $f) && !preg_match('/^\./', $f) && (!$ext || preg_match('/\.' . preg_quote($ext) . '$/i', $f))) {
                if (!$model || preg_match('/^' . preg_quote($model) . '$/i', $f)) {
                    $res[$prefix . $f] = $dir . '/' . $f;
                }
            }
        }

        return $res;
    }
}
