<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\importExport;

use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\App;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\File\Zip\Unzip;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\File;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Password;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Exception;

/**
 * @brief   The import flat module handler.
 * @ingroup importExport
 */
class ModuleImportFlat extends Module
{
    /**
     * Current import type (full|single).
     *
     * @var     string  $status
     */
    protected $status = '';

    public function setInfo(): void
    {
        $this->type        = 'import';
        $this->name        = __('Flat file import');
        $this->description = __('Imports a blog or a full Dotclear installation from flat file.');
    }

    public function process(string $do): void
    {
        if ($do === 'single' || $do === 'full') {
            $this->status = $do;

            return;
        }

        $to_unlink = false;

        # Single blog import
        $files      = $this->getPublicFiles();
        $single_upl = null;
        if (!empty($_POST['public_single_file']) && in_array($_POST['public_single_file'], $files)) {
            $single_upl = false;
        } elseif (!empty($_FILES['up_single_file'])) {
            $single_upl = true;
        }

        if ($single_upl !== null) {
            if ($single_upl) {
                Files::uploadStatus($_FILES['up_single_file']);
                $file = App::config()->cacheRoot() . '/' . md5(uniqid());
                if (!move_uploaded_file($_FILES['up_single_file']['tmp_name'], $file)) {
                    throw new Exception(__('Unable to move uploaded file.'));
                }
                $to_unlink = true;
            } else {
                $file = $_POST['public_single_file'];
            }

            $unzip_file = '';

            try {
                # Try to unzip file
                $unzip_file = $this->unzip($file);
                if (false !== $unzip_file) {
                    $bk = new FlatImportV2($unzip_file);
                }
                # Else this is a normal file
                else {
                    $bk = new FlatImportV2($file);
                }

                $bk->importSingle();
            } catch (Exception $e) {
                if (false !== $unzip_file) {
                    @unlink($unzip_file);
                }
                if ($to_unlink) {
                    @unlink($file);
                }

                throw $e;
            }
            if ($unzip_file) {
                @unlink($unzip_file);
            }
            if ($to_unlink) {
                @unlink($file);
            }
            Http::redirect($this->getURL() . '&do=single');
        }

        # Full import
        $full_upl = null;
        if (!empty($_POST['public_full_file']) && in_array($_POST['public_full_file'], $files)) {
            $full_upl = false;
        } elseif (!empty($_FILES['up_full_file'])) {
            $full_upl = true;
        }

        if ($full_upl !== null && App::auth()->isSuperAdmin()) {
            if (empty($_POST['your_pwd']) || !App::auth()->checkPassword($_POST['your_pwd'])) {
                throw new Exception(__('Password verification failed'));
            }

            if ($full_upl) {
                Files::uploadStatus($_FILES['up_full_file']);
                $file = App::config()->cacheRoot() . '/' . md5(uniqid());
                if (!move_uploaded_file($_FILES['up_full_file']['tmp_name'], $file)) {
                    throw new Exception(__('Unable to move uploaded file.'));
                }
                $to_unlink = true;
            } else {
                $file = $_POST['public_full_file'];
            }

            $unzip_file = '';

            try {
                # Try to unzip file
                $unzip_file = $this->unzip($file);
                if (false !== $unzip_file) {
                    $bk = new FlatImportV2($unzip_file);
                }
                # Else this is a normal file
                else {
                    $bk = new FlatImportV2($file);
                }

                $bk->importFull();
            } catch (Exception $e) {
                if (false !== $unzip_file) {
                    @unlink($unzip_file);
                }
                if ($to_unlink) {
                    @unlink($file);
                }

                throw $e;
            }
            if ($unzip_file) {
                @unlink($unzip_file);
            }
            if ($to_unlink) {
                @unlink($file);
            }
            Http::redirect($this->getURL() . '&do=full');
        }

        header('content-type:text/plain');
        var_dump($_POST);
        exit;
    }

