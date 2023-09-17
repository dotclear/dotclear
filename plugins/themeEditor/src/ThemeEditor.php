<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\themeEditor;

use Dotclear\App;
use Dotclear\Core\Frontend\Utility;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\L10n;
use Dotclear\Module\ModuleDefine;
use Exception;

/**
 * @brief   The theme editor handler.
 * @ingroup themeEditor
 */
class ThemeEditor
{
    /**
     * Current theme.
     *
     * @var     string  $user_theme
     */
    protected $user_theme;

    /**
     * Parent theme if any.
     *
     * @var     string  $parent_theme
     */
    protected $parent_theme;

    /**
     * Theme template set.
     *
     * @var     string  $tplset_theme
     */
    protected $tplset_theme;

    /**
     * Parent theme name if any.
     *
     * @var     string  $parent_name
     */
    protected $parent_name;

    /**
     * Theme template set name.
     *
     * @var     string  $tplset_name
     */
    protected $tplset_name;

    /**
     * List of file from parent theme if any and from theme template set.
     *
     * @var     array<string,string>    $tpl_model
     */
    protected $tpl_model = [];

    /**
     * List of theme template files.
     *
     * @var     array<string,string>    $tpl
     */
    public $tpl = [];

    /**
     * List of theme CSS files.
     *
     * @var     array<string,string>    $css
     */
    public $css = [];

    /**
     * List of theme JS files.
     *
     * @var     array<string,string>    $js
     */
    public $js = [];

    /**
     * List of theme translation files.
     *
     * @var     array<string,string>    $po
     */
    public $po = [];

    /**
     * List of theme PHP files.
     *
     * @var     array<string,string>    $php
     */
    public $php = [];

