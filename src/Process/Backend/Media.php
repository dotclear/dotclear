<?php

/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Process\Backend;

use Dotclear\App;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\File\Zip\Zip;
use Dotclear\Helper\Html\Form\Button;
use Dotclear\Helper\Html\Form\Capture;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\File;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Option;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Span;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Ul;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Helper\Stack\Filter;
use Exception;

/**
 * @since 2.27 Before as admin/media.php
 */
class Media
{
    use TraitProcess;

    public static function init(): bool
    {
        App::backend()->page()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_MEDIA,
            App::auth()::PERMISSION_MEDIA_ADMIN,
        ]));

        // Backward compatibility
        App::backend()->page = App::backend()->mediaPage();

        return self::status(true);
    }

    public static function process(): bool
    {
        # Zip download
        if (!empty($_GET['zipdl']) && App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_MEDIA_ADMIN,
        ]), App::blog()->id())) {
            try {
                if (str_starts_with((string) realpath(App::media()->getRoot() . '/' . App::backend()->mediaPage()->d), (string) realpath(App::media()->getRoot()))) {
                    // Media folder or one of it's sub-folder(s)
                    @set_time_limit(300);
                    $fp  = fopen('php://output', 'wb');
                    $zip = new Zip($fp);

                    $thumb_sizes  = implode('|', array_keys(App::media()->getThumbSizes()));
                    $thumb_prefix = App::media()->getThumbnailPrefix();
                    // Exclude . (hidden files) and prefixed thumbnails if necessary
                    $pattern_prefix = $thumb_prefix !== '.' ? sprintf('(\.|%s)', preg_quote($thumb_prefix)) : '\.';
                    $zip->addExclusion('/(^|\/)' . $pattern_prefix . '(.*?)_(' . $thumb_sizes . ')\.(jpg|jpeg|png|webp|avif)$/');
                    $zip->addExclusion('#(^|/)(__MACOSX|\.svn|\.hg.*|\.git.*|\.DS_Store|\.directory|Thumbs\.db)(/|$)#');

                    $zip->addDirectory(App::media()->getRoot() . '/' . App::backend()->mediaPage()->d, '', true);
                    header('Content-Disposition: attachment;filename=' . date('Y-m-d') . '-' . App::blog()->id() . '-' . (App::backend()->mediaPage()->d ?: 'media') . '.zip');
                    header('Content-Type: application/x-zip');
                    $zip->write();
                    unset($zip);
                    dotclear_exit();
                }
                App::backend()->mediaPage()->d = null;
                App::media()->chdir(App::backend()->mediaPage()->d);

                throw new Exception(__('Not a valid directory'));
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        # User last and fav dirs
        if (App::backend()->mediaPage()->showLast() > 0) {
            if (!empty($_GET['fav']) && App::backend()->mediaPage()->updateFav(rtrim((string) App::backend()->mediaPage()->d, '/'), $_GET['fav'] == 'n')) {
                App::backend()->url()->redirect('admin.media', App::backend()->mediaPage()->values());
            }
            App::backend()->mediaPage()->updateLast(rtrim((string) App::backend()->mediaPage()->d, '/'));
        }

        # New directory
        if (App::backend()->mediaPage()->getDirs() && !empty($_POST['newdir'])) {
            $nd = Files::tidyFileName($_POST['newdir']);
            if (array_filter((array) App::backend()->mediaPage()->getDirs('files'), fn ($i): bool => ($i->basename === $nd)) || array_filter((array) App::backend()->mediaPage()->getDirs('dirs'), fn ($i): bool => ($i->basename === $nd))
            ) {
                App::backend()->notices()->addWarningNotice(sprintf(
                    __('Directory or file "%s" already exists.'),
                    Html::escapeHTML($nd)
                ));
            } else {
                try {
                    App::media()->makeDir($_POST['newdir']);
                    App::backend()->notices()->addSuccessNotice(sprintf(
                        __('Directory "%s" has been successfully created.'),
                        Html::escapeHTML($nd)
                    ));
                    App::backend()->url()->redirect('admin.media', App::backend()->mediaPage()->values());
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());
                }
            }
        }

        # Adding a file
        if (App::backend()->mediaPage()->getDirs() && !empty($_FILES['upfile'])) {
            // only one file per request : @see option singleFileUploads in admin/js/jsUpload/jquery.fileupload
            $upfile = [
                'name'      => $_FILES['upfile']['name'][0],
                'type'      => $_FILES['upfile']['type'][0],
                'tmp_name'  => $_FILES['upfile']['tmp_name'][0],
                'error'     => is_array($_FILES['upfile']['error']) ? $_FILES['upfile']['error'][0] : 0,
                'size'      => is_array($_FILES['upfile']['size']) ? $_FILES['upfile']['size'][0] : 0,
                'full_path' => '',
            ];

            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                header('Content-type: application/json');
                $message = [];

                try {
                    Files::uploadStatus($upfile);
                    $new_file_id = App::media()->uploadFile($upfile['tmp_name'], $upfile['name']);

                    $message['files'][] = [
                        'name' => $upfile['name'],
                        'size' => $upfile['size'],
                        'html' => App::backend()->mediaPage()->mediaLine($new_file_id),
                    ];
                } catch (Exception $e) {
                    $message['files'][] = [
                        'name'  => $upfile['name'],
                        'size'  => $upfile['size'],
                        'error' => $e->getMessage(),
                    ];
                }
                echo json_encode($message, JSON_THROW_ON_ERROR);
                dotclear_exit();
            }

            try {
                Files::uploadStatus($upfile);   // @phpstan-ignore-line

                $f_title   = (isset($_POST['upfiletitle']) ? Html::escapeHTML($_POST['upfiletitle']) : '');
                $f_private = ($_POST['upfilepriv'] ?? false);

                App::media()->uploadFile($upfile['tmp_name'], $upfile['name'], false, $f_title, (bool) $f_private);

                App::backend()->notices()->addSuccessNotice(__('Files have been successfully uploaded.'));
                App::backend()->url()->redirect('admin.media', App::backend()->mediaPage()->values());
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        # Removing items
        if (App::backend()->mediaPage()->getDirs() && !empty($_POST['medias']) && !empty($_POST['delete_medias'])) {
            try {
                $currentDir    = App::backend()->mediaPage()->d;
                $search_filter = isset($_POST['q']) && $_POST['q'] !== '';
                if ($search_filter) {
                    // In search mode, medias contain full paths (relative to media main folder), so go back to main folder
                    App::media()->chdir(null);
                }

                foreach ($_POST['medias'] as $media) {
                    App::media()->removeItem(rawurldecode((string) $media));
                }

                if ($search_filter) {
                    // Back to current directory
                    App::media()->chdir($currentDir);
                }

                App::backend()->notices()->addSuccessNotice(
                    sprintf(
                        __(
                            'Successfully delete one media.',
                            'Successfully delete %d medias.',
                            is_countable($_POST['medias']) ? count($_POST['medias']) : 0
                        ),
                        is_countable($_POST['medias']) ? count($_POST['medias']) : 0
                    )
                );
                App::backend()->url()->redirect('admin.media', App::backend()->mediaPage()->values());
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        # Removing item from popup only
        if (App::backend()->mediaPage()->getDirs() && !empty($_POST['rmyes']) && !empty($_POST['remove'])) {
            $_POST['remove'] = rawurldecode((string) $_POST['remove']);
            $forget          = false;

            try {
                $currentDir    = App::backend()->mediaPage()->d;
                $search_filter = isset($_POST['q']) && $_POST['q'] !== '';
                if ($search_filter) {
                    // In search mode, medias contain full paths (relative to media main folder), so go back to main folder
                    App::media()->chdir(null);
                }

                if (is_dir((string) Path::real(App::media()->getPwd() . '/' . Path::clean($_POST['remove'])))) {
                    $msg = __('Directory has been successfully removed.');
                    # Remove dir from recents/favs if necessary
                    $forget = true;
                } else {
                    $msg = __('File has been successfully removed.');
                }
                App::media()->removeItem($_POST['remove']);

                if ($search_filter) {
                    // Back to current directory
                    App::media()->chdir($currentDir);
                }

                if ($forget) {
                    App::backend()->mediaPage()->updateLast(App::backend()->mediaPage()->d . '/' . Path::clean($_POST['remove']), true);
                    App::backend()->mediaPage()->updateFav(App::backend()->mediaPage()->d . '/' . Path::clean($_POST['remove']), true);
                }

                App::backend()->notices()->addSuccessNotice($msg);
                App::backend()->url()->redirect('admin.media', App::backend()->mediaPage()->values());
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        # Build missing directory thumbnails
        if (App::backend()->mediaPage()->getDirs() && App::auth()->isSuperAdmin() && !empty($_POST['complete'])) {
            try {
                App::media()->rebuildThumbnails(App::backend()->mediaPage()->d);

                App::backend()->notices()->addSuccessNotice(
                    sprintf(
                        __('Directory "%s" has been successfully completed.'),
                        Html::escapeHTML(App::backend()->mediaPage()->d)
                    )
                );
                App::backend()->url()->redirect('admin.media', App::backend()->mediaPage()->values());
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        # Rebuild all directory thumbnails
        if (App::backend()->mediaPage()->getDirs() && App::auth()->isSuperAdmin() && !empty($_POST['rebuild'])) {
            try {
                App::media()->rebuildThumbnails(App::backend()->mediaPage()->d, false, true);

                App::backend()->notices()->addSuccessNotice(
                    sprintf(
                        __('Directory "%s" has been successfully rebuild.'),
                        Html::escapeHTML(App::backend()->mediaPage()->d)
                    )
                );
                App::backend()->url()->redirect('admin.media', App::backend()->mediaPage()->values());
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        # DISPLAY confirm page for rmdir & rmfile
        if (App::backend()->mediaPage()->getDirs() && !empty($_GET['remove']) && empty($_GET['noconfirm'])) {
            App::backend()->mediaPage()->openPage(App::backend()->mediaPage()->breadcrumb([__('confirm removal') => '']));

            echo (new Form('frm-remove'))
                ->method('post')
                ->action(Html::escapeURL(App::backend()->url()->get('admin.media')))
                ->fields([
                    (new Note())
                        ->text(sprintf(__('Are you sure you want to remove %s?'), Html::escapeHTML($_GET['remove']))),
                    (new Para())
                        ->class('form-buttons')
                        ->items([
                            (new Submit('cancel', __('Cancel'))),
                            (new Submit('rmyes', __('Yes'))),
                            ... App::backend()->url()->hiddenFormFields('admin.media', App::backend()->mediaPage()->values()),
                            App::nonce()->formNonce(),
                            (new Hidden('remove', Html::escapeHTML($_GET['remove']))),
                        ]),
                ])
            ->render();

            App::backend()->mediaPage()->closePage();
            dotclear_exit();
        }

        return true;
    }

    public static function render(): void
    {
        // Recent media folders
        $recent_folders      = new None();
        $recent_folders_list = [];
        if (App::backend()->mediaPage()->showLast() > 0) {
            $fav_url      = '';
            $fav_img      = '';
            $fav_img_dark = '';
            $fav_alt      = '';
            // Favorites directories
            $fav_dirs = App::backend()->mediaPage()->getFav();
            foreach ($fav_dirs as $ld) {
                // Add favorites dirs on top of combo
                $ld_params             = App::backend()->mediaPage()->values();
                $ld_params['d']        = $ld;
                $ld_params['q']        = ''; // Reset search
                $is_current            = ($ld === rtrim((string) App::backend()->mediaPage()->d, '/'));
                $recent_folders_list[] = (new Option('/' . $ld, urldecode(App::backend()->url()->get('admin.media', $ld_params))))
                    ->selected($is_current);
                if ($is_current) {
                    // Current directory is a favorite → button will un-fav
                    $ld_params['fav'] = 'n';
                    $fav_url          = urldecode(App::backend()->url()->get('admin.media', $ld_params));
                    unset($ld_params['fav']);
                    $fav_img      = 'images/fav-on.svg';
                    $fav_img_dark = 'images/fav-on-dark.svg';
                    $fav_alt      = __('Remove this folder from your favorites');
                }
            }
            if ($recent_folders_list !== []) {
                // add a separator between favorite dirs and recent dirs
                $recent_folders_list[] = (new Option('_________', ''))
                    ->disabled(true);
            }
            // Recent directories
            $last_dirs = App::backend()->mediaPage()->getLast();
            foreach ($last_dirs as $ld) {
                if (!in_array($ld, $fav_dirs)) {
                    $ld_params             = App::backend()->mediaPage()->values();
                    $ld_params['d']        = $ld;
                    $ld_params['q']        = ''; // Reset search
                    $is_current            = ($ld === rtrim((string) App::backend()->mediaPage()->d, '/'));
                    $recent_folders_list[] = (new Option('/' . $ld, urldecode(App::backend()->url()->get('admin.media', $ld_params))))
                        ->selected($is_current);
                    if ($is_current) {
                        // Current directory is not a favorite → button will fav
                        $ld_params['fav'] = 'y';
                        $fav_url          = urldecode(App::backend()->url()->get('admin.media', $ld_params));
                        unset($ld_params['fav']);
                        $fav_img      = 'images/fav-off.svg';
                        $fav_img_dark = 'images/fav-off-dark.svg';
                        $fav_alt      = __('Add this folder to your favorites');
                    }
                }
            }
            if ($recent_folders_list !== []) {
                $recent_folders = (new Para())
                    ->class(['media-recent', 'form-buttons', 'hidden-if-no-js'])
                    ->items([
                        (new Select('switchfolder'))
                            ->items($recent_folders_list)
                            ->default(rtrim((string) App::backend()->mediaPage()->d, '/'))
                            ->label(new Label(__('Goto recent folder:'), Label::OL_TF)),
                        (new Link('media-fav-dir'))
                            ->href($fav_url)
                            ->title($fav_alt)
                            ->items([
                                (new Img($fav_img))
                                    ->alt($fav_alt)
                                    ->class(['mark', 'mark-fav', 'light-only']),
                                (new Img($fav_img_dark))
                                    ->alt($fav_alt)
                                    ->class(['mark', 'mark-fav', 'dark-only']),
                            ]),
                    ]);
            }
        }

        $starting_scripts = '';
        if (App::backend()->mediaPage()->popup && (App::backend()->mediaPage()->plugin_id !== '')) {
            # --BEHAVIOR-- adminPopupMediaManager -- string
            $starting_scripts .= App::behavior()->callBehavior('adminPopupMediaManager', App::backend()->mediaPage()->plugin_id);
        }

        App::backend()->mediaPage()->openPage(
            App::backend()->mediaPage()->breadcrumb(),
            App::backend()->page()->jsModal() .
            App::backend()->mediaPage()->js(App::backend()->url()->get('admin.media', array_diff_key(App::backend()->mediaPage()->values(), App::backend()->mediaPage()->values(false, true)), '&')) .
            App::backend()->page()->jsLoad('js/_media.js') .
            $starting_scripts .
            (App::backend()->mediaPage()->mediaWritable() ? App::backend()->page()->jsUpload() : '')
        );

        if (App::backend()->mediaPage()->popup) {
            echo
            App::backend()->notices()->getNotices();
        }

        if (!App::backend()->mediaPage()->mediaWritable() && !App::error()->flag()) {
            App::backend()->notices()->warning(__('You do not have sufficient permissions to write to this folder.'));
        }

        if (!App::backend()->mediaPage()->getDirs()) {
            App::backend()->mediaPage()->closePage();
            dotclear_exit();
        }

        if (App::backend()->mediaPage()->select) {
            // Select mode (popup or not)
            echo (new Div())
                ->class([App::backend()->mediaPage()->popup ? 'form-note' : '', 'info', 'attach-media'])
                ->items([
                    (new Para())
                        ->class(['form-buttons', 'is-a-phrase'])
                        ->items([
                            (new Text(
                                null,
                                App::backend()->mediaPage()->select == 1 ?
                                sprintf(
                                    __('Select a file by clicking on %s'),
                                    (new Img('images/plus.svg'))->alt(__('Select this file'))->render()
                                ) :
                                sprintf(
                                    __('Select files and click on <strong>%s</strong> button'),
                                    __('Choose selected medias')
                                )
                            )),
                            (App::backend()->mediaPage()->mediaWritable() ?
                                (new Set())
                                    ->items([
                                        (new Text(null, __('or'))),
                                        (new Link())
                                            ->href('#fileupload')
                                            ->text(__('upload a new file')),
                                    ]) :
                                (new None())),
                        ]),
                ])
            ->render();
        } else {
            if (App::backend()->mediaPage()->post_id) {
                $post_link = (new Link())
                    ->href(App::postTypes()->get(App::backend()->mediaPage()->getPostType())->adminUrl(App::backend()->mediaPage()->post_id))
                    ->text(Html::escapeHTML(App::backend()->mediaPage()->getPostTitle()))
                ->render();
                echo (new Div())
                    ->class(['form-note', 'info', 'attach-media'])
                    ->items([
                        (new Note())
                            ->text('<!-- ' . __LINE__ . ' -->'),
                        (new Para())
                            ->class(['form-buttons', 'is-a-phrase'])
                            ->items([
                                (new Text(
                                    null,
                                    sprintf(
                                        __('Choose a file to attach to entry %1$s by clicking on %2$s'),
                                        $post_link,
                                        (new Img('images/plus.svg'))->alt(__('Attach this file to entry'))->render()
                                    )
                                )),
                                (App::backend()->mediaPage()->mediaWritable() ?
                                    (new Set())
                                        ->items([
                                            (new Text(null, __('or'))),
                                            (new Link())
                                                ->href('#fileupload')
                                                ->text(__('upload a new file')),
                                        ]) :
                                    (new None())),
                            ]),
                    ])
                ->render();
            }
            if (App::backend()->mediaPage()->popup) {
                echo (new Div())
                    ->class(['form-note', 'info', 'attach-media'])
                    ->items([
                        (new Para())
                            ->class(['form-buttons', 'is-a-phrase'])
                            ->items([
                                (new Text(
                                    null,
                                    sprintf(
                                        __('Choose a file to insert into entry by clicking on %s'),
                                        (new Img('images/plus.svg'))->alt(__('Insert this file into entry'))->render()
                                    )
                                )),
                                (App::backend()->mediaPage()->mediaWritable() ?
                                    (new Set())
                                        ->items([
                                            (new Text(null, __('or'))),
                                            (new Link())
                                                ->href('#fileupload')
                                                ->text(__('upload a new file')),
                                        ]) :
                                    (new None())),
                            ]),
                    ])
                ->render();
            }
        }

        $rs         = App::backend()->mediaPage()->getDirsRecord();
        $media_list = App::backend()->listing()->media($rs, $rs->count());

        // add file mode into the filter box
        $filter = (new Para())
            ->items([
                (new Span())
                    ->class('media-file-mode')
                    ->items([
                        (new Link())
                            ->href(App::backend()->url()->get('admin.media', array_merge(App::backend()->mediaPage()->values(), ['file_mode' => App::backend()->filter()->media()::MODE_GRID])))
                            ->title(__('Grid display mode'))
                            ->items([
                                (new Img('images/grid.svg'))
                                    ->class(['light-only', (App::backend()->mediaPage()->file_mode === App::backend()->filter()->media()::MODE_GRID ? '' : ' disabled')])
                                    ->alt(__('Grid display mode')),
                                (new Img('images/grid-dark.svg'))
                                    ->class(['dark-only', (App::backend()->mediaPage()->file_mode === App::backend()->filter()->media()::MODE_GRID ? '' : ' disabled')])
                                    ->alt(__('Grid display mode')),
                            ]),
                        (new Link())
                            ->href(App::backend()->url()->get('admin.media', array_merge(App::backend()->mediaPage()->values(), ['file_mode' => App::backend()->filter()->media()::MODE_LIST])))
                            ->title(__('List display mode'))
                            ->items([
                                (new Img('images/list.svg'))
                                    ->class(['light-only', (App::backend()->mediaPage()->file_mode === App::backend()->filter()->media()::MODE_LIST ? '' : ' disabled')])
                                    ->alt(__('List display mode')),
                                (new Img('images/list-dark.svg'))
                                    ->class(['dark-only', (App::backend()->mediaPage()->file_mode === App::backend()->filter()->media()::MODE_LIST ? '' : ' disabled')])
                                    ->alt(__('List display mode')),
                            ]),
                    ]),
            ])
        ->render();
        App::backend()->mediaPage()->add((new Filter('file_mode'))->value(App::backend()->mediaPage()->file_mode)->html($filter, false));

        $actions = (new None());
        if (!App::backend()->mediaPage()->popup || App::backend()->mediaPage()->select > 1) {
            // Checkboxes and action
            $actions = (new Div())
                ->class([
                    App::backend()->mediaPage()->popup ? '' : 'medias-delete',
                    App::backend()->mediaPage()->select > 1 ? 'medias-select' : '',
                ])
                ->items([
                    (new Para())->class('checkboxes-helpers'),
                    (new Para())
                        ->class('form-buttons')
                        ->items([
                            App::backend()->mediaPage()->select > 1 ?
                                (new Submit('select_medias', __('Choose selected medias')))->class('select') :
                                (new None()),
                            App::backend()->mediaPage()->popup ?
                                (new None()) :
                                (new Submit('delete_medias', __('Remove selected medias')))->class('delete'),
                        ]),
                ]);
        }

        $form = (new Form('form-medias'))
            ->method('post')
            ->action(App::backend()->url()->get('admin.media'))
            ->fields([
                (new Div())
                    ->class('files-group')
                    ->items([
                        (new Text(null, '%s')),
                    ]),
                (new Para())
                    ->class('hidden')
                    ->items([
                        App::nonce()->formNonce(),
                        ... App::backend()->url()->hiddenFormFields('admin.media', App::backend()->mediaPage()->values()),
                    ]),
                $actions,
            ])
        ->render();

        // remove form filters from hidden fields
        $form_filters_hidden_fields = array_diff_key(
            App::backend()->mediaPage()->values(),
            ['nb' => '', 'order' => '', 'sortby' => '', 'q' => '', 'file_type' => '']
        );

        // Display recent folders, filter and media list
        echo (new Div())
            ->class('media-list')
            ->items([
                $recent_folders,
                (new Capture(
                    // display filter
                    App::backend()->mediaPage()->display(...),
                    ['admin.media', App::backend()->url()->getHiddenFormFields('admin.media', $form_filters_hidden_fields)]
                )),
                (new Capture(
                    // display list
                    $media_list->display(...),
                    [App::backend()->mediaPage(), $form, App::backend()->mediaPage()->hasQuery()]
                )),
            ])
        ->render();

        // Other tools
        $tools = [];

        if ((!App::backend()->mediaPage()->hasQuery()) && (App::backend()->mediaPage()->mediaWritable() || App::backend()->mediaPage()->mediaArchivable())) {
            $dirtools = [];

            // Create directory
            if (App::backend()->mediaPage()->mediaWritable()) {
                $dirtools[] = (new Form('newdir-form'))
                    ->method('post')
                    ->action(App::backend()->url()->getBase('admin.media'))
                    ->fields([
                        (new Fieldset())
                            ->legend(new Legend(__('Create new directory')))
                            ->fields([
                                (new Para())
                                    ->items([
                                        (new Input('newdir'))
                                            ->size(35)
                                            ->maxlength(255)
                                            ->label((new Label(__('Directory Name:'), Label::OL_TF))),
                                    ]),
                                (new Para())
                                    ->class('form-buttons')
                                    ->items([
                                        App::nonce()->formNonce(),
                                        (new Submit('newdir-submit', __('Create'))),
                                        ... App::backend()->url()->hiddenFormFields('admin.media', App::backend()->mediaPage()->values()),
                                    ]),
                            ]),
                    ]);
            }

            // Rebuild directory (complete / force)
            if (App::auth()->isSuperAdmin() && !App::backend()->mediaPage()->popup && App::backend()->mediaPage()->mediaWritable()) {
                $dirtools[] = (new Form('rebuild-form'))
                    ->method('post')
                    ->action(App::backend()->url()->getBase('admin.media'))
                    ->fields([
                        (new Fieldset())
                            ->legend(new Legend(__('Build missing thumbnails in directory')))
                            ->fields([
                                (new Para())
                                    ->items([
                                        App::nonce()->formNonce(),
                                        (new Submit('rebuild-submit', __('Build'))),
                                        ... App::backend()->url()->hiddenFormFields('admin.media', array_merge(App::backend()->mediaPage()->values(), ['complete' => 1])),
                                    ]),
                            ]),
                    ]);
                $dirtools[] = (new Form('force-rebuild-form'))
                    ->method('post')
                    ->action(App::backend()->url()->getBase('admin.media'))
                    ->fields([
                        (new Fieldset())
                            ->legend(new Legend(__('Force rebuild all thumbnails in directory')))
                            ->fields([
                                (new Para())
                                    ->items([
                                        App::nonce()->formNonce(),
                                        (new Submit('force-rebuild-submit', __('Force rebuild'))),
                                        ... App::backend()->url()->hiddenFormFields('admin.media', array_merge(App::backend()->mediaPage()->values(), ['rebuild' => 1])),
                                    ]),
                                (new Note())
                                    ->class(['warning', 'form-note'])
                                    ->text(__('Use with caution especially if there is a large numbers of media if this directory.')),
                            ]),
                    ]);
            }

            // Get zip directory
            if (App::backend()->mediaPage()->mediaArchivable() && !App::backend()->mediaPage()->popup) {
                $dirtools[] = (new Fieldset())
                    ->legend(new Legend(sprintf(__('Backup content of %s'), (App::backend()->mediaPage()->d == '' ? '“' . __('Media manager') . '”' : '“' . App::backend()->mediaPage()->d . '”'))))
                    ->fields([
                        (new Para())
                            ->items([
                                (new Link('zip-submit'))
                                    ->class(['button', 'submit'])
                                    ->href(App::backend()->url()->get('admin.media', array_merge(App::backend()->mediaPage()->values(), ['zipdl' => 1])))
                                    ->text(__('Download zip file')),
                            ]),
                    ]);
            }

            if ($dirtools !== []) {
                $tools[] = (new Div())
                    ->class(['two-boxes', 'odd'])
                    ->items($dirtools);
            }
        }

        if (!App::backend()->mediaPage()->hasQuery() && App::backend()->mediaPage()->mediaWritable()) {
            $tools[] = (new Div())
                ->class(['two-boxes', 'even', 'fieldset'])
                ->items([
                    (new Div())
                        ->class(App::backend()->mediaPage()->showUploader() ? 'enhanced_uploader' : '')
                        ->items([
                            (new Text('h4', __('Add files'))),
                            (new Note())
                                ->class('more-info')
                                ->text(__('Please take care to publish media that you own and that are not protected by copyright.')),
                            (new Form('fileupload'))
                                ->method('post')
                                ->action(Html::escapeURL(App::backend()->url()->get('admin.media', App::backend()->mediaPage()->values())))
                                ->enctype('multipart/form-data')
                                ->extra('aria-disabled="false"')
                                ->fields([
                                    (new Div())
                                        ->class('fileupload-ctrl')
                                        ->items([
                                            (new Para())
                                                ->class('queue-message'),
                                            (new Ul())
                                                ->class('files'),
                                        ]),
                                    (new Div())
                                        ->class(['fileupload-buttonbar', 'clear'])
                                        ->items([
                                            (new Para())
                                                ->items([
                                                    (new Label(
                                                        (new Span(__('Choose file')))
                                                            ->class(['add-label', 'one-file'])
                                                        ->render(),
                                                        Label::OL_TF
                                                    ))
                                                    ->for('upfile'),
                                                    (new Button('choose_button', __('Choose files')))
                                                        ->class(['button', 'choose_files']),
                                                    (new File(['upfile[]', 'upfile']))
                                                        ->data([
                                                            'url' => Html::escapeURL(App::backend()->url()->get('admin.media', App::backend()->mediaPage()->values())),
                                                        ])
                                                        ->extra([
                                                            App::backend()->mediaPage()->showUploader() ? ' multiple="mutiple"' : '',
                                                        ]),
                                                ]),
                                            (new Note())
                                                ->class(['max-sizer', 'form-note'])
                                                ->text(__('Maximum file size allowed:') . ' ' . Files::size(App::config()->maxUploadSize())),
                                            (new Para())
                                                ->class('one-file')
                                                ->items([
                                                    (new Input('upfiletitle'))
                                                        ->size(35)
                                                        ->maxlength(255)
                                                        ->label(new Label(__('Title:'), Label::OL_TF)),
                                                ]),
                                            (new Para())
                                                ->class('one-file')
                                                ->items([
                                                    (new Checkbox('upfilepriv'))
                                                        ->value(1)
                                                        ->label(new Label(__('Private'), Label::IL_FT)),
                                                ]),
                                            (
                                                App::backend()->mediaPage()->showUploader() ?
                                                (new None()) :
                                                (new Para())
                                                    ->class(['one-file', 'form-help', 'info'])
                                                    ->separator(' ')
                                                    ->items([
                                                        (new Text(null, __('To send several files at the same time, you can activate the enhanced uploader in'))),
                                                        (new Link())
                                                            ->href(App::backend()->url()->get('admin.user.preferences', ['tab' => 'user-options']))
                                                            ->text(__('My preferences')),
                                                    ])
                                            ),
                                            (new Para())
                                                ->class(['form-buttons', 'clear'])
                                                ->items([
                                                    (new Button('upclean', __('Refresh')))
                                                        ->class(['button', 'clean']),
                                                    (new Input('upclear'))
                                                        ->value(__('Clear all'))
                                                        ->type('reset')
                                                        ->class(['button', 'cancel', 'one-file']),
                                                    (new Input('upstart'))
                                                        ->value(__('Upload'))
                                                        ->type('submit')
                                                        ->class(['button', 'start']),
                                                ]),
                                        ]),
                                    (new Para())
                                        ->class(['form-buttons', 'clear'])
                                        ->items([
                                            (new Hidden(['MAX_FILE_SIZE'], (string) App::config()->maxUploadSize())),
                                            App::nonce()->formNonce(),
                                            ... App::backend()->url()->hiddenFormFields('admin.media', App::backend()->mediaPage()->values()),
                                        ]),
                                ]),
                        ]),
                ]);
        }

        if ($tools !== []) {
            echo (new Div())
                ->class('vertical-separator')
                ->items([
                    (new Text('h3', sprintf(__('In %s:'), (App::backend()->mediaPage()->d == '' ? '“' . __('Media manager') . '”' : '“' . App::backend()->mediaPage()->d . '”'))))
                        ->class('out-of-screen-if-js'),
                    ... $tools,
                ])
            ->render();
        }

        // Empty remove form (for javascript actions)
        echo (new Form('media-remove-hide'))
            ->method('post')
            ->action(Html::escapeURL(App::backend()->url()->get('admin.media', App::backend()->mediaPage()->values())))
            ->class('hidden')
            ->fields([
                (new Div())
                    ->items([
                        (new Hidden('rmyes', '1')),
                        (new Hidden('remove', '')),
                        ... App::backend()->url()->hiddenFormFields('admin.media', App::backend()->mediaPage()->values()),
                        App::nonce()->formNonce(),
                    ]),
            ])
        ->render();

        if (!App::backend()->mediaPage()->popup) {
            echo (new Note())
                ->class('info')
                ->text(sprintf(
                    __('Current settings for medias and images are defined in %s'),
                    (new Link())
                        ->href(App::backend()->url()->get('admin.blog.pref') . '#params.medias-settings')
                        ->text(__('Blog parameters'))
                    ->render()
                ))
            ->render();

            // Go back button
            echo (new Para())
                ->class('form-buttons')
                ->items([
                    (new Button('back'))
                        ->class(['go-back', 'reset', 'hidden-if-no-js'])
                        ->value(__('Back')),
                ])
            ->render();
        }

        App::backend()->mediaPage()->closePage();
    }
}
