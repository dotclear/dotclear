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
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Date;
use Dotclear\Helper\File\File;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Html\Form\Btn;
use Dotclear\Helper\Html\Form\Button;
use Dotclear\Helper\Html\Form\Capture;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Datetime;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\File as FormFile;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Number;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Radio;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Single;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Textarea;
use Dotclear\Helper\Html\Form\Ul;
use Dotclear\Helper\Html\Html;
use Exception;
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
            (new Text('span', App::backend()->file->basename))->class('page-title')->render());
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

        $parts = [];

        // Insertion/Selection part

        if (App::backend()->select === 1) {
            // Selection mode

            // Get alternate text
            $media_alt = App::media()->getMediaAlt(App::backend()->file);

            // Get legend
            $media_legend = App::media()->getMediaLegend(
                App::backend()->file,
                App::blog()->settings()->system->media_img_title_pattern,
                (bool) App::blog()->settings()->system->media_img_use_dto_first,
                (bool) App::blog()->settings()->system->media_img_no_date_alone
            );
            if ($media_legend === $media_alt) {
                $media_legend = '';
            }

            $defaults = $getImageDefaults(App::backend()->file);

            $part_image_size = (new None());
            if (App::backend()->file->media_type == 'image') {
                $media_type = 'image';

                // Image sizes
                $image_sizes = function () use ($defaults) {
                    foreach (array_reverse(App::backend()->file->media_thumb) as $key => $value) {
                        yield (new Radio(['src'], $defaults['size'] === $key))
                            ->value(Html::escapeHTML($value))
                            ->label(new Label(App::media()->getThumbSizes()[$key][2], Label::IL_FT));
                    }
                };
                $part_image_size = (new Set())
                    ->items([
                        (new Text('h3', __('Image size:'))),
                        (new Para())
                            ->items([
                                ... $image_sizes(),
                                (new Radio(['src'], !isset(App::backend()->file->media_thumb[$defaults['size']])))
                                    ->value(App::backend()->file->file_url)
                                    ->label(new Label(__('original'), Label::IL_FT)),
                            ]),
                    ]);
            } elseif (App::backend()->file_type[0] === 'audio') {
                $media_type = 'mp3';
            } elseif (App::backend()->file_type[0] === 'video') {
                $media_type = 'flv';
            } else {
                $media_type = 'default';
            }

            $parts[] = (new Div('media-select'))
                ->class('multi-part')
                ->title(__('Select media item'))
                ->items([
                    (new Text('h3', __('Select media item'))),
                    (new Form('media-select-form'))
                        ->method('get')
                        ->action('')
                        ->fields([
                            $part_image_size,
                            (new Para())
                                ->class('form-buttons')
                                ->items([
                                    (new Btn('media-select-ok', __('Select')))
                                        ->class('submit'),
                                    (new Btn('media-select-cancel', __('Cancel'))),
                                    (new Hidden(['type'], Html::escapeHTML($media_type))),
                                    (new Hidden(['title'], Html::escapeHTML($media_alt))),
                                    (new Hidden(['description'], Html::escapeHTML($media_legend))),
                                    (new Hidden(['url'], App::backend()->file->file_url)),
                                ]),
                        ]),
                ]);
        }

        if (App::backend()->popup && (App::backend()->select === 0)) {
            // Insertion popup

            // Get alternate text
            $media_alt = App::media()->getMediaAlt(App::backend()->file);

            // Get legend
            $media_legend = App::media()->getMediaLegend(
                App::backend()->file,
                App::blog()->settings()->system->media_img_title_pattern,
                (bool) App::blog()->settings()->system->media_img_use_dto_first,
                (bool) App::blog()->settings()->system->media_img_no_date_alone
            );
            if ($media_legend === $media_alt) {
                $media_legend = '';
            }

            // Get title
            $media_title = App::media()->getMediaTitle(App::backend()->file, false);

            $defaults = $getImageDefaults(App::backend()->file);

            // Image alignments
            $i_align = [
                'none'   => [__('None'), $defaults['alignment'] === 'none'],
                'left'   => [__('Left'), $defaults['alignment'] === 'left'],
                'right'  => [__('Right'), $defaults['alignment'] === 'right'],
                'center' => [__('Center'), $defaults['alignment'] === 'center'],
            ];
            $image_alignments = function () use ($i_align) {
                foreach ($i_align as $key => $value) {
                    yield (new Radio(['alignment'], $value[1]))
                        ->value($key)
                        ->label(new Label($value[0], Label::IL_FT));
                }
            };

            $media_insert_options = (new None());
            if (App::backend()->file->media_type == 'image') {
                $media_type = 'image';

                // Image sizes
                $image_sizes = function () use ($defaults) {
                    foreach (array_reverse(App::backend()->file->media_thumb) as $key => $value) {
                        yield (new Radio(['src'], $defaults['size'] === $key))
                            ->value(Html::escapeHTML($value))
                            ->label(new Label(App::media()->getThumbSizes()[$key][2], Label::IL_FT));
                    }
                };

                $media_insert_options = (new Set())
                    ->items([
                        (new Div())
                            ->class('two-boxes')
                            ->items([
                                (new Text('h3', __('Image size:'))),
                                (new Para())
                                    ->items([
                                        ... $image_sizes(),
                                        (new Radio(['src'], !isset(App::backend()->file->media_thumb[$defaults['size']])))
                                            ->value(App::backend()->file->file_url)
                                            ->label(new Label(__('original'), Label::IL_FT)),
                                    ]),
                            ]),
                        (new Div())
                            ->class('two-boxes')
                            ->items([
                                (new Text('h3', __('Image legend and alternate text'))),
                                (new Para())
                                    ->items([
                                        (new Radio(['legend', 'legend1'], $defaults['legend'] === 'legend' && $media_alt !== '' && $media_legend !== ''))
                                            ->value('legend')
                                            ->disabled($media_alt !== '' && $media_legend !== '' ? false : true)
                                            ->label(new Label(__('Legend and alternate text'), Label::IL_FT)),
                                        (new Radio(['legend', 'legend2'], $defaults['legend'] === 'title' && $media_alt !== ''))
                                            ->value('title')
                                            ->disabled($media_alt === '')
                                            ->label(new Label(__('Alternate text'), Label::IL_FT)),
                                        (new Radio(['legend', 'legend3'], $defaults['legend'] === 'none' || $media_alt === ''))
                                            ->value('none')
                                            ->label(new Label(__('None'), Label::IL_FT)),
                                    ]),
                                (new Para('media-attribute'))
                                    ->items([
                                        (new Text(null, __('Alternate text:') . ' ' . ($media_alt !== '' ?
                                            (new Text('span', $media_alt))
                                                ->class('media-title') :
                                            (new Text(null, __('(none)'))))->render())),
                                        (new Single('br')),
                                        (new Text(null, __('Legend:') . ' ' . ($media_legend !== '' ?
                                            (new Text('span', $media_legend))
                                                ->class('media-desc') :
                                            (new Text(null, __('(none)'))))->render())),
                                    ]),
                            ]),
                        (new Div())
                            ->class('two-boxes')
                            ->items([
                                (new Text('h3', __('Image alignment'))),
                                (new Para())
                                    ->items($image_alignments()),
                            ]),
                        (new Div())
                            ->class('two-boxes')
                            ->items([
                                (new Text('h3', __('Image insertion'))),
                                (new Para())
                                    ->items([
                                        (new Radio(['insertion', 'insert1'], !$defaults['link'] || $media_alt === ''))
                                            ->value('simple')
                                            ->label(new Label(__('As a single image'), Label::IL_FT)),
                                        (new Radio(['insertion', 'insert2'], $defaults['link'] && $media_alt !== ''))
                                            ->value('link')
                                            ->label(new Label(__('As a link to the original image'), Label::IL_FT))
                                            ->disabled($media_alt === ''),
                                    ]),
                            ]),
                    ]);
            } elseif (App::backend()->file_type[0] === 'audio') {
                $media_type = 'mp3';

                $url = App::backend()->file->file_url;
                if (str_starts_with($url, App::blog()->host())) {
                    $url = substr($url, strlen(App::blog()->host()));
                }

                $media_insert_options = (new Set())
                    ->items([
                        (new Div())
                            ->items([
                                (new Text('h3', __('MP3 disposition'))),
                                (new Para())
                                    ->items([
                                        ... $image_alignments(),
                                        (new Hidden('blog_host', Html::escapeHTML(App::blog()->host()))),
                                        (new Hidden('public_player', Html::escapeHTML(App::media()::audioPlayer(App::backend()->file->type, $url, alt: $media_alt, descr: $media_legend)))),
                                    ]),
                                (new Note())
                                    ->class('warning')
                                    ->text(__('Please note that you cannot insert mp3 files with standard editor in WYSIWYG HTML mode.')),
                            ]),
                    ]);
            } elseif (App::backend()->file_type[0] === 'video') {
                $media_type = 'flv';

                $url = App::backend()->file->file_url;
                if (str_starts_with($url, App::blog()->host())) {
                    $url = substr($url, strlen(App::blog()->host()));
                }

                $media_insert_options = (new Set())
                    ->items([
                        (new Div())
                            ->class('two-boxes')
                            ->items([
                                (new Text('h3', __('Video size'))),
                                (new Para())
                                    ->items([
                                        (new Number('video_w', 0, 9999, (int) App::blog()->settings()->system->media_video_width))
                                            ->label(new Label(__('Width:'), Label::IL_TF)),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Number('video_h', 0, 9999, (int) App::blog()->settings()->system->media_video_height))
                                            ->label(new Label(__('Height:'), Label::IL_TF)),
                                    ]),
                            ]),
                        (new Div())
                            ->class('two-boxes')
                            ->items([
                                (new Text('h3', __('Video disposition'))),
                                (new Para())
                                    ->items([
                                        ... $image_alignments(),
                                        (new Hidden('blog_host', Html::escapeHTML(App::blog()->host()))),
                                        (new Hidden('public_player', Html::escapeHTML(App::media()::videoPlayer(App::backend()->file->type, $url, alt: $media_alt, descr: $media_legend)))),
                                    ]),
                            ]),
                        (new Note())
                            ->class('warning')
                            ->text(__('Please note that you cannot insert video files with standard editor in WYSIWYG HTML mode.')),
                    ]);
            } else {
                $media_type = 'default';

                $media_insert_options = (new Note())
                    ->text(__('Media item will be inserted as a link.'))
                    ->class('info');
            }

            $save_settings = (new None());
            if ($media_type !== 'default') {
                $local = App::media()->getRoot() . '/' . dirname(App::backend()->file->relname) . '/' . '.mediadef';
                if (!file_exists($local)) {
                    $local .= '.json';
                }

                $save_settings = (new Div())
                    ->class('border-top')
                    ->items([
                        (new Form('save_settings'))
                            ->method('post')
                            ->action(App::backend()->url()->getBase('admin.media.item'))
                            ->fields([
                                (new Para())
                                    ->class('form-buttons')
                                    ->items([
                                        (new Text(null, __('Make current settings as default'))),
                                        (new Submit('save_blog_prefs', __('For the blog')))
                                            ->class('reset'),
                                        (new Submit('save_folder_prefs', __('For this folder only')))
                                            ->class('reset'),
                                        (new Hidden(['pref_src'], '')),
                                        (new Hidden(['pref_alignment'], '')),
                                        (new Hidden(['pref_insertion'], '')),
                                        (new Hidden(['pref_legend'], '')),
                                        ... App::backend()->url()->hiddenFormFields('admin.media.item', App::backend()->page_url_params),
                                        App::nonce()->formNonce(),
                                    ]),
                                (file_exists($local)) ? (new Para())
                                    ->class('form-buttons')
                                    ->items([
                                        (new Text(null, __('Settings exist for this folder:'))),
                                        (new Submit('remove_folder_prefs', __('Remove them')))
                                            ->class('delete'),
                                    ])
                                : (new None()),
                            ]),
                    ]);
            }

            $parts[] = (new Div('media-insert'))
                ->class('multi-part')
                ->title(__('Insert media item'))
                ->items([
                    (new Text('h3', __('Insert media item'))),
                    (new Form('media-insert-form'))
                        ->method('get')
                        ->action('')
                        ->fields([
                            $media_insert_options,
                            (new Para())
                                ->class('form-buttons')
                                ->items([
                                    (new Btn('media-insert-ok', __('Insert')))
                                        ->class('submit'),
                                    (new Btn('media-insert-cancel', __('Cancel'))),
                                    (new Hidden(['type'], Html::escapeHTML($media_type))),
                                    (new Hidden(['real_title'], Html::escapeHTML($media_title))),
                                    (new Hidden(['title'], Html::escapeHTML($media_alt))),
                                    (new Hidden(['description'], Html::escapeHTML($media_legend))),
                                    (new Hidden(['url'], App::backend()->file->file_url)),
                                ]),
                        ]),
                    $save_settings,
                ]);
        }

        // Details part

        $media_details = [];

        $media_details[] = (new Para('media-icon'))
            ->items([
                (new Img(App::backend()->file->media_icon . '?' . time() * random_int(0, mt_getrandmax())))
                    ->class(App::backend()->file->media_preview ? 'media-icon-square' : ''),
            ]);

        $media_details_display = (new None());
        if (App::backend()->file->media_image) {
            $thumb_size = empty($_GET['size']) ? 's' : (string) $_GET['size'];

            if (!isset(App::media()->getThumbSizes()[$thumb_size]) && $thumb_size !== 'o') {
                $thumb_size = 's';
            }

            $image_infos = [];

            if (isset(App::backend()->file->media_thumb[$thumb_size])) {
                $image_infos[] = (new Para())
                    ->items([
                        (new Link())
                            ->href(App::backend()->file->file_url)  // @phpstan-ignore-line (undefined property object::$file_url)
                            ->class('modal-image')
                            ->items([
                                (new Img(App::backend()->file->media_thumb[$thumb_size] . '?' . time() * random_int(0, mt_getrandmax())))
                                    ->alt(''),
                            ]),
                    ]);
            } elseif ($thumb_size === 'o') {
                $image_size = getimagesize(App::backend()->file->file);

                $image_infos[] = (new Para('media-original-image'))
                    ->class(!$image_size || ($image_size[1] > 500) ? 'overheight' : '')
                    ->items([
                        (new Link())
                            ->href(App::backend()->file->file_url)
                            ->class('modal-image')
                            ->items([
                                (new Img(App::backend()->file->file_url . '?' . time() * random_int(0, mt_getrandmax())))
                                    ->alt(''),
                            ]),
                    ]);
            }

            $available_sizes = function () use ($thumb_size) {
                foreach (array_keys(array_reverse(App::backend()->file->media_thumb)) as $key) {
                    yield (new Text(
                        $key === $thumb_size ? 'strong' : null,
                        (new Link())
                            ->href(App::backend()->url()->get('admin.media.item', array_merge(App::backend()->page_url_params, ['size' => $key, 'tab' => 'media-details-tab'])))
                            ->text(App::media()->getThumbSizes()[$key][2])
                        ->render()
                    ));
                }
            };
            $image_infos[] = (new Para())
                ->separator(' ')
                ->items([
                    (new Text(null, __('Available sizes:'))),
                    (new Set())
                        ->separator(' | ')
                        ->items([
                            ... $available_sizes(),
                            (new Link())
                                ->href(App::backend()->url()->get('admin.media.item', array_merge(App::backend()->page_url_params, ['size' => 'o', 'tab' => 'media-details-tab'])))
                                ->text(__('original')),
                        ]),
                ]);

            if ($thumb_size !== 'o' && isset(App::backend()->file->media_thumb[$thumb_size])) {
                $path_info  = Path::info(App::backend()->file->file);   // @phpstan-ignore-line
                $thumb_tp   = App::media()->getThumbnailFilePattern($path_info['extension']);
                $thumb      = sprintf($thumb_tp, $path_info['dirname'], $path_info['base'], '%s');
                $thumb_file = sprintf($thumb, $thumb_size);
                $stats      = stat($thumb_file);

                $infos_list = [];

                $image_size = getimagesize($thumb_file);
                if ($image_size !== false) {
                    $infos_list[] = (new Li())
                        ->separator(' ')
                        ->items([
                            (new Text('strong', __('Image width:'))),
                            (new Text(null, $image_size[0] . 'px')),
                        ]);
                    $infos_list[] = (new Li())
                        ->separator(' ')
                        ->items([
                            (new Text('strong', __('Image height:'))),
                            (new Text(null, $image_size[1] . 'px')),
                        ]);
                }
                if ($stats) {
                    $infos_list[] = (new Li())
                        ->separator(' ')
                        ->items([
                            (new Text('strong', __('File size:'))),
                            (new Text(null, Files::size($stats[7]))),
                        ]);
                }

                $infos_list[] = (new Li())
                    ->separator(' ')
                    ->items([
                        (new Text('strong', __('File URL:'))),
                        (new Link())
                            ->href(App::backend()->file->media_thumb[$thumb_size])
                            ->text(App::backend()->file->media_thumb[$thumb_size]),
                    ]);

                $image_infos[] = (new Set())
                    ->items([
                        (new Text('h3', __('Thumbnail details'))),
                        (new Ul())
                            ->items($infos_list),
                    ]);
            }

            $media_details_display = (new Set())
                ->items($image_infos);
        }

        // Show player if relevant
        if (App::backend()->file_type[0] === 'audio') {
            $media_details_display = (new Text(null, App::media()::audioPlayer(App::backend()->file->type, App::backend()->file->file_url)));
        }
        if (App::backend()->file_type[0] === 'video') {
            $media_details_display = (new Text(null, App::media()::videoPlayer(App::backend()->file->type, App::backend()->file->file_url)));
        }

        $infos_list = [];

        $infos_list[] = (new Li())
            ->separator(' ')
            ->items([
                (new Text('strong', __('File owner:'))),
                (new Text(null, App::backend()->file->media_user)),
            ]);
        $infos_list[] = (new Li())
            ->separator(' ')
            ->items([
                (new Text('strong', __('File type:'))),
                (new Text(null, App::backend()->file->type)),
            ]);

        if (App::backend()->file->media_image) {
            if (App::backend()->file->type === 'image/svg+xml') {
                if (($xmlget = simplexml_load_file(App::backend()->file->file)) !== false && $xmlattributes = $xmlget->attributes()) {
                    $image_size = [
                        (string) $xmlattributes->width,
                        (string) $xmlattributes->height,
                    ];
                    if ($image_size[0] !== '') {
                        $infos_list[] = (new Li())
                            ->separator(' ')
                            ->items([
                                (new Text('strong', __('Image width:'))),
                                (new Text(null, $image_size[0])),
                            ]);
                    }
                    if ($image_size[1] !== '') {
                        $infos_list[] = (new Li())
                            ->separator(' ')
                            ->items([
                                (new Text('strong', __('Image width:'))),
                                (new Text(null, $image_size[1])),
                            ]);
                    }
                }
            } else {
                $image_size = getimagesize(App::backend()->file->file);
                if (is_array($image_size)) {
                    $infos_list[] = (new Li())
                        ->separator(' ')
                        ->items([
                            (new Text('strong', __('Image width:'))),
                            (new Text(null, $image_size[0] . 'px')),
                        ]);
                    $infos_list[] = (new Li())
                        ->separator(' ')
                        ->items([
                            (new Text('strong', __('Image height:'))),
                            (new Text(null, $image_size[1] . 'px')),
                        ]);
                }
            }
        }

        $infos_list[] = (new Li())
            ->separator(' ')
            ->items([
                (new Text('strong', __('File size:'))),
                (new Text(null, Files::size(App::backend()->file->size))),
            ]);
        $infos_list[] = (new Li())
            ->separator(' ')
            ->items([
                (new Text('strong', __('File URL:'))),
                (new Link())
                    ->href(App::backend()->file->file_url)
                    ->text(App::backend()->file->file_url),
            ]);

        if (empty($_GET['find_posts'])) {
            $media_entries = (new Para())
                ->items([
                    (new Link())
                        ->href(App::backend()->url()->get('admin.media.item', array_merge(App::backend()->page_url_params, ['find_posts' => 1, 'tab' => 'media-details-tab'])))
                        ->text(__('Show entries containing this media')),
                ]);
        } else {
            /**
             * @var        string
             */
            $relname = App::con()->escape(App::backend()->file->relname);

            // 1st, look inside entries content
            $params = [
                'post_type' => '',
                'sql'       => 'AND (' .
                "post_content_xhtml LIKE '%" . $relname . "%' " .
                "OR post_excerpt_xhtml LIKE '%" . $relname . "%' ",
            ];

            if (App::backend()->file->media_image) {
                // We look for thumbnails too
                if (preg_match('#^http(s)?://#', (string) App::blog()->settings()->system->public_url)) {
                    $media_root = App::blog()->settings()->system->public_url;
                } else {
                    $media_root = App::blog()->host() . Path::clean(App::blog()->settings()->system->public_url) . '/';
                }
                foreach (App::backend()->file->media_thumb as $value) {
                    /**
                     * @var        string
                     */
                    $value = App::con()->escapeStr((string) preg_replace('/^' . preg_quote($media_root, '/') . '/', '', $value)); // @phpstan-ignore-line
                    $params['sql'] .= "OR post_content_xhtml LIKE '%" . $value . "%' ";
                    $params['sql'] .= "OR post_excerpt_xhtml LIKE '%" . $value . "%' ";
                }
            }

            $params['sql'] .= ') ';

            $rsInside = App::blog()->getPosts($params);

            // 2nd, look inside entries attachments (any kind)
            $params = [
                'post_type' => '',
                'join'      => 'LEFT OUTER JOIN ' . App::con()->prefix() . App::postMedia()::POST_MEDIA_TABLE_NAME . ' PM ON P.post_id = PM.post_id ',
                'sql'       => 'AND (PM.media_id = ' . (int) App::backend()->id . ')',
            ];

            $rsLinked = App::blog()->getPosts($params);

            if ($rsInside->isEmpty() && $rsLinked->isEmpty()) {
                $entries = (new Note())
                    ->text(__('No entry seems contain this media.'));
            } else {
                $entriesList = function (MetaRecord $rs): Ul {
                    $list = [];
                    while ($rs->fetch()) {
                        $list[] = (new Li())
                            ->separator(' ')
                            ->items([
                                App::status()->post()->image((int) $rs->post_status),
                                (new Link())
                                    ->href(App::postTypes()->get($rs->post_type)->adminUrl($rs->post_id))
                                    ->text($rs->post_title),
                                ($rs->post_type !== 'post' ?
                                    (new Text(null, '(' . Html::escapeHTML($rs->post_type) . ')')) :
                                    (new None())),
                                (new Text(null, '-')),
                                (new Text(null, Date::dt2str(__('%Y-%m-%d %H:%M'), $rs->post_dt))),
                            ]);
                    }

                    return (new Ul())
                        ->items($list);
                };

                $entries = (new Ul())
                    ->items([
                        $rsInside->isEmpty() ?
                        (new None()) :
                        (new Li())
                            ->items([
                                (new Text(null, __('Inside the entry'))),
                                $entriesList($rsInside),
                            ]),
                        $rsLinked->isEmpty() ?
                        (new None()) :
                        (new Li())
                            ->items([
                                (new Text(null, __('Linked to entry'))),
                                $entriesList($rsLinked),
                            ]),
                    ]);
            }

            $media_entries = (new Set())
                ->items([
                    (new Text('h3', __('Entries containing this media'))),
                    $entries,
                ]);
        }

        $metadata = [];
        if (App::backend()->file->media_title !== '') {
            $metadata[] = (new Li())
                ->separator(' ')
                ->items([
                    (new Text('strong', __('Title'))),
                    (new Text(null, Html::escapeHTML((string) App::backend()->file->media_title))),
                ]);
        }
        $alttext = App::media()->getMediaAlt(App::backend()->file, false);
        if ($alttext !== '') {
            $metadata[] = (new Li())
                ->separator(' ')
                ->items([
                    (new Text('strong', __('Alternate text:'))),
                    (new Text(null, Html::escapeHTML($alttext))),
                ]);
        }
        if ((is_countable(App::backend()->file->media_meta) ? count(App::backend()->file->media_meta) : 0) > 0) {
            foreach (App::backend()->file->media_meta as $k => $value) {
                if ($k === 'Title' && App::backend()->file->media_title !== '' && (string) $value) {
                    // Title already displayed
                    continue;
                }

                if ($k !== 'AltText' && (string) $value) {
                    $metadata[] = (new Li())
                        ->separator(' ')
                        ->items([
                            (new Text('strong', $k . __(':'))),
                            (new Text(null, Html::escapeHTML((string) $value))),
                        ]);
                }
            }
        }
        $media_metadata = (new None());
        if ($metadata !== []) {
            $media_metadata = (new Set())
                ->items([
                    (new Text('h3', __('Metadata'))),
                    (new Ul())
                        ->items($metadata),
                ]);
        }

        $media_details[] = (new Div('media_details'))
            ->items([
                (new Div())
                    ->class('near-icon')
                    ->items([
                        $media_details_display,
                        (new Text('h3', __('Media details'))),
                        (new Ul())
                            ->items($infos_list),
                        $media_entries,
                        $media_metadata,
                    ]),
            ]);

        // Media actions
        $actions = [];

        if (App::backend()->file->editable && App::backend()->is_media_writable) {
            if (App::backend()->file->media_type == 'image') {
                $actions[] = (new Form('update-thumbnails-form'))
                    ->method('post')
                    ->action(App::backend()->url()->get('admin.media.item'))
                    ->class(['clear', 'fieldset'])
                    ->fields([
                        (new Text('h4', __('Update thumbnails'))),
                        (new Note())
                            ->text(__('This will create or update thumbnails for this image.'))
                            ->class('more-info'),
                        (new Para())
                            ->class('form-buttons')
                            ->items([
                                (new Submit('thumbs', __('Update thumbnails'))),
                                ... App::backend()->url()->hiddenFormFields('admin.media.item', App::backend()->page_url_params),
                                App::nonce()->formNonce(),
                            ]),
                    ]);
            }

            if (App::backend()->file->type == 'application/zip') {
                $inflate_combo = [
                    __('Extract in a new directory')   => 'new',
                    __('Extract in current directory') => 'current',
                ];

                $actions[] = (new Form('file-unzip'))
                    ->method('post')
                    ->action(App::backend()->url()->get('admin.media.item'))
                    ->class(['clear', 'fieldset'])
                    ->fields([
                        (new Text('h4', __('Extract archive'))),
                        (new Ul())
                            ->items([
                                (new Li())
                                    ->separator(' : ')
                                    ->items([
                                        (new Text('strong', __('Extract in a new directory'))),
                                        (new Text(null, __('This will extract archive in a new directory that should not exist yet.'))),
                                    ]),
                                (new Li())
                                    ->separator(' : ')
                                    ->items([
                                        (new Text('strong', __('Extract in current directory'))),
                                        (new Text(null, __('This will extract archive in current directory and will overwrite existing files or directory.'))),
                                    ]),
                            ]),
                        (new Para())
                            ->items([
                                (new Select('inflate_mode'))
                                    ->items($inflate_combo)
                                    ->default('new')
                                    ->label(new Label(__('Extract mode:'), Label::IL_TF)),
                                (new Submit('unzip', __('Extract'))),
                                ... App::backend()->url()->hiddenFormFields('admin.media.item', App::backend()->page_url_params),
                                App::nonce()->formNonce(),
                            ]),
                    ]);
            }

            $actions[] = (new Form('change-properties-form'))
                ->method('post')
                ->action(App::backend()->url()->get('admin.media.item'))
                ->class(['clear', 'fieldset'])
                ->fields([
                    (new Text('h4', __('Change media properties'))),
                    (new Para())
                        ->items([
                            (new Para())
                                ->items([
                                    (new Input('media_file'))
                                        ->size(30)
                                        ->maxlength(255)
                                        ->value(Html::escapeHTML(App::backend()->file->basename))
                                        ->label(new Label(__('File name:'), Label::OL_TF)),
                                ]),
                            (new Para())
                                ->items([
                                    (new Input('media_title'))
                                        ->size(80)
                                        ->maxlength(255)
                                        ->value(Html::escapeHTML(App::backend()->file->media_title))
                                        ->label(new Label(__('Title:'), Label::OL_TF))
                                        ->lang(App::auth()->getInfo('user_lang'))
                                        ->spellcheck(true),
                                ]),
                            (new Para())
                                ->items([
                                    (new Textarea('media_alt', Html::escapeHTML(App::media()->getMediaAlt(App::backend()->file, false))))
                                        ->cols(80)
                                        ->rows(5)
                                        ->label(new Label(__('Alternate text:'), Label::OL_TF))
                                        ->lang(App::auth()->getInfo('user_lang'))
                                        ->spellcheck(true),
                                ]),
                            (new Para())
                                ->items([
                                    (new Textarea('media_desc', Html::escapeHTML(App::media()->getMediaLegend(App::backend()->file, 'Description'))))
                                        ->cols(80)
                                        ->rows(5)
                                        ->label(new Label(__('Description:'), Label::OL_TF))
                                        ->lang(App::auth()->getInfo('user_lang'))
                                        ->spellcheck(true),
                                ]),
                            (new Para())
                                ->items([
                                    (new Datetime('media_dt', Html::escapeHTML(Date::str('%Y-%m-%dT%H:%M', App::backend()->file->media_dt))))
                                        ->label(new Label(__('File date:'), Label::OL_TF)),
                                ]),
                            (new Para())
                                ->items([
                                    (new Checkbox('media_private', App::backend()->file->media_priv))
                                        ->value('1')
                                        ->label(new Label(__('Private'), Label::IL_FT)),
                                ]),
                            (new Para())
                                ->items([
                                    (new Select('media_path'))
                                        ->items(App::backend()->dirs_combo)
                                        ->default(dirname(App::backend()->file->relname))
                                        ->label(new Label(__('New directory:'), Label::OL_TF)),
                                ]),
                            (new Submit('change-properties-submit', __('Save')))
                                ->accesskey('s'),
                            ... App::backend()->url()->hiddenFormFields('admin.media.item', App::backend()->page_url_params),
                            App::nonce()->formNonce(),
                        ]),
                ]);

            $actions[] = (new Form('change-file-form'))
                ->method('post')
                ->action(App::backend()->url()->get('admin.media.item'))
                ->enctype('multipart/form-data')
                ->class(['clear', 'fieldset'])
                ->fields([
                    (new Text('h4', __('Change file'))),
                    (new Div())
                        ->items([
                            (new Hidden(['MAX_FILE_SIZE'], (string) App::config()->maxUploadSize())),
                        ]),
                    (new Para())
                        ->items([
                            (new FormFile('upfile'))
                                ->size(35)
                                ->label(new Label(__('Choose a file:') . ' (' . sprintf(__('Maximum size %s'), Files::size(App::config()->maxUploadSize())) . ')', Label::IL_TF)),
                        ]),
                    (new Para())
                        ->items([
                            (new Submit('change-file-submit')),
                            ... App::backend()->url()->hiddenFormFields('admin.media.item', App::backend()->page_url_params),
                            App::nonce()->formNonce(),
                        ]),
                ]);

            if (App::backend()->file->del) {
                $actions[] = (new Form('delete-form'))
                    ->method('post')
                    ->action(App::backend()->url()->get('admin.media'))
                    ->fields([
                        (new Para())
                            ->class('form-buttons')
                            ->items([
                                (new Submit('delete', __('Delete this media')))
                                    ->class('delete'),
                                (new Hidden('remove', rawurlencode(App::backend()->file->basename))),
                                (new Hidden('rmyes', '1')),
                                ... App::backend()->url()->hiddenFormFields('admin.media', App::backend()->media_page_url_params),
                                App::nonce()->formNonce(),
                            ]),
                    ]);
            }

            $actions[] = (new Capture(
                # --BEHAVIOR-- adminMediaItemForm -- File
                App::behavior()->callBehavior(...),
                ['adminMediaItemForm', App::backend()->file]
            ));
        }

        $media_action = (new Div())
            ->items([
                (new Text('h3', __('Updates and modifications'))),
                ... $actions,
            ]);

        if (App::backend()->popup && (App::backend()->select === 0) || (App::backend()->select === 1)) {
            $parts[] = (new Set())
                ->items([
                    (new Div('media-details-tab'))
                        ->class('multi-part')
                        ->title(__('Media details'))
                        ->items([
                            ... $media_details,
                            $media_action,
                        ]),
                ]);
        } else {
            $parts[] = (new Set())
                ->items([
                    (new Text('h3', __('Media details')))
                        ->class('out-of-screen-if-js'),
                    ... $media_details,
                    $media_action,
                    (new Para())
                        ->items([
                            // Go back button
                            (new Button('back'))
                                ->class(['go-back', 'reset', 'hidden-if-no-js'])
                                ->value(__('Back')),
                        ]),
                ]);
        }

        echo (new Set())
            ->items($parts)
        ->render();

        call_user_func(App::backend()->close_function);
    }
}