    public function gui(): void
    {
        if ($this->status === 'single') {
            Notices::success(__('Single blog successfully imported.'));

            return;
        }
        if ($this->status === 'full') {
            Notices::success(__('Content successfully imported.'));

            return;
        }

        $public_files = ['-' => '', ...$this->getPublicFiles()];
        $has_files    = (bool) (count($public_files) - 1);

        echo
        Page::jsJson(
            'ie_import_flat_msg',
            ['confirm_full_import' => __('Are you sure you want to import a full backup file?')]
        ) .
        My::jsLoad('import_flat');

        echo (new Form('ie-form'))
            ->method('post')
            ->action($this->getURL(true))
            ->enctype('multipart/form-data')
            ->fields([
                (new Fieldset())
                    ->legend(new Legend(__('Single blog')))
                    ->fields([
                        (new Note())
                            ->class('form-note')
                            ->text(sprintf(
                                __('This will import a single blog backup as new content in the current blog: <strong>%s</strong>.'),
                                Html::escapeHTML(App::blog()->name())
                            )),
                        (new Para())->items([
                            (new File('up_single_file'))
                                ->label(
                                    (new Label(
                                        __('Upload a backup file') . ' (' . sprintf(
                                            __('maximum size %s'),
                                            Files::size(App::config()->maxUploadSize())
                                        ) . ')',
                                        Label::OUTSIDE_TEXT_BEFORE
                                    ))
                                ),
                        ]),
                        ($has_files ?
                            (new Para())
                                ->items([
                                    (new Select('public_single_file'))
                                        ->items($public_files)
                                        ->label((new Label(__('or pick up a local file in your public directory'), Label::OUTSIDE_TEXT_BEFORE))),
                                ])
                            : (new None())),
                        (new Para())
                            ->class('form-buttons')
                            ->items([
                                ...My::hiddenFields(),
                                (new Hidden(['do'], '1')),
                                (new Hidden(['MAX_FILE_SIZE'], (string) App::config()->maxUploadSize())),
                                (new Submit(['ie-form-submit'], __('Import'))),
                            ]),
                    ]),
            ])
        ->render();

        if (App::auth()->isSuperAdmin()) {
            echo (new Form('formfull'))
                ->method('post')
                ->action($this->getURL(true))
                ->enctype('multipart/form-data')
                ->fields([
                    (new Fieldset())
                        ->legend(new Legend(__('Multiple blogs')))
                        ->fields([
                            (new Note())
                                ->class('form-note')
                                ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Text('span', '*'))->class('required')->render())),
                            (new Note())
                                ->class('warning')
                                ->text(__('This will reset all the content of your database, except users.')),
                            (new Para())->items([
                                (new File('up_full_file'))
                                    ->label(
                                        (new Label(
                                            __('Upload a backup file') . ' (' . sprintf(
                                                __('maximum size %s'),
                                                Files::size(App::config()->maxUploadSize())
                                            ) . ')',
                                            Label::OUTSIDE_TEXT_BEFORE
                                        ))
                                    ),
                            ]),
                            ($has_files ?
                                (new Para())
                                    ->items([
                                        (new Select('public_full_file'))
                                            ->items($public_files)
                                            ->label((new Label(__('or pick up a local file in your public directory'), Label::OUTSIDE_TEXT_BEFORE))),
                                    ])
                                : (new None())),
                            (new Para())->items([
                                (new Password('your_pwd'))
                                    ->size(20)
                                    ->maxlength(255)
                                    ->required(true)
                                    ->placeholder(__('Password'))
                                    ->autocomplete('current-password')
                                    ->label(
                                        (new Label(
                                            (new Text('span', '*'))->render() . __('Your password:'),
                                            Label::OUTSIDE_TEXT_BEFORE
                                        ))->class('required')
                                    )
                                    ->title(__('Required field')),
                            ]),
                            (new Para())
                                ->class('form-buttons')
                                ->items([
                                    ...My::hiddenFields(),
                                    (new Hidden(['do'], '1')),
                                    (new Hidden(['MAX_FILE_SIZE'], (string) App::config()->maxUploadSize())),
                                    (new Submit(['formfull-submit'], __('Import'))),
                                ]),
                        ]),
                ])
            ->render();
        }
    }

    /**
     * Gets the public files.
     *
     * @return  array<string, mixed>   The public files.
     */
    protected function getPublicFiles(): array
    {
        $public_files = [];
        $dir          = @dir(App::blog()->publicPath());
        if ($dir) {
            while (($entry = $dir->read()) !== false) {
                $entry_path = $dir->path . '/' . $entry;

                // Do not test each zip file content here, its too long
                if (is_file($entry_path) && is_readable($entry_path) && (str_ends_with($entry_path, '.zip') || self::checkFileContent($entry_path))) {
                    $public_files[$entry] = $entry_path;
                }
            }
        }

        return $public_files;
    }

    /**
     * Check if the file is in flat export format.
     *
     * @param   string  $entry_path     The entry path
     */
    protected static function checkFileContent(string $entry_path): bool
    {
        $ret = false;

        $fp = fopen($entry_path, 'rb');
        if ($fp !== false) {
            $ret = str_starts_with((string) fgets($fp), '///DOTCLEAR|');
            fclose($fp);
        }

        return $ret;
    }

    /**
     * Unzip a file.
     *
     * @param   string  $file   The file
     *
     * @throws  Exception
     *
     * @return  false|string
     */
    private function unzip(string $file): false|string
    {
        $zip = new Unzip($file);

        if ($zip->isEmpty()) {
            $zip->close();

            return false;
        }

        foreach ($zip->getFilesList() as $zip_file) {
            # Check zipped file name
            if (!str_ends_with($zip_file, '.txt')) {
                continue;
            }

            # Check zipped file contents
            $content = $zip->unzip($zip_file);
            if (!str_starts_with((string) $content, '///DOTCLEAR|')) {
                unset($content);

                continue;
            }

            $target = Path::fullFromRoot($zip_file, dirname($file));

            # Check existing files with same name
            if (file_exists($target)) {
                $zip->close();
                unset($content);

                throw new Exception(__('Another file with same name exists.'));
            }

            # Extract backup content
            if (file_put_contents($target, $content) === false) {
                $zip->close();
                unset($content);

                throw new Exception(__('Failed to extract backup file.'));
            }

            $zip->close();
            unset($content);

            # Return extracted file name
            return $target;
        }

        $zip->close();

        throw new Exception(__('No backup in compressed file.'));
    }
}
