<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\themeEditor;

use Dotclear\App;
use Dotclear\Core\Frontend\Utility;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Html\Form\Details;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Strong;
use Dotclear\Helper\Html\Form\Summary;
use Dotclear\Helper\Html\Form\Ul;
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
     * Customized theme.
     */
    protected string $custom_theme;

    /**
     * Current theme.
     */
    protected string $user_theme;

    /**
     * Parent theme if any.
     *
     * @var     string  $parent_theme
     */
    protected $parent_theme;

    /**
     * Theme template set.
     */
    protected string $tplset_theme;

    /**
     * Parent theme name if any.
     *
     * @var     string  $parent_name
     */
    protected $parent_name;

    /**
     * Theme template set name.
     */
    protected string $tplset_name;

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
        $this->custom_theme = App::config()->varRoot() . '/themes/' . App::blog()->id() . '/' . App::blog()->settings()->system->theme;

        // Create var hierarchy if necessary
        if (!is_dir(dirname($this->custom_theme))) {
            Files::makeDir($this->custom_theme, true);
        }

        $user_theme_path  = Path::real(App::blog()->themesPath() . '/' . App::blog()->settings()->system->theme);
        $this->user_theme = $user_theme_path !== false ? $user_theme_path : '';

        $this->tplset_theme = App::config()->dotclearRoot() . '/inc/public/' . Utility::TPL_ROOT . '/' . App::config()->defaultTplset();
        $this->tplset_name  = App::config()->defaultTplset();

        $parent_theme = App::themes()->moduleInfo(App::blog()->settings()->system->theme, 'parent');
        if ($parent_theme) {
            $parent_theme_path  = Path::real(App::blog()->themesPath() . '/' . $parent_theme);
            $this->parent_theme = $parent_theme_path !== false ? $parent_theme_path : '';
            $this->parent_name  = $parent_theme;
        }

        $tplset = App::themes()->moduleInfo(App::blog()->settings()->system->theme, 'tplset');
        if ($tplset) {
            $this->tplset_theme = App::config()->dotclearRoot() . '/inc/public/' . Utility::TPL_ROOT . '/' . $tplset;
            $this->tplset_name  = $tplset;
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
     */
    public function filesList(string $type, string  $item = '%1$s'): string
    {
        $files = $this->getFilesFromType($type);

        if ($files === []) {
            return (new Note())
                ->text(__('No file'))
            ->render();
        }

        $tpl_custom   = []; // Files from customized theme
        $tpl_theme    = []; // Files from current theme
        $tpl_parent   = []; // Files from parent of current theme
        $tpl_template = []; // Files from template set used by current theme
        foreach ($files as $k => $v) {
            if (str_starts_with($v, App::config()->varRoot())) {
                $tpl_custom[] = (new Li())
                    ->class('custom-file')
                    ->text(sprintf($item, $k, Html::escapeHTML($k)));
            } elseif (str_starts_with($v, $this->user_theme)) {
                $tpl_theme[] = (new Li())
                    ->class('default-file')
                    ->text(sprintf($item, $k, Html::escapeHTML($k)));
            } elseif ($this->parent_theme && str_starts_with($v, $this->parent_theme)) {
                $tpl_parent[] = (new Li())
                    ->class('parent-file')
                    ->text(sprintf($item, $k, Html::escapeHTML($k)));
            } else {
                $tpl_template[] = (new Li())
                    ->text(sprintf($item, $k, Html::escapeHTML($k)));
            }
        }

        $groups = [];
        if ($tpl_custom !== []) {
            $groups[] = (new Li())->class('group-file')
                ->text(__('Customized:'))
                ->items([
                    (new Ul())->items($tpl_custom),
                ]);
        }
        if ($tpl_theme !== []) {
            if ($tpl_custom === []) {
                $groups[] = (new Li())->class('group-file')
                    ->text(__('From theme:'))
                    ->items([
                        (new Ul())->items($tpl_theme),
                    ]);
            } else {
                $groups[] = (new Details())
                    ->summary(new Summary(__('From theme:')))
                    ->items([
                        (new Li())->class('group-file')->items([
                            (new Ul())->items($tpl_theme),
                        ]),
                    ]);
            }
        }
        if ($tpl_parent !== []) {
            $name     = (new Strong($this->parent_name))->render();
            $groups[] = (new Details())
                ->summary(new Summary(__('From parent:') . ' ' . $name))
                ->items([
                    (new Li())->class('group-file')->items([
                        (new Ul())->items($tpl_parent),
                    ]),
                ]);
        }
        if ($tpl_template !== []) {
            $name     = (new Strong($this->tplset_name))->render();
            $groups[] = (new Details())
                ->summary(new Summary(__('From template set:') . ' ' . $name))
                ->items([
                    (new Li())->class('group-file')->items([
                        (new Ul())->items($tpl_template),
                    ]),
                ]);
        }

        return (new Ul())
            ->items($groups)
        ->render();
    }

    /**
     * Gets the file content.
     *
     * @param   string  $type   The type
     * @param   string  $f      The file ID
     *
     * @throws  Exception
     *
     * @return  array<string, mixed>   The file content.
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

            // Create sub folders if necessary
            if (!is_dir(dirname($dest))) {
                Files::makeDir(dirname($dest), true);
            }

            $fp = @fopen($dest, 'wb');
            if (!$fp) {
                throw new Exception('tocatch');
            }

            $content = (string) preg_replace('/(\r?\n)/m', "\n", $content);
            $content = (string) preg_replace('/\r/m', "\n", $content);

            fwrite($fp, $content);
            fclose($fp);

            if ($type === 'po') {
                // Build PHP file from PO
                L10n::generatePhpFileFromPo(dirname($dest) . DIRECTORY_SEPARATOR . basename($dest, '.po'), $this->license_block());
            }

            // Updating inner files list
            $this->updateFileInList($type, $f, $dest);
        } catch (Exception) {
            throw new Exception(sprintf(__('Unable to write file %s. Please check your theme files and folders permissions.'), $f));
        }
    }

    /**
     * Check if a file is deletable (in var only).
     *
     * @param   string  $type   The type
     * @param   string  $f      The file ID
     */
    public function deletableFile(string $type, string $f): bool
    {
        $files = $this->getFilesFromType($type);
        if (isset($files[$f])) {
            $dest = $this->getDestinationFile($type, $f);
            if ($dest && (file_exists($dest) && is_writable($dest)) && str_starts_with($dest, App::config()->varRoot())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Delete a file (in var only).
     *
     * @param   string  $type   The type
     * @param   string  $f      The file ID
     *
     * @throws  Exception
     */
    public function deleteFile(string $type, string $f): void
    {
        $files = $this->getFilesFromType($type);
        if (!isset($files[$f])) {
            throw new Exception(__('File does not exist.'));
        }

        try {
            $dest = $this->getDestinationFile($type, $f);
            if ($dest !== false) {
                // File exists and may be deleted
                unlink($dest);

                if ($type === 'po') {
                    // Remove also PHP file
                    $compiled = dirname($dest) . DIRECTORY_SEPARATOR . basename($dest, '.po') . '.lang.php';
                    if (file_exists($compiled)) {
                        unlink($compiled);
                    }
                }

                // Cleanup empty folder
                if (Files::isDeletable(dirname($dest))) {
                    rmdir(dirname($dest));
                }

                // Updating template files list
                $this->findTemplates();
            }
        } catch (Exception) {
            throw new Exception(sprintf(__('Unable to delete file %s. Please check your theme files and folders permissions.'), $f));
        }
    }

    /**
     * Gets the destination file.
     *
     * @param   string  $type   The type
     * @param   string  $f      The file ID
     */
    protected function getDestinationFile(string $type, string $f): false|string
    {
        if ($type === 'tpl') {
            $dest = $this->custom_theme . '/tpl/' . $f;
        } elseif ($type === 'po') {
            $dest = $this->custom_theme . '/locales/' . $f;
        } else {
            $dest = $this->custom_theme . '/' . $f;
        }

        if (file_exists($dest) && is_writable($dest)) {
            return $dest;
        }

        if (is_writable(dirname($this->custom_theme))) {
            // Intermediate folders will be created if necessary
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
        $this->tpl = [
            ...$this->getFilesInDir($this->tplset_theme),
            ...$this->getFilesInDir($this->parent_theme . '/tpl'),
            ...$this->getFilesInDir($this->user_theme . '/tpl'),
            ...$this->getFilesInDir($this->custom_theme . '/tpl'),
        ];

        # Then we look in Utility::TPL_ROOT plugins directory
        foreach (App::plugins()->getDefines(['state' => ModuleDefine::STATE_ENABLED]) as $define) {
            // Looking in Utility::TPL_ROOT and Utility::TPL_ROOT/tplset directory
            $this->tpl = [
                ...$this->getFilesInDir($define->get('root') . '/' . Utility::TPL_ROOT . '/' . $this->tplset_name),
                ...$this->getFilesInDir($define->get('root') . '/' . Utility::TPL_ROOT),
                ...$this->tpl,
            ];
        }

        uksort($this->tpl, $this->sortFilesHelper(...));
    }

    /**
     * Get CSS files of theme.
     */
    protected function findStyles(): void
    {
        $this->css = [];

        if ($this->parent_theme) {
            $this->css = [
                ...$this->css,
                // Parent theme
                ...$this->getFilesInDir($this->parent_theme, 'css'),
                ...$this->getFilesInDir($this->parent_theme . '/style', 'css', 'style/'),
                ...$this->getFilesInDir($this->parent_theme . '/css', 'css', 'css/'),
            ];
        }
        $this->css = [
            ...$this->css,
            // Current theme
            ...$this->getFilesInDir($this->user_theme, 'css'),
            ...$this->getFilesInDir($this->user_theme . '/style', 'css', 'style/'),
            ...$this->getFilesInDir($this->user_theme . '/css', 'css', 'css/'),
            // Custom theme
            ...$this->getFilesInDir($this->custom_theme, 'css'),
            ...$this->getFilesInDir($this->custom_theme . '/style', 'css', 'style/'),
            ...$this->getFilesInDir($this->custom_theme . '/css', 'css', 'css/'),
        ];

        uksort($this->css, $this->sortFilesHelper(...));
    }

    /**
     * Get Javascript files of theme.
     */
    protected function findScripts(): void
    {
        $this->js = [];

        if ($this->parent_theme) {
            $this->js = [
                ...$this->js,
                // Parent theme
                ...$this->getFilesInDir($this->parent_theme, 'js'),
                ...$this->getFilesInDir($this->parent_theme . '/js', 'js', 'js/'),
            ];
        }
        $this->js = [
            ...$this->js,
            // Current theme
            ...$this->getFilesInDir($this->user_theme, 'js'),
            ...$this->getFilesInDir($this->user_theme . '/js', 'js', 'js/'),
            // Custom theme
            ...$this->getFilesInDir($this->custom_theme, 'js'),
            ...$this->getFilesInDir($this->custom_theme . '/js', 'js', 'js/'),
        ];

        uksort($this->js, $this->sortFilesHelper(...));
    }

    /**
     * Get translations files of theme.
     */
    protected function findLocales(): void
    {
        $this->po = [];

        $langs = L10n::getISOcodes(true, true);
        foreach ($langs as $v) {
            if ($this->parent_theme) {
                $this->po = [
                    ...$this->po,
                    // Parent theme
                    ...$this->getFilesInDir($this->parent_theme . '/locales/' . $v, 'po', $v . '/'),
                ];
            }
            $this->po = [
                ...$this->po,
                // Current theme
                ...$this->getFilesInDir($this->user_theme . '/locales/' . $v, 'po', $v . '/'),
                // Custom theme
                ...$this->getFilesInDir($this->custom_theme . '/locales/' . $v, 'po', $v . '/'),
            ];
        }

        uksort($this->po, $this->sortFilesHelper(...));
    }

    /**
     * Get PHP files of theme.
     */
    protected function findCodes(): void
    {
        $this->php = [];

        if ($this->parent_theme) {
            $this->php = [
                ...$this->php,
                // Parent theme
                ...$this->getFilesInDir($this->parent_theme, 'php'),
                ...$this->getFilesInDir($this->parent_theme . '/src', 'php', 'src/'),
            ];
        }

        $this->php = [
            ...$this->php,
            // Current theme
            ...$this->getFilesInDir($this->user_theme, 'php'),
            ...$this->getFilesInDir($this->user_theme . '/src', 'php', 'src/'),
            // Custom theme
            ...$this->getFilesInDir($this->custom_theme, 'php'),
            ...$this->getFilesInDir($this->custom_theme . '/src', 'php', 'src/'),
        ];

        uksort($this->php, $this->sortFilesHelper(...));
    }

    /**
     * Stack files sorting helper.
     *
     * Sort by file extension first, then by file name
     *
     * @param   string  $a  1st file
     * @param   string  $b  2nd file
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

        $res = [];

        $d = dir($dir);
        if ($d !== false) {
            while (($f = $d->read()) !== false) {
                /* @phpstan-ignore-next-line */
                if (is_file($dir . '/' . $f) && !preg_match('/^\./', $f) && (!$ext || preg_match('/\.' . preg_quote($ext) . '$/i', $f)) && (!$model || preg_match('/^' . preg_quote($model) . '$/i', $f))) {
                    $res[$prefix . $f] = $dir . '/' . $f;
                }
            }
        }

        return $res;
    }

    private function license_block(): string
    {
        // Tricky code to avoid xgettext bug on indented end heredoc identifier (see https://savannah.gnu.org/bugs/?62158)
        // Warning: don't use <<< if there is some __() l10n calls after as xgettext will not find them
        return <<<EOF
            /**
             * @package Dotclear
             *
             * @copyright Olivier Meunier & Association Dotclear
             * @copyright AGPL-3.0
             */
            EOF;
    }
}
