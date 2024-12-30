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

use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Helper\Date;
use Dotclear\Helper\File\File;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Html\Html;
use Exception;
use form;
use SimpleXMLElement;

/**
 * @since 2.27 Before as admin/media_item.php
 */
class MediaItem extends Process
{
    public static function init(): bool
    {
        Page::check(App::auth()->makePermissions([
            App::auth()::PERMISSION_MEDIA,
            App::auth()::PERMISSION_MEDIA_ADMIN,
        ]));

        App::backend()->tab = empty($_REQUEST['tab']) ? '' : $_REQUEST['tab'];

        $post_id = empty($_REQUEST['post_id']) ? null : (int) $_REQUEST['post_id'];
        if ($post_id) {
            $post = App::blog()->getPosts(['post_id' => $post_id]);
            if ($post->isEmpty()) {
                $post_id = null;
            }
        }

        // Attachement type if any
        $link_type = empty($_REQUEST['link_type']) ? null : $_REQUEST['link_type'];

        App::backend()->file  = null;
        App::backend()->popup = (int) !empty($_REQUEST['popup']);

        // 0 : none, 1 : single media, >1 : multiple medias
        App::backend()->select = empty($_REQUEST['select']) ? 0 : (int) $_REQUEST['select'];

        App::backend()->plugin_id = isset($_REQUEST['plugin_id']) ? Html::sanitizeURL($_REQUEST['plugin_id']) : '';

        App::backend()->page_url_params = [
            'popup'   => App::backend()->popup,
            'select'  => App::backend()->select,
            'post_id' => $post_id,
        ];
        App::backend()->media_page_url_params = [
            'popup'     => App::backend()->popup,
            'select'    => App::backend()->select,
            'post_id'   => $post_id,
            'link_type' => $link_type,
        ];

        if (App::backend()->plugin_id !== '') {
            App::backend()->page_url_params = [
                ...App::backend()->page_url_params,
                'plugin_id' => App::backend()->plugin_id,
            ];
            App::backend()->media_page_url_params = [
                ...App::backend()->media_page_url_params,
                'plugin_id' => App::backend()->plugin_id,
            ];
        }

        App::backend()->id = empty($_REQUEST['id']) ? '' : (int) $_REQUEST['id'];

        if (App::backend()->id != '') {
            App::backend()->page_url_params = [
                ...App::backend()->page_url_params,
                'id' => App::backend()->id,
            ];
        }

        if (App::backend()->popup !== 0) {
            App::backend()->open_function  = Page::openPopup(...);
            App::backend()->close_function = Page::closePopup(...);
        } else {
            App::backend()->open_function  = Page::open(...);
            App::backend()->close_function = function (): void {
                Page::helpBlock('core_media');
                Page::close();
            };
        }

        App::backend()->is_media_writable = false;

        $dirs_combo = [];

        try {
            if (App::backend()->id) {
                App::backend()->file = App::media()->getFile((int) App::backend()->id);
            }

            if (!App::backend()->file instanceof File) {
                throw new Exception(__('Not a valid file'));
            }

            App::media()->chdir(dirname(App::backend()->file->relname));
            App::backend()->is_media_writable = App::media()->writable();

            # Prepare directories combo box
            foreach (App::media()->getDBDirs() as $v) {
                $dirs_combo['/' . $v] = $v;
            }
            # Add parent and direct childs directories if any
            App::media()->getFSDir();
            foreach (App::media()->getDirs() as $v) {
                $dirs_combo['/' . $v->relname] = $v->relname;
            }
            ksort($dirs_combo);

            if (App::themes()->isEmpty()) {
                # -- Loading themes, may be useful for some configurable theme --
                App::themes()->loadModules(App::blog()->themesPath(), 'admin', App::lang()->getLang());
            }
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }
        App::backend()->dirs_combo = $dirs_combo;

        return self::status(true);
    }

