<?php
/**
 * @since 2.27 Before as admin/media_item.php
 *
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Process\Backend;

use dcBlog;
use dcCore;
use dcMedia;
use dcPostMedia;
use dcThemes;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Core;
use Dotclear\Core\Process;
use Dotclear\Helper\Date;
use Dotclear\Helper\File\File;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Html\Html;
use Exception;
use form;
use SimpleXMLElement;

class MediaItem extends Process
{
    public static function init(): bool
    {
        Page::check(Core::auth()->makePermissions([
            Core::auth()::PERMISSION_MEDIA,
            Core::auth()::PERMISSION_MEDIA_ADMIN,
        ]));

        Core::backend()->tab = empty($_REQUEST['tab']) ? '' : $_REQUEST['tab'];

        $post_id = !empty($_REQUEST['post_id']) ? (int) $_REQUEST['post_id'] : null;
        if ($post_id) {
            $post = Core::blog()->getPosts(['post_id' => $post_id]);
            if ($post->isEmpty()) {
                $post_id = null;
            }
        }

        // Attachement type if any
        $link_type = !empty($_REQUEST['link_type']) ? $_REQUEST['link_type'] : null;

        Core::backend()->file  = null;
        Core::backend()->popup = (int) !empty($_REQUEST['popup']);

        // 0 : none, 1 : single media, >1 : multiple medias
        Core::backend()->select = !empty($_REQUEST['select']) ? (int) $_REQUEST['select'] : 0;

        Core::backend()->plugin_id = isset($_REQUEST['plugin_id']) ? Html::sanitizeURL($_REQUEST['plugin_id']) : '';

        Core::backend()->page_url_params = [
            'popup'   => Core::backend()->popup,
            'select'  => Core::backend()->select,
            'post_id' => $post_id,
        ];
        Core::backend()->media_page_url_params = [
            'popup'     => Core::backend()->popup,
            'select'    => Core::backend()->select,
            'post_id'   => $post_id,
            'link_type' => $link_type,
        ];

        if (Core::backend()->plugin_id !== '') {
            Core::backend()->page_url_params = array_merge(
                Core::backend()->page_url_params,
                ['plugin_id' => Core::backend()->plugin_id]
            );
            Core::backend()->media_page_url_params = array_merge(
                Core::backend()->media_page_url_params,
                ['plugin_id' => Core::backend()->plugin_id]
            );
        }

        Core::backend()->id = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : '';

        if (Core::backend()->id != '') {
            Core::backend()->page_url_params = array_merge(
                Core::backend()->page_url_params,
                ['id' => Core::backend()->id]
            );
        }

        if (Core::backend()->popup) {
            Core::backend()->open_function  = [Page::class, 'openPopup'];
            Core::backend()->close_function = [Page::class, 'closePopup'];
        } else {
            Core::backend()->open_function  = [Page::class, 'open'];
            Core::backend()->close_function = function () {
                Page::helpBlock('core_media');
                Page::close();
            };
        }

        Core::backend()->is_media_writable = false;

        $dirs_combo = [];

        try {
            dcCore::app()->media = new dcMedia();

            if (Core::backend()->id) {
                Core::backend()->file = dcCore::app()->media->getFile((int) Core::backend()->id);
            }

            if (Core::backend()->file === null) {
                throw new Exception(__('Not a valid file'));
            }

            dcCore::app()->media->chdir(dirname(Core::backend()->file->relname));
            Core::backend()->is_media_writable = dcCore::app()->media->writable();

            # Prepare directories combo box
            foreach (dcCore::app()->media->getDBDirs() as $v) {
                $dirs_combo['/' . $v] = $v;
            }
            # Add parent and direct childs directories if any
            dcCore::app()->media->getFSDir();
            foreach (dcCore::app()->media->dir['dirs'] as $v) {
                $dirs_combo['/' . $v->relname] = $v->relname;
            }
            ksort($dirs_combo);

            if (Core::themes()->isEmpty()) {
                # -- Loading themes, may be useful for some configurable theme --
                Core::themes()->loadModules(Core::blog()->themes_path, 'admin', Core::lang());
            }
        } catch (Exception $e) {
            Core::error()->add($e->getMessage());
        }
        Core::backend()->dirs_combo = $dirs_combo;

        return self::status(true);
    }

    public static function process(): bool
    {
        if (Core::backend()->file && !empty($_FILES['upfile']) && Core::backend()->file->editable && Core::backend()->is_media_writable) {
            // Upload a new file

            try {
                Files::uploadStatus($_FILES['upfile']);
                dcCore::app()->media->uploadFile($_FILES['upfile']['tmp_name'], Core::backend()->file->basename, true, null, false);

                Notices::addSuccessNotice(__('File has been successfully updated.'));
                Core::backend()->url->redirect('admin.media.item', Core::backend()->page_url_params);
            } catch (Exception $e) {
                Core::error()->add($e->getMessage());
            }
        }

        if (Core::backend()->file && !empty($_POST['media_file']) && Core::backend()->file->editable && Core::backend()->is_media_writable) {
            // Update file

            $newFile = clone Core::backend()->file;

            $newFile->basename = $_POST['media_file'];

            if ($_POST['media_path']) {
                $newFile->dir     = $_POST['media_path'];
                $newFile->relname = $_POST['media_path'] . '/' . $newFile->basename;
            } else {
                $newFile->dir     = '';
                $newFile->relname = $newFile->basename;
            }
            $newFile->media_title = Html::escapeHTML($_POST['media_title']);
            $newFile->media_dt    = strtotime($_POST['media_dt']);
            $newFile->media_dtstr = $_POST['media_dt'];
            $newFile->media_priv  = !empty($_POST['media_private']);

            $desc = isset($_POST['media_desc']) ? Html::escapeHTML($_POST['media_desc']) : '';

            if (Core::backend()->file->media_meta instanceof SimpleXMLElement) {
                if (count(Core::backend()->file->media_meta) > 0) {
                    foreach (Core::backend()->file->media_meta as $k => $v) {
                        if ($k == 'Description') {
                            // Update value
                            $v[0] = $desc;  // @phpstan-ignore-line

                            break;
                        }
                    }
                } else {
                    if ($desc) {
                        // Add value
                        Core::backend()->file->media_meta->addChild('Description', $desc);
                    }
                }
            } else {
                if ($desc) {
                    // Create meta and add value
                    Core::backend()->file->media_meta = simplexml_load_string('<meta></meta>');
                    Core::backend()->file->media_meta->addChild('Description', $desc);
                }
            }

            try {
                dcCore::app()->media->updateFile(Core::backend()->file, $newFile);

                Notices::addSuccessNotice(__('File has been successfully updated.'));
                Core::backend()->page_url_params = array_merge(
                    Core::backend()->page_url_params,
                    ['tab' => 'media-details-tab']
                );
                Core::backend()->url->redirect('admin.media.item', Core::backend()->page_url_params);
            } catch (Exception $e) {
                Core::error()->add($e->getMessage());
            }
        }

        if (!empty($_POST['thumbs']) && Core::backend()->file->media_type == 'image' && Core::backend()->file->editable && Core::backend()->is_media_writable) {
            // Update thumbnails

            try {
                dcCore::app()->media->mediaFireRecreateEvent(Core::backend()->file);

                Notices::addSuccessNotice(__('Thumbnails have been successfully updated.'));
                Core::backend()->page_url_params = array_merge(
                    Core::backend()->page_url_params,
                    ['tab' => 'media-details-tab']
                );
                Core::backend()->url->redirect('admin.media.item', Core::backend()->page_url_params);
            } catch (Exception $e) {
                Core::error()->add($e->getMessage());
            }
        }

        if (!empty($_POST['unzip']) && Core::backend()->file->type == 'application/zip' && Core::backend()->file->editable && Core::backend()->is_media_writable) {
            // Unzip file

            try {
                $unzip_dir = dcCore::app()->media->inflateZipFile(Core::backend()->file, $_POST['inflate_mode'] == 'new');

                Notices::addSuccessNotice(__('Zip file has been successfully extracted.'));
                Core::backend()->media_page_url_params = array_merge(
                    Core::backend()->media_page_url_params,
                    ['d' => $unzip_dir]
                );
                Core::backend()->url->redirect('admin.media', Core::backend()->media_page_url_params);
            } catch (Exception $e) {
                Core::error()->add($e->getMessage());
            }
        }

        if (!empty($_POST['save_blog_prefs'])) {
            // Save media insertion settings for the blog

            if (!empty($_POST['pref_src'])) {
                if (!($s = array_search($_POST['pref_src'], Core::backend()->file->media_thumb))) {
                    $s = 'o';
                }
                Core::blog()->settings->system->put('media_img_default_size', $s);
            }
            if (!empty($_POST['pref_alignment'])) {
                Core::blog()->settings->system->put('media_img_default_alignment', $_POST['pref_alignment']);
            }
            if (!empty($_POST['pref_insertion'])) {
                Core::blog()->settings->system->put('media_img_default_link', ($_POST['pref_insertion'] == 'link'));
            }
            if (!empty($_POST['pref_legend'])) {
                Core::blog()->settings->system->put('media_img_default_legend', $_POST['pref_legend']);
            }

            Notices::addSuccessNotice(__('Default media insertion settings have been successfully updated.'));
            Core::backend()->url->redirect('admin.media.item', Core::backend()->page_url_params);
        }

        if (!empty($_POST['save_folder_prefs'])) {
            // Save media insertion settings for the folder

            $prefs = [];
            if (!empty($_POST['pref_src'])) {
                if (!($s = array_search($_POST['pref_src'], Core::backend()->file->media_thumb))) {
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

            $local = dcCore::app()->media->root . '/' . dirname(Core::backend()->file->relname) . '/' . '.mediadef.json';
            if (file_put_contents($local, json_encode($prefs, JSON_PRETTY_PRINT))) {
                Notices::addSuccessNotice(__('Media insertion settings have been successfully registered for this folder.'));
            }
            Core::backend()->url->redirect('admin.media.item', Core::backend()->page_url_params);
        }

        if (!empty($_POST['remove_folder_prefs'])) {
            // Delete media insertion settings for the folder (.mediadef and .mediadef.json)

            $local      = dcCore::app()->media->root . '/' . dirname(Core::backend()->file->relname) . '/' . '.mediadef';
            $local_json = $local . '.json';
            if ((file_exists($local) && unlink($local)) || (file_exists($local_json) && unlink($local_json))) {
                Notices::addSuccessNotice(__('Media insertion settings have been successfully removed for this folder.'));
            }
            Core::backend()->url->redirect('admin.media.item', Core::backend()->page_url_params);
        }

        return true;
    }

    public static function render(): void
    {
        // Display helpers

        # Function to get image title based on meta
        $getImageTitle = function (?File $file, $pattern, bool $dto_first = false, bool $no_date_alone = false): string {
            if (!$file) {
                return '';
            }

            $res     = [];
            $pattern = preg_split('/\s*;;\s*/', $pattern);
            $sep     = ', ';
            $dates   = 0;
            $items   = 0;

            foreach ($pattern as $v) {
                if ($v == 'Title') {
                    if ($file->media_title != '') {
                        $res[] = $file->media_title;
                    }
                    $items++;
                } elseif ($file->media_meta->{$v}) {
                    if ((string) $file->media_meta->{$v} != '') {
                        $res[] = (string) $file->media_meta->{$v};
                    }
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
            if ($no_date_alone && $dates == count($res) && $dates < $items) {
                // On ne laisse pas les dates seules, sauf si ce sont les seuls items du pattern (hors séparateur)
                return '';
            }

            return implode($sep, $res);
        };

        $getImageDescription = function (?File $file, string $default = ''): string {
            if (!$file) {
                return (string) $default;
            }

            if ((is_countable($file->media_meta) ? count($file->media_meta) : 0) > 0) {
                foreach ($file->media_meta as $k => $v) {
                    if ((string) $v && ($k == 'Description')) {
                        return (string) $v;
                    }
                }
            }

            return (string) $default;
        };

        $getImageDefaults = function (?File $file): array {
            $defaults = [
                'size'      => Core::blog()->settings->system->media_img_default_size ?: 'm',
                'alignment' => Core::blog()->settings->system->media_img_default_alignment ?: 'none',
                'link'      => (bool) Core::blog()->settings->system->media_img_default_link,
                'legend'    => Core::blog()->settings->system->media_img_default_legend ?: 'legend',
                'mediadef'  => false,
            ];

            if (!$file) {
                return $defaults;
            }

            try {
                $local = dcCore::app()->media->root . '/' . dirname($file->relname) . '/' . '.mediadef';
                if (!file_exists($local)) {
                    $local .= '.json';
                }
                if (file_exists($local) && $specifics = json_decode(file_get_contents($local) ?? '', true, 512, JSON_THROW_ON_ERROR)) {  // @phpstan-ignore-line
                    foreach (array_keys($defaults) as $key) {
                        $defaults[$key]       = $specifics[$key] ?? $defaults[$key];
                        $defaults['mediadef'] = true;
                    }
                }
            } catch (Exception $e) {
                // Ignore exceptions
            }

            return $defaults;
        };

        // Display page

        $starting_scripts = Page::jsModal() . Page::jsLoad('js/_media_item.js');
        if (Core::backend()->popup && Core::backend()->plugin_id !== '') {
            # --BEHAVIOR-- adminPopupMedia -- string
            $starting_scripts .= Core::behavior()->callBehavior('adminPopupMedia', Core::backend()->plugin_id);
        }
        $temp_params      = Core::backend()->media_page_url_params;
        $temp_params['d'] = '%s';
        $breadcrumb       = dcCore::app()->media->breadCrumb(Core::backend()->url->get('admin.media', $temp_params, '&amp;', true)) . (Core::backend()->file === null ?
            '' :
            '<span class="page-title">' . Core::backend()->file->basename . '</span>');
        $temp_params['d'] = '';
        $home_url         = Core::backend()->url->get('admin.media', $temp_params);
        call_user_func(
            Core::backend()->open_function,
            __('Media manager'),
            $starting_scripts .
            (Core::backend()->popup ? Page::jsPageTabs(Core::backend()->tab) : ''),
            Page::breadcrumb(
                [
                    Html::escapeHTML(Core::blog()->name) => '',
                    __('Media manager')                  => $home_url,
                    $breadcrumb                          => '',
                ],
                [
                    'home_link' => !Core::backend()->popup,
                    'hl'        => false,
                ]
            )
        );

        if (Core::backend()->popup) {
            // Display notices
            echo Notices::getNotices();
        }

        if (Core::backend()->file === null) {
            call_user_func(Core::backend()->close_function);
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
        Core::backend()->file_type = explode('/', Core::backend()->file->type);

        if (Core::backend()->select === 1) {
            // Selection mode

            // Let user choose thumbnail size if image
            $media_title = Core::backend()->file->media_title;
            if ($media_title == Core::backend()->file->basename || Files::tidyFileName($media_title) == Core::backend()->file->basename) {
                $media_title = '';
            }

            $media_desc = $getImageDescription(Core::backend()->file, (string) $media_title);
            $defaults   = $getImageDefaults(Core::backend()->file);

            echo
            '<div id="media-select" class="multi-part" title="' . __('Select media item') . '">' .
            '<h3>' . __('Select media item') . '</h3>' .
            '<form id="media-select-form" action="" method="get">';

            if (Core::backend()->file->media_type == 'image') {
                $media_type  = 'image';
                $media_title = $getImageTitle(
                    Core::backend()->file,
                    Core::blog()->settings->system->media_img_title_pattern,
                    (bool) Core::blog()->settings->system->media_img_use_dto_first,
                    (bool) Core::blog()->settings->system->media_img_no_date_alone
                );
                if ($media_title == Core::backend()->file->basename || Files::tidyFileName($media_title) == Core::backend()->file->basename) {
                    $media_title = '';
                }

                echo
                '<h3>' . __('Image size:') . '</h3> ';

                $s_checked = false;
                echo
                '<p>';
                foreach (array_reverse(Core::backend()->file->media_thumb) as $s => $v) {
                    $s_checked = ($s == $defaults['size']);
                    echo
                    '<label class="classic">' .
                    form::radio(['src'], Html::escapeHTML($v), $s_checked) . ' ' .
                    dcCore::app()->media->thumb_sizes[$s][2] . '</label><br /> ';
                }
                $s_checked = (!isset(Core::backend()->file->media_thumb[$defaults['size']]));
                echo
                '<label class="classic">' .
                form::radio(['src'], Core::backend()->file->file_url, $s_checked) . ' ' . __('original') . '</label><br /> ' .
                '</p>';
            } elseif (Core::backend()->file_type[0] == 'audio') {
                $media_type = 'mp3';
            } elseif (Core::backend()->file_type[0] == 'video') {
                $media_type = 'flv';
            } else {
                $media_type = 'default';
            }

            echo
            '<p>' .
            '<button type="button" id="media-select-ok" class="submit">' . __('Select') . '</button> ' .
            '<button type="button" id="media-select-cancel">' . __('Cancel') . '</button>' .
            form::hidden(['type'], Html::escapeHTML($media_type)) .
            form::hidden(['title'], Html::escapeHTML($media_title)) .
            form::hidden(['description'], Html::escapeHTML($media_desc)) .
            form::hidden(['url'], Core::backend()->file->file_url) .
            '</p>' .

            '</form>' .
            '</div>';
        }

        if (Core::backend()->popup && (Core::backend()->select === 0)) {
            // Insertion popup

            $media_title = Core::backend()->file->media_title;
            if ($media_title == Core::backend()->file->basename || Files::tidyFileName($media_title) == Core::backend()->file->basename) {
                $media_title = '';
            }

            $media_desc = $getImageDescription(Core::backend()->file, (string) $media_title);
            $defaults   = $getImageDefaults(Core::backend()->file);

            echo
            '<div id="media-insert" class="multi-part" title="' . __('Insert media item') . '">' .
            '<h3>' . __('Insert media item') . '</h3>' .
            '<form id="media-insert-form" action="" method="get">';

            if (Core::backend()->file->media_type == 'image') {
                $media_type  = 'image';
                $media_title = $getImageTitle(
                    Core::backend()->file,
                    Core::blog()->settings->system->media_img_title_pattern,
                    (bool) Core::blog()->settings->system->media_img_use_dto_first,
                    (bool) Core::blog()->settings->system->media_img_no_date_alone
                );
                if ($media_title == Core::backend()->file->basename || Files::tidyFileName($media_title) == Core::backend()->file->basename) {
                    $media_title = '';
                }

                echo
                '<div class="two-boxes">' .
                '<h3>' . __('Image size:') . '</h3> ';
                $s_checked = false;
                echo
                '<p>';
                foreach (array_reverse(Core::backend()->file->media_thumb) as $s => $v) {
                    $s_checked = ($s == $defaults['size']);
                    echo
                    '<label class="classic">' .
                    form::radio(['src'], Html::escapeHTML($v), $s_checked) . ' ' .
                    dcCore::app()->media->thumb_sizes[$s][2] . '</label><br /> ';
                }
                $s_checked = (!isset(Core::backend()->file->media_thumb[$defaults['size']]));
                echo
                '<label class="classic">' .
                form::radio(['src'], Core::backend()->file->file_url, $s_checked) . ' ' . __('original') . '</label><br /> ' .
                '</p>' .
                '</div>' .

                '<div class="two-boxes">' .
                '<h3>' . __('Image legend and title') . '</h3>' .
                '<p>' .
                '<label for="legend1" class="classic">' . form::radio(
                    ['legend', 'legend1'],
                    'legend',
                    ($defaults['legend'] == 'legend')
                ) .
                __('Legend and title') . '</label><br />' .
                '<label for="legend2" class="classic">' . form::radio(
                    ['legend', 'legend2'],
                    'title',
                    ($defaults['legend'] == 'title')
                ) .
                __('Title') . '</label><br />' .
                '<label for="legend3" class="classic">' . form::radio(
                    ['legend', 'legend3'],
                    'none',
                    ($defaults['legend'] == 'none')
                ) .
                __('None') . '</label>' .
                '</p>' .
                '<p id="media-attribute">' .
                __('Title: ') . ($media_title != '' ? '<span class="media-title">' . $media_title . '</span>' : __('(none)')) .
                '<br />' .
                __('Legend: ') . ($media_desc != '' ? ' <span class="media-desc">' . $media_desc . '</span>' : __('(none)')) .
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
                    form::radio(['alignment'], $k, $v[1]) . ' ' . $v[0] . '</label><br /> ';
                }
                echo
                '</p>' .
                '</div>' .

                '<div class="two-boxes">' .
                '<h3>' . __('Image insertion') . '</h3>' .
                '<p>' .
                '<label for="insert1" class="classic">' . form::radio(['insertion', 'insert1'], 'simple', !$defaults['link']) .
                __('As a single image') . '</label><br />' .
                '<label for="insert2" class="classic">' . form::radio(['insertion', 'insert2'], 'link', $defaults['link']) .
                __('As a link to the original image') . '</label>' .
                '</p>' .
                '</div>';
            } elseif (Core::backend()->file_type[0] == 'audio') {
                $media_type = 'mp3';

                echo
                '<div class="two-boxes">' .
                '<h3>' . __('MP3 disposition') . '</h3>';
                Notices::message(__('Please note that you cannot insert mp3 files with visual editor.'), false);

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
                    form::radio(['alignment'], $k, $v[1]) . ' ' . $v[0] . '</label><br /> ';
                }

                $url = Core::backend()->file->file_url;
                if (substr($url, 0, strlen(Core::blog()->host)) === Core::blog()->host) {
                    $url = substr($url, strlen(Core::blog()->host));
                }
                echo
                form::hidden('blog_host', Html::escapeHTML(Core::blog()->host)) .
                form::hidden('public_player', Html::escapeHTML(dcMedia::audioPlayer(Core::backend()->file->type, $url))) .
                '</p>' .
                '</div>';
            } elseif (Core::backend()->file_type[0] == 'video') {
                $media_type = 'flv';

                Notices::message(__('Please note that you cannot insert video files with visual editor.'), false);

                echo
                '<div class="two-boxes">' .
                '<h3>' . __('Video size') . '</h3>' .
                '<p><label for="video_w" class="classic">' . __('Width:') . '</label> ' .
                form::number('video_w', 0, 9999, (string) Core::blog()->settings->system->media_video_width) . '  ' .
                '<label for="video_h" class="classic">' . __('Height:') . '</label> ' .
                form::number('video_h', 0, 9999, (string) Core::blog()->settings->system->media_video_height) .
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
                    form::radio(['alignment'], $k, $v[1]) . ' ' . $v[0] . '</label><br /> ';
                }

                $url = Core::backend()->file->file_url;
                if (substr($url, 0, strlen(Core::blog()->host)) === Core::blog()->host) {
                    $url = substr($url, strlen(Core::blog()->host));
                }
                echo
                form::hidden('blog_host', Html::escapeHTML(Core::blog()->host)) .
                form::hidden('public_player', Html::escapeHTML(dcMedia::videoPlayer(Core::backend()->file->type, $url))) .
                '</p>' .
                '</div>';
            } else {
                $media_type  = 'default';
                $media_title = Core::backend()->file->media_title;
                echo
                '<p>' . __('Media item will be inserted as a link.') . '</p>';
            }

            echo
            '<p>' .
            '<button type="button" id="media-insert-ok" class="submit">' . __('Insert') . '</button> ' .
            '<button type="button" id="media-insert-cancel">' . __('Cancel') . '</button>' .
            form::hidden(['type'], Html::escapeHTML($media_type)) .
            form::hidden(['title'], Html::escapeHTML($media_title)) .
            form::hidden(['description'], Html::escapeHTML($media_desc)) .
            form::hidden(['url'], Core::backend()->file->file_url) .
            '</p>';

            echo
            '</form>';

            if ($media_type != 'default') {
                echo
                '<div class="border-top">' .
                '<form id="save_settings" action="' . Core::backend()->url->getBase('admin.media.item') . '" method="post">' .
                '<p>' . __('Make current settings as default') . ' ' .
                '<input class="reset" type="submit" name="save_blog_prefs" value="' . __('For the blog') . '" /> ' . __('or') . ' ' .
                '<input class="reset" type="submit" name="save_folder_prefs" value="' . __('For this folder only') . '" />';

                $local = dcCore::app()->media->root . '/' . dirname(Core::backend()->file->relname) . '/' . '.mediadef';
                if (!file_exists($local)) {
                    $local .= '.json';
                }
                if (file_exists($local)) {
                    echo
                    '</p>' .
                    '<p>' . __('Settings exist for this folder:') . ' ' .
                    '<input class="delete" type="submit" name="remove_folder_prefs" value="' . __('Remove them') . '" /> ';
                }

                echo
                form::hidden(['pref_src'], '') .
                form::hidden(['pref_alignment'], '') .
                form::hidden(['pref_insertion'], '') .
                form::hidden(['pref_legend'], '') .
                Core::backend()->url->getHiddenFormFields('admin.media.item', Core::backend()->page_url_params) .
                Core::nonce()->getFormNonce() . '</p>' .
                '</form></div>';
            }

            echo
            '</div>';
        }

        if (Core::backend()->popup && (Core::backend()->select === 0) || (Core::backend()->select === 1)) {
            echo
            '<div class="multi-part" title="' . __('Media details') . '" id="media-details-tab">';
        } else {
            echo
            '<h3 class="out-of-screen-if-js">' . __('Media details') . '</h3>';
        }

        echo
        '<p id="media-icon"><img class="media-icon-square' . (Core::backend()->file->media_preview ? ' media-icon-preview' : '') . '" src="' . Core::backend()->file->media_icon . '?' . time() * random_int(0, mt_getrandmax()) . '" alt="" /></p>' .

        '<div id="media-details">' .
        '<div class="near-icon">';

        if (Core::backend()->file->media_image) {
            $thumb_size = !empty($_GET['size']) ? (string) $_GET['size'] : 's';

            if (!isset(dcCore::app()->media->thumb_sizes[$thumb_size]) && $thumb_size !== 'o') {
                $thumb_size = 's';
            }

            if (isset(Core::backend()->file->media_thumb[$thumb_size])) {
                $url = Core::backend()->file->file_url;    // @phpstan-ignore-line
                echo
                '<p><a class="modal-image" href="' . $url . '">' .
                '<img src="' . Core::backend()->file->media_thumb[$thumb_size] . '?' . time() * random_int(0, mt_getrandmax()) . '" alt="" />' .
                '</a></p>';
            } elseif ($thumb_size === 'o') {
                $image_size = getimagesize(Core::backend()->file->file);
                $class      = !$image_size || ($image_size[1] > 500) ? ' class="overheight"' : '';
                echo
                '<p id="media-original-image"' . $class . '><a class="modal-image" href="' . Core::backend()->file->file_url . '">' .
                '<img src="' . Core::backend()->file->file_url . '?' . time() * random_int(0, mt_getrandmax()) . '" alt="" />' .
                '</a></p>';
            }

            echo
            '<p>' . __('Available sizes:') . ' ';
            foreach (array_reverse(Core::backend()->file->media_thumb) as $s => $v) {
                $strong_link = ($s === $thumb_size) ? '<strong>%s</strong>' : '%s';
                echo
                sprintf($strong_link, '<a href="' . Core::backend()->url->get('admin.media.item', array_merge(
                    Core::backend()->page_url_params,
                    ['size' => $s, 'tab' => 'media-details-tab']
                )) . '">' . dcCore::app()->media->thumb_sizes[$s][2] . '</a> | ');
            }

            echo
            '<a href="' . Core::backend()->url->get('admin.media.item', array_merge(Core::backend()->page_url_params, ['size' => 'o', 'tab' => 'media-details-tab'])) . '">' . __('original') . '</a>' .
            '</p>';

            if ($thumb_size !== 'o' && isset(Core::backend()->file->media_thumb[$thumb_size])) {
                $path_info = Path::info(Core::backend()->file->file);   // @phpstan-ignore-line
                $alpha     = ($path_info['extension'] == 'png') || ($path_info['extension'] == 'PNG');
                $alpha     = strtolower($path_info['extension']) === 'png';
                $webp      = strtolower($path_info['extension']) === 'webp';
                $thumb_tp  = ($alpha ?
                    dcCore::app()->media->thumb_tp_alpha :
                    ($webp ?
                        dcCore::app()->media->thumb_tp_webp :
                        dcCore::app()->media->thumb_tp));
                $thumb      = sprintf($thumb_tp, $path_info['dirname'], $path_info['base'], '%s');
                $thumb_file = sprintf($thumb, $thumb_size);
                $image_size = getimagesize($thumb_file);
                $stats      = stat($thumb_file);
                echo
                '<h3>' . __('Thumbnail details') . '</h3>' .
                '<ul>';
                if (is_array($image_size)) {
                    echo
                    '<li><strong>' . __('Image width:') . '</strong> ' . $image_size[0] . ' px</li>' .
                    '<li><strong>' . __('Image height:') . '</strong> ' . $image_size[1] . ' px</li>';
                }
                echo
                '<li><strong>' . __('File size:') . '</strong> ' . Files::size($stats[7]) . '</li>' .
                '<li><strong>' . __('File URL:') . '</strong> <a href="' . Core::backend()->file->media_thumb[$thumb_size] . '">' .
                Core::backend()->file->media_thumb[$thumb_size] . '</a></li>' .
                '</ul>';
            }
        }

        // Show player if relevant
        if (Core::backend()->file_type[0] == 'audio') {
            echo dcMedia::audioPlayer(Core::backend()->file->type, Core::backend()->file->file_url);
        }
        if (Core::backend()->file_type[0] == 'video') {
            echo dcMedia::videoPlayer(Core::backend()->file->type, Core::backend()->file->file_url);
        }

        echo
        '<h3>' . __('Media details') . '</h3>' .
        '<ul>' .
        '<li><strong>' . __('File owner:') . '</strong> ' . Core::backend()->file->media_user . '</li>' .
        '<li><strong>' . __('File type:') . '</strong> ' . Core::backend()->file->type . '</li>';
        if (Core::backend()->file->media_image) {
            $image_size = getimagesize(Core::backend()->file->file);
            if (is_array($image_size)) {
                echo
                '<li><strong>' . __('Image width:') . '</strong> ' . $image_size[0] . ' px</li>' .
                '<li><strong>' . __('Image height:') . '</strong> ' . $image_size[1] . ' px</li>';
            }
        }
        echo
        '<li><strong>' . __('File size:') . '</strong> ' . Files::size(Core::backend()->file->size) . '</li>' .
        '<li><strong>' . __('File URL:') . '</strong> <a href="' . Core::backend()->file->file_url . '">' . Core::backend()->file->file_url . '</a></li>' .
        '</ul>';

        if (empty($_GET['find_posts'])) {
            echo
            '<p><a class="button" href="' . Core::backend()->url->get('admin.media.item', array_merge(Core::backend()->page_url_params, ['find_posts' => 1, 'tab' => 'media-details-tab'])) . '">' .
            __('Show entries containing this media') . '</a></p>';
        } else {
            echo
            '<h3>' . __('Entries containing this media') . '</h3>';
            $params = [
                'post_type' => '',
                'join'      => 'LEFT OUTER JOIN ' . Core::con()->prefix() . dcPostMedia::POST_MEDIA_TABLE_NAME . ' PM ON P.post_id = PM.post_id ',
                'sql'       => 'AND (' .
                'PM.media_id = ' . (int) Core::backend()->id . ' ' .
                "OR post_content_xhtml LIKE '%" . Core::con()->escape(Core::backend()->file->relname) . "%' " .
                "OR post_excerpt_xhtml LIKE '%" . Core::con()->escape(Core::backend()->file->relname) . "%' ",
            ];

            if (Core::backend()->file->media_image) {
                // We look for thumbnails too
                if (preg_match('#^http(s)?://#', (string) Core::blog()->settings->system->public_url)) {
                    $media_root = Core::blog()->settings->system->public_url;
                } else {
                    $media_root = Core::blog()->host . Path::clean(Core::blog()->settings->system->public_url) . '/';
                }
                foreach (Core::backend()->file->media_thumb as $v) {
                    $v = preg_replace('/^' . preg_quote($media_root, '/') . '/', '', $v);
                    $params['sql'] .= "OR post_content_xhtml LIKE '%" . Core::con()->escape($v) . "%' ";
                    $params['sql'] .= "OR post_excerpt_xhtml LIKE '%" . Core::con()->escape($v) . "%' ";
                }
            }

            $params['sql'] .= ') ';

            $rs = Core::blog()->getPosts($params);

            if ($rs->isEmpty()) {
                echo
                '<p>' . __('No entry seems contain this media.') . '</p>';
            } else {
                echo
                '<ul>';
                while ($rs->fetch()) {
                    $img        = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
                    $img_status = match ((int) $rs->post_status) {
                        dcBlog::POST_PUBLISHED   => sprintf($img, __('published'), 'check-on.png'),
                        dcBlog::POST_UNPUBLISHED => sprintf($img, __('unpublished'), 'check-off.png'),
                        dcBlog::POST_SCHEDULED   => sprintf($img, __('scheduled'), 'scheduled.png'),
                        dcBlog::POST_PENDING     => sprintf($img, __('pending'), 'check-wrn.png'),
                        default                  => '',
                    };

                    echo
                    '<li>' . $img_status . ' ' . '<a href="' . Core::postTypes()->get($rs->post_type)->adminUrl($rs->post_id) . '">' .
                    $rs->post_title . '</a>' .
                    ($rs->post_type != 'post' ? ' (' . Html::escapeHTML($rs->post_type) . ')' : '') .
                    ' - ' . Date::dt2str(__('%Y-%m-%d %H:%M'), $rs->post_dt) . '</li>';
                }
                echo
                '</ul>';
            }
        }

        if (Core::backend()->file->media_image) {
            echo
            '<h3>' . __('Image details') . '</h3>';

            $details = '';
            if ((is_countable(Core::backend()->file->media_meta) ? count(Core::backend()->file->media_meta) : 0) > 0) {
                foreach (Core::backend()->file->media_meta as $k => $v) {
                    if ((string) $v) {
                        $details .= '<li><strong>' . $k . ':</strong> ' . Html::escapeHTML((string) $v) . '</li>';
                    }
                }
            }
            if ($details) {
                echo
                '<ul>' . $details . '</ul>';
            } else {
                echo
                '<p>' . __('No detail') . '</p>';
            }
        }

        echo
        '</div>' .

        '<h3>' . __('Updates and modifications') . '</h3>';

        if (Core::backend()->file->editable && Core::backend()->is_media_writable) {
            if (Core::backend()->file->media_type == 'image') {
                echo
                '<form class="clear fieldset" action="' . Core::backend()->url->get('admin.media.item') . '" method="post">' .
                '<h4>' . __('Update thumbnails') . '</h4>' .
                '<p class="more-info">' . __('This will create or update thumbnails for this image.') . '</p>' .
                '<p><input type="submit" name="thumbs" value="' . __('Update thumbnails') . '" />' .
                Core::backend()->url->getHiddenFormFields('admin.media.item', Core::backend()->page_url_params) .
                Core::nonce()->getFormNonce() . '</p>' .
                '</form>';
            }

            if (Core::backend()->file->type == 'application/zip') {
                $inflate_combo = [
                    __('Extract in a new directory')   => 'new',
                    __('Extract in current directory') => 'current',
                ];

                echo
                '<form class="clear fieldset" id="file-unzip" action="' . Core::backend()->url->get('admin.media.item') . '" method="post">' .
                '<h4>' . __('Extract archive') . '</h4>' .
                '<ul>' .
                '<li><strong>' . __('Extract in a new directory') . '</strong> : ' .
                __('This will extract archive in a new directory that should not exist yet.') . '</li>' .
                '<li><strong>' . __('Extract in current directory') . '</strong> : ' .
                __('This will extract archive in current directory and will overwrite existing files or directory.') . '</li>' .
                '</ul>' .
                '<p><label for="inflate_mode" class="classic">' . __('Extract mode:') . '</label> ' .
                form::combo('inflate_mode', $inflate_combo, 'new') .
                '<input type="submit" name="unzip" value="' . __('Extract') . '" />' .
                Core::backend()->url->getHiddenFormFields('admin.media.item', Core::backend()->page_url_params) .
                Core::nonce()->getFormNonce() . '</p>' .
                '</form>';
            }

            echo
            '<form class="clear fieldset" action="' . Core::backend()->url->get('admin.media.item') . '" method="post">' .
            '<h4>' . __('Change media properties') . '</h4>' .
            '<p><label for="media_file">' . __('File name:') . '</label>' .
            form::field('media_file', 30, 255, Html::escapeHTML(Core::backend()->file->basename)) . '</p>' .
            '<p><label for="media_title">' . __('File title:') . '</label>' .
            form::field(
                'media_title',
                30,
                255,
                [
                    'default'    => Html::escapeHTML(Core::backend()->file->media_title),
                    'extra_html' => 'lang="' . Core::auth()->getInfo('user_lang') . '" spellcheck="true"',
                ]
            ) . '</p>';

            if (Core::backend()->file->media_image) {
                echo
                '<p><label for="media_desc">' . __('File description:') . '</label>' .
                form::field(
                    'media_desc',
                    60,
                    255,
                    [
                        'default'    => Html::escapeHTML($getImageDescription(Core::backend()->file, '')),
                        'extra_html' => 'lang="' . Core::auth()->getInfo('user_lang') . '" spellcheck="true"',
                    ]
                ) . '</p>' .
                '<p><label for="media_dt">' . __('File date:') . '</label>';
            }

            echo
            form::datetime('media_dt', ['default' => Html::escapeHTML(Date::str('%Y-%m-%dT%H:%M', Core::backend()->file->media_dt))]) .
            '</p>' .
            '<p><label for="media_private" class="classic">' . form::checkbox('media_private', 1, Core::backend()->file->media_priv) . ' ' .
            __('Private') . '</label></p>' .
            '<p><label for="media_path">' . __('New directory:') . '</label>' .
            form::combo('media_path', Core::backend()->dirs_combo, dirname(Core::backend()->file->relname)) . '</p>' .
            '<p><input type="submit" accesskey="s" value="' . __('Save') . '" />' .
            Core::backend()->url->getHiddenFormFields('admin.media.item', Core::backend()->page_url_params) .
            Core::nonce()->getFormNonce() . '</p>' .
            '</form>' .

            '<form class="clear fieldset" action="' . Core::backend()->url->get('admin.media.item') . '" method="post" enctype="multipart/form-data">' .
            '<h4>' . __('Change file') . '</h4>' .
            '<div>' . form::hidden(['MAX_FILE_SIZE'], (string) DC_MAX_UPLOAD_SIZE) . '</div>' .
            '<p><label for="upfile">' . __('Choose a file:') .
            ' (' . sprintf(__('Maximum size %s'), Files::size((int) DC_MAX_UPLOAD_SIZE)) . ') ' .
            '<input type="file" id="upfile" name="upfile" size="35" />' .
            '</label></p>' .
            '<p><input type="submit" value="' . __('Send') . '" />' .
            Core::backend()->url->getHiddenFormFields('admin.media.item', Core::backend()->page_url_params) .
            Core::nonce()->getFormNonce() . '</p>' .
            '</form>';

            if (Core::backend()->file->del) {
                echo
                '<form id="delete-form" method="post" action="' . Core::backend()->url->getBase('admin.media.item') . '">' .
                '<p><input name="delete" type="submit" class="delete" value="' . __('Delete this media') . '" />' .
                form::hidden('remove', rawurlencode(Core::backend()->file->basename)) .
                form::hidden('rmyes', 1) .
                Core::backend()->url->getHiddenFormFields('admin.media.item', Core::backend()->media_page_url_params) .
                Core::nonce()->getFormNonce() . '</p>' .
                '</form>';
            }

            # --BEHAVIOR-- adminMediaItemForm -- File
            Core::behavior()->callBehavior('adminMediaItemForm', Core::backend()->file);
        }

        echo
        '</div>';

        if (Core::backend()->popup && (Core::backend()->select === 0) || (Core::backend()->select === 1)) {
            echo
            '</div>';
        } else {
            // Go back button
            echo
            '<p><input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" /></p>';
        }

        call_user_func(Core::backend()->close_function);
    }
}
