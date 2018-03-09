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

if (!defined('DC_RC_PATH')) {return;}

class dcThemeEditor
{
    protected $core;

    protected $user_theme;
    protected $parent_theme;
    protected $tplset_theme;

    protected $parent_name;
    protected $tplset_name;

    public $tpl = array();
    public $css = array();
    public $js  = array();
    public $po  = array();

    public function __construct($core)
    {
        $this->core          = &$core;
        $this->default_theme = path::real($this->core->blog->themes_path . '/default');
        $this->user_theme    = path::real($this->core->blog->themes_path . '/' . $this->core->blog->settings->system->theme);
        $this->tplset_theme  = DC_ROOT . '/inc/public/default-templates/' . DC_DEFAULT_TPLSET;
        $this->tplset_name   = DC_DEFAULT_TPLSET;
        if (null !== $this->core->themes) {
            $parent_theme = $this->core->themes->moduleInfo($this->core->blog->settings->system->theme, 'parent');
            if ($parent_theme) {
                $this->parent_theme = path::real($this->core->blog->themes_path . '/' . $parent_theme);
                $this->parent_name  = $parent_theme;
            }
            $tplset = $this->core->themes->moduleInfo($this->core->blog->settings->system->theme, 'tplset');
            if ($tplset) {
                $this->tplset_theme = DC_ROOT . '/inc/public/default-templates/' . $tplset;
                $this->tplset_name  = $tplset;
            }
        }
        $this->findTemplates();
        $this->findStyles();
        $this->findScripts();
        $this->findLocales();
    }

    public function filesList($type, $item = '%1$s', $split = true)
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
            $list .= ($list_parent != '' ? sprintf('<li class="group-file">' . __('From parent:') . ' %s<ul>%s</ul></li>',
                $this->parent_name, $list_parent) : '');
            $list .= ($list_tpl != '' ? sprintf('<li class="group-file">' . __('From template set:') . ' %s<ul>%s</ul></li>',
                $this->tplset_name, $list_tpl) : '');
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

    public function getFileContent($type, $f)
    {
        $files = $this->getFilesFromType($type);

        if (!isset($files[$f])) {
            throw new Exception(__('File does not exist.'));
        }

        $F = $files[$f];
        if (!is_readable($F)) {
            throw new Exception(sprintf(__('File %s is not readable'), $f));
        }

        return array(
            'c'    => file_get_contents($F),
            'w'    => $this->getDestinationFile($type, $f) !== false,
            'type' => $type,
            'f'    => $f
        );
    }

    public function writeFile($type, $f, $content)
    {
        $files = $this->getFilesFromType($type);

        if (!isset($files[$f])) {
            throw new Exception(__('File does not exist.'));
        }

        try
        {
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

            # Updating inner files list
            $this->updateFileInList($type, $f, $dest);
        } catch (Exception $e) {
            throw new Exception(sprintf(__('Unable to write file %s. Please check your theme files and folders permissions.'), $f));
        }
    }

    public function deletableFile($type, $f)
    {
        if ($type != 'tpl') {
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

    public function deleteFile($type, $f)
    {
        if ($type != 'tpl') {
            // Only tpl files may be deleted
            return;
        }

        $files = $this->getFilesFromType($type);
        if (!isset($files[$f])) {
            throw new Exception(__('File does not exist.'));
        }

        try
        {
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

    protected function getDestinationFile($type, $f)
    {
        if ($type == 'tpl') {
            $dest = $this->user_theme . '/tpl/' . $f;
        } elseif ($type == 'po') {
            $dest = $this->user_theme . '/locales/' . $f;
        } else {
            $dest = $this->user_theme . '/' . $f;
        }

        if (file_exists($dest) && is_writable($dest)) {
            return $dest;
        }

        if ($type == 'tpl' && !is_dir(dirname($dest))) {
            if (is_writable($this->user_theme)) {
                return $dest;
            }
        }

        if ($type == 'po' && !is_dir(dirname($dest))) {
            if (is_writable($this->user_theme)) {
                return $dest;
            }
        }

        if (is_writable(dirname($dest))) {
            return $dest;
        }

        return false;
    }

    protected function getFilesFromType($type)
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
            default:
                return array();
        }
    }

    protected function updateFileInList($type, $f, $file)
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
            default:
                return;
        }

        $list[$f] = $file;
    }

    protected function findTemplates()
    {
        $this->tpl = array_merge(
            $this->getFilesInDir($this->tplset_theme),
            $this->getFilesInDir($this->parent_theme . '/tpl')
        );
        $this->tpl_model = $this->tpl;

        $this->tpl = array_merge($this->tpl, $this->getFilesInDir($this->user_theme . '/tpl'));

        # Then we look in 'default-templates' plugins directory
        $plugins = $this->core->plugins->getModules();
        foreach ($plugins as $p) {
            // Looking in default-templates directory
            $this->tpl       = array_merge($this->getFilesInDir($p['root'] . '/default-templates'), $this->tpl);
            $this->tpl_model = array_merge($this->getFilesInDir($p['root'] . '/default-templates'), $this->tpl_model);
            // Looking in default-templates/tplset directory
            $this->tpl       = array_merge($this->getFilesInDir($p['root'] . '/default-templates/' . $this->tplset_name), $this->tpl);
            $this->tpl_model = array_merge($this->getFilesInDir($p['root'] . '/default-templates/' . $this->tplset_name), $this->tpl_model);
        }

        uksort($this->tpl, array($this, 'sortFilesHelper'));
    }

    protected function findStyles()
    {
        $this->css = $this->getFilesInDir($this->user_theme, 'css');
        $this->css = array_merge($this->css, $this->getFilesInDir($this->user_theme . '/style', 'css', 'style/'));
        $this->css = array_merge($this->css, $this->getFilesInDir($this->user_theme . '/css', 'css', 'css/'));
    }

    protected function findScripts()
    {
        $this->js = $this->getFilesInDir($this->user_theme, 'js');
        $this->js = array_merge($this->js, $this->getFilesInDir($this->user_theme . '/js', 'js', 'js/'));
    }

    protected function findLocales()
    {
        $langs = l10n::getISOcodes(1, 1);
        foreach ($langs as $k => $v) {
            if ($this->parent_theme) {
                $this->po = array_merge($this->po, $this->getFilesInDir($this->parent_theme . '/locales/' . $v, 'po', $v . '/'));
            }
            $this->po = array_merge($this->po, $this->getFilesInDir($this->user_theme . '/locales/' . $v, 'po', $v . '/'));
        }
    }

    protected function getFilesInDir($dir, $ext = null, $prefix = '', $model = null)
    {
        $dir = path::real($dir);
        if (!$dir || !is_dir($dir) || !is_readable($dir)) {
            return array();
        }

        $d   = dir($dir);
        $res = array();
        while (($f = $d->read()) !== false) {
            if (is_file($dir . '/' . $f) && !preg_match('/^\./', $f) && (!$ext || preg_match('/\.' . preg_quote($ext) . '$/i', $f))) {
                if (!$model || preg_match('/^' . preg_quote($model) . '$/i', $f)) {
                    $res[$prefix . $f] = $dir . '/' . $f;
                }
            }
        }

        return $res;
    }

    protected function sortFilesHelper($a, $b)
    {
        if ($a == $b) {
            return 0;
        }

        $ext_a = files::getExtension($a);
        $ext_b = files::getExtension($b);

        return strcmp($ext_a . '.' . $a, $ext_b . '.' . $b);
    }
}