    public static function process(): bool
    {
        if (App::backend()->file && !empty($_FILES['upfile']) && App::backend()->file->editable && App::backend()->is_media_writable) {
            // Upload a new file

            try {
                Files::uploadStatus($_FILES['upfile']);
                App::media()->uploadFile($_FILES['upfile']['tmp_name'], App::backend()->file->basename, true, null, false);

                Notices::addSuccessNotice(__('File has been successfully updated.'));
                App::backend()->url()->redirect('admin.media.item', App::backend()->page_url_params);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if (App::backend()->file && !empty($_POST['media_file']) && App::backend()->file->editable && App::backend()->is_media_writable) {
            // Update file

            $newFile = clone App::backend()->file;

            $newFile->basename = $_POST['media_file'];

            if ($_POST['media_path']) {
                $newFile->dir     = $_POST['media_path'];
                $newFile->relname = $_POST['media_path'] . '/' . $newFile->basename;
            } else {
                $newFile->dir     = '';
                $newFile->relname = $newFile->basename;
            }
            $newFile->media_title = Html::escapeHTML($_POST['media_title']);
            $newFile->media_dt    = strtotime((string) $_POST['media_dt']);
            $newFile->media_dtstr = $_POST['media_dt'];
            $newFile->media_priv  = !empty($_POST['media_private']);

            // Update alt and description in metadata
            $alt       = isset($_POST['media_alt']) ? Html::escapeHTML($_POST['media_alt']) : '';
            $desc      = isset($_POST['media_desc']) ? Html::escapeHTML($_POST['media_desc']) : '';
            $alt_done  = false;
            $desc_done = false;
            if (App::backend()->file->media_meta instanceof SimpleXMLElement) {
                if (count(App::backend()->file->media_meta) > 0) {
                    foreach (App::backend()->file->media_meta as $k => $v) {
                        if ($k === 'AltText') {
                            $v[0]     = $alt;  // @phpstan-ignore-line
                            $alt_done = true;
                        }
                        if ($k === 'Description') {
                            $v[0]      = $desc;  // @phpstan-ignore-line
                            $desc_done = true;
                        }
                    }
                }
                if (!$alt_done) {
                    App::backend()->file->media_meta->addChild('AltText', $alt);
                }
                if (!$desc_done) {
                    App::backend()->file->media_meta->addChild('Description', $desc);
                }
            } else {
                // Create meta and add values
                App::backend()->file->media_meta = simplexml_load_string('<meta></meta>');
                if (App::backend()->file->media_meta) {
                    App::backend()->file->media_meta->addChild('Description', $desc);
                    App::backend()->file->media_meta->addChild('AltText', $alt);
                }
            }

            try {
                App::media()->updateFile(App::backend()->file, $newFile);

                Notices::addSuccessNotice(__('File has been successfully updated.'));
                App::backend()->page_url_params = array_merge(
                    App::backend()->page_url_params,
                    ['tab' => 'media-details-tab']
                );
                App::backend()->url()->redirect('admin.media.item', App::backend()->page_url_params);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if (!empty($_POST['thumbs']) && App::backend()->file->media_type == 'image' && App::backend()->file->editable && App::backend()->is_media_writable) {
            // Update thumbnails

            try {
                App::media()->mediaFireRecreateEvent(App::backend()->file);

                Notices::addSuccessNotice(__('Thumbnails have been successfully updated.'));
                App::backend()->page_url_params = array_merge(
                    App::backend()->page_url_params,
                    ['tab' => 'media-details-tab']
                );
                App::backend()->url()->redirect('admin.media.item', App::backend()->page_url_params);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if (!empty($_POST['unzip']) && App::backend()->file->type == 'application/zip' && App::backend()->file->editable && App::backend()->is_media_writable) {
            // Unzip file

            try {
                $unzip_dir = App::media()->inflateZipFile(App::backend()->file, $_POST['inflate_mode'] == 'new');

                Notices::addSuccessNotice(__('Zip file has been successfully extracted.'));
                App::backend()->media_page_url_params = array_merge(
                    App::backend()->media_page_url_params,
                    ['d' => $unzip_dir]
                );
                App::backend()->url()->redirect('admin.media', App::backend()->media_page_url_params);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if (!empty($_POST['save_blog_prefs'])) {
            // Save media insertion settings for the blog

            if (!empty($_POST['pref_src'])) {
                if (!($s = array_search($_POST['pref_src'], App::backend()->file->media_thumb))) {
                    $s = 'o';
                }
                App::blog()->settings()->system->put('media_img_default_size', $s);
            }
            if (!empty($_POST['pref_alignment'])) {
                App::blog()->settings()->system->put('media_img_default_alignment', $_POST['pref_alignment']);
            }
            if (!empty($_POST['pref_insertion'])) {
                App::blog()->settings()->system->put('media_img_default_link', ($_POST['pref_insertion'] == 'link'));
            }
            if (!empty($_POST['pref_legend'])) {
                App::blog()->settings()->system->put('media_img_default_legend', $_POST['pref_legend']);
            }

            Notices::addSuccessNotice(__('Default media insertion settings have been successfully updated.'));
            App::backend()->url()->redirect('admin.media.item', App::backend()->page_url_params);
        }

        if (!empty($_POST['save_folder_prefs'])) {
            // Save media insertion settings for the folder

            $prefs = [];
            if (!empty($_POST['pref_src'])) {
                if (!($s = array_search($_POST['pref_src'], App::backend()->file->media_thumb))) {
                    $s = 'o';
                }
                $prefs['size'] = $s;
            }
            if (!empty($_POST['pref_alignment'])) {
                $prefs['alignment'] = $_POST['pref_alignment'];
            }
            if (!empty($_POST['pref_insertion'])) {
                $prefs['link'] = ($_POST['pref_insertion'] == 'link');
            }
            if (!empty($_POST['pref_legend'])) {
                $prefs['legend'] = $_POST['pref_legend'];
            }

            $local = App::media()->getRoot() . '/' . dirname(App::backend()->file->relname) . '/' . '.mediadef.json';
            if (file_put_contents($local, json_encode($prefs, JSON_PRETTY_PRINT))) {
                Notices::addSuccessNotice(__('Media insertion settings have been successfully registered for this folder.'));
            }
            App::backend()->url()->redirect('admin.media.item', App::backend()->page_url_params);
        }

        if (!empty($_POST['remove_folder_prefs'])) {
            // Delete media insertion settings for the folder (.mediadef and .mediadef.json)

            $local      = App::media()->getRoot() . '/' . dirname(App::backend()->file->relname) . '/' . '.mediadef';
            $local_json = $local . '.json';
            if ((file_exists($local) && unlink($local)) || (file_exists($local_json) && unlink($local_json))) {
                Notices::addSuccessNotice(__('Media insertion settings have been successfully removed for this folder.'));
            }
            App::backend()->url()->redirect('admin.media.item', App::backend()->page_url_params);
        }

        return true;
    }

    public static function render(): void
    {
        // Display helpers

        // Function to get image alternate text
        $getImageAlt = function (?File $file, bool $fallback = true): string {
            if (!$file instanceof File) {
                return '';
            }

            // Use metadata AltText if present
            if (is_countable($file->media_meta) && count($file->media_meta) && is_iterable($file->media_meta)) {
                foreach ($file->media_meta as $k => $v) {
                    if ((string) $v && ($k == 'AltText')) {
                        return (string) $v;
                    }
                }
            }

            // Fallback to title if present
            if ($fallback && $file->media_title !== '') {
                return $file->media_title;
            }

            return '';
        };

        // Function to get image legend
        $getImageLegend = function (?File $file, $pattern, bool $dto_first = false, bool $no_date_alone = false): string {
            if (!$file instanceof File) {
                return '';
            }

            $res     = [];
            $pattern = preg_split('/\s*;;\s*/', (string) $pattern);
            $sep     = ', ';
            $dates   = 0;
            $items   = 0;

            if ($pattern) {
                foreach ($pattern as $v) {
                    if ($v === 'Title' || $v === 'Description') { // Keep Title for compatibility purpose (since 2.29)
                        if (is_countable($file->media_meta) && count($file->media_meta) && is_iterable($file->media_meta)) {
                            foreach ($file->media_meta as $k => $v) {
                                if ((string) $v && ($k == 'Description')) {
                                    $res[] = $v;
                                    $items++;

                                    break;
                                }
                            }
                        }
                    } elseif ($file->media_meta->{$v}) {
                        $res[] = (string) $file->media_meta->{$v};
                        $items++;
                    } elseif (preg_match('/^Date\((.+?)\)$/u', $v, $m)) {
                        if ($dto_first && ($file->media_meta->DateTimeOriginal != 0)) {
                            $res[] = Date::dt2str($m[1], (string) $file->media_meta->DateTimeOriginal);
                        } else {
                            $res[] = Date::str($m[1], $file->media_dt);
                        }
                        $items++;
                        $dates++;
                    } elseif (preg_match('/^DateTimeOriginal\((.+?)\)$/u', $v, $m) && $file->media_meta->DateTimeOriginal) {
                        $res[] = Date::dt2str($m[1], (string) $file->media_meta->DateTimeOriginal);
                        $items++;
                        $dates++;
                    } elseif (preg_match('/^separator\((.*?)\)$/u', $v, $m)) {
                        $sep = $m[1];
                    }
                }
            }
            if ($no_date_alone && $dates === count($res) && $dates < $items) {
                // On ne laisse pas les dates seules, sauf si ce sont les seuls items du pattern (hors séparateur)
                return '';
            }

            return implode($sep, $res);
        };

        $getImageDefaults = function (?File $file): array {
            $defaults = [
                'size'      => App::blog()->settings()->system->media_img_default_size ?: 'm',
                'alignment' => App::blog()->settings()->system->media_img_default_alignment ?: 'none',
                'link'      => (bool) App::blog()->settings()->system->media_img_default_link,
                'legend'    => App::blog()->settings()->system->media_img_default_legend ?: 'legend',
                'mediadef'  => false,
            ];

            if (!$file instanceof File) {
                return $defaults;
            }

            try {
                $local = App::media()->getRoot() . '/' . dirname($file->relname) . '/' . '.mediadef';
                if (!file_exists($local)) {
                    $local .= '.json';
                }
                if (file_exists($local) && $specifics = json_decode(file_get_contents($local) ?? '', true, 512, JSON_THROW_ON_ERROR)) {  // @phpstan-ignore-line
                    foreach (array_keys($defaults) as $key) {
                        $defaults[$key]       = $specifics[$key] ?? $defaults[$key];
                        $defaults['mediadef'] = true;
                    }
                }
            } catch (Exception) {
                // Ignore exceptions
            }

            return $defaults;
        };

        // Display page

        $starting_scripts = Page::jsModal() . Page::jsLoad('js/_media_item.js');
        if (App::backend()->popup && App::backend()->plugin_id !== '') {
            # --BEHAVIOR-- adminPopupMedia -- string
            $starting_scripts .= App::behavior()->callBehavior('adminPopupMedia', App::backend()->plugin_id);
        }
        $temp_params      = App::backend()->media_page_url_params;
        $temp_params['d'] = '%s';
        $breadcrumb       = App::media()->breadCrumb(App::backend()->url()->get('admin.media', $temp_params, '&amp;', true)) . (App::backend()->file === null ?
            '' :
            '<span class="page-title">' . App::backend()->file->basename . '</span>');
        $temp_params['d'] = '';
        $home_url         = App::backend()->url()->get('admin.media', $temp_params);
        call_user_func(
            App::backend()->open_function,
            __('Media manager'),
            $starting_scripts .
            (App::backend()->popup ? Page::jsPageTabs(App::backend()->tab) : ''),
            Page::breadcrumb(
                [
                    Html::escapeHTML(App::blog()->name()) => '',
                    __('Media manager')                   => $home_url,
                    $breadcrumb                           => '',
                ],
                [
                    'home_link' => !App::backend()->popup,
                    'hl'        => false,
                ]
            )
        );

        if (App::backend()->popup) {
            // Display notices
            echo Notices::getNotices();
        }

        if (App::backend()->file === null) {
            call_user_func(App::backend()->close_function);
            exit;
        }

        if (!empty($_GET['fupd']) || !empty($_GET['fupl'])) {
            Notices::success(__('File has been successfully updated.'));
        }
        if (!empty($_GET['thumbupd'])) {
            Notices::success(__('Thumbnails have been successfully updated.'));
        }
        if (!empty($_GET['blogprefupd'])) {
            Notices::success(__('Default media insertion settings have been successfully updated.'));
        }

        // Get major file type (first part of mime type)
        App::backend()->file_type = explode('/', App::backend()->file->type);

        if (App::backend()->select === 1) {
            // Selection mode

            // Get alternate text
            $media_alt = $getImageAlt(App::backend()->file);
            if ($media_alt == App::backend()->file->basename || Files::tidyFileName($media_alt) == App::backend()->file->basename) {
                $media_alt = '';
            }

            // Get legend
            $media_legend = $getImageLegend(
                App::backend()->file,
                App::blog()->settings()->system->media_img_title_pattern,
                (bool) App::blog()->settings()->system->media_img_use_dto_first,
                (bool) App::blog()->settings()->system->media_img_no_date_alone
            );
            if ($media_legend === $media_alt) {
                $media_legend = '';
            }

            $defaults = $getImageDefaults(App::backend()->file);

            echo
            '<div id="media-select" class="multi-part" title="' . __('Select media item') . '">' .
            '<h3>' . __('Select media item') . '</h3>' .
            '<form id="media-select-form" action="" method="get">';

            if (App::backend()->file->media_type == 'image') {
                $media_type = 'image';
                $media_alt  = $getImageAlt(App::backend()->file);
                if ($media_alt == App::backend()->file->basename || Files::tidyFileName($media_alt) == App::backend()->file->basename) {
                    $media_alt = '';
                }

                echo
                '<h3>' . __('Image size:') . '</h3> ';

                $s_checked = false;
                echo
                '<p>';
                foreach (array_reverse(App::backend()->file->media_thumb) as $s => $v) {
                    $s_checked = ($s == $defaults['size']);
                    echo
                    '<label class="classic">' .
                    form::radio(['src'], Html::escapeHTML($v), $s_checked) . ' ' .
                    App::media()->getThumbSizes()[$s][2] . '</label><br> ';
                }
                $s_checked = (!isset(App::backend()->file->media_thumb[$defaults['size']]));
                echo
                '<label class="classic">' .
                form::radio(['src'], App::backend()->file->file_url, $s_checked) . ' ' . __('original') . '</label><br> ' .
                '</p>';
            } elseif (App::backend()->file_type[0] === 'audio') {
                $media_type = 'mp3';
            } elseif (App::backend()->file_type[0] === 'video') {
                $media_type = 'flv';
            } else {
                $media_type = 'default';
            }

            echo
            '<p>' .
            '<button type="button" id="media-select-ok" class="submit">' . __('Select') . '</button> ' .
            '<button type="button" id="media-select-cancel">' . __('Cancel') . '</button>' .
            form::hidden(['type'], Html::escapeHTML($media_type)) .
            form::hidden(['title'], Html::escapeHTML($media_alt)) .
            form::hidden(['description'], Html::escapeHTML($media_legend)) .
            form::hidden(['url'], App::backend()->file->file_url) .
            '</p>' .

            '</form>' .
            '</div>';
        }

        if (App::backend()->popup && (App::backend()->select === 0)) {
            // Insertion popup

            // Get alternate text
            $media_alt = $getImageAlt(App::backend()->file);
            if ($media_alt == App::backend()->file->basename || Files::tidyFileName($media_alt) == App::backend()->file->basename) {
                $media_alt = '';
            }

            // Get legend
            $media_legend = $getImageLegend(
                App::backend()->file,
                App::blog()->settings()->system->media_img_title_pattern,
                (bool) App::blog()->settings()->system->media_img_use_dto_first,
                (bool) App::blog()->settings()->system->media_img_no_date_alone
            );
            if ($media_legend === $media_alt) {
                $media_legend = '';
            }

            $defaults = $getImageDefaults(App::backend()->file);

            echo
            '<div id="media-insert" class="multi-part" title="' . __('Insert media item') . '">' .
            '<h3>' . __('Insert media item') . '</h3>' .
            '<form id="media-insert-form" action="" method="get">';

            if (App::backend()->file->media_type == 'image') {
                $media_type = 'image';
                $media_alt  = $getImageAlt(App::backend()->file);
                if ($media_alt == App::backend()->file->basename || Files::tidyFileName($media_alt) == App::backend()->file->basename) {
                    $media_alt = '';
                }

                echo
                '<div class="two-boxes">' .
                '<h3>' . __('Image size:') . '</h3> ';
                $s_checked = false;
                echo
                '<p>';
                foreach (array_reverse(App::backend()->file->media_thumb) as $s => $v) {
                    $s_checked = ($s == $defaults['size']);
                    echo
                    '<label class="classic">' .
                    form::radio(['src'], Html::escapeHTML($v), $s_checked) . ' ' .
                    App::media()->getThumbSizes()[$s][2] . '</label><br> ';
                }
                $s_checked = (!isset(App::backend()->file->media_thumb[$defaults['size']]));
                echo
                '<label class="classic">' .
                form::radio(['src'], App::backend()->file->file_url, $s_checked) . ' ' . __('original') . '</label><br> ' .
                '</p>' .
                '</div>' .

                '<div class="two-boxes">' .
                '<h3>' . __('Image legend and alternate text') . '</h3>' .
                '<p>' .
                '<label for="legend1" class="classic">' . form::radio(
                    ['legend', 'legend1'],
                    'legend',
                    ($defaults['legend'] === 'legend' && $media_alt !== '' && $media_legend !== ''),
                    '',
                    '',
                    $media_alt !== '' && $media_legend !== '' ? false : true
                ) .
                __('Legend and alternate text') . '</label><br>' .
                '<label for="legend2" class="classic">' . form::radio(
                    ['legend', 'legend2'],
                    'title',
                    ($defaults['legend'] === 'title' && $media_alt !== ''),
                    '',
                    '',
                    $media_alt === ''
                ) .
                __('Alternate text') . '</label><br>' .
                '<label for="legend3" class="classic">' . form::radio(
                    ['legend', 'legend3'],
                    'none',
                    ($defaults['legend'] === 'none' || $media_alt === '')
                ) .
                __('None') . '</label>' .
                '</p>' .
                '<p id="media-attribute">' .
                __('Alternate text:') . ' ' . ($media_alt !== '' ? '<span class="media-title">' . $media_alt . '</span>' : __('(none)')) .
                '<br>' .
                __('Legend:') . ' ' . ($media_legend !== '' ? ' <span class="media-desc">' . $media_legend . '</span>' : __('(none)')) .
                '</p>' .
                '</div>' .

                '<div class="two-boxes">' .
                '<h3>' . __('Image alignment') . '</h3>';
                $i_align = [
                    'none'   => [__('None'), ($defaults['alignment'] == 'none' ? 1 : 0)],
                    'left'   => [__('Left'), ($defaults['alignment'] == 'left' ? 1 : 0)],
                    'right'  => [__('Right'), ($defaults['alignment'] == 'right' ? 1 : 0)],
                    'center' => [__('Center'), ($defaults['alignment'] == 'center' ? 1 : 0)],
                ];

                echo
                '<p>';
                foreach ($i_align as $k => $v) {
                    echo
                    '<label class="classic">' .
                    form::radio(['alignment'], $k, $v[1]) . ' ' . $v[0] . '</label><br> ';
                }
                echo
                '</p>' .
                '</div>' .

                '<div class="two-boxes">' .
                '<h3>' . __('Image insertion') . '</h3>' .
                '<p>' .
                '<label for="insert1" class="classic">' . form::radio(['insertion', 'insert1'], 'simple', !$defaults['link'] || $media_alt === '') .
                __('As a single image') . '</label><br>' .
                '<label for="insert2" class="classic">' . form::radio(['insertion', 'insert2'], 'link', $defaults['link'] && $media_alt !== '', '', '', $media_alt === '') .
                __('As a link to the original image') . '</label>' .
                '</p>' .
                '</div>';
            } elseif (App::backend()->file_type[0] === 'audio') {
                $media_type = 'mp3';

                echo
                '<div class="two-boxes">' .
                '<h3>' . __('MP3 disposition') . '</h3>';

                if (App::backend()->plugin_id === 'dcLegacyEditor') {
                    Notices::message(__('Please note that you cannot insert mp3 files with standard editor in WYSIWYG HTML mode.'), false);
                }

                $i_align = [
                    'none'   => [__('None'), ($defaults['alignment'] == 'none' ? 1 : 0)],
                    'left'   => [__('Left'), ($defaults['alignment'] == 'left' ? 1 : 0)],
                    'right'  => [__('Right'), ($defaults['alignment'] == 'right' ? 1 : 0)],
                    'center' => [__('Center'), ($defaults['alignment'] == 'center' ? 1 : 0)],
                ];

                echo '<p>';
                foreach ($i_align as $k => $v) {
                    echo
                    '<label class="classic">' .
                    form::radio(['alignment'], $k, $v[1]) . ' ' . $v[0] . '</label><br> ';
                }

                $url = App::backend()->file->file_url;
                if (str_starts_with($url, App::blog()->host())) {
                    $url = substr($url, strlen(App::blog()->host()));
                }
                echo
                form::hidden('blog_host', Html::escapeHTML(App::blog()->host())) .
                form::hidden('public_player', Html::escapeHTML(App::media()::audioPlayer(App::backend()->file->type, $url))) .
                '</p>' .
                '</div>';
            } elseif (App::backend()->file_type[0] === 'video') {
                $media_type = 'flv';

                if (App::backend()->plugin_id === 'dcLegacyEditor') {
                    Notices::message(__('Please note that you cannot insert video files with standard editor in WYSIWYG HTML mode.'), false);
                }

                echo
                '<div class="two-boxes">' .
                '<h3>' . __('Video size') . '</h3>' .
                '<p><label for="video_w" class="classic">' . __('Width:') . '</label> ' .
                form::number('video_w', 0, 9999, (string) App::blog()->settings()->system->media_video_width) . '  ' .
                '<label for="video_h" class="classic">' . __('Height:') . '</label> ' .
                form::number('video_h', 0, 9999, (string) App::blog()->settings()->system->media_video_height) .
                '</p>' .
                '</div>';

                echo
                '<div class="two-boxes">' .
                '<h3>' . __('Video disposition') . '</h3>';

                $i_align = [
                    'none'   => [__('None'), ($defaults['alignment'] == 'none' ? 1 : 0)],
                    'left'   => [__('Left'), ($defaults['alignment'] == 'left' ? 1 : 0)],
                    'right'  => [__('Right'), ($defaults['alignment'] == 'right' ? 1 : 0)],
                    'center' => [__('Center'), ($defaults['alignment'] == 'center' ? 1 : 0)],
                ];

                echo '<p>';
                foreach ($i_align as $k => $v) {
                    echo
                    '<label class="classic">' .
                    form::radio(['alignment'], $k, $v[1]) . ' ' . $v[0] . '</label><br> ';
                }

                $url = App::backend()->file->file_url;
                if (str_starts_with($url, App::blog()->host())) {
                    $url = substr($url, strlen(App::blog()->host()));
                }
                echo
                form::hidden('blog_host', Html::escapeHTML(App::blog()->host())) .
                form::hidden('public_player', Html::escapeHTML(App::media()::videoPlayer(App::backend()->file->type, $url))) .
                '</p>' .
                '</div>';
            } else {
                $media_type = 'default';
                $media_alt  = App::backend()->file->media_title;
                echo
                '<p>' . __('Media item will be inserted as a link.') . '</p>';
            }

            echo
            '<p class="form-buttons">' .
            '<button type="button" id="media-insert-ok" class="submit">' . __('Insert') . '</button> ' .
            '<button type="button" id="media-insert-cancel">' . __('Cancel') . '</button>' .
            form::hidden(['type'], Html::escapeHTML($media_type)) .
            form::hidden(['title'], Html::escapeHTML($media_alt)) .
            form::hidden(['description'], Html::escapeHTML($media_legend)) .
            form::hidden(['url'], App::backend()->file->file_url) .
            '</p>';

            echo
            '</form>';

            if ($media_type !== 'default') {
                echo
                '<div class="border-top">' .
                '<form id="save_settings" action="' . App::backend()->url()->getBase('admin.media.item') . '" method="post">' .
                '<p>' . __('Make current settings as default') . ' ' .
                '<input class="reset" type="submit" name="save_blog_prefs" value="' . __('For the blog') . '"> ' . __('or') . ' ' .
                '<input class="reset" type="submit" name="save_folder_prefs" value="' . __('For this folder only') . '">';

                $local = App::media()->getRoot() . '/' . dirname(App::backend()->file->relname) . '/' . '.mediadef';
                if (!file_exists($local)) {
                    $local .= '.json';
                }
                if (file_exists($local)) {
                    echo
                    '</p>' .
                    '<p>' . __('Settings exist for this folder:') . ' ' .
                    '<input class="delete" type="submit" name="remove_folder_prefs" value="' . __('Remove them') . '"> ';
                }

                echo
                form::hidden(['pref_src'], '') .
                form::hidden(['pref_alignment'], '') .
                form::hidden(['pref_insertion'], '') .
                form::hidden(['pref_legend'], '') .
                App::backend()->url()->getHiddenFormFields('admin.media.item', App::backend()->page_url_params) .
                App::nonce()->getFormNonce() . '</p>' .
                '</form></div>';
            }

            echo
            '</div>';
        }

        if (App::backend()->popup && (App::backend()->select === 0) || (App::backend()->select === 1)) {
            echo
            '<div class="multi-part" title="' . __('Media details') . '" id="media-details-tab">';
        } else {
            echo
            '<h3 class="out-of-screen-if-js">' . __('Media details') . '</h3>';
        }

        echo
        '<p id="media-icon"><img class="media-icon-square' . (App::backend()->file->media_preview ? ' media-icon-preview' : '') . '" src="' . App::backend()->file->media_icon . '?' . time() * random_int(0, mt_getrandmax()) . '" alt=""></p>' .

        '<div id="media-details">' .
        '<div class="near-icon">';

        if (App::backend()->file->media_image) {
            $thumb_size = empty($_GET['size']) ? 's' : (string) $_GET['size'];

            if (!isset(App::media()->getThumbSizes()[$thumb_size]) && $thumb_size !== 'o') {
                $thumb_size = 's';
            }

            if (isset(App::backend()->file->media_thumb[$thumb_size])) {
                $url = App::backend()->file->file_url;    // @phpstan-ignore-line
                echo
                '<p><a class="modal-image" href="' . $url . '">' .
                '<img src="' . App::backend()->file->media_thumb[$thumb_size] . '?' . time() * random_int(0, mt_getrandmax()) . '" alt="">' .
                '</a></p>';
            } elseif ($thumb_size === 'o') {
                $image_size = getimagesize(App::backend()->file->file);
                $class      = !$image_size || ($image_size[1] > 500) ? ' class="overheight"' : '';
                echo
                '<p id="media-original-image"' . $class . '><a class="modal-image" href="' . App::backend()->file->file_url . '">' .
                '<img src="' . App::backend()->file->file_url . '?' . time() * random_int(0, mt_getrandmax()) . '" alt="">' .
                '</a></p>';
            }

            echo
            '<p>' . __('Available sizes:') . ' ';
            foreach (array_keys(array_reverse(App::backend()->file->media_thumb)) as $s) {
                $strong_link = ($s === $thumb_size) ? '<strong>%s</strong>' : '%s';
                echo
                sprintf($strong_link, '<a href="' . App::backend()->url()->get('admin.media.item', array_merge(
                    App::backend()->page_url_params,
                    ['size' => $s, 'tab' => 'media-details-tab']
                )) . '">' . App::media()->getThumbSizes()[$s][2] . '</a> | ');
            }

            echo
            '<a href="' . App::backend()->url()->get('admin.media.item', array_merge(App::backend()->page_url_params, ['size' => 'o', 'tab' => 'media-details-tab'])) . '">' . __('original') . '</a>' .
            '</p>';

            if ($thumb_size !== 'o' && isset(App::backend()->file->media_thumb[$thumb_size])) {
                $path_info  = Path::info(App::backend()->file->file);   // @phpstan-ignore-line
                $thumb_tp   = App::media()->getThumbnailFilePattern($path_info['extension']);
                $thumb      = sprintf($thumb_tp, $path_info['dirname'], $path_info['base'], '%s');
                $thumb_file = sprintf($thumb, $thumb_size);
                $stats      = stat($thumb_file);
                echo
                '<h3>' . __('Thumbnail details') . '</h3>' .
                '<ul>';
                $image_size = getimagesize($thumb_file);
                if ($image_size !== false) {
                    echo
                    '<li><strong>' . __('Image width:') . '</strong> ' . $image_size[0] . ' px</li>' .
                    '<li><strong>' . __('Image height:') . '</strong> ' . $image_size[1] . ' px</li>';
                }
                if ($stats) {
                    echo
                    '<li><strong>' . __('File size:') . '</strong> ' . Files::size($stats[7]) . '</li>';
                }
                echo
                '<li><strong>' . __('File URL:') . '</strong> <a href="' . App::backend()->file->media_thumb[$thumb_size] . '">' .
                App::backend()->file->media_thumb[$thumb_size] . '</a></li>' .
                '</ul>';
            }
        }

        // Show player if relevant
        if (App::backend()->file_type[0] === 'audio') {
            echo App::media()::audioPlayer(App::backend()->file->type, App::backend()->file->file_url);
        }
        if (App::backend()->file_type[0] === 'video') {
            echo App::media()::videoPlayer(App::backend()->file->type, App::backend()->file->file_url);
        }

        echo
        '<h3>' . __('Media details') . '</h3>' .
        '<ul>' .
        '<li><strong>' . __('File owner:') . '</strong> ' . App::backend()->file->media_user . '</li>' .
        '<li><strong>' . __('File type:') . '</strong> ' . App::backend()->file->type . '</li>';
        if (App::backend()->file->media_image) {
            if (App::backend()->file->type === 'image/svg+xml') {
                if (($xmlget = simplexml_load_file(App::backend()->file->file)) !== false && $xmlattributes = $xmlget->attributes()) {
                    $image_size = [
                        (string) $xmlattributes->width,
                        (string) $xmlattributes->height,
                    ];
                    if ($image_size[0] !== '') {
                        echo
                        '<li><strong>' . __('Image width:') . '</strong> ' . $image_size[0] . '</li>';
                    }
                    if ($image_size[1] !== '') {
                        echo
                        '<li><strong>' . __('Image height:') . '</strong> ' . $image_size[1] . '</li>';
                    }
                }
            } else {
                $image_size = getimagesize(App::backend()->file->file);
                if (is_array($image_size)) {
                    echo
                    '<li><strong>' . __('Image width:') . '</strong> ' . $image_size[0] . ' px</li>' .
                    '<li><strong>' . __('Image height:') . '</strong> ' . $image_size[1] . ' px</li>';
                }
            }
        }
        echo
        '<li><strong>' . __('File size:') . '</strong> ' . Files::size(App::backend()->file->size) . '</li>' .
        '<li><strong>' . __('File URL:') . '</strong> <a href="' . App::backend()->file->file_url . '">' . App::backend()->file->file_url . '</a></li>' .
        '</ul>';

        if (empty($_GET['find_posts'])) {
            echo
            '<p><a class="button" href="' . App::backend()->url()->get('admin.media.item', array_merge(App::backend()->page_url_params, ['find_posts' => 1, 'tab' => 'media-details-tab'])) . '">' .
            __('Show entries containing this media') . '</a></p>';
        } else {
            echo
            '<h3>' . __('Entries containing this media') . '</h3>';
            /**
             * @var        string
             */
            $relname = App::con()->escape(App::backend()->file->relname);
            $params  = [
                'post_type' => '',
                'join'      => 'LEFT OUTER JOIN ' . App::con()->prefix() . App::postMedia()::POST_MEDIA_TABLE_NAME . ' PM ON P.post_id = PM.post_id ',
                'sql'       => 'AND (' .
                'PM.media_id = ' . (int) App::backend()->id . ' ' .
                "OR post_content_xhtml LIKE '%" . $relname . "%' " .
                "OR post_excerpt_xhtml LIKE '%" . $relname . "%' ",
            ];

            if (App::backend()->file->media_image) {
                // We look for thumbnails too
                if (preg_match('#^http(s)?://#', (string) App::blog()->settings()->system->public_url)) {
                    $media_root = App::blog()->settings()->system->public_url;
                } else {
                    $media_root = App::blog()->host() . Path::clean(App::blog()->settings()->system->public_url) . '/';
                }
                foreach (App::backend()->file->media_thumb as $v) {
                    /**
                     * @var        string
                     */
                    $v = App::con()->escapeStr((string) preg_replace('/^' . preg_quote($media_root, '/') . '/', '', $v)); // @phpstan-ignore-line
                    $params['sql'] .= "OR post_content_xhtml LIKE '%" . $v . "%' ";
                    $params['sql'] .= "OR post_excerpt_xhtml LIKE '%" . $v . "%' ";
                }
            }

            $params['sql'] .= ') ';

            $rs = App::blog()->getPosts($params);

            if ($rs->isEmpty()) {
                echo
                '<p>' . __('No entry seems contain this media.') . '</p>';
            } else {
                echo
                '<ul>';
                while ($rs->fetch()) {
                    $img        = '<img alt="%1$s" class="mark mark-%3$s" src="images/%2$s">';
                    $img_status = match ((int) $rs->post_status) {
                        App::blog()::POST_PUBLISHED   => sprintf($img, __('Published'), 'published.svg', 'published'),
                        App::blog()::POST_UNPUBLISHED => sprintf($img, __('Unpublished'), 'unpublished.svg', 'unpublished'),
                        App::blog()::POST_SCHEDULED   => sprintf($img, __('Scheduled'), 'scheduled.svg', 'scheduled'),
                        App::blog()::POST_PENDING     => sprintf($img, __('Pending'), 'pending.svg', 'pending'),
                        default                       => '',
                    };

                    echo
                    '<li>' . $img_status . ' ' . '<a href="' . App::postTypes()->get($rs->post_type)->adminUrl($rs->post_id) . '">' .
                    $rs->post_title . '</a>' .
                    ($rs->post_type != 'post' ? ' (' . Html::escapeHTML($rs->post_type) . ')' : '') .
                    ' - ' . Date::dt2str(__('%Y-%m-%d %H:%M'), $rs->post_dt) . '</li>';
                }
                echo
                '</ul>';
            }
        }

        $details = '';
        if (App::backend()->file->media_title !== '') {
            $details .= '<li><strong>' . __('Title') . __(':') . '</strong> ' . Html::escapeHTML((string) App::backend()->file->media_title) . '</li>';
        }
        $alttext = $getImageAlt(App::backend()->file, false);
        if ($alttext !== '') {
            $details .= '<li><strong>' . __('Alternate text:') . '</strong> ' . Html::escapeHTML($alttext) . '</li>';
        }
        if ((is_countable(App::backend()->file->media_meta) ? count(App::backend()->file->media_meta) : 0) > 0) {
            foreach (App::backend()->file->media_meta as $k => $v) {
                if ($k === 'Title' && App::backend()->file->media_title !== '' && (string) $v) {
                    // Title already displayed
                    continue;
                }

                if ($k !== 'AltText' && (string) $v) {
                    $details .= '<li><strong>' . $k . __(':') . '</strong> ' . Html::escapeHTML((string) $v) . '</li>';
                }
            }
        }
        if ($details !== '') {
            echo
            '<h3>' . __('Metadata') . '</h3>' .
            '<ul>' . $details . '</ul>';
        }

        echo
        '</div>' .

        '<h3>' . __('Updates and modifications') . '</h3>';

        if (App::backend()->file->editable && App::backend()->is_media_writable) {
            if (App::backend()->file->media_type == 'image') {
                echo
                '<form class="clear fieldset" action="' . App::backend()->url()->get('admin.media.item') . '" method="post">' .
                '<h4>' . __('Update thumbnails') . '</h4>' .
                '<p class="more-info">' . __('This will create or update thumbnails for this image.') . '</p>' .
                '<p><input type="submit" name="thumbs" value="' . __('Update thumbnails') . '">' .
                App::backend()->url()->getHiddenFormFields('admin.media.item', App::backend()->page_url_params) .
                App::nonce()->getFormNonce() . '</p>' .
                '</form>';
            }

            if (App::backend()->file->type == 'application/zip') {
                $inflate_combo = [
                    __('Extract in a new directory')   => 'new',
                    __('Extract in current directory') => 'current',
                ];

                echo
                '<form class="clear fieldset" id="file-unzip" action="' . App::backend()->url()->get('admin.media.item') . '" method="post">' .
                '<h4>' . __('Extract archive') . '</h4>' .
                '<ul>' .
                '<li><strong>' . __('Extract in a new directory') . '</strong> : ' .
                __('This will extract archive in a new directory that should not exist yet.') . '</li>' .
                '<li><strong>' . __('Extract in current directory') . '</strong> : ' .
                __('This will extract archive in current directory and will overwrite existing files or directory.') . '</li>' .
                '</ul>' .
                '<p><label for="inflate_mode" class="classic">' . __('Extract mode:') . '</label> ' .
                form::combo('inflate_mode', $inflate_combo, 'new') .
                '<input type="submit" name="unzip" value="' . __('Extract') . '">' .
                App::backend()->url()->getHiddenFormFields('admin.media.item', App::backend()->page_url_params) .
                App::nonce()->getFormNonce() . '</p>' .
                '</form>';
            }

            echo
            '<form class="clear fieldset" action="' . App::backend()->url()->get('admin.media.item') . '" method="post">' .
            '<h4>' . __('Change media properties') . '</h4>' .
            '<p><label for="media_file">' . __('File name:') . '</label>' .
            form::field('media_file', 30, 255, Html::escapeHTML(App::backend()->file->basename)) . '</p>' .
            '<p><label for="media_title">' . __('Title:') . '</label>' .
            form::field(
                'media_title',
                80,
                255,
                [
                    'default'    => Html::escapeHTML(App::backend()->file->media_title),
                    'extra_html' => 'lang="' . App::auth()->getInfo('user_lang') . '" spellcheck="true"',
                ]
            ) . '</p>';

            //            if (App::backend()->file->media_image) {
            echo
            '<p><label for="media_alt">' . __('Alternate text:') . '</label>' .
            form::textArea(
                'media_alt',
                80,
                5,
                [
                    'default'    => Html::escapeHTML($getImageAlt(App::backend()->file, false)),
                    'extra_html' => 'lang="' . App::auth()->getInfo('user_lang') . '" spellcheck="true"',
                ]
            ) . '</p>';

            echo
            '<p><label for="media_desc">' . __('Description:') . '</label>' .
            form::textArea(
                'media_desc',
                80,
                10,
                [
                    'default'    => Html::escapeHTML($getImageLegend(App::backend()->file, 'Description')),
                    'extra_html' => 'lang="' . App::auth()->getInfo('user_lang') . '" spellcheck="true"',
                ]
            ) . '</p>';
            //            }

            echo
            '<p><label for="media_dt">' . __('File date:') . '</label>' .
            form::datetime('media_dt', ['default' => Html::escapeHTML(Date::str('%Y-%m-%dT%H:%M', App::backend()->file->media_dt))]) .
            '</p>' .
            '<p><label for="media_private" class="classic">' . form::checkbox('media_private', 1, App::backend()->file->media_priv) . ' ' .
            __('Private') . '</label></p>' .
            '<p><label for="media_path">' . __('New directory:') . '</label>' .
            form::combo('media_path', App::backend()->dirs_combo, dirname(App::backend()->file->relname)) . '</p>' .
            '<p><input type="submit" accesskey="s" value="' . __('Save') . '">' .
            App::backend()->url()->getHiddenFormFields('admin.media.item', App::backend()->page_url_params) .
            App::nonce()->getFormNonce() . '</p>' .
            '</form>' .

            '<form class="clear fieldset" action="' . App::backend()->url()->get('admin.media.item') . '" method="post" enctype="multipart/form-data">' .
            '<h4>' . __('Change file') . '</h4>' .
            '<div>' . form::hidden(['MAX_FILE_SIZE'], (string) App::config()->maxUploadSize()) . '</div>' .
            '<p><label for="upfile">' . __('Choose a file:') .
            ' (' . sprintf(__('Maximum size %s'), Files::size(App::config()->maxUploadSize())) . ') ' .
            '<input type="file" id="upfile" name="upfile" size="35">' .
            '</label></p>' .
            '<p><input type="submit" value="' . __('Send') . '">' .
            App::backend()->url()->getHiddenFormFields('admin.media.item', App::backend()->page_url_params) .
            App::nonce()->getFormNonce() . '</p>' .
            '</form>';

            if (App::backend()->file->del) {
                echo
                '<form id="delete-form" method="post" action="' . App::backend()->url()->get('admin.media') . '">' .
                '<p><input name="delete" type="submit" class="delete" value="' . __('Delete this media') . '">' .
                form::hidden('remove', rawurlencode(App::backend()->file->basename)) .
                form::hidden('rmyes', 1) .
                App::backend()->url()->getHiddenFormFields('admin.media', App::backend()->media_page_url_params) .
                App::nonce()->getFormNonce() . '</p>' .
                '</form>';
            }

            # --BEHAVIOR-- adminMediaItemForm -- File
            App::behavior()->callBehavior('adminMediaItemForm', App::backend()->file);
        }

        echo
        '</div>';

        if (App::backend()->popup && (App::backend()->select === 0) || (App::backend()->select === 1)) {
            echo
            '</div>';
        } else {
            // Go back button
            echo
            '<p><input type="button" value="' . __('Back') . '" class="go-back reset hidden-if-no-js"></p>';
        }

        call_user_func(App::backend()->close_function);
    }
}
