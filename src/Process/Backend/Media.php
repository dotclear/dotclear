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

use Dotclear\Core\Backend\Filter\Filter;
use Dotclear\Core\Backend\Listing\ListingMedia;
use Dotclear\Core\Backend\MediaPage;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\App;
use Dotclear\Core\Backend\Filter\FilterMedia;
use Dotclear\Core\Process;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\File\Zip\Zip;
use Dotclear\Helper\Html\Html;
use Exception;
use form;

/**
 * @since 2.27 Before as admin/media.php
 */
class Media extends Process
{
    public static function init(): bool
    {
        Page::check(App::auth()->makePermissions([
            App::auth()::PERMISSION_MEDIA,
            App::auth()::PERMISSION_MEDIA_ADMIN,
        ]));

        App::backend()->page = new MediaPage();

        return self::status(true);
    }

    public static function process(): bool
    {
        # Zip download
        if (!empty($_GET['zipdl']) && App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_MEDIA_ADMIN,
        ]), App::blog()->id())) {
            try {
                if (str_starts_with((string) realpath(App::media()->getRoot() . '/' . App::backend()->page->d), (string) realpath(App::media()->getRoot()))) {
                    // Media folder or one of it's sub-folder(s)
                    @set_time_limit(300);
                    $fp  = fopen('php://output', 'wb');
                    $zip = new Zip($fp);

                    $thumb_sizes  = implode('|', array_keys(App::media()->getThumbSizes()));
                    $thumb_prefix = App::media()->getThumbnailPrefix();
                    if ($thumb_prefix !== '.') {
                        // Exclude . (hidden files) and prefixed thumbnails
                        $pattern_prefix = sprintf('(\.|%s)', preg_quote($thumb_prefix));
                    } else {
                        // Exclude . (hidden files)
                        $pattern_prefix = '\.';
                    }
                    $zip->addExclusion('/(^|\/)' . $pattern_prefix . '(.*?)_(' . $thumb_sizes . ')\.(jpg|jpeg|png|webp|avif)$/');
                    $zip->addExclusion('#(^|/)(__MACOSX|\.svn|\.hg.*|\.git.*|\.DS_Store|\.directory|Thumbs\.db)(/|$)#');

                    $zip->addDirectory(App::media()->getRoot() . '/' . App::backend()->page->d, '', true);
                    header('Content-Disposition: attachment;filename=' . date('Y-m-d') . '-' . App::blog()->id() . '-' . (App::backend()->page->d ?: 'media') . '.zip');
                    header('Content-Type: application/x-zip');
                    $zip->write();
                    unset($zip);
                    exit;
                }
                App::backend()->page->d = null;
                App::media()->chdir(App::backend()->page->d);

                throw new Exception(__('Not a valid directory'));
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        # User last and fav dirs
        if (App::backend()->page->showLast()) {
            if (!empty($_GET['fav']) && App::backend()->page->updateFav(rtrim((string) App::backend()->page->d, '/'), $_GET['fav'] == 'n')) {
                App::backend()->url()->redirect('admin.media', App::backend()->page->values());
            }
            App::backend()->page->updateLast(rtrim((string) App::backend()->page->d, '/'));
        }

        # New directory
        if (App::backend()->page->getDirs() && !empty($_POST['newdir'])) {
            $nd = Files::tidyFileName($_POST['newdir']);
            if (array_filter(App::backend()->page->getDirs('files'), fn ($i) => ($i->basename === $nd))
        || array_filter(App::backend()->page->getDirs('dirs'), fn ($i) => ($i->basename === $nd))
            ) {
                Notices::addWarningNotice(sprintf(
                    __('Directory or file "%s" already exists.'),
                    Html::escapeHTML($nd)
                ));
            } else {
                try {
                    App::media()->makeDir($_POST['newdir']);
                    Notices::addSuccessNotice(sprintf(
                        __('Directory "%s" has been successfully created.'),
                        Html::escapeHTML($nd)
                    ));
                    App::backend()->url()->redirect('admin.media', App::backend()->page->values());
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());
                }
            }
        }

        # Adding a file
        if (App::backend()->page->getDirs() && !empty($_FILES['upfile'])) {
            // only one file per request : @see option singleFileUploads in admin/js/jsUpload/jquery.fileupload
            $upfile = [
                'name'     => $_FILES['upfile']['name'][0],
                'type'     => $_FILES['upfile']['type'][0],
                'tmp_name' => $_FILES['upfile']['tmp_name'][0],
                'error'    => is_array($_FILES['upfile']['error']) ? $_FILES['upfile']['error'][0] : 0,
                'size'     => is_array($_FILES['upfile']['size']) ? $_FILES['upfile']['size'][0] : 0,
                'title'    => Html::escapeHTML($_FILES['upfile']['name'][0]),
            ];

            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                header('Content-type: application/json');
                $message = [];

                try {
                    Files::uploadStatus($upfile);
                    $new_file_id = App::media()->uploadFile($upfile['tmp_name'], $upfile['name'], false, $upfile['title']);

                    $message['files'][] = [
                        'name' => $upfile['name'],
                        'size' => $upfile['size'],
                        'html' => App::backend()->page->mediaLine((string) $new_file_id),
                    ];
                } catch (Exception $e) {
                    $message['files'][] = [
                        'name'  => $upfile['name'],
                        'size'  => $upfile['size'],
                        'error' => $e->getMessage(),
                    ];
                }
                echo json_encode($message, JSON_THROW_ON_ERROR);
                exit();
            }

            try {
                Files::uploadStatus($upfile);

                $f_title   = (isset($_POST['upfiletitle']) ? Html::escapeHTML($_POST['upfiletitle']) : '');
                $f_private = ($_POST['upfilepriv'] ?? false);

                App::media()->uploadFile($upfile['tmp_name'], $upfile['name'], false, $f_title, $f_private);

                Notices::addSuccessNotice(__('Files have been successfully uploaded.'));
                App::backend()->url()->redirect('admin.media', App::backend()->page->values());
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        # Removing items
        if (App::backend()->page->getDirs() && !empty($_POST['medias']) && !empty($_POST['delete_medias'])) {
            try {
                $search_filter = isset($_POST['q']) && $_POST['q'] !== '';
                if ($search_filter) {
                    // In search mode, medias contain full paths (relative to media main folder), so go back to main folder
                    $currentDir = App::backend()->page->d;
                    App::media()->chdir(null);
                }
                foreach ($_POST['medias'] as $media) {
                    App::media()->removeItem(rawurldecode($media));
                }
                if ($search_filter) {
                    // Back to current directory
                    App::media()->chdir($currentDir);
                }

                Notices::addSuccessNotice(
                    sprintf(
                        __(
                            'Successfully delete one media.',
                            'Successfully delete %d medias.',
                            is_countable($_POST['medias']) ? count($_POST['medias']) : 0
                        ),
                        is_countable($_POST['medias']) ? count($_POST['medias']) : 0
                    )
                );
                App::backend()->url()->redirect('admin.media', App::backend()->page->values());
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        # Removing item from popup only
        if (App::backend()->page->getDirs() && !empty($_POST['rmyes']) && !empty($_POST['remove'])) {
            $_POST['remove'] = rawurldecode($_POST['remove']);
            $forget          = false;

            try {
                if (is_dir((string) Path::real(App::media()->getPwd() . '/' . Path::clean($_POST['remove'])))) {
                    $msg = __('Directory has been successfully removed.');
                    # Remove dir from recents/favs if necessary
                    $forget = true;
                } else {
                    $msg = __('File has been successfully removed.');
                }
                App::media()->removeItem($_POST['remove']);
                if ($forget) {
                    App::backend()->page->updateLast(App::backend()->page->d . '/' . Path::clean($_POST['remove']), true);
                    App::backend()->page->updateFav(App::backend()->page->d . '/' . Path::clean($_POST['remove']), true);
                }
                Notices::addSuccessNotice($msg);
                App::backend()->url()->redirect('admin.media', App::backend()->page->values());
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        # Build missing directory thumbnails
        if (App::backend()->page->getDirs() && App::auth()->isSuperAdmin() && !empty($_POST['complete'])) {
            try {
                App::media()->rebuildThumbnails(App::backend()->page->d);

                Notices::addSuccessNotice(
                    sprintf(
                        __('Directory "%s" has been successfully completed.'),
                        Html::escapeHTML(App::backend()->page->d)
                    )
                );
                App::backend()->url()->redirect('admin.media', App::backend()->page->values());
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        # DISPLAY confirm page for rmdir & rmfile
        if (App::backend()->page->getDirs() && !empty($_GET['remove']) && empty($_GET['noconfirm'])) {
            App::backend()->page->openPage(App::backend()->page->breadcrumb([__('confirm removal') => '']));

            echo
            '<form action="' . Html::escapeURL(App::backend()->url()->get('admin.media')) . '" method="post">' .
            '<p>' . sprintf(
                __('Are you sure you want to remove %s?'),
                Html::escapeHTML($_GET['remove'])
            ) . '</p>' .
            '<p><input type="submit" value="' . __('Cancel') . '"> ' .
            ' &nbsp; <input type="submit" name="rmyes" value="' . __('Yes') . '">' .
            App::backend()->url()->getHiddenFormFields('admin.media', App::backend()->page->values()) .
            App::nonce()->getFormNonce() .
            form::hidden('remove', Html::escapeHTML($_GET['remove'])) . '</p>' .
            '</form>';

            App::backend()->page->closePage();
            exit;
        }

        return true;
    }

    public static function render(): void
    {
        // Recent media folders
        $last_folders = '';
        if (App::backend()->page->showLast()) {
            $last_folders_item = '';
            $fav_url           = '';
            $fav_img           = '';
            $fav_img_dark      = '';
            $fav_alt           = '';
            // Favorites directories
            $fav_dirs = App::backend()->page->getFav();
            foreach ($fav_dirs as $ld) {
                // Add favorites dirs on top of combo
                $ld_params      = App::backend()->page->values();
                $ld_params['d'] = $ld;
                $ld_params['q'] = ''; // Reset search
                $last_folders_item .= '<option value="' . urldecode(App::backend()->url()->get('admin.media', $ld_params)) . '"' .
            ($ld == rtrim((string) App::backend()->page->d, '/') ? ' selected="selected"' : '') . '>' .
            '/' . $ld . '</option>' . "\n";
                if ($ld == rtrim((string) App::backend()->page->d, '/')) {
                    // Current directory is a favorite → button will un-fav
                    $ld_params['fav'] = 'n';
                    $fav_url          = urldecode(App::backend()->url()->get('admin.media', $ld_params));
                    unset($ld_params['fav']);
                    $fav_img      = 'images/fav-on.svg';
                    $fav_img_dark = 'images/fav-on-dark.svg';
                    $fav_alt      = __('Remove this folder from your favorites');
                }
            }
            if ($last_folders_item != '') {
                // add a separator between favorite dirs and recent dirs
                $last_folders_item .= '<option disabled>_________</option>';
            }
            // Recent directories
            $last_dirs = App::backend()->page->getlast();
            foreach ($last_dirs as $ld) {
                if (!in_array($ld, $fav_dirs)) {
                    $ld_params      = App::backend()->page->values();
                    $ld_params['d'] = $ld;
                    $ld_params['q'] = ''; // Reset search
                    $last_folders_item .= '<option value="' . urldecode(App::backend()->url()->get('admin.media', $ld_params)) . '"' .
                ($ld == rtrim((string) App::backend()->page->d, '/') ? ' selected="selected"' : '') . '>' .
                '/' . $ld . '</option>' . "\n";
                    if ($ld == rtrim((string) App::backend()->page->d, '/')) {
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
            if ($last_folders_item != '') {
                $last_folders = '<p class="media-recent hidden-if-no-js">' .
                    '<label class="classic" for="switchfolder">' . __('Goto recent folder:') . '</label> ' .
                    '<select name="switchfolder" id="switchfolder">' .
                    $last_folders_item .
                    '</select>' .
                    ' <a id="media-fav-dir" href="' . $fav_url . '" title="' . $fav_alt . '">' .
                    '<img class="mark mark-fav light-only" src="' . $fav_img . '" alt="' . $fav_alt . '">' .
                    '<img class="mark mark-fav dark-only" src="' . $fav_img_dark . '" alt="' . $fav_alt . '">' .
                    '</a>' .
                    '</p>';
            }
        }

        $starting_scripts = '';
        if (App::backend()->page->popup && (App::backend()->page->plugin_id !== '')) {
            # --BEHAVIOR-- adminPopupMediaManager -- string
            $starting_scripts .= App::behavior()->callBehavior('adminPopupMediaManager', App::backend()->page->plugin_id);
        }

        App::backend()->page->openPage(
            App::backend()->page->breadcrumb(),
            Page::jsModal() .
            App::backend()->page->js(App::backend()->url()->get('admin.media', array_diff_key(App::backend()->page->values(), App::backend()->page->values(false, true)), '&')) .
            Page::jsLoad('js/_media.js') .
            $starting_scripts .
            (App::backend()->page->mediaWritable() ? Page::jsUpload() : '')
        );

        if (App::backend()->page->popup) {
            echo
            Notices::getNotices();
        }

        if (!App::backend()->page->mediaWritable() && !App::error()->flag()) {
            Notices::warning(__('You do not have sufficient permissions to write to this folder.'));
        }

        if (!App::backend()->page->getDirs()) {
            App::backend()->page->closePage();
            exit;
        }

        if (App::backend()->page->select) {
            // Select mode (popup or not)
            echo
            '<div class="' . (App::backend()->page->popup ? 'form-note ' : '') . 'info attach-media"><p>';
            if (App::backend()->page->select == 1) {
                echo
                sprintf(__('Select a file by clicking on %s'), '<img src="images/plus.svg" alt="' . __('Select this file') . '">');
            } else {
                echo
                sprintf(__('Select files and click on <strong>%s</strong> button'), __('Choose selected medias'));
            }
            if (App::backend()->page->mediaWritable()) {
                echo
                ' ' . __('or') . ' ' . sprintf('<a href="#fileupload">%s</a>', __('upload a new file'));
            }
            echo '</p></div>';
        } else {
            if (App::backend()->page->post_id) {
                echo
                '<div class="form-note info attach-media"><p>' . sprintf(
                    __('Choose a file to attach to entry %s by clicking on %s'),
                    '<a href="' . App::postTypes()->get(App::backend()->page->getPostType())->adminUrl(App::backend()->page->post_id) . '">' . Html::escapeHTML(App::backend()->page->getPostTitle()) . '</a>',
                    '<img src="images/plus.svg" alt="' . __('Attach this file to entry') . '">'
                );
                if (App::backend()->page->mediaWritable()) {
                    echo
                    ' ' . __('or') . ' ' . sprintf('<a href="#fileupload">%s</a>', __('upload a new file'));
                }
                echo
                '</p></div>';
            }
            if (App::backend()->page->popup) {
                echo
                '<div class="info attach-media"><p>' . sprintf(
                    __('Choose a file to insert into entry by clicking on %s'),
                    '<img src="images/plus.svg" alt="' . __('Attach this file to entry') . '">'
                );
                if (App::backend()->page->mediaWritable()) {
                    echo
                    ' ' . __('or') . ' ' . sprintf('<a href="#fileupload">%s</a>', __('upload a new file'));
                }
                echo
                '</p></div>';
            }
        }

        $rs         = App::backend()->page->getDirsRecord();
        $media_list = new ListingMedia($rs, $rs->count());

        // add file mode into the filter box
        App::backend()->page->add((new Filter('file_mode'))->value(App::backend()->page->file_mode)->html(
            '<p><span class="media-file-mode">' .
            '<a href="' . App::backend()->url()->get('admin.media', array_merge(App::backend()->page->values(), ['file_mode' => FilterMedia::MODE_GRID])) . '" title="' . __('Grid display mode') . '">' .
            '<img class="light-only' . (App::backend()->page->file_mode === FilterMedia::MODE_GRID ? '' : ' disabled') . '" src="images/grid.svg" alt="' . __('Grid display mode') . '">' .
            '<img class="dark-only' . (App::backend()->page->file_mode === FilterMedia::MODE_GRID ? '' : ' disabled') . '" src="images/grid-dark.svg" alt="' . __('Grid display mode') . '">' .
            '</a>' .
            '<a href="' . App::backend()->url()->get('admin.media', array_merge(App::backend()->page->values(), ['file_mode' => FilterMedia::MODE_LIST])) . '" title="' . __('List display mode') . '">' .
            '<img class="light-only' . (App::backend()->page->file_mode === FilterMedia::MODE_LIST ? '' : ' disabled') . '" src="images/list.svg" alt="' . __('List display mode') . '">' .
            '<img class="dark-only' . (App::backend()->page->file_mode === FilterMedia::MODE_LIST ? '' : ' disabled') . '" src="images/list-dark.svg" alt="' . __('List display mode') . '">' .
            '</a>' .
            '</span></p>',
            false
        ));

        $fmt_form_media = '<form action="' . App::backend()->url()->get('admin.media') . '" method="post" id="form-medias">' .
            '<div class="files-group">%s</div>' .
            '<p class="hidden">' .
            App::nonce()->getFormNonce() .
            App::backend()->url()->getHiddenFormFields('admin.media', App::backend()->page->values()) .
            '</p>';

        if (!App::backend()->page->popup || App::backend()->page->select > 1) {
            // Checkboxes and action
            $fmt_form_media .= '<div class="' . (!App::backend()->page->popup ? 'medias-delete' : '') . ' ' . (App::backend()->page->select > 1 ? 'medias-select' : '') . '">' .
                '<p class="checkboxes-helpers"></p>' .
                '<p>';
            if (App::backend()->page->select > 1) {
                $fmt_form_media .= '<input type="submit" class="select" id="select_medias" name="select_medias" value="' . __('Choose selected medias') . '"> ';
            }
            if (!App::backend()->page->popup) {
                $fmt_form_media .= '<input type="submit" class="delete" id="delete_medias" name="delete_medias" value="' . __('Remove selected medias') . '">';
            }
            $fmt_form_media .= '</p></div>';
        }
        $fmt_form_media .= '</form>';

        echo
        '<div class="media-list">' . $last_folders;

        // remove form filters from hidden fields
        $form_filters_hidden_fields = array_diff_key(
            App::backend()->page->values(),
            ['nb' => '', 'order' => '', 'sortby' => '', 'q' => '', 'file_type' => '']
        );

        // display filter
        App::backend()->page->display('admin.media', App::backend()->url()->getHiddenFormFields('admin.media', $form_filters_hidden_fields));

        // display list
        $media_list->display(App::backend()->page, $fmt_form_media, App::backend()->page->hasQuery());

        echo
        '</div>';

        if ((!App::backend()->page->hasQuery()) && (App::backend()->page->mediaWritable() || App::backend()->page->mediaArchivable())) {
            echo
            '<div class="vertical-separator">' .
            '<h3 class="out-of-screen-if-js">' . sprintf(__('In %s:'), (App::backend()->page->d == '' ? '“' . __('Media manager') . '”' : '“' . App::backend()->page->d . '”')) . '</h3>';
        }

        if ((!App::backend()->page->hasQuery()) && (App::backend()->page->mediaWritable() || App::backend()->page->mediaArchivable())) {
            echo
            '<div class="two-boxes odd">';

            // Create directory
            if (App::backend()->page->mediaWritable()) {
                echo
                '<form action="' . App::backend()->url()->getBase('admin.media') . '" method="post" class="fieldset">' .
                '<div id="new-dir-f">' .
                '<h4 class="pretty-title">' . __('Create new directory') . '</h4>' .
                App::nonce()->getFormNonce() .
                '<p><label for="newdir">' . __('Directory Name:') . '</label>' .
                form::field('newdir', 35, 255) . '</p>' .
                '<p><input type="submit" value="' . __('Create') . '">' .
                App::backend()->url()->getHiddenFormFields('admin.media', App::backend()->page->values()) .
                '</p>' .
                '</div>' .
                '</form>';
            }

            // Rebuild directory
            if (App::auth()->isSuperAdmin() && !App::backend()->page->popup && App::backend()->page->mediaWritable()) {
                echo
                '<form action="' . App::backend()->url()->getBase('admin.media') . '" method="post" class="fieldset">' .
                '<h4 class="pretty-title">' . __('Build missing thumbnails in directory') . '</h4>' .
                App::nonce()->getFormNonce() .
                '<p><input type="submit" value="' . __('Build') . '">' .
                App::backend()->url()->getHiddenFormFields('admin.media', array_merge(App::backend()->page->values(), ['complete' => 1])) .
                '</p>' .
                '</form>';
            }

            // Get zip directory
            if (App::backend()->page->mediaArchivable() && !App::backend()->page->popup) {
                echo
                '<div class="fieldset">' .
                '<h4 class="pretty-title">' . sprintf(__('Backup content of %s'), (App::backend()->page->d == '' ? '“' . __('Media manager') . '”' : '“' . App::backend()->page->d . '”')) . '</h4>' .
                '<p><a class="button submit" href="' . App::backend()->url()->get(
                    'admin.media',
                    array_merge(App::backend()->page->values(), ['zipdl' => 1])
                ) . '">' . __('Download zip file') . '</a></p>' .
                '</div>';
            }

            echo
            '</div>';
        }

        if (!App::backend()->page->hasQuery() && App::backend()->page->mediaWritable()) {
            echo
            '<div class="two-boxes fieldset even">';
            if (App::backend()->page->showUploader()) {
                echo
                '<div class="enhanced_uploader">';
            } else {
                echo
                '<div>';
            }

            echo
            '<h4>' . __('Add files') . '</h4>' .
            '<p class="more-info">' . __('Please take care to publish media that you own and that are not protected by copyright.') . '</p>' .
            '<form id="fileupload" action="' . Html::escapeURL(App::backend()->url()->get('admin.media', App::backend()->page->values())) . '" method="post" enctype="multipart/form-data" aria-disabled="false">' .
            '<p>' . form::hidden(['MAX_FILE_SIZE'], (string) App::config()->maxUploadSize()) .
            App::nonce()->getFormNonce() . '</p>' .
                '<div class="fileupload-ctrl"><p class="queue-message"></p><ul class="files"></ul></div>' .

            '<div class="fileupload-buttonbar clear">' .

            '<p><label for="upfile">' . '<span class="add-label one-file">' . __('Choose file') . '</span>' . '</label>' .
            '<button class="button choose_files">' . __('Choose files') . '</button>' .
            '<input type="file" id="upfile" name="upfile[]"' . (App::backend()->page->showUploader() ? ' multiple="mutiple"' : '') . ' data-url="' . Html::escapeURL(App::backend()->url()->get('admin.media', App::backend()->page->values())) . '"></p>' .

            '<p class="max-sizer form-note">&nbsp;' . __('Maximum file size allowed:') . ' ' . Files::size(App::config()->maxUploadSize()) . '</p>' .

            '<p class="one-file"><label for="upfiletitle">' . __('Alternate text:') . '</label>' . form::field('upfiletitle', 35, 255) . '</p>' .
            '<p class="one-file"><label for="upfilepriv" class="classic">' . __('Private') . '</label> ' .
            form::checkbox('upfilepriv', 1) . '</p>';

            if (!App::backend()->page->showUploader()) {
                echo
                '<p class="one-file form-help info">' . __('To send several files at the same time, you can activate the enhanced uploader in') .
                ' <a href="' . App::backend()->url()->get('admin.user.preferences', ['tab' => 'user-options']) . '">' . __('My preferences') . '</a></p>';
            }

            echo
            '<p class="clear form-buttons"><button class="button clean">' . __('Refresh') . '</button>' .
            '<input class="button cancel one-file" type="reset" value="' . __('Clear all') . '">' .
            '<input class="button start" type="submit" value="' . __('Upload') . '"></p>' .
            '</div>';

            echo
            '<p style="clear:both;">' .
            App::backend()->url()->getHiddenFormFields('admin.media', App::backend()->page->values()) .
            '</p>' .
            '</form>' .
            '</div>' .
            '</div>';
        }

        # Empty remove form (for javascript actions)
        echo
        '<form id="media-remove-hide" action="' . Html::escapeURL(App::backend()->url()->get('admin.media', App::backend()->page->values())) . '" method="post" class="hidden">' .
        '<div>' .
        form::hidden('rmyes', 1) .
        App::backend()->url()->getHiddenFormFields('admin.media', App::backend()->page->values()) .
        form::hidden('remove', '') .
        App::nonce()->getFormNonce() .
        '</div>' .
        '</form>';

        if ((!App::backend()->page->hasQuery()) && (App::backend()->page->mediaWritable() || App::backend()->page->mediaArchivable())) {
            echo
            '</div>';
        }

        if (!App::backend()->page->popup) {
            echo
            '<p class="info">' . sprintf(
                __('Current settings for medias and images are defined in %s'),
                '<a href="' . App::backend()->url()->get('admin.blog.pref') . '#medias-settings">' . __('Blog parameters') . '</a>'
            ) . '</p>';

            // Go back button
            echo
            '<p><input type="button" value="' . __('Back') . '" class="go-back reset hidden-if-no-js"></p>';
        }

        App::backend()->page->closePage();
    }
}