    /**
     * Constructs a new instance.
     */
    public function __construct()
    {
        $this->user_theme   = Path::real(App::blog()->themesPath() . '/' . App::blog()->settings()->system->theme);
        $this->tplset_theme = App::config()->dotclearRoot() . '/inc/public/' . Utility::TPL_ROOT . '/' . App::config()->defaultTplset();
        $this->tplset_name  = App::config()->defaultTplset();
        if (null !== App::themes()) {
            $parent_theme = App::themes()->moduleInfo(App::blog()->settings()->system->theme, 'parent');
            if ($parent_theme) {
                $this->parent_theme = Path::real(App::blog()->themesPath() . '/' . $parent_theme);
                $this->parent_name  = $parent_theme;
            }
            $tplset = App::themes()->moduleInfo(App::blog()->settings()->system->theme, 'tplset');
            if ($tplset) {
                $this->tplset_theme = App::config()->dotclearRoot() . '/inc/public/' . Utility::TPL_ROOT . '/' . $tplset;
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
     * Display a file list.
     *
     * @param   string  $type   The type of file
     * @param   string  $item   The item pattern
     * @param   bool    $split  Split from source?
     *
     * @return  string
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
                if (str_starts_with($v, $this->user_theme)) {
                    $li = sprintf('<li class="default-file">%s</li>', $item);
                    $list_theme .= sprintf($li, $k, Html::escapeHTML($k));
                } elseif ($this->parent_theme && str_starts_with($v, $this->parent_theme)) {
                    $li = sprintf('<li class="parent-file">%s</li>', $item);
                    $list_parent .= sprintf($li, $k, Html::escapeHTML($k));
                } else {
                    $li = sprintf('<li>%s</li>', $item);
                    $list_tpl .= sprintf($li, $k, Html::escapeHTML($k));
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
                if (str_starts_with($v, $this->user_theme)) {
                    $li = sprintf('<li class="default-file">%s</li>', $item);
                } elseif ($this->parent_theme && str_starts_with($v, $this->parent_theme)) {
                    $li = sprintf('<li class="parent-file">%s</li>', $item);
                } else {
                    $li = sprintf('<li>%s</li>', $item);
                }
                $list .= sprintf($li, $k, Html::escapeHTML($k));
            }
        }

        return sprintf('<ul>%s</ul>', $list);
    }

    /**
     * Gets the file content.
     *
     * @param   string  $type   The type
     * @param   string  $f      The file ID
     *
     * @throws  Exception
     *
     * @return  array   The file content.
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
     * @param   string  $type       The type
     * @param   string  $f          The file ID
     * @param   string  $content    The content
     *
     * @throws  Exception
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

            if ($type === 'tpl' && !is_dir(dirname($dest))) {
                Files::makeDir(dirname($dest));
            }

            if ($type === 'po' && !is_dir(dirname($dest))) {
                Files::makeDir(dirname($dest));
            }

            $fp = @fopen($dest, 'wb');
            if (!$fp) {
                throw new Exception('tocatch');
            }

            $content = preg_replace('/(\r?\n)/m', "\n", $content);
            $content = preg_replace('/\r/m', "\n", $content);

            fwrite($fp, $content);
            fclose($fp);

            if ($type === 'po') {
                // Build PHP file from PO
                L10n::generatePhpFileFromPo(dirname($dest) . '/' . basename($dest, '.po'), self::license_block());
            }

            // Updating inner files list
            $this->updateFileInList($type, $f, $dest);
        } catch (Exception $e) {
            throw new Exception(sprintf(__('Unable to write file %s. Please check your theme files and folders permissions.'), $f));
        }
    }

    /**
     * Check if a file is deletable.
     *
     * @param   string  $type   The type
     * @param   string  $f      The file ID
     *
     * @return  bool
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
     * Delete a file.
     *
     * @param   string  $type   The type
     * @param   string  $f      The file ID
     *
     * @throws  Exception
     */
    public function deleteFile(string $type, string $f)
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
     * @param   string  $type   The type
     * @param   string  $f      The file ID
     *
     * @return  bool|string     The destination file.
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
     * @param   string  $type   The type
     *
     * @return  array<string,string>    The list.
     */
    protected function getFilesFromType(string $type): array
    {
        return match ($type) {
            'tpl'   => $this->tpl,
            'css'   => $this->css,
            'js'    => $this->js,
            'po'    => $this->po,
            'php'   => $this->php,
            default => [],
        };
    }

    /**
     * Update a file in a list.
     *
     * @param   string  $type   The type
     * @param   string  $f      The file ID
     * @param   string  $file   The file
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

        # Then we look in Utility::TPL_ROOT plugins directory
        foreach (App::plugins()->getDefines(['state' => ModuleDefine::STATE_ENABLED]) as $define) {
            // Looking in Utility::TPL_ROOT directory
            $this->tpl       = array_merge($this->getFilesInDir($define->get('root') . '/' . Utility::TPL_ROOT), $this->tpl);
            $this->tpl_model = array_merge($this->getFilesInDir($define->get('root') . '/' . Utility::TPL_ROOT), $this->tpl_model);
            // Looking in Utility::TPL_ROOT/tplset directory
            $this->tpl       = array_merge($this->getFilesInDir($define->get('root') . '/' . Utility::TPL_ROOT . '/' . $this->tplset_name), $this->tpl);
            $this->tpl_model = array_merge($this->getFilesInDir($define->get('root') . '/' . Utility::TPL_ROOT . '/' . $this->tplset_name), $this->tpl_model);
        }

        uksort($this->tpl, $this->sortFilesHelper(...));
    }

    /**
     * Get CSS files of theme.
     */
    protected function findStyles(): void
    {
        $this->css = $this->getFilesInDir($this->user_theme, 'css');
        $this->css = array_merge($this->css, $this->getFilesInDir($this->user_theme . '/style', 'css', 'style/'));
        $this->css = array_merge($this->css, $this->getFilesInDir($this->user_theme . '/css', 'css', 'css/'));

        uksort($this->css, $this->sortFilesHelper(...));
    }

    /**
     * Get Javascript files of theme.
     */
    protected function findScripts(): void
    {
        $this->js = $this->getFilesInDir($this->user_theme, 'js');
        $this->js = array_merge($this->js, $this->getFilesInDir($this->user_theme . '/js', 'js', 'js/'));

        uksort($this->js, $this->sortFilesHelper(...));
    }

    /**
     * Get translations files of theme.
     */
    protected function findLocales(): void
    {
        $langs = L10n::getISOcodes(true, true);
        foreach ($langs as $v) {
            if ($this->parent_theme) {
                $this->po = array_merge($this->po, $this->getFilesInDir($this->parent_theme . '/locales/' . $v, 'po', $v . '/'));
            }
            $this->po = array_merge($this->po, $this->getFilesInDir($this->user_theme . '/locales/' . $v, 'po', $v . '/'));
        }

        uksort($this->po, $this->sortFilesHelper(...));
    }

    /**
     * Get PHP files of theme.
     */
    protected function findCodes(): void
    {
        $this->php = $this->getFilesInDir($this->user_theme, 'php');

        uksort($this->php, $this->sortFilesHelper(...));
    }

    /**
     * Stack files sorting helper.
     *
     * Sort by file extension first, then by file name
     *
     * @param   string  $a  1st file
     * @param   string  $b  2nd file
     *
     * @return  int
     */
    protected function sortFilesHelper(string $a, string $b): int
    {
        if ($a === $b) {
            return 0;
        }

        return strcmp(Files::getExtension($a) . '.' . $a, Files::getExtension($b) . '.' . $b);
    }

    /**
     * Gets the files in dir.
     *
     * @param   string          $dir        The dir
     * @param   null|string     $ext        The search extension
     * @param   string          $prefix     The key prefix
     * @param   null|string     $model      The model
     *
     * @return  array<string,string>    The files in dir.
     */
    protected function getFilesInDir(string $dir, ?string $ext = null, string $prefix = '', ?string $model = null): array
    {
        $dir = Path::real($dir);
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

    private static function license_block(): string
    {
        // Tricky code to avoid xgettext bug on indented end heredoc identifier (see https://savannah.gnu.org/bugs/?62158)
        // Warning: don't use <<< if there is some __() l10n calls after as xgettext will not find them
        return <<<EOF
            /**
             * @package Dotclear
             *
             * @copyright Olivier Meunier & Association Dotclear
             * @copyright GPL-2.0-only
             */
            EOF;
    }
}
