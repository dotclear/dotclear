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
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\JoinStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Helper\Date;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\MediaFile;
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
use Dotclear\Helper\Html\Form\Option;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Radio;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Single;
use Dotclear\Helper\Html\Form\Span;
use Dotclear\Helper\Html\Form\Strong;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Textarea;
use Dotclear\Helper\Html\Form\Ul;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Process\TraitProcess;
use Exception;
use SimpleXMLElement;

/**
 * @since 2.27 Before as admin/media_item.php
 */
class MediaItem
{
    use TraitProcess;

    protected static MediaFile $file;
    protected static bool $file_loaded;
    protected static string $file_type;

    protected static string $tab;
    protected static bool $popup;
    protected static int $select;
    protected static string $plugin_id;
    protected static int $id;
    protected static bool $is_media_writable;

    /**
     * @var array<string, mixed> $page_url_params
     */
    protected static array $page_url_params;

    /**
     * @var array<string, string> $dirs_combo
     */
    protected static array $dirs_combo;

    /**
     * @var array<string, mixed> $media_page_url_params
     */
    protected static array $media_page_url_params;

    public static function init(): bool
    {
        App::backend()->page()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_MEDIA,
            App::auth()::PERMISSION_MEDIA_ADMIN,
        ]));

        $post_id = isset($_REQUEST['post_id']) && is_numeric($post_id = $_REQUEST['post_id']) ? (int) $post_id : 0;
        if ($post_id !== 0) {
            $post = App::blog()->getPosts(['post_id' => $post_id]);
            if ($post->isEmpty()) {
                $post_id = null;
            }
        }

        // Attachement type if any
        $link_type = empty($_REQUEST['link_type']) ? null : $_REQUEST['link_type'];

        self::$file_loaded = false;

        self::$popup = !empty($_REQUEST['popup']);
        self::$tab   = isset($_REQUEST['tab']) && is_string($tab = $_REQUEST['tab']) ? $tab : '';

        // 0 : none, 1 : single media, >1 : multiple medias
        self::$select = isset($_REQUEST['select']) && is_numeric($select = $_REQUEST['select']) ? (int) $select : 0;

        self::$plugin_id = isset($_REQUEST['plugin_id']) && is_string($plugin_id = $_REQUEST['plugin_id']) ? $plugin_id : '';

        self::$page_url_params = [
            'popup'   => self::$popup,
            'select'  => self::$select,
            'post_id' => $post_id,
        ];
        self::$media_page_url_params = [
            'popup'     => self::$popup,
            'select'    => self::$select,
            'post_id'   => $post_id,
            'link_type' => $link_type,
        ];

        if (self::$plugin_id !== '') {
            self::$page_url_params = array_merge(
                self::$page_url_params,
                ['plugin_id' => self::$plugin_id],
            );
            self::$media_page_url_params = array_merge(
                self::$media_page_url_params,
                ['plugin_id' => self::$plugin_id],
            );
        }

        self::$id = isset($_REQUEST['id']) && is_numeric($id = $_REQUEST['id']) ? (int) $id : 0;
        if (self::$id !== 0) {
            self::$page_url_params = array_merge(
                self::$page_url_params,
                ['id' => self::$id],
            );
        }

        self::$is_media_writable = false;

        $dirs_combo = [];

        try {
            if (self::$id !== 0) {
                $file = App::media()->getFile(self::$id);
                if ($file instanceof MediaFile) {
                    self::$file        = $file;
                    self::$file_loaded = true;
                }
            }

            if (!self::$file_loaded) {
                throw new Exception(__('Not a valid file'));
            }

            App::media()->chdir(dirname(self::$file->relname));
            self::$is_media_writable = App::media()->writable();

            # Prepare directories combo box
            foreach (App::media()->getDBDirs() as $v) {
                $dirs_combo['/' . $v] = $v;
            }
            # Add parent and direct childs directories if any
            App::media()->getDir(false, false);   // No need to sort dirs/files, it will be done in combo later
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
        self::$dirs_combo = $dirs_combo;

        return self::status(true);
    }

    public static function process(): bool
    {
        if (!self::$file_loaded) {
            throw new Exception(__('Not a valid file'));
        }

        // Post data helpers
        $_Bool = fn (string $name): bool => !empty($_POST[$name]);
        $_Str  = fn (string $name, string $default = ''): string => isset($_POST[$name]) && is_string($val = $_POST[$name]) ? $val : $default;

        if (!empty($_FILES['upfile'])
            && self::$file->editable
            && self::$is_media_writable
        ) {
            // Upload a new file

            try {
                /**
                 * @var array{name: string, type: string, size: int, tmp_name: string, error?: int, full_path: string}  $file
                 */
                $file = $_FILES['upfile'];
                Files::uploadStatus($file);
                App::media()->uploadFile($file['tmp_name'], self::$file->basename, true, null, false);

                App::backend()->notices()->addSuccessNotice(__('File has been successfully updated.'));
                App::backend()->url()->redirect('admin.media.item', self::$page_url_params);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if (!empty($_POST['media_file'])
            && self::$file->editable
            && self::$is_media_writable
        ) {
            // Update file

            $newFile = clone self::$file;

            $newFile->basename = $_Str('media_file');

            if (!empty($_POST['media_path'])) {
                $newFile->dir     = $_Str('media_path');
                $newFile->relname = $_Str('media_path') . '/' . $newFile->basename;
            } else {
                $newFile->dir     = '';
                $newFile->relname = $newFile->basename;
            }
            $newFile->media_title = Html::escapeHTML($_Str('media_title'));
            $newFile->media_dt    = (int) strtotime($_Str('media_dt'));
            $newFile->media_dtstr = $_Str('media_dt');
            $newFile->media_priv  = $_Bool('media_private');

            // Update alt and description in metadata
            $alt       = Html::escapeHTML($_Str('media_alt'));
            $desc      = Html::escapeHTML($_Str('media_desc'));
            $alt_done  = false;
            $desc_done = false;
            if (self::$file->media_meta instanceof SimpleXMLElement) {
                if (count(self::$file->media_meta) > 0) {
                    foreach (self::$file->media_meta as $k => $v) {
                        if ($k === 'AltText') {
                            $v[0]     = $alt;
                            $alt_done = true;
                        }
                        if ($k === 'Description') {
                            $v[0]      = $desc;
                            $desc_done = true;
                        }
                    }
                }
                if (!$alt_done) {
                    self::$file->media_meta->addChild('AltText', $alt);
                }
                if (!$desc_done) {
                    self::$file->media_meta->addChild('Description', $desc);
                }
            } else {
                // Create meta and add values
                $meta = simplexml_load_string('<meta></meta>');
                if ($meta instanceof SimpleXMLElement) {
                    self::$file->media_meta = $meta;
                    self::$file->media_meta->addChild('Description', $desc);
                    self::$file->media_meta->addChild('AltText', $alt);
                }
            }

            try {
                App::media()->updateFile(self::$file, $newFile);

                App::backend()->notices()->addSuccessNotice(__('File has been successfully updated.'));
                self::$page_url_params = array_merge(
                    self::$page_url_params,
                    ['tab' => 'media-details-tab']
                );
                App::backend()->url()->redirect('admin.media.item', self::$page_url_params);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if (!empty($_POST['thumbs'])
            && self::$file->media_type === 'image'
            && self::$file->editable
            && self::$is_media_writable
        ) {
            // Update thumbnails

            try {
                App::media()->mediaFireRecreateEvent(self::$file);

                App::backend()->notices()->addSuccessNotice(__('Thumbnails have been successfully updated.'));
                self::$page_url_params = array_merge(
                    self::$page_url_params,
                    ['tab' => 'media-details-tab']
                );
                App::backend()->url()->redirect('admin.media.item', self::$page_url_params);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if ((isset($_POST['flip_h']) || isset($_POST['flip_v']))
            && self::$file->media_type === 'image'
            && self::$file->editable
            && self::$is_media_writable
        ) {
            // Flip image

            try {
                $horizontal = isset($_POST['flip_v']);  // Note: flip vertically implies flipping on horizontal axis
                $vertical   = isset($_POST['flip_h']);  //       flip horizontally implies flipping on vertical axis
                App::media()->imageFlip(self::$file, $horizontal, $vertical);

                App::media()->mediaFireRecreateEvent(self::$file);

                App::backend()->notices()->addSuccessNotice(__('The image has been flipped and the thumbnails have been successfully updated.'));
                self::$page_url_params = array_merge(
                    self::$page_url_params,
                    ['tab' => 'media-details-tab']
                );
                App::backend()->url()->redirect('admin.media.item', self::$page_url_params);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if ((isset($_POST['rotate_c']) || isset($_POST['rotate_a']))
            && self::$file->media_type === 'image'
            && self::$file->editable
            && self::$is_media_writable
        ) {
            // Rotate image

            try {
                // Rotate image by 90° according to given choice (anticlockwise or clockwise)
                $angle = isset($_POST['rotate_c']) ? 270 : 90;
                App::media()->imageRotate(self::$file, $angle);

                App::media()->mediaFireRecreateEvent(self::$file);

                App::backend()->notices()->addSuccessNotice(__('The image has been rotated and the thumbnails have been successfully updated.'));
                self::$page_url_params = array_merge(
                    self::$page_url_params,
                    ['tab' => 'media-details-tab']
                );
                App::backend()->url()->redirect('admin.media.item', self::$page_url_params);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if (!empty($_POST['unzip'])
            && self::$file->type === 'application/zip'
            && self::$file->editable
            && self::$is_media_writable
        ) {
            // Unzip file

            try {
                $unzip_dir = App::media()->inflateZipFile(self::$file, $_POST['inflate_mode'] == 'new');

                App::backend()->notices()->addSuccessNotice(__('Zip file has been successfully extracted.'));
                self::$media_page_url_params = array_merge(
                    self::$media_page_url_params,
                    ['d' => $unzip_dir]
                );
                App::backend()->url()->redirect('admin.media', self::$media_page_url_params);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if (!empty($_POST['save_blog_prefs'])) {
            // Save media insertion settings for the blog

            if (!empty($_POST['pref_src'])) {
                if (!($s = array_search($_POST['pref_src'], self::$file->media_thumb))) {
                    $s = 'o';
                }
                App::blog()->settings()->get('system')->put('media_img_default_size', $s);
            }
            if (!empty($_POST['pref_alignment'])) {
                App::blog()->settings()->get('system')->put('media_img_default_alignment', $_POST['pref_alignment']);
            }
            if (!empty($_POST['pref_insertion'])) {
                App::blog()->settings()->get('system')->put('media_img_default_link', ($_POST['pref_insertion'] == 'link'));
            }
            if (!empty($_POST['pref_legend'])) {
                App::blog()->settings()->get('system')->put('media_img_default_legend', $_POST['pref_legend']);
            }

            App::backend()->notices()->addSuccessNotice(__('Default media insertion settings have been successfully updated.'));
            App::backend()->url()->redirect('admin.media.item', self::$page_url_params);
        }

        if (!empty($_POST['save_folder_prefs'])) {
            // Save media insertion settings for the folder

            $prefs = [];
            if (!empty($_POST['pref_src'])) {
                if (!($s = array_search($_POST['pref_src'], self::$file->media_thumb))) {
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

            $local = App::media()->getRoot() . '/' . dirname(self::$file->relname) . '/' . '.mediadef.json';
            if (file_put_contents($local, json_encode($prefs, JSON_PRETTY_PRINT))) {
                App::backend()->notices()->addSuccessNotice(__('Media insertion settings have been successfully registered for this folder.'));
            }
            App::backend()->url()->redirect('admin.media.item', self::$page_url_params);
        }

        if (!empty($_POST['remove_folder_prefs'])) {
            // Delete media insertion settings for the folder (.mediadef and .mediadef.json)

            $local      = App::media()->getRoot() . '/' . dirname(self::$file->relname) . '/' . '.mediadef';
            $local_json = $local . '.json';
            if ((file_exists($local) && unlink($local)) || (file_exists($local_json) && unlink($local_json))) {
                App::backend()->notices()->addSuccessNotice(__('Media insertion settings have been successfully removed for this folder.'));
            }
            App::backend()->url()->redirect('admin.media.item', self::$page_url_params);
        }

        return true;
    }

    public static function render(): void
    {
        if (self::$popup) {
            $open_function  = App::backend()->page()->openPopup(...);
            $close_function = App::backend()->page()->closePopup(...);
        } else {
            $open_function  = App::backend()->page()->open(...);
            $close_function = function (): void {
                App::backend()->page()->helpBlock('core_media');
                App::backend()->page()->close();
            };
        }

        // Display page

        $starting_scripts = App::backend()->page()->jsModal() .
            App::backend()->page()->jsLoad('js/_media_item.js') .
            App::backend()->page()->jsConfirmClose('change-properties-form');

        if (self::$popup && self::$plugin_id !== '') {
            # --BEHAVIOR-- adminPopupMedia -- string
            $starting_scripts .= App::behavior()->callBehavior('adminPopupMedia', self::$plugin_id);
        }
        $temp_params      = self::$media_page_url_params;
        $temp_params['d'] = '%s';

        $breadcrumb = App::media()->breadCrumb(App::backend()->url()->get('admin.media', $temp_params, '&amp;', true)) . (self::$file_loaded
            ? (new Span(self::$file->basename))->class('page-title')->render()
            : '');

        $temp_params['d'] = '';
        $home_url         = App::backend()->url()->get('admin.media', $temp_params);
        call_user_func(
            $open_function,
            __('Media manager'),
            $starting_scripts .
            (self::$popup ? App::backend()->page()->jsPageTabs(self::$tab) : ''),
            App::backend()->page()->breadcrumb(
                [
                    Html::escapeHTML(App::blog()->name()) => '',
                    __('Media manager')                   => $home_url,
                    $breadcrumb                           => '',
                ],
                [
                    'home_link' => !self::$popup,
                    'hl'        => false,
                ]
            )
        );

        $user_lang = is_string($user_lang = App::auth()->getInfo('user_lang')) ? $user_lang : '';

        if (self::$popup) {
            // Display notices
            echo App::backend()->notices()->getNotices();
        }

        if (!self::$file_loaded) {
            call_user_func($close_function);
            dotclear_exit();
        }

        if (!empty($_GET['fupd']) || !empty($_GET['fupl'])) {
            App::backend()->notices()->success(__('File has been successfully updated.'));
        }
        if (!empty($_GET['thumbupd'])) {
            App::backend()->notices()->success(__('Thumbnails have been successfully updated.'));
        }
        if (!empty($_GET['blogprefupd'])) {
            App::backend()->notices()->success(__('Default media insertion settings have been successfully updated.'));
        }

        // Get major file type (first part of mime type)
        $file_type       = explode('/', (string) self::$file->type);
        self::$file_type = $file_type[0];

        $parts = [];

        // Insertion/Selection part

        if (self::$select === 1) {
            // Selection mode

            // Get alternate text
            $media_alt = App::media()->getMediaAlt(self::$file);

            // Get legend
            $media_legend = App::media()->getMediaLegend(
                self::$file,
                App::blog()->settings()->get('system')->getStr('media_img_title_pattern'),
                (bool) App::blog()->settings()->get('system')->getBool('media_img_use_dto_first', false),
                (bool) App::blog()->settings()->get('system')->getBool('media_img_no_date_alone', false)
            );

            $defaults = self::getImageDefaults(self::$file);

            $part_image_size = (new None());
            if (self::$file->media_type === 'image') {
                $media_type = 'image';

                // Image sizes
                $image_sizes = function () use ($defaults) {
                    foreach (array_reverse(self::$file->media_thumb) as $key => $value) {
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
                                (new Radio(['src'], !isset(self::$file->media_thumb[$defaults['size']])))
                                    ->value(self::$file->file_url)
                                    ->label(new Label(__('original'), Label::IL_FT)),
                            ]),
                    ]);
            } elseif (self::$file_type === 'audio') {
                $media_type = 'mp3';
            } elseif (self::$file_type === 'video') {
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
                                    (new Hidden(['url'], self::$file->file_url)),
                                ]),
                        ]),
                ]);
        }

        if (self::$popup && self::$select === 0) {
            // Insertion popup

            // Get alternate text
            $media_alt = App::media()->getMediaAlt(self::$file);

            // Get legend
            $media_legend = App::media()->getMediaLegend(
                self::$file,
                App::blog()->settings()->get('system')->getStr('media_img_title_pattern'),
                (bool) App::blog()->settings()->get('system')->getBool('media_img_use_dto_first', false),
                (bool) App::blog()->settings()->get('system')->getBool('media_img_no_date_alone', false)
            );

            // Get title
            $media_title = App::media()->getMediaTitle(self::$file, false);

            $defaults = self::getImageDefaults(self::$file);

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
            if (self::$file->media_type === 'image') {
                $media_type = 'image';

                // Image sizes
                $image_sizes = function () use ($defaults) {
                    foreach (array_reverse(self::$file->media_thumb) as $key => $value) {
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
                                        (new Radio(['src'], !isset(self::$file->media_thumb[$defaults['size']])))
                                            ->value(self::$file->file_url)
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
                                            (new Span($media_alt))
                                                ->class('media-title') :
                                            (new Text(null, __('(none)'))))->render())),
                                        (new Single('br')),
                                        (new Text(null, __('Legend:') . ' ' . ($media_legend !== '' ?
                                            (new Span($media_legend))
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
            } elseif (self::$file_type === 'audio') {
                $media_type = 'mp3';

                $url = self::$file->file_url;
                if (str_starts_with((string) $url, App::blog()->host())) {
                    $url = substr((string) $url, strlen(App::blog()->host()));
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
                                        (new Hidden('public_player', Html::escapeHTML(App::media()::audioPlayer((string) self::$file->type, $url, alt: $media_alt, descr: $media_legend)))),
                                    ]),
                                (new Note())
                                    ->class('warning')
                                    ->text(__('Please note that you cannot insert mp3 files with standard editor in WYSIWYG HTML mode.')),
                            ]),
                    ]);
            } elseif (self::$file_type === 'video') {
                $media_type = 'flv';

                $url = self::$file->file_url;
                if (str_starts_with((string) $url, App::blog()->host())) {
                    $url = substr((string) $url, strlen(App::blog()->host()));
                }

                $media_insert_options = (new Set())
                    ->items([
                        (new Div())
                            ->class('two-boxes')
                            ->items([
                                (new Text('h3', __('Video size'))),
                                (new Para())
                                    ->items([
                                        (new Number('video_w', 0, 9999, (int) App::blog()->settings()->get('system')->getInt('media_video_width', false)))
                                            ->label(new Label(__('Width:'), Label::IL_TF)),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Number('video_h', 0, 9999, (int) App::blog()->settings()->get('system')->getInt('media_video_height', false)))
                                            ->label(new Label(__('Height:'), Label::IL_TF)),
                                    ]),
                                (new Note())
                                    ->class(['form-note', 'info'])
                                    ->text(__('A value of 0 means that the corresponding size is not included when inserting a video.')),
                            ]),
                        (new Div())
                            ->class('two-boxes')
                            ->items([
                                (new Text('h3', __('Video disposition'))),
                                (new Para())
                                    ->items([
                                        ... $image_alignments(),
                                        (new Hidden('blog_host', Html::escapeHTML(App::blog()->host()))),
                                        (new Hidden('public_player', Html::escapeHTML(App::media()::videoPlayer((string) self::$file->type, $url, alt: $media_alt, descr: $media_legend)))),
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
                $local = App::media()->getRoot() . '/' . dirname((string) self::$file->relname) . '/' . '.mediadef';
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
                                        ... App::backend()->url()->hiddenFormFields('admin.media.item', self::$page_url_params),
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
                                    (new Hidden(['url'], self::$file->file_url)),
                                ]),
                        ]),
                    $save_settings,
                ]);
        }

        // Details part

        $media_details = [];

        $media_details[] = (new Para('media-icon'))
            ->items([
                (new Img(self::$file->media_icon . '?' . time() * random_int(0, mt_getrandmax())))
                    ->class(self::$file->media_preview ? 'media-icon-square' : ''),
            ]);

        $media_details_display = (new None());
        if (self::$file->media_image) {
            $thumb_size = isset($_GET['size']) && is_string($thumb_size = $_GET['size']) ? $thumb_size : '';

            if ($thumb_size === '') {
                $thumb_size = 's';
            }

            if (!isset(App::media()->getThumbSizes()[$thumb_size]) && $thumb_size !== 'o') {
                $thumb_size = 's';
            }

            $image_infos = [];

            if (isset(self::$file->media_thumb[$thumb_size])) {
                $image_infos[] = (new Para())
                    ->items([
                        (new Link())
                            ->href(self::$file->file_url)  // @phpstan-ignore argument.type, property.notFound ((undefined property object::$file_url))
                            ->class('modal-image')
                            ->items([
                                (new Img(self::$file->media_thumb[$thumb_size] . '?' . time() * random_int(0, mt_getrandmax())))
                                    ->alt(''),
                            ]),
                    ]);
            } elseif ($thumb_size === 'o') {
                $image_size = getimagesize(self::$file->file);

                $image_infos[] = (new Para('media-original-image'))
                    ->class(!$image_size || ($image_size[1] > 500) ? 'overheight' : '')
                    ->items([
                        (new Link())
                            ->href(self::$file->file_url)
                            ->class('modal-image')
                            ->items([
                                (new Img(self::$file->file_url . '?' . time() * random_int(0, mt_getrandmax())))
                                    ->alt(''),
                            ]),
                    ]);
            }

            $available_sizes = function () use ($thumb_size) {
                foreach (array_keys(array_reverse(self::$file->media_thumb)) as $key) {
                    $link = (new Link())
                        ->href(App::backend()->url()->get('admin.media.item', array_merge(self::$page_url_params, ['size' => $key, 'tab' => 'media-details-tab'])))
                        ->text(App::media()->getThumbSizes()[$key][2]);
                    if ($key === $thumb_size) {
                        yield (new Strong())->items([$link]);
                    } else {
                        yield (new Text())->items([$link]);
                    }
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
                                ->href(App::backend()->url()->get('admin.media.item', array_merge(self::$page_url_params, ['size' => 'o', 'tab' => 'media-details-tab'])))
                                ->text(__('original')),
                        ]),
                ]);

            // Add rotate and flip buttons
            $image_infos[] = (new Para())
                ->items([
                    (new Form('flip-rotate-form'))
                        ->method('post')
                        ->action(App::backend()->url()->get('admin.media.item'))
                        ->class(['clear', 'fieldset'])
                        ->fields([
                            (new Text('h4', __('Flip/Rotate image'))),
                            (new Para())
                                ->class('form-buttons')
                                ->items([
                                    (new Submit('flip_v', __('Flip image vertically')))
                                        ->title(__('Flip image vertically')),
                                    (new Submit('flip_h', __('Flip image horizontally')))
                                        ->title(__('Flip image horizontally')),
                                    (new Submit('rotate_c', __('Rotate image by 90° (clockwise)')))
                                        ->title(__('Rotate image by 90° (clockwise)')),
                                    (new Submit('rotate_a', __('Rotate image by 90° (anticlockwise)')))
                                        ->title(__('Rotate image by 90° (anticlockwise)')),
                                    ... App::backend()->url()->hiddenFormFields('admin.media.item', self::$page_url_params),
                                    App::nonce()->formNonce(),
                                ]),
                            (new Note())
                                ->text(__('This will also recreate thumbnails for this image.'))
                                ->class('form-note'),
                        ]),
                ]);

            if ($thumb_size !== 'o' && isset(self::$file->media_thumb[$thumb_size])) {
                $path_info  = Path::info(self::$file->file);   // @phpstan-ignore property.notFound, argument.type ((undefined property object::$file))
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
                            (new Strong(__('Image width:'))),
                            (new Text(null, $image_size[0] . 'px')),
                        ]);
                    $infos_list[] = (new Li())
                        ->separator(' ')
                        ->items([
                            (new Strong(__('Image height:'))),
                            (new Text(null, $image_size[1] . 'px')),
                        ]);
                }
                if ($stats) {
                    $infos_list[] = (new Li())
                        ->separator(' ')
                        ->items([
                            (new Strong(__('File size:'))),
                            (new Text(null, Files::size($stats[7]))),
                        ]);
                }

                $infos_list[] = (new Li())
                    ->separator(' ')
                    ->items([
                        (new Strong(__('File URL:'))),
                        (new Link())
                            ->href(self::$file->media_thumb[$thumb_size])
                            ->text(self::$file->media_thumb[$thumb_size]),
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
        if (self::$file_type === 'audio') {
            $media_details_display = (new Text(null, App::media()::audioPlayer((string) self::$file->type, self::$file->file_url)));
        }
        if (self::$file_type === 'video') {
            $media_details_display = (new Text(null, App::media()::videoPlayer((string) self::$file->type, self::$file->file_url)));
        }

        $infos_list = [];

        $infos_list[] = (new Li())
            ->separator(' ')
            ->items([
                (new Strong(__('File owner:'))),
                (new Text(null, self::$file->media_user)),
            ]);
        $infos_list[] = (new Li())
            ->separator(' ')
            ->items([
                (new Strong(__('File type:'))),
                (new Text(null, self::$file->type)),
            ]);

        if (self::$file->media_image) {
            if (self::$file->type === 'image/svg+xml') {
                if (($xmlget = simplexml_load_file(self::$file->file)) !== false && $xmlattributes = $xmlget->attributes()) {
                    $image_size = [
                        (string) $xmlattributes->width,
                        (string) $xmlattributes->height,
                    ];
                    if ($image_size[0] !== '') {
                        $infos_list[] = (new Li())
                            ->separator(' ')
                            ->items([
                                (new Strong(__('Image width:'))),
                                (new Text(null, $image_size[0])),
                            ]);
                    }
                    if ($image_size[1] !== '') {
                        $infos_list[] = (new Li())
                            ->separator(' ')
                            ->items([
                                (new Strong(__('Image width:'))),
                                (new Text(null, $image_size[1])),
                            ]);
                    }
                }
            } else {
                $image_size = getimagesize(self::$file->file);
                if (is_array($image_size)) {
                    $infos_list[] = (new Li())
                        ->separator(' ')
                        ->items([
                            (new Strong(__('Image width:'))),
                            (new Text(null, $image_size[0] . 'px')),
                        ]);
                    $infos_list[] = (new Li())
                        ->separator(' ')
                        ->items([
                            (new Strong(__('Image height:'))),
                            (new Text(null, $image_size[1] . 'px')),
                        ]);
                }
            }
        }

        $infos_list[] = (new Li())
            ->separator(' ')
            ->items([
                (new Strong(__('File size:'))),
                (new Text(null, Files::size(self::$file->size))),
            ]);
        $infos_list[] = (new Li())
            ->separator(' ')
            ->items([
                (new Strong(__('File URL:'))),
                (new Link())
                    ->href(self::$file->file_url)
                    ->text(self::$file->file_url),
            ]);

        if (empty($_GET['find_posts'])) {
            $media_entries = (new Para())
                ->items([
                    (new Link())
                        ->href(App::backend()->url()->get('admin.media.item', array_merge(self::$page_url_params, ['find_posts' => 1, 'tab' => 'media-details-tab'])))
                        ->text(__('Show entries containing this media')),
                ]);
        } else {
            [$rsInside, $rsLinked] = self::findMediaInEntries(self::$file);

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
                                App::status()->post()->image($rs->intField('post_status')),
                                (new Link())
                                    ->href(App::postTypes()->get($rs->strField('post_type'))->adminUrl($rs->intField('post_id')))
                                    ->text($rs->strField('post_title')),
                                ($rs->post_type !== 'post' ?
                                    (new Text(null, '(' . Html::escapeHTML($rs->strField('post_type')) . ')')) :
                                    (new None())),
                                (new Text(null, '-')),
                                (new Text(null, Date::dt2str(__('%Y-%m-%d %H:%M'), $rs->strField('post_dt')))),
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
        if (self::$file->media_title !== '') {
            $metadata[] = (new Li())
                ->separator(' ')
                ->items([
                    (new Strong(__('Title'))),
                    (new Text(null, Html::escapeHTML(self::$file->media_title))),
                ]);
        }

        $alttext = App::media()->getMediaAlt(self::$file, false);
        if ($alttext !== '') {
            $metadata[] = (new Li())
                ->separator(' ')
                ->items([
                    (new Strong(__('Alternate text:'))),
                    (new Text(null, Html::escapeHTML($alttext))),
                ]);
        }

        if (self::$file->media_meta instanceof SimpleXMLElement) {
            foreach (self::$file->media_meta as $k => $value) {
                if ($k === 'Title' && self::$file->media_title !== '' && (string) $value) {
                    // Title already displayed
                    continue;
                }

                if ($k !== 'AltText' && (string) $value) {
                    $metadata[] = (new Li())
                        ->separator(' ')
                        ->items([
                            (new Strong($k . __(':'))),
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

        if (self::$file->editable && self::$is_media_writable) {
            if (self::$file->media_type === 'image') {
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
                                ... App::backend()->url()->hiddenFormFields('admin.media.item', self::$page_url_params),
                                App::nonce()->formNonce(),
                            ]),
                    ]);
            }

            if ((string) self::$file->type === 'application/zip') {
                $inflate_combo = [
                    new Option(__('Extract in a new directory'), 'new'),
                    new Option(__('Extract in current directory'), 'current'),
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
                                        (new Strong(__('Extract in a new directory'))),
                                        (new Text(null, __('This will extract archive in a new directory that should not exist yet.'))),
                                    ]),
                                (new Li())
                                    ->separator(' : ')
                                    ->items([
                                        (new Strong(__('Extract in current directory'))),
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
                                ... App::backend()->url()->hiddenFormFields('admin.media.item', self::$page_url_params),
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
                                        ->value(Html::escapeHTML(self::$file->basename))
                                        ->label(new Label(__('File name:'), Label::OL_TF)),
                                ]),
                            (new Para())
                                ->items([
                                    (new Input('media_title'))
                                        ->size(80)
                                        ->maxlength(255)
                                        ->value(Html::escapeHTML(self::$file->media_title))
                                        ->label(new Label(__('Title:'), Label::OL_TF))
                                        ->lang($user_lang)
                                        ->spellcheck(true),
                                ]),
                            (new Para())
                                ->items([
                                    (new Textarea('media_alt', Html::escapeHTML(App::media()->getMediaAlt(self::$file, false))))
                                        ->cols(80)
                                        ->rows(5)
                                        ->label(new Label(__('Alternate text:'), Label::OL_TF))
                                        ->lang($user_lang)
                                        ->spellcheck(true),
                                ]),
                            (new Para())
                                ->items([
                                    (new Textarea('media_desc', Html::escapeHTML(App::media()->getMediaLegend(self::$file, 'Description'))))
                                        ->cols(80)
                                        ->rows(5)
                                        ->label(new Label(__('Description:'), Label::OL_TF))
                                        ->lang($user_lang)
                                        ->spellcheck(true),
                                ]),
                            (new Para())
                                ->items([
                                    (new Datetime('media_dt', Html::escapeHTML(Date::str('%Y-%m-%dT%H:%M', self::$file->media_dt))))
                                        ->label(new Label(__('File date:'), Label::OL_TF)),
                                ]),
                            (new Para())
                                ->items([
                                    (new Checkbox('media_private', self::$file->media_priv))
                                        ->value('1')
                                        ->label(new Label(__('Private'), Label::IL_FT)),
                                ]),
                            (new Para())
                                ->items([
                                    (new Select('media_path'))
                                        ->items(self::$dirs_combo)
                                        ->default(dirname((string) self::$file->relname))
                                        ->label(new Label(__('New directory:'), Label::OL_TF)),
                                ]),
                            (new Submit('change-properties-submit', __('Save')))
                                ->accesskey('s'),
                            ... App::backend()->url()->hiddenFormFields('admin.media.item', self::$page_url_params),
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
                            ... App::backend()->url()->hiddenFormFields('admin.media.item', self::$page_url_params),
                            App::nonce()->formNonce(),
                        ]),
                ]);

            if (self::$file->del) {
                $q         = isset($_REQUEST['q']) && is_string($q = $_REQUEST['q']) ? $q : '';
                $filename  = $q !== '' ? self::$file->relname : self::$file->basename;
                $actions[] = (new Form('delete-form'))
                    ->method('post')
                    ->action(App::backend()->url()->get('admin.media'))
                    ->fields([
                        (new Para())
                            ->class('form-buttons')
                            ->items([
                                (new Submit('delete', __('Delete this media')))
                                    ->class('delete'),
                                (new Hidden('remove', rawurlencode((string) $filename))),
                                (new Hidden('rmyes', '1')),
                                (new Hidden('q', $q)),
                                ... App::backend()->url()->hiddenFormFields('admin.media', self::$media_page_url_params),
                                App::nonce()->formNonce(),
                            ]),
                    ]);
            }

            $actions[] = (new Capture(
                # --BEHAVIOR-- adminMediaItemForm -- MediaFile
                App::behavior()->callBehavior(...),
                ['adminMediaItemForm', self::$file]
            ));
        }

        $media_action = (new Div())
            ->items([
                (new Text('h3', __('Updates and modifications'))),
                ... $actions,
            ]);

        if (self::$popup
            && (self::$select === 0 || self::$select === 1)
        ) {
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

        call_user_func($close_function);
    }

    /**
     * Get image default insertion parameters
     *
     * @param  MediaFile $file Media file
     *
     * @return array{size: string, alignment: string, link: bool, legend: string, mediadef: bool}
     */
    protected static function getImageDefaults(?MediaFile $file): array
    {
        $defaults = [
            'size'      => App::blog()->settings()->get('system')->getStr('media_img_default_size')      ?? 'm',
            'alignment' => App::blog()->settings()->get('system')->getStr('media_img_default_alignment') ?? 'none',
            'link'      => App::blog()->settings()->get('system')->getBool('media_img_default_link', false),
            'legend'    => App::blog()->settings()->get('system')->getStr('media_img_default_legend') ?? 'legend',
            'mediadef'  => false,
        ];

        if (!$file instanceof MediaFile) {
            return $defaults;
        }

        try {
            $local = App::media()->getRoot() . '/' . dirname($file->relname) . '/' . '.mediadef';
            if (!file_exists($local)) {
                $local .= '.json';
            }
            if (file_exists($local)) {
                $content = file_get_contents($local);
                if ($content !== false) {
                    /**
                     * @var array{size?: string, alignment?: string, link?: bool, legend?: string, mediadef?: bool} $specifics
                     */
                    $specifics = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
                    foreach (array_keys($defaults) as $key) {
                        $defaults[$key] = $specifics[$key] ?? $defaults[$key];
                    }
                    $defaults['mediadef'] = true;
                }
            }
        } catch (Exception) {
            // Ignore exceptions
        }

        return $defaults;
    }

    /**
     * Find media in entries (in entry content and as any kind of attachment)
     *
     * @return array{MetaRecord, MetaRecord}
     */
    protected static function findMediaInEntries(MediaFile $file): array
    {
        $relname = $file->relname;

        // 1st, look inside entries content
        $sql  = new SelectStatement();
        $or   = [];
        $or[] = $sql->like('post_content_xhtml', '%' . $sql->escape($relname) . '%');
        $or[] = $sql->like('post_excerpt_xhtml', '%' . $sql->escape($relname) . '%');

        if ($file->media_image) {
            // We look for thumbnails too
            if (preg_match('#^http(s)?://#', (string) App::blog()->settings()->get('system')->getStr('public_url', false))) {
                $media_root = App::blog()->settings()->get('system')->getStr('public_url', false);
            } else {
                $media_root = App::blog()->host() . Path::clean(App::blog()->settings()->get('system')->getStr('public_url', false)) . '/';
            }
            foreach ($file->media_thumb as $value) {
                $value = (string) preg_replace('/^' . preg_quote($media_root, '/') . '/', '', $value);
                $or[]  = $sql->like('post_content_xhtml', '%' . $sql->escape($value) . '%');
                $or[]  = $sql->like('post_excerpt_xhtml', '%' . $sql->escape($value) . '%');
            }
        }

        $params = [
            'post_type' => '',
            'sql'       => 'AND ' . $sql->orGroup($or),
        ];

        $rsInside = App::blog()->getPosts($params);

        // 2nd, look inside entries attachments (any kind)
        $sql   = new SelectStatement();
        $and   = [];
        $and[] = 'PM.media_id = ' . self::$id;

        $join = (new JoinStatement())
            ->left()
            ->from($sql->as(App::db()->con()->prefix() . App::postMedia()::POST_MEDIA_TABLE_NAME, 'PM'))
            ->on('P.post_id = PM.post_id');

        $params = [
            'post_type' => '',
            'join'      => $join->statement(),
            'sql'       => 'AND ' . $sql->andGroup($and),
        ];

        $rsLinked = App::blog()->getPosts($params);

        return [$rsInside, $rsLinked];
    }
}
