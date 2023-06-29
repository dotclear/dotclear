<?php
/**
 * @since 2.27 Before as admin/media.php
 *
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Process\Backend;

use dcCore;
use Dotclear\Core\Backend\Filter\Filter;
use Dotclear\Core\Backend\Listing\ListingMedia;
use Dotclear\Core\Backend\MediaPage;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\File\File;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\File\Zip\Zip;
use Dotclear\Helper\Html\Html;
use Exception;
use form;

class Media extends Process
{
    public static function init(): bool
    {
        Page::check(dcCore::app()->auth->makePermissions([
            dcCore::app()->auth::PERMISSION_MEDIA,
            dcCore::app()->auth::PERMISSION_MEDIA_ADMIN,
        ]));

        dcCore::app()->admin->page = new MediaPage();

        return self::status(true);
    }

    public static function process(): bool
    {
        # Zip download
        if (!empty($_GET['zipdl']) && dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcCore::app()->auth::PERMISSION_MEDIA_ADMIN,
        ]), dcCore::app()->blog->id)) {
            try {
                if (strpos(realpath(dcCore::app()->media->root . '/' . dcCore::app()->admin->page->d), (string) realpath(dcCore::app()->media->root)) === 0) {
                    // Media folder or one of it's sub-folder(s)
                    @set_time_limit(300);
                    $fp  = fopen('php://output', 'wb');
                    $zip = new Zip($fp);
                    $zip->addExclusion('/(^|\/).(.*?)_(m|s|sq|t).(jpg|jpeg|png|webp)$/');
                    $zip->addDirectory(dcCore::app()->media->root . '/' . dcCore::app()->admin->page->d, '', true);
                    header('Content-Disposition: attachment;filename=' . date('Y-m-d') . '-' . dcCore::app()->blog->id . '-' . (dcCore::app()->admin->page->d ?: 'media') . '.zip');
                    header('Content-Type: application/x-zip');
                    $zip->write();
                    unset($zip);
                    exit;
                }
                dcCore::app()->admin->page->d = null;
                dcCore::app()->media->chdir(dcCore::app()->admin->page->d);

                throw new Exception(__('Not a valid directory'));
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        # User last and fav dirs
        if (dcCore::app()->admin->page->showLast()) {
            if (!empty($_GET['fav']) && dcCore::app()->admin->page->updateFav(rtrim((string) dcCore::app()->admin->page->d, '/'), $_GET['fav'] == 'n')) {
                dcCore::app()->adminurl->redirect('admin.media', dcCore::app()->admin->page->values());
            }
            dcCore::app()->admin->page->updateLast(rtrim((string) dcCore::app()->admin->page->d, '/'));
        }

        # New directory
        if (dcCore::app()->admin->page->getDirs() && !empty($_POST['newdir'])) {
            $nd = Files::tidyFileName($_POST['newdir']);
            if (array_filter(dcCore::app()->admin->page->getDirs('files'), fn ($i) => ($i->basename === $nd))
        || array_filter(dcCore::app()->admin->page->getDirs('dirs'), fn ($i) => ($i->basename === $nd))
            ) {
                Page::addWarningNotice(sprintf(
                    __('Directory or file "%s" already exists.'),
                    Html::escapeHTML($nd)
                ));
            } else {
                try {
                    dcCore::app()->media->makeDir($_POST['newdir']);
                    Page::addSuccessNotice(sprintf(
                        __('Directory "%s" has been successfully created.'),
                        Html::escapeHTML($nd)
                    ));
                    dcCore::app()->adminurl->redirect('admin.media', dcCore::app()->admin->page->values());
                } catch (Exception $e) {
                    dcCore::app()->error->add($e->getMessage());
                }
            }
        }

        # Adding a file
        if (dcCore::app()->admin->page->getDirs() && !empty($_FILES['upfile'])) {
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
                    $new_file_id = dcCore::app()->media->uploadFile($upfile['tmp_name'], $upfile['name'], false, $upfile['title']);

                    $message['files'][] = [
                        'name' => $upfile['name'],
                        'size' => $upfile['size'],
                        'html' => dcCore::app()->admin->page->mediaLine((string) $new_file_id),
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

                dcCore::app()->media->uploadFile($upfile['tmp_name'], $upfile['name'], false, $f_title, $f_private);

                Page::addSuccessNotice(__('Files have been successfully uploaded.'));
                dcCore::app()->adminurl->redirect('admin.media', dcCore::app()->admin->page->values());
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        # Removing items
        if (dcCore::app()->admin->page->getDirs() && !empty($_POST['medias']) && !empty($_POST['delete_medias'])) {
            try {
                foreach ($_POST['medias'] as $media) {
                    dcCore::app()->media->removeItem(rawurldecode($media));
                }
                Page::addSuccessNotice(
                    sprintf(
                        __(
                            'Successfully delete one media.',
                            'Successfully delete %d medias.',
                            is_countable($_POST['medias']) ? count($_POST['medias']) : 0
                        ),
                        is_countable($_POST['medias']) ? count($_POST['medias']) : 0
                    )
                );
                dcCore::app()->adminurl->redirect('admin.media', dcCore::app()->admin->page->values());
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        # Removing item from popup only
        if (dcCore::app()->admin->page->getDirs() && !empty($_POST['rmyes']) && !empty($_POST['remove'])) {
            $_POST['remove'] = rawurldecode($_POST['remove']);
            $forget          = false;

            try {
                if (is_dir(Path::real(dcCore::app()->media->getPwd() . '/' . Path::clean($_POST['remove'])))) {
                    $msg = __('Directory has been successfully removed.');
                    # Remove dir from recents/favs if necessary
                    $forget = true;
                } else {
                    $msg = __('File has been successfully removed.');
                }
                dcCore::app()->media->removeItem($_POST['remove']);
                if ($forget) {
                    dcCore::app()->admin->page->updateLast(dcCore::app()->admin->page->d . '/' . Path::clean($_POST['remove']), true);
                    dcCore::app()->admin->page->updateFav(dcCore::app()->admin->page->d . '/' . Path::clean($_POST['remove']), true);
                }
                Page::addSuccessNotice($msg);
                dcCore::app()->adminurl->redirect('admin.media', dcCore::app()->admin->page->values());
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        # Build missing directory thumbnails
        if (dcCore::app()->admin->page->getDirs() && dcCore::app()->auth->isSuperAdmin() && !empty($_POST['complete'])) {
            try {
                dcCore::app()->media->rebuildThumbnails(dcCore::app()->admin->page->d);

                Page::addSuccessNotice(
                    sprintf(
                        __('Directory "%s" has been successfully completed.'),
                        Html::escapeHTML(dcCore::app()->admin->page->d)
                    )
                );
                dcCore::app()->adminurl->redirect('admin.media', dcCore::app()->admin->page->values());
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        # DISPLAY confirm page for rmdir & rmfile
        if (dcCore::app()->admin->page->getDirs() && !empty($_GET['remove']) && empty($_GET['noconfirm'])) {
            dcCore::app()->admin->page->openPage(dcCore::app()->admin->page->breadcrumb([__('confirm removal') => '']));

            echo
            '<form action="' . Html::escapeURL(dcCore::app()->adminurl->get('admin.media')) . '" method="post">' .
            '<p>' . sprintf(
                __('Are you sure you want to remove %s?'),
                Html::escapeHTML($_GET['remove'])
            ) . '</p>' .
            '<p><input type="submit" value="' . __('Cancel') . '" /> ' .
            ' &nbsp; <input type="submit" name="rmyes" value="' . __('Yes') . '" />' .
            dcCore::app()->adminurl->getHiddenFormFields('admin.media', dcCore::app()->admin->page->values()) .
            dcCore::app()->formNonce() .
            form::hidden('remove', Html::escapeHTML($_GET['remove'])) . '</p>' .
            '</form>';

            dcCore::app()->admin->page->closePage();
            exit;
        }

        return true;
    }

    public static function render(): void
    {
        // Recent media folders
        $last_folders = '';
        if (dcCore::app()->admin->page->showLast()) {
            $last_folders_item = '';
            $fav_url           = '';
            $fav_img           = '';
            $fav_alt           = '';
            // Favorites directories
            $fav_dirs = dcCore::app()->admin->page->getFav();
            foreach ($fav_dirs as $ld) {
                // Add favorites dirs on top of combo
                $ld_params      = dcCore::app()->admin->page->values();
                $ld_params['d'] = $ld;
                $ld_params['q'] = ''; // Reset search
                $last_folders_item .= '<option value="' . urldecode(dcCore::app()->adminurl->get('admin.media', $ld_params)) . '"' .
            ($ld == rtrim((string) dcCore::app()->admin->page->d, '/') ? ' selected="selected"' : '') . '>' .
            '/' . $ld . '</option>' . "\n";
                if ($ld == rtrim((string) dcCore::app()->admin->page->d, '/')) {
                    // Current directory is a favorite → button will un-fav
                    $ld_params['fav'] = 'n';
                    $fav_url          = urldecode(dcCore::app()->adminurl->get('admin.media', $ld_params));
                    unset($ld_params['fav']);
                    $fav_img = 'images/fav-on.png';
                    $fav_alt = __('Remove this folder from your favorites');
                }
            }
            if ($last_folders_item != '') {
                // add a separator between favorite dirs and recent dirs
                $last_folders_item .= '<option disabled>_________</option>';
            }
            // Recent directories
            $last_dirs = dcCore::app()->admin->page->getlast();
            foreach ($last_dirs as $ld) {
                if (!in_array($ld, $fav_dirs)) {
                    $ld_params      = dcCore::app()->admin->page->values();
                    $ld_params['d'] = $ld;
                    $ld_params['q'] = ''; // Reset search
                    $last_folders_item .= '<option value="' . urldecode(dcCore::app()->adminurl->get('admin.media', $ld_params)) . '"' .
                ($ld == rtrim((string) dcCore::app()->admin->page->d, '/') ? ' selected="selected"' : '') . '>' .
                '/' . $ld . '</option>' . "\n";
                    if ($ld == rtrim((string) dcCore::app()->admin->page->d, '/')) {
                        // Current directory is not a favorite → button will fav
                        $ld_params['fav'] = 'y';
                        $fav_url          = urldecode(dcCore::app()->adminurl->get('admin.media', $ld_params));
                        unset($ld_params['fav']);
                        $fav_img = 'images/fav-off.png';
                        $fav_alt = __('Add this folder to your favorites');
                    }
                }
            }
            if ($last_folders_item != '') {
                $last_folders = '<p class="media-recent hidden-if-no-js">' .
                    '<label class="classic" for="switchfolder">' . __('Goto recent folder:') . '</label> ' .
                    '<select name="switchfolder" id="switchfolder">' .
                    $last_folders_item .
                    '</select>' .
                    ' <a id="media-fav-dir" href="' . $fav_url . '" title="' . $fav_alt . '"><img src="' . $fav_img . '" alt="' . $fav_alt . '" /></a>' .
                    '</p>';
            }
        }

        $starting_scripts = '';
        if (dcCore::app()->admin->page->popup && (dcCore::app()->admin->page->plugin_id !== '')) {
            # --BEHAVIOR-- adminPopupMediaManager -- string
            $starting_scripts .= dcCore::app()->callBehavior('adminPopupMediaManager', dcCore::app()->admin->page->plugin_id);
        }

        dcCore::app()->admin->page->openPage(
            dcCore::app()->admin->page->breadcrumb(),
            Page::jsModal() .
            dcCore::app()->admin->page->js(dcCore::app()->adminurl->get('admin.media', array_diff_key(dcCore::app()->admin->page->values(), dcCore::app()->admin->page->values(false, true)), '&')) .
            Page::jsLoad('js/_media.js') .
            $starting_scripts .
            (dcCore::app()->admin->page->mediaWritable() ? Page::jsUpload(['d=' . dcCore::app()->admin->page->d]) : '')
        );

        if (dcCore::app()->admin->page->popup) {
            echo
            Page::notices();
        }

        if (!dcCore::app()->admin->page->mediaWritable() && !dcCore::app()->error->flag()) {
            Page::warning(__('You do not have sufficient permissions to write to this folder.'));
        }

        if (!dcCore::app()->admin->page->getDirs()) {
            dcCore::app()->admin->page->closePage();
            exit;
        }

        if (dcCore::app()->admin->page->select) {
            // Select mode (popup or not)
            echo
            '<div class="' . (dcCore::app()->admin->page->popup ? 'form-note ' : '') . 'info"><p>';
            if (dcCore::app()->admin->page->select == 1) {
                echo
                sprintf(__('Select a file by clicking on %s'), '<img src="images/plus.png" alt="' . __('Select this file') . '" />');
            } else {
                echo
                sprintf(__('Select files and click on <strong>%s</strong> button'), __('Choose selected medias'));
            }
            if (dcCore::app()->admin->page->mediaWritable()) {
                echo
                ' ' . __('or') . ' ' . sprintf('<a href="#fileupload">%s</a>', __('upload a new file'));
            }
            echo '</p></div>';
        } else {
            if (dcCore::app()->admin->page->post_id) {
                echo
                '<div class="form-note info"><p>' . sprintf(
                    __('Choose a file to attach to entry %s by clicking on %s'),
                    '<a href="' . dcCore::app()->getPostAdminURL(dcCore::app()->admin->page->getPostType(), dcCore::app()->admin->page->post_id) . '">' . Html::escapeHTML(dcCore::app()->admin->page->getPostTitle()) . '</a>',
                    '<img src="images/plus.png" alt="' . __('Attach this file to entry') . '" />'
                );
                if (dcCore::app()->admin->page->mediaWritable()) {
                    echo
                    ' ' . __('or') . ' ' . sprintf('<a href="#fileupload">%s</a>', __('upload a new file'));
                }
                echo
                '</p></div>';
            }
            if (dcCore::app()->admin->page->popup) {
                echo
                '<div class="info"><p>' . sprintf(
                    __('Choose a file to insert into entry by clicking on %s'),
                    '<img src="images/plus.png" alt="' . __('Attach this file to entry') . '" />'
                );
                if (dcCore::app()->admin->page->mediaWritable()) {
                    echo
                    ' ' . __('or') . ' ' . sprintf('<a href="#fileupload">%s</a>', __('upload a new file'));
                }
                echo
                '</p></div>';
            }
        }

        $rs         = dcCore::app()->admin->page->getDirsRecord();
        $media_list = new ListingMedia($rs, $rs->count());

        // add file mode into the filter box
        dcCore::app()->admin->page->add((new Filter('file_mode'))->value(dcCore::app()->admin->page->file_mode)->html(
            '<p><span class="media-file-mode">' .
            '<a href="' . dcCore::app()->adminurl->get('admin.media', array_merge(dcCore::app()->admin->page->values(), ['file_mode' => 'grid'])) . '" title="' . __('Grid display mode') . '">' .
            '<img src="images/grid-' . (dcCore::app()->admin->page->file_mode == 'grid' ? 'on' : 'off') . '.png" alt="' . __('Grid display mode') . '" />' .
            '</a>' .
            '<a href="' . dcCore::app()->adminurl->get('admin.media', array_merge(dcCore::app()->admin->page->values(), ['file_mode' => 'list'])) . '" title="' . __('List display mode') . '">' .
            '<img src="images/list-' . (dcCore::app()->admin->page->file_mode == 'list' ? 'on' : 'off') . '.png" alt="' . __('List display mode') . '" />' .
            '</a>' .
            '</span></p>',
            false
        ));

        $fmt_form_media = '<form action="' . dcCore::app()->adminurl->get('admin.media') . '" method="post" id="form-medias">' .
            '<div class="files-group">%s</div>' .
            '<p class="hidden">' .
            dcCore::app()->formNonce() .
            dcCore::app()->adminurl->getHiddenFormFields('admin.media', dcCore::app()->admin->page->values()) .
            '</p>';

        if (!dcCore::app()->admin->page->popup || dcCore::app()->admin->page->select > 1) {
            // Checkboxes and action
            $fmt_form_media .= '<div class="' . (!dcCore::app()->admin->page->popup ? 'medias-delete' : '') . ' ' . (dcCore::app()->admin->page->select > 1 ? 'medias-select' : '') . '">' .
                '<p class="checkboxes-helpers"></p>' .
                '<p>';
            if (dcCore::app()->admin->page->select > 1) {
                $fmt_form_media .= '<input type="submit" class="select" id="select_medias" name="select_medias" value="' . __('Choose selected medias') . '"/> ';
            }
            if (!dcCore::app()->admin->page->popup) {
                $fmt_form_media .= '<input type="submit" class="delete" id="delete_medias" name="delete_medias" value="' . __('Remove selected medias') . '"/>';
            }
            $fmt_form_media .= '</p></div>';
        }
        $fmt_form_media .= '</form>';

        echo
        '<div class="media-list">' . $last_folders;

        // remove form filters from hidden fields
        $form_filters_hidden_fields = array_diff_key(
            dcCore::app()->admin->page->values(),
            ['nb' => '', 'order' => '', 'sortby' => '', 'q' => '', 'file_type' => '']
        );

        // display filter
        dcCore::app()->admin->page->display('admin.media', dcCore::app()->adminurl->getHiddenFormFields('admin.media', $form_filters_hidden_fields));

        // display list
        $media_list->display(dcCore::app()->admin->page, $fmt_form_media, dcCore::app()->admin->page->hasQuery());

        echo
        '</div>';

        if ((!dcCore::app()->admin->page->hasQuery()) && (dcCore::app()->admin->page->mediaWritable() || dcCore::app()->admin->page->mediaArchivable())) {
            echo
            '<div class="vertical-separator">' .
            '<h3 class="out-of-screen-if-js">' . sprintf(__('In %s:'), (dcCore::app()->admin->page->d == '' ? '“' . __('Media manager') . '”' : '“' . dcCore::app()->admin->page->d . '”')) . '</h3>';
        }

        if ((!dcCore::app()->admin->page->hasQuery()) && (dcCore::app()->admin->page->mediaWritable() || dcCore::app()->admin->page->mediaArchivable())) {
            echo
            '<div class="two-boxes odd">';

            // Create directory
            if (dcCore::app()->admin->page->mediaWritable()) {
                echo
                '<form action="' . dcCore::app()->adminurl->getBase('admin.media') . '" method="post" class="fieldset">' .
                '<div id="new-dir-f">' .
                '<h4 class="pretty-title">' . __('Create new directory') . '</h4>' .
                dcCore::app()->formNonce() .
                '<p><label for="newdir">' . __('Directory Name:') . '</label>' .
                form::field('newdir', 35, 255) . '</p>' .
                '<p><input type="submit" value="' . __('Create') . '" />' .
                dcCore::app()->adminurl->getHiddenFormFields('admin.media', dcCore::app()->admin->page->values()) .
                '</p>' .
                '</div>' .
                '</form>';
            }

            // Rebuild directory
            if (dcCore::app()->auth->isSuperAdmin() && !dcCore::app()->admin->page->popup && dcCore::app()->admin->page->mediaWritable()) {
                echo
                '<form action="' . dcCore::app()->adminurl->getBase('admin.media') . '" method="post" class="fieldset">' .
                '<h4 class="pretty-title">' . __('Build missing thumbnails in directory') . '</h4>' .
                dcCore::app()->formNonce() .
                '<p><input type="submit" value="' . __('Build') . '" />' .
                dcCore::app()->adminurl->getHiddenFormFields('admin.media', array_merge(dcCore::app()->admin->page->values(), ['complete' => 1])) .
                '</p>' .
                '</form>';
            }

            // Get zip directory
            if (dcCore::app()->admin->page->mediaArchivable() && !dcCore::app()->admin->page->popup) {
                echo
                '<div class="fieldset">' .
                '<h4 class="pretty-title">' . sprintf(__('Backup content of %s'), (dcCore::app()->admin->page->d == '' ? '“' . __('Media manager') . '”' : '“' . dcCore::app()->admin->page->d . '”')) . '</h4>' .
                '<p><a class="button submit" href="' . dcCore::app()->adminurl->get(
                    'admin.media',
                    array_merge(dcCore::app()->admin->page->values(), ['zipdl' => 1])
                ) . '">' . __('Download zip file') . '</a></p>' .
                '</div>';
            }

            echo
            '</div>';
        }

        if (!dcCore::app()->admin->page->hasQuery() && dcCore::app()->admin->page->mediaWritable()) {
            echo
            '<div class="two-boxes fieldset even">';
            if (dcCore::app()->admin->page->showUploader()) {
                echo
                '<div class="enhanced_uploader">';
            } else {
                echo
                '<div>';
            }

            echo
            '<h4>' . __('Add files') . '</h4>' .
            '<p class="more-info">' . __('Please take care to publish media that you own and that are not protected by copyright.') . '</p>' .
            '<form id="fileupload" action="' . Html::escapeURL(dcCore::app()->adminurl->get('admin.media', dcCore::app()->admin->page->values())) . '" method="post" enctype="multipart/form-data" aria-disabled="false">' .
            '<p>' . form::hidden(['MAX_FILE_SIZE'], (string) DC_MAX_UPLOAD_SIZE) .
            dcCore::app()->formNonce() . '</p>' .
                '<div class="fileupload-ctrl"><p class="queue-message"></p><ul class="files"></ul></div>' .

            '<div class="fileupload-buttonbar clear">' .

            '<p><label for="upfile">' . '<span class="add-label one-file">' . __('Choose file') . '</span>' . '</label>' .
            '<button class="button choose_files">' . __('Choose files') . '</button>' .
            '<input type="file" id="upfile" name="upfile[]"' . (dcCore::app()->admin->page->showUploader() ? ' multiple="mutiple"' : '') . ' data-url="' . Html::escapeURL(dcCore::app()->adminurl->get('admin.media', dcCore::app()->admin->page->values())) . '" /></p>' .

            '<p class="max-sizer form-note">&nbsp;' . __('Maximum file size allowed:') . ' ' . Files::size((int) DC_MAX_UPLOAD_SIZE) . '</p>' .

            '<p class="one-file"><label for="upfiletitle">' . __('Title:') . '</label>' . form::field('upfiletitle', 35, 255) . '</p>' .
            '<p class="one-file"><label for="upfilepriv" class="classic">' . __('Private') . '</label> ' .
            form::checkbox('upfilepriv', 1) . '</p>';

            if (!dcCore::app()->admin->page->showUploader()) {
                echo
                '<p class="one-file form-help info">' . __('To send several files at the same time, you can activate the enhanced uploader in') .
                ' <a href="' . dcCore::app()->adminurl->get('admin.user.preferences', ['tab' => 'user-options']) . '">' . __('My preferences') . '</a></p>';
            }

            echo
            '<p class="clear"><button class="button clean">' . __('Refresh') . '</button>' .
            '<input class="button cancel one-file" type="reset" value="' . __('Clear all') . '"/>' .
            '<input class="button start" type="submit" value="' . __('Upload') . '"/></p>' .
            '</div>';

            echo
            '<p style="clear:both;">' .
            dcCore::app()->adminurl->getHiddenFormFields('admin.media', dcCore::app()->admin->page->values()) .
            '</p>' .
            '</form>' .
            '</div>' .
            '</div>';
        }

        # Empty remove form (for javascript actions)
        echo
        '<form id="media-remove-hide" action="' . Html::escapeURL(dcCore::app()->adminurl->get('admin.media', dcCore::app()->admin->page->values())) . '" method="post" class="hidden">' .
        '<div>' .
        form::hidden('rmyes', 1) .
        dcCore::app()->adminurl->getHiddenFormFields('admin.media', dcCore::app()->admin->page->values()) .
        form::hidden('remove', '') .
        dcCore::app()->formNonce() .
        '</div>' .
        '</form>';

        if ((!dcCore::app()->admin->page->hasQuery()) && (dcCore::app()->admin->page->mediaWritable() || dcCore::app()->admin->page->mediaArchivable())) {
            echo
            '</div>';
        }

        if (!dcCore::app()->admin->page->popup) {
            echo
            '<p class="info">' . sprintf(
                __('Current settings for medias and images are defined in %s'),
                '<a href="' . dcCore::app()->adminurl->get('admin.blog.pref') . '#medias-settings">' . __('Blog parameters') . '</a>'
            ) . '</p>';

            // Go back button
            echo
            '<p><input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" /></p>';
        }

        dcCore::app()->admin->page->closePage();
    }
}
