<?php

/**
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Exception;

use dcCore;
use DirectoryIterator;
use SimpleXMLElement;
use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\DeleteStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Database\Statement\UpdateStatement;
use Dotclear\Exception\BadRequestException;
use Dotclear\Exception\ProcessException;
use Dotclear\Exception\UnauthorizedException;
use Dotclear\Helper\Date;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Image\ImageMeta;
use Dotclear\Helper\File\Image\ImageTools;
use Dotclear\Helper\File\MediaFile;
use Dotclear\Helper\File\MediaManager;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\File\Zip\Unzip;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Html\XmlTag;
use Dotclear\Helper\Text;
use Dotclear\Interface\Core\MediaInterface;
use Dotclear\Interface\Core\PostMediaInterface;
use stdClass;
use Throwable;

/**
 * @brief   Media items handler.
 *
 * @since   2.28, container services have been added to constructor
 * @since   2.36, constructor arguments has been replaced by Core instance
 */
class Media extends MediaManager implements MediaInterface
{
    /**
     * Media table name
     */
    protected string $table;

    /**
     * Media type filter
     */
    protected string $type = '';

    /**
     * Sort criteria
     */
    protected string $file_sort = 'name-asc';

    /**
     * Current path
     */
    protected string $path;

    /**
     * Ccurrent relative path
     */
    protected string $relpwd;

    /**
     * Stack of callbacks
     *
     * @var array<string, array<string, callable[]> >   $file_handler
     */
    protected array $file_handler = [];

    /**
     * Media thumbnail prefix
     */
    protected string $thumbnail_prefix = '.';

    /**
     * Thumbnail pattern:
     * `<path>/<prefix><filename>_<sizecode>.<extension>`
     */
    protected string $thumbnail_pattern = '%1$s/%2$s%3$s_%4$s.%5$s';

    /**
     * Thumbnail file pattern
     *
     * @deprecated since 2.28, use self::getThumbnailFilePattern()
     */
    public string $thumb_tp = '%s/.%s_%s.jpg';

    /**
     * Thumbnail file pattern (PNG with alpha layer)
     *
     * @deprecated since 2.28, use self::getThumbnailFilePattern('alpha')
     */
    public string $thumb_tp_alpha = '%s/.%s_%s.png';

    /**
     * Thumbnail file pattern (WebP)
     *
     * @deprecated since 2.28, use self::getThumbnailFilePattern('wepb')
     */
    public string $thumb_tp_webp = '%s/.%s_%s.webp';

    /**
     * Thumbnail file pattern (Avif)
     *
     * @deprecated since 2.28, use self::getThumbnailFilePattern('avif')
     */
    public string $thumb_tp_avif = '%s/.%s_%s.avif';

    /**
     * Get available thumb sizes.
     *
     * Tubmnail sizes:
     * - m: medium image
     * - s: small image
     * - t: thumbnail image
     * - sq: square image
     *
     * @deprecated since 2.28, use self::getThumbnailCombo()
     *
     * @var array<string, list{0:int, 1:string, 2:string, 3?:string}>  $thumb_sizes
     */
    public array $thumb_sizes = [
        'm'  => [448, 'ratio', 'medium'],
        's'  => [240, 'ratio', 'small'],
        't'  => [100, 'ratio', 'thumbnail'],
        'sq' => [48, 'crop', 'square'],
    ];

    /**
     * Icon file pattern
     */
    public string $icon_img = 'images/media/%s.svg';

    /**
     * Identify if blog root public folder is missing or not
     */
    protected bool $root_missing = false;

    /**
     * Constructs a new instance.
     *
     * @param   Core    $core   The core container
     */
    public function __construct(
        protected Core $core
    ) {
        if (!$this->core->blog()->isDefined()) {
            throw new ProcessException(__('No blog defined.'));
        }

        $this->table = $this->core->db()->con()->prefix() . $this->core->postMedia()::MEDIA_TABLE_NAME;
        $root        = $this->core->blog()->publicPath();

        if (preg_match('#^http(s)?://#', (string) $this->core->blog()->settings()->system->public_url)) {
            $root_url = rawurldecode((string) $this->core->blog()->settings()->system->public_url);
        } else {
            $root_url = rawurldecode($this->core->blog()->host() . Path::clean($this->core->blog()->settings()->system->public_url));
        }

        // Check public directory
        if (!is_dir($root) || !is_readable($root)) {
            $this->root_missing = true;
        }

        if (!$this->root_missing) {
            parent::__construct($root, $root_url);
            $this->chdir('');

            $this->path = $this->core->blog()->settings()->system->public_path;
        }

        $this->addExclusion($this->core->config()->configPath());
        $this->addExclusion(__DIR__ . '/../');

        $this->addExcludePattern($this->core->blog()->settings()->system->media_exclusion);

        // Disallow double (or more) extensions if the 1st one will allow a potential RCE (remote code execution)
        $this->addExcludePattern('/\.(phps?|pht(ml)?|phl|phar|.?html?|inc|xml|js)\d*(\.\w*)+$/');

        if (((string) $this->core->blog()->settings()->system->media_thumbnail_prefix !== '') && ((string) $this->core->blog()->settings()->system->media_thumbnail_prefix !== $this->thumbnail_prefix)) {
            $this->thumbnail_prefix = (string) $this->core->blog()->settings()->system->media_thumbnail_prefix;
            $this->addExcludePattern(sprintf('/^%s(.*)/', preg_quote($this->thumbnail_prefix, '/')));
        }

        // Ensure correct pattern values for deprecated properties
        $this->thumb_tp       = $this->getThumbnailFilePattern('');
        $this->thumb_tp_alpha = $this->getThumbnailFilePattern('alpha');
        $this->thumb_tp_webp  = $this->getThumbnailFilePattern('webp');
        $this->thumb_tp_avif  = $this->getThumbnailFilePattern('avif');

        # Event handlers
        $this->addFileHandler('image/jpeg', 'create', $this->imageThumbCreate(...));
        $this->addFileHandler('image/png', 'create', $this->imageThumbCreate(...));
        $this->addFileHandler('image/gif', 'create', $this->imageThumbCreate(...));
        $this->addFileHandler('image/webp', 'create', $this->imageThumbCreate(...));
        $this->addFileHandler('image/avif', 'create', $this->imageThumbCreate(...));

        $this->addFileHandler('image/png', 'update', $this->imageThumbUpdate(...));
        $this->addFileHandler('image/jpeg', 'update', $this->imageThumbUpdate(...));
        $this->addFileHandler('image/gif', 'update', $this->imageThumbUpdate(...));
        $this->addFileHandler('image/webp', 'update', $this->imageThumbUpdate(...));
        $this->addFileHandler('image/avif', 'update', $this->imageThumbUpdate(...));

        $this->addFileHandler('image/png', 'remove', $this->imageThumbRemove(...));
        $this->addFileHandler('image/jpeg', 'remove', $this->imageThumbRemove(...));
        $this->addFileHandler('image/gif', 'remove', $this->imageThumbRemove(...));
        $this->addFileHandler('image/webp', 'remove', $this->imageThumbRemove(...));
        $this->addFileHandler('image/avif', 'remove', $this->imageThumbRemove(...));

        $this->addFileHandler('image/jpeg', 'create', $this->imageMetaCreate(...));
        $this->addFileHandler('image/webp', 'create', $this->imageMetaCreate(...));
        $this->addFileHandler('image/svg+xml', 'create', $this->imageMetaCreate(...));

        $this->addFileHandler('image/jpeg', 'recreate', $this->imageThumbCreate(...));
        $this->addFileHandler('image/png', 'recreate', $this->imageThumbCreate(...));
        $this->addFileHandler('image/gif', 'recreate', $this->imageThumbCreate(...));
        $this->addFileHandler('image/webp', 'recreate', $this->imageThumbCreate(...));
        $this->addFileHandler('image/avif', 'recreate', $this->imageThumbCreate(...));

        # Thumbnails sizes
        $this->thumb_sizes['m'][0] = abs((int) $this->core->blog()->settings()->system->media_img_m_size);
        $this->thumb_sizes['s'][0] = abs((int) $this->core->blog()->settings()->system->media_img_s_size);
        $this->thumb_sizes['t'][0] = abs((int) $this->core->blog()->settings()->system->media_img_t_size);

        # --BEHAVIOR-- coreMediaConstruct -- Manager -- deprecated since 2.28, as plugins are not yet loaded here
        $this->core->behavior()->callBehavior('coreMediaConstruct', $this);

        // Sort thumb_sizes DESC on largest sizes
        $sizes = [];
        foreach ($this->thumb_sizes as $code => $size) {
            $sizes[$code] = $size[0];
        }
        array_multisort($sizes, SORT_DESC, $this->thumb_sizes);

        // Set thumbnails translations
        foreach (array_keys($this->thumb_sizes) as $code) {
            $this->thumb_sizes[$code][3] = $this->thumb_sizes[$code][2];
            $this->thumb_sizes[$code][2] = __($this->thumb_sizes[$code][2]);
        }

        // deprecated since 2.28, use App::media() instead
        dcCore::app()->media = $this;
    }

    public function isRootMissing(): bool
    {
        return $this->root_missing;
    }

    public function getRoot(): string
    {
        return $this->root_missing ? '' : parent::getRoot();
    }

    public function getRootUrl(): string
    {
        return $this->root_missing ? '' : parent::getRootUrl();
    }

    public function openMediaCursor(): Cursor
    {
        return $this->core->db()->con()->openCursor($this->table);
    }

    public function postMedia(): PostMediaInterface
    {
        return $this->core->postMedia();
    }

    public function getThumbnailFilePattern(string $type = ''): string
    {
        return match (strtolower($type)) {
            'alpha', 'png' => sprintf($this->thumbnail_pattern, '%s', $this->thumbnail_prefix, '%s', '%s', 'png'),
            'webp'  => sprintf($this->thumbnail_pattern, '%s', $this->thumbnail_prefix, '%s', '%s', 'webp'),
            'avif'  => sprintf($this->thumbnail_pattern, '%s', $this->thumbnail_prefix, '%s', '%s', 'avif'),
            default => sprintf($this->thumbnail_pattern, '%s', $this->thumbnail_prefix, '%s', '%s', 'jpg'),
        };
    }

    public function getThumbnailPrefix(): string
    {
        return $this->thumbnail_prefix;
    }

    public function hasMediaAlphaLayer(string $type = ''): bool
    {
        return match (strtolower($type)) {
            'alpha', 'png', 'webp', 'avif' => true,
            default => false,
        };
    }

    public function getThumbSizes(): array
    {
        return $this->thumb_sizes;
    }

    public function setThumbSizes(array $thumb_sizes): void
    {
        $this->thumb_sizes = $thumb_sizes;

        // Sort thumb_sizes DESC on largest sizes
        $sizes = [];
        foreach ($this->thumb_sizes as $code => $size) {
            $sizes[$code] = $size[0];
        }
        array_multisort($sizes, SORT_DESC, $this->thumb_sizes);

        // Set thumbnails translations if necessary
        foreach (array_keys($this->thumb_sizes) as $code) {
            if (!isset($this->thumb_sizes[$code][3])) {
                $this->thumb_sizes[$code][3] = $this->thumb_sizes[$code][2];
                $this->thumb_sizes[$code][2] = __($this->thumb_sizes[$code][2]);
            }
        }
    }

    public function setFilterMimeType(string $type): void
    {
        $this->type = $type;
    }

    public function addFileHandler(string $type, string $event, $function): void
    {
        if (is_callable($function)) {   // @phpstan-ignore-line waiting to put callable type in method signature for $function
            $this->file_handler[$type][$event][] = $function;
        }
    }

    /**
     * Call filehandler depending on media type and event
     *
     * @param      string  $type     The type
     * @param      string  $event    The event
     * @param      mixed   ...$args  The arguments
     */
    protected function callFileHandler(string $type, string $event, ...$args): void
    {
        if (!empty($this->file_handler[$type][$event])) {
            foreach ($this->file_handler[$type][$event] as $f) {
                $f(...$args);
            }
        }
    }

    public function breadCrumb(string $href, string $last = ''): string
    {
        if ($this->root_missing) {
            return '';
        }

        $res = '';
        if ($this->relpwd && $this->relpwd !== '.') {
            $pwd   = '';
            $arr   = explode('/', $this->relpwd);
            $count = count($arr);
            foreach ($arr as $v) {
                if (($last !== '') && (0 === --$count)) {
                    $res .= sprintf($last, $v);
                } else {
                    $pwd .= rawurlencode($v) . '/';
                    $res .= '<a href="' . sprintf($href, $pwd) . '">' . $v . '</a> / ';
                }
            }
        }

        return $res;
    }

    public function chdir(?string $dir): void
    {
        if ($this->root_missing) {
            return;
        }

        parent::chdir($dir);
        $this->relpwd = (string) preg_replace('/^' . preg_quote($this->root, '/') . '\/?/', '', $this->pwd);
    }

    /**
     * Get media file information from recordset
     *
     * @param      MetaRecord    $rs  The recordset
     *
     * @return     MediaFile  The file item.
     */
    protected function fileRecord(MetaRecord $rs): ?MediaFile
    {
        if ($this->root_missing) {
            return null;
        }

        if ($rs->isEmpty()) {
            return null;
        }

        if (!$this->isFileExclude($this->root . '/' . $rs->media_file) && is_file($this->root . '/' . $rs->media_file)) {
            $fi = new MediaFile($this->root . '/' . $rs->media_file, $this->root, $this->root_url);

            if ($this->type && $fi->type_prefix !== $this->type) {
                // Check file mimetype base (before 1st /)
                return null;
            }

            $meta         = @simplexml_load_string((string) $rs->media_meta);
            $default_meta = @simplexml_load_string('<meta></meta>');
            if (!$default_meta instanceof SimpleXMLElement) {
                $default_meta = null;
            }

            $fi->editable    = true;
            $fi->media_id    = (int) $rs->media_id;
            $fi->media_title = $rs->media_title;
            $fi->media_meta  = $meta instanceof SimpleXMLElement ? $meta : $default_meta;
            $fi->media_user  = $rs->user_id;
            $fi->media_priv  = (bool) $rs->media_private;
            $fi->media_dt    = (int) strtotime($rs->media_dt);
            $fi->media_dtstr = Date::str('%Y-%m-%d %H:%M', $fi->media_dt);

            $fi->media_image   = false;
            $fi->media_preview = false;

            if (!$this->core->auth()->check($this->core->auth()->makePermissions([
                $this->core->auth()::PERMISSION_MEDIA_ADMIN,
            ]), $this->core->blog()->id())
                && $this->core->auth()->userID() != $fi->media_user) {
                $fi->del      = false;
                $fi->editable = false;
            }

            $type_prefix = explode('/', (string) $fi->type);
            $type_prefix = $type_prefix[0];

            switch ($type_prefix) {
                case 'image':
                    $fi->media_image = true;
                    $fi->media_icon  = 'image';

                    break;
                case 'audio':
                    $fi->media_icon = 'audio';

                    break;
                case 'text':
                    $fi->media_icon = 'text';

                    break;
                case 'video':
                    $fi->media_icon = 'video';

                    break;
                default:
                    $fi->media_icon = 'blank';
            }
            switch ($fi->type) {
                case 'application/msword':
                case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                case 'application/vnd.oasis.opendocument.text':
                case 'application/vnd.sun.xml.writer':
                case 'application/pdf':
                case 'application/postscript':
                    $fi->media_icon = 'document';

                    break;
                case 'application/msexcel':
                case 'vnd.openxmlformats-officedocument.spreadsheetml.sheet':
                case 'application/vnd.oasis.opendocument.spreadsheet':
                case 'application/vnd.sun.xml.calc':
                    $fi->media_icon = 'spreadsheet';

                    break;
                case 'application/mspowerpoint':
                case 'application/vnd.openxmlformats-officedocument.presentationml.presentation':
                case 'application/vnd.oasis.opendocument.presentation':
                case 'application/vnd.sun.xml.impress':
                    $fi->media_icon = 'presentation';

                    break;
                case 'application/x-debian-package':
                case 'application/x-bzip':
                case 'application/x-bzip2':
                case 'application/x-gzip':
                case 'application/x-java-archive':
                case 'application/rar':
                case 'application/x-redhat-package-manager':
                case 'application/x-tar':
                case 'application/x-gtar':
                case 'application/zip':
                    $fi->media_icon = 'package';

                    break;
                case 'application/octet-stream':
                    $fi->media_icon = 'executable';

                    break;
                case 'application/x-shockwave-flash':
                    $fi->media_icon = 'video';

                    break;
                case 'application/ogg':
                    $fi->media_icon = 'audio';

                    break;
                case 'text/html':
                    $fi->media_icon = 'html';

                    break;
            }

            $fi->media_type = $fi->media_icon;
            $fi->media_icon = sprintf($this->icon_img, $fi->media_icon);

            # Thumbnails
            $fi->media_thumb = [];
            $p               = Path::info($fi->relname);

            $alpha   = $this->hasMediaAlphaLayer($p['extension']);
            $pattern = $this->getThumbnailFilePattern($p['extension']);

            $thumb = sprintf(
                $pattern,
                $this->root . '/' . $p['dirname'],
                $p['base'],
                '%s'
            );
            $thumb_url = sprintf(
                $pattern,
                $this->root_url . $p['dirname'],
                $p['base'],
                '%s'
            );

            # Cleaner URLs
            $thumb_url = (string) preg_replace('#\./#', '/', $thumb_url);
            $thumb_url = (string) preg_replace('#(?<!:)/+#', '/', $thumb_url);

            $thumb_alt     = '';
            $thumb_url_alt = '';

            if ($alpha) {
                $thumb_alt     = sprintf($this->thumb_tp, $this->root . '/' . $p['dirname'], $p['base'], '%s');
                $thumb_url_alt = sprintf($this->thumb_tp, $this->root_url . $p['dirname'], $p['base'], '%s');
                # Cleaner URLs
                $thumb_url_alt = (string) preg_replace('#\./#', '/', $thumb_url_alt);
                $thumb_url_alt = (string) preg_replace('#(?<!:)/+#', '/', $thumb_url_alt);
            }

            foreach (array_keys($this->thumb_sizes) as $suffix) {
                if (file_exists(sprintf($thumb, $suffix))) {
                    $fi->media_thumb[$suffix] = sprintf($thumb_url, $suffix);
                } elseif (($alpha) && file_exists(sprintf($thumb_alt, $suffix))) {
                    $fi->media_thumb[$suffix] = sprintf($thumb_url_alt, $suffix);
                }
            }

            if ($fi->media_type === 'image') {
                $fi->media_preview = true;
                if (isset($fi->media_thumb['sq'])) {
                    $fi->media_icon = $fi->media_thumb['sq'];
                } elseif (strtolower($p['extension']) === 'svg') {
                    $fi->media_icon = $this->root_url . $p['dirname'] . '/' . $p['base'] . '.' . $p['extension'];
                }
            }

            return $fi;
        }

        return null;
    }

    public function setFileSort(string $type = 'name-asc'): void
    {
        if (in_array($type, [
            'size-asc',
            'size-desc',
            'name-asc',
            'name-desc',
            'date-asc',
            'date-desc',
            'title-asc',
            'title-desc',
        ])) {
            $this->file_sort = $type;
        }
    }

    /**
     * Sort calllback
     *
     * @param      MediaFile  $a      1st media
     * @param      MediaFile  $b      2nd media
     */
    protected function sortFileHandler(?MediaFile $a, ?MediaFile $b): int
    {
        if (is_null($a) || is_null($b)) {
            return is_null($a) ? 1 : -1;
        }

        return match ($this->file_sort) {
            'title-asc'  => $this->core->lexical()->lexicalCompare($a->media_title, $b->media_title, $this->core->lexical()::ADMIN_LOCALE),
            'title-desc' => $this->core->lexical()->lexicalCompare($b->media_title, $a->media_title, $this->core->lexical()::ADMIN_LOCALE),
            'size-asc'   => $a->size     <=> $b->size,
            'size-desc'  => $b->size     <=> $a->size,
            'date-asc'   => $a->media_dt <=> $b->media_dt,
            'date-desc'  => $b->media_dt <=> $a->media_dt,
            'name-asc'   => $this->core->lexical()->lexicalCompare($a->basename, $b->basename, $this->core->lexical()::ADMIN_LOCALE),
            'name-desc'  => $this->core->lexical()->lexicalCompare($b->basename, $a->basename, $this->core->lexical()::ADMIN_LOCALE),
            default      => $this->core->lexical()->lexicalCompare($a->basename, $b->basename, $this->core->lexical()::ADMIN_LOCALE),
        };
    }

    public function getDir(bool $sort_dirs = true, bool $sort_files = true, $type = null): void
    {
        if ($this->root_missing) {
            return;
        }

        if ($type) {
            $this->type = $type;
        }

        $media_dir = $this->relpwd ?: '.';

        $sql = new SelectStatement();
        $sql
            ->columns([
                'media_file',
                'media_id',
                'media_path',
                'media_title',
                'media_meta',
                'media_dt',
                'media_creadt',
                'media_upddt',
                'media_private',
                'user_id',
            ])
            ->from($this->table)
            ->where('media_path = ' . $sql->quote($this->path))
            ->and('media_dir = ' . $sql->quote($media_dir));

        if (!$this->core->auth()->check($this->core->auth()->makePermissions([
            $this->core->auth()::PERMISSION_MEDIA_ADMIN,
        ]), $this->core->blog()->id())) {
            $list = ['media_private <> 1'];
            if ($user_id = $this->core->auth()->userID()) {
                $list[] = 'user_id = ' . $sql->quote($user_id);
            }
            $sql->and($sql->orGroup($list));
        }

        $rs = $sql->select();

        // Get list of private files in dir
        $sql = new SelectStatement();
        $sql
            ->columns([
                'media_file',
                'media_id',
                'media_path',
                'media_title',
                'media_meta',
                'media_dt',
                'media_creadt',
                'media_upddt',
                'media_private',
                'user_id',
            ])
            ->from($this->table)
            ->where('media_path = ' . $sql->quote($this->path))
            ->and('media_dir = ' . $sql->quote($media_dir))
            ->and('media_private = 1');

        $privates = [];
        if (($rsp = $sql->select()) instanceof MetaRecord) {
            while ($rsp->fetch()) {
                # File in subdirectory, forget about it!
                if (dirname($rsp->media_file) !== '.' && dirname($rsp->media_file) !== $this->relpwd) {
                    continue;
                }
                if (($f = $this->fileRecord($rsp)) instanceof MediaFile) {
                    $privates[] = $f->relname;
                }
            }
        }

        // Get directory content but whitout sorting files as it will be done after (see below)

        $dir         = Path::clean($this->pwd);
        $directories = [];
        $files       = [];

        try {
            $dirfiles = new DirectoryIterator($dir);
            foreach ($dirfiles as $file) {
                $fullname = $file->getPathname();
                if ($this->inJail($fullname) && !$this->isExclude($fullname)) {
                    $filename = $file->getFilename();
                    if ($file->isDir()) {
                        if ($filename !== '.') {
                            $directory = new MediaFile($fullname, $this->root, $this->root_url);
                            if ($filename === '..') {
                                $directory->parent = true;
                            }
                            $directories[] = $directory;
                        }
                    } elseif (!str_starts_with($filename, '.') && !$this->isFileExclude($filename)) {
                        $files[] = new MediaFile($fullname, $this->root, $this->root_url);
                    }
                }
            }
        } catch (Exception) {
            throw new Exception('Unable to read directory.');
        }

        $this->dir = [
            'dirs'  => $directories,
            'files' => $files,
        ];

        $f_res = [];
        $p_dir = $this->dir;

        # If type is set, remove items from p_dir
        if ($this->type !== '') {
            foreach ($p_dir['files'] as $k => $f) {
                if ($f->type_prefix != $this->type) {
                    unset($p_dir['files'][$k]);
                }
            }
        }

        $f_reg = [];
        if ($rs instanceof MetaRecord) {
            while ($rs->fetch()) {
                # File in subdirectory, forget about it!
                if (dirname($rs->media_file) !== '.' && dirname($rs->media_file) !== $this->relpwd) {
                    continue;
                }

                if ($this->inFiles($rs->media_file)) {
                    if (($f = $this->fileRecord($rs)) instanceof MediaFile) {
                        if (isset($f_reg[$rs->media_file])) {
                            # That media is duplicated in the database,
                            # time to do a bit of house cleaning.
                            $sql = new DeleteStatement();
                            $sql
                                ->from($this->table)
                                ->where('media_id = ' . $f->media_id);

                            $sql->delete();
                        } else {
                            $f_res[]                = $f;
                            $f_reg[$rs->media_file] = 1;
                        }
                    }
                } elseif ($p_dir['files'] !== [] && $this->relpwd === '') {
                    # Physical file does not exist remove it from DB
                    # Because we don't want to erase everything on
                    # dotclear upgrade, do it only if there are files
                    # in directory and directory is root
                    $sql = new DeleteStatement();
                    $sql
                        ->from($this->table)
                        ->where('media_path = ' . $sql->quote($this->path))
                        ->and('media_file = ' . $sql->quote($rs->media_file));

                    $sql->delete();
                    $this->callFileHandler(Files::getMimeType($rs->media_file), 'remove', $this->pwd . '/' . $rs->media_file);
                }
            }
        }

        $this->dir['files'] = $f_res;
        foreach ($this->dir['dirs'] as $v) {
            $v->media_icon = sprintf($this->icon_img, ($v->parent ? 'folder-up' : 'folder'));
        }

        # Check files that don't exist in database and create them
        if ($this->core->auth()->check($this->core->auth()->makePermissions([
            $this->core->auth()::PERMISSION_MEDIA,
            $this->core->auth()::PERMISSION_MEDIA_ADMIN,
        ]), $this->core->blog()->id())) {
            $count = 0;
            foreach ($p_dir['files'] as $f) {
                if (++$count >= $this->core->config()->mediaUpdateDBLimit()) {
                    // Limit reached, keep the remaining ones for the next pass
                    break;
                }

                // Warning a file may exist in DB but in private mode for the user, so we don't have to recreate it
                if (!isset($f_reg[$f->relname]) && !in_array($f->relname, $privates) && ($id = $this->createFile($f->basename, null, false, null, false)) !== false && $gf = $this->getFile($id)) {
                    $this->dir['files'][] = $gf;
                }
            }
        }

        // Finally sort final list of files (not sorted before, see above parent::getDir(...))
        if ($sort_files && $this->file_sort !== '') {
            try {
                usort($this->dir['files'], $this->sortFileHandler(...));
            } catch (Throwable) {
                // Ignore exceptions
            }
        }
    }

    public function getFile(int $id): ?MediaFile
    {
        $sql = new SelectStatement();
        $sql
            ->from($this->table)
            ->columns([
                'media_id',
                'media_path',
                'media_title',
                'media_file',
                'media_meta',
                'media_dt',
                'media_creadt',
                'media_upddt',
                'media_private',
                'user_id',
            ])
            ->where('media_path = ' . $sql->quote($this->path))
            ->and('media_id = ' . $id);

        if (!$this->core->auth()->check($this->core->auth()->makePermissions([
            $this->core->auth()::PERMISSION_MEDIA_ADMIN,
        ]), $this->core->blog()->id())) {
            $list = ['media_private <> 1'];
            if ($user_id = $this->core->auth()->userID()) {
                $list[] = 'user_id = ' . $sql->quote($user_id);
            }
            $sql->and($sql->orGroup($list));
        }

        $rs = $sql->select();

        return $rs instanceof MetaRecord ? $this->fileRecord($rs) : null;
    }

    public function searchMedia(string $query): bool
    {
        if ($this->root_missing) {
            return false;
        }

        if ($query === '') {
            return false;
        }

        $sql = new SelectStatement();
        $sql
            ->from($this->table)
            ->columns([
                'media_file',
                'media_id',
                'media_path',
                'media_title',
                'media_meta',
                'media_dt',
                'media_creadt',
                'media_upddt',
                'media_private',
                'user_id',
            ])
            ->where('media_path = ' . $sql->quote($this->path))
            ->and($sql->orGroup([
                $sql->like('media_title', '%' . $sql->escape($query) . '%'),
                $sql->like('media_file', '%' . $sql->escape($query) . '%'),
                $sql->like('media_meta', '%<Description>%' . $sql->escape($query) . '%</Description>%'),
                $sql->like('media_meta', '%<AltText>%' . $sql->escape($query) . '%</AltText>%'),
            ]));

        if (!$this->core->auth()->check($this->core->auth()->makePermissions([
            $this->core->auth()::PERMISSION_MEDIA_ADMIN,
        ]), $this->core->blog()->id())) {
            $list = ['media_private <> 1'];
            if ($user_id = $this->core->auth()->userID()) {
                $list[] = 'user_id = ' . $sql->quote($user_id);
            }
            $sql->and($sql->orGroup($list));
        }

        $rs = $sql->select();

        $this->dir = ['dirs' => [], 'files' => []];
        $f_res     = [];
        if ($rs instanceof MetaRecord) {
            while ($rs->fetch()) {
                if (($fr = $this->fileRecord($rs)) instanceof MediaFile) {
                    $f_res[] = $fr;
                }
            }
        }
        $this->dir['files'] = $f_res;

        if ($this->file_sort !== '') {
            try {
                usort($this->dir['files'], $this->sortFileHandler(...));
            } catch (Throwable) {
                // Ignore exceptions
            }
        }

        return ((bool) count($f_res));
    }

    public function getPostMedia(int $post_id, $media_id = null, $link_type = null): array
    {
        $params = [
            'post_id'    => $post_id,
            'media_path' => $this->path,
        ];
        if ($media_id) {
            $params['media_id'] = (int) $media_id;
        }
        if ($link_type) {
            $params['link_type'] = $link_type;
        }
        $rs = $this->core->postMedia()->getPostMedia($params);

        $res = [];

        while ($rs->fetch()) {
            if (($f = $this->fileRecord($rs)) instanceof MediaFile) {
                $res[] = $f;
            }
        }

        return $res;
    }

    public function getMediaTitle(MediaFile|stdClass $file, bool $fallback = true, bool $no_filename = true): string
    {
        if ((string) $file->media_title !== '') {
            if (($no_filename) && $file->media_title != $file->basename && Files::tidyFileName($file->media_title) != $file->basename) {
                return $file->media_title;
            }

            return $file->media_title;
        }

        // Use metadata AltText if present
        if ($fallback && is_countable($file->media_meta) && count($file->media_meta) && is_iterable($file->media_meta)) {
            foreach ($file->media_meta as $k => $v) {
                if ((string) $v && ($k == 'AltText')) {
                    return (string) $v;
                }
            }
        }

        return '';
    }

    public function getMediaAlt(MediaFile|stdClass $file, bool $fallback = true, bool $no_filename = true): string
    {
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
            if (($no_filename) && ($file->media_title == $file->basename || Files::tidyFileName($file->media_title) == $file->basename)) {
                // Do not use media filename as title
                return '';
            }

            return $file->media_title;
        }

        return '';
    }

    public function getMediaLegend(MediaFile|stdClass $file, ?string $pattern = null, bool $dto_first = false, bool $no_date_alone = false): string
    {
        $res   = [];
        $sep   = ', ';
        $dates = 0;
        $items = 0;

        if (is_null($pattern)) {
            // Récupération réglage blog
            $pattern = $this->core->blog()->settings()->system->media_img_title_pattern;
        }
        if ((string) $pattern === '') {
            $pattern = 'Description';
        }

        $pattern = preg_split('/\s*;;\s*/', (string) $pattern);

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

        return implode($sep, array_filter($res, fn ($item): bool => (bool) trim((string) $item)));
    }

    public function rebuild(string $pwd = '', bool $recursive = false): void
    {
        if ($this->root_missing) {
            return;
        }

        if (!$this->core->auth()->isSuperAdmin()) {
            throw new UnauthorizedException(__('You are not a super administrator.'));
        }

        $this->chdir($pwd);
        parent::getDir(false, false);

        $dir = $this->dir;

        if ($recursive) {
            foreach ($dir['dirs'] as $d) {
                if (!$d->parent) {
                    $this->rebuild($d->relname, $recursive);
                }
            }
        }

        foreach ($dir['files'] as $f) {
            $this->chdir(dirname($f->relname));
            $this->createFile($f->basename);
        }

        $this->rebuildDB($pwd);
    }

    public function rebuildThumbnails(string $pwd = '', bool $recursive = false, bool $force = false): void
    {
        if ($this->root_missing) {
            return;
        }

        if (!$this->core->auth()->isSuperAdmin()) {
            throw new UnauthorizedException(__('You are not a super administrator.'));
        }

        $this->chdir($pwd);
        parent::getDir();

        $dir = $this->dir;

        if ($recursive) {
            foreach ($dir['dirs'] as $d) {
                if (!$d->parent) {
                    $this->rebuildThumbnails($d->relname, $recursive, $force);
                }
            }
        }

        foreach ($dir['files'] as $f) {
            try {
                $this->chdir(dirname($f->relname));
                $this->callFileHandler(Files::getMimeType($f->basename), 'recreate', null, $f->basename, 0, $force);
            } catch (Throwable) {
                // Ignore errors on trying to rebuild thumbnails
            }
        }
    }

    /**
     * Rebuilds database items collection. Optional <var>$pwd</var> parameter is
     * the path where to start rebuild else its the current directory
     *
     * @param      string     $pwd    The directory to rebuild
     */
    protected function rebuildDB(?string $pwd): void
    {
        if ($this->root_missing) {
            return;
        }

        $media_dir = $pwd ?: '.';

        $sql = new SelectStatement();
        $sql
            ->from($this->table)
            ->columns([
                'media_file',
                'media_id',
            ])
            ->where('media_path = ' . $sql->quote($this->path))
            ->and('media_dir = ' . $sql->quote($media_dir));

        $del_ids = [];
        if (($rs = $sql->select()) instanceof MetaRecord) {
            while ($rs->fetch()) {
                if (!is_file($this->root . '/' . $rs->media_file)) {
                    $del_ids[] = (int) $rs->media_id;
                }
            }
        }
        if ($del_ids !== []) {
            $sql = new DeleteStatement();
            $sql
                ->from($this->table)
                ->where('media_id' . $sql->in($del_ids));

            $sql->delete();
        }
    }

    public function makeDir(?string $name): void
    {
        if ($this->root_missing) {
            return;
        }

        $name = Files::tidyFileName((string) $name);
        parent::makeDir($name);

        # --BEHAVIOR-- coreAfterMediaDirCreate -- string|null
        $this->core->behavior()->callBehavior('coreAfterMediaDirCreate', $name);
    }

    public function removeDir(?string $directory): void
    {
        if ($this->root_missing) {
            return;
        }

        parent::removeDir($directory);

        # --BEHAVIOR-- coreAfterMediaDirDelete - string|null
        $this->core->behavior()->callBehavior('coreAfterMediaDirDelete', $directory);
    }

    public function createFile(string $name, ?string $title = null, bool $private = false, $dt = null, bool $force = true): false|int
    {
        if ($this->root_missing) {
            return false;
        }

        if (!$this->core->auth()->check($this->core->auth()->makePermissions([
            $this->core->auth()::PERMISSION_MEDIA,
            $this->core->auth()::PERMISSION_MEDIA_ADMIN,
        ]), $this->core->blog()->id())) {
            throw new UnauthorizedException(__('Permission denied.'));
        }

        $file = $this->pwd . '/' . $name;
        if (!file_exists($file)) {
            return false;
        }

        $media_file = $this->relpwd !== '' ? Path::clean($this->relpwd . '/' . $name) : Path::clean($name);
        $media_type = Files::getMimeType($name);

        $cur = $this->openMediaCursor();

        $sql = new SelectStatement();
        $sql
            ->from($this->table)
            ->column('media_id')
            ->where('media_path = ' . $sql->quote($this->path))
            ->and('media_file = ' . $sql->quote($media_file));

        $rs = $sql->select();

        if (!$rs instanceof MetaRecord || $rs->isEmpty()) {
            $this->core->db()->con()->writeLock($this->table);

            try {
                $sql = new SelectStatement();
                $sql
                    ->from($this->table)
                    ->column($sql->max('media_id'));

                $rsId     = $sql->select();
                $media_id = $rsId instanceof MetaRecord ? (int) $rsId->f(0) + 1 : 1;

                $cur->media_id     = $media_id;
                $cur->user_id      = $this->core->auth()->userID();
                $cur->media_path   = $this->path;
                $cur->media_file   = $media_file;
                $cur->media_dir    = dirname($media_file);
                $cur->media_creadt = date('Y-m-d H:i:s');
                $cur->media_upddt  = date('Y-m-d H:i:s');

                $cur->media_title   = !$title || $title === $name ? '' : $title;
                $cur->media_private = (int) $private;

                if ($dt) {
                    $cur->media_dt = (string) $dt;
                } else {
                    $ft = filemtime($file);
                    if ($ft === false) {
                        $ft = 0;
                    }
                    $cur->media_dt = Date::strftime('%Y-%m-%d %H:%M:%S', $ft);
                }

                try {
                    $cur->insert();
                } catch (Throwable $e) {
                    @unlink($name);

                    throw $e;
                }
                $this->core->db()->con()->unlock();
            } catch (Throwable $e) {
                $this->core->db()->con()->unlock();

                throw $e;
            }
        } else {
            $media_id = (int) $rs->media_id;

            $cur->media_upddt = date('Y-m-d H:i:s');

            $sql = new UpdateStatement();
            $sql->where('media_id = ' . $media_id);

            $sql->update($cur);
        }

        $this->callFileHandler($media_type, 'create', $cur, $name, $media_id, $force);

        return $media_id;
    }

    public function updateFile(MediaFile $file, MediaFile $newFile): void
    {
        if ($this->root_missing) {
            return;
        }

        if (!$this->core->auth()->check($this->core->auth()->makePermissions([
            $this->core->auth()::PERMISSION_MEDIA,
            $this->core->auth()::PERMISSION_MEDIA_ADMIN,
        ]), $this->core->blog()->id())) {
            throw new UnauthorizedException(__('Permission denied.'));
        }

        $id = $file->media_id;

        if ($id === 0) {
            throw new BadRequestException('No file ID');
        }

        if (!$this->core->auth()->check($this->core->auth()->makePermissions([
            $this->core->auth()::PERMISSION_MEDIA_ADMIN,
        ]), $this->core->blog()->id())
            && $this->core->auth()->userID() != $file->media_user) {
            throw new UnauthorizedException(__('You are not the file owner.'));
        }

        $cur = $this->openMediaCursor();

        # We need to tidy newFile basename. If dir isn't empty, concat to basename
        $newFile->relname = Files::tidyFileName($newFile->basename);
        if ($newFile->dir) {
            $newFile->relname = $newFile->dir . '/' . $newFile->relname;
        }

        if ($file->relname != $newFile->relname) {
            $newFile->file = $this->root . '/' . $newFile->relname;

            if ($this->isFileExclude($newFile->relname)) {
                throw new UnauthorizedException(__('This file is not allowed.'));
            }

            if (file_exists($newFile->file)) {
                throw new UnauthorizedException(__('New file already exists.'));
            }

            $this->moveFile($file->relname, $newFile->relname);

            $cur->media_file = $newFile->relname;
            $cur->media_dir  = dirname($newFile->relname);
        }

        $cur->media_title   = $newFile->media_title;
        $cur->media_dt      = $newFile->media_dtstr;
        $cur->media_upddt   = date('Y-m-d H:i:s');
        $cur->media_private = (int) $newFile->media_priv;

        if ($newFile->media_meta instanceof SimpleXMLElement) {
            $cur->media_meta = $newFile->media_meta->asXML();
        }

        $sql = new UpdateStatement();
        $sql->where('media_id = ' . $id);

        $sql->update($cur);

        $this->callFileHandler((string) $file->type, 'update', $file, $newFile);
    }

    public function uploadFile(string $tmp, string $dest, bool $overwrite = false, ?string $title = null, bool $private = false): string
    {
        if ($this->root_missing) {
            return '';
        }

        if (!$this->core->auth()->check($this->core->auth()->makePermissions([
            $this->core->auth()::PERMISSION_MEDIA,
            $this->core->auth()::PERMISSION_MEDIA_ADMIN,
        ]), $this->core->blog()->id())) {
            throw new UnauthorizedException(__('Permission denied.'));
        }

        $dest = Files::tidyFileName($dest);

        parent::uploadFile($tmp, $dest, $overwrite);

        $id = $this->createFile($dest, $title, $private);

        return $id === false ? '' : (string) $id;
    }

    public function uploadBits(string $name, string $bits): string
    {
        if ($this->root_missing) {
            return '';
        }

        if (!$this->core->auth()->check($this->core->auth()->makePermissions([
            $this->core->auth()::PERMISSION_MEDIA,
            $this->core->auth()::PERMISSION_MEDIA_ADMIN,
        ]), $this->core->blog()->id())) {
            throw new UnauthorizedException(__('Permission denied.'));
        }

        $name = Files::tidyFileName($name);

        parent::uploadBits($name, $bits);

        $id = $this->createFile($name, null, false);

        return $id === false ? '' : (string) $id;
    }

    public function removeFile(?string $file): void
    {
        if ($this->root_missing) {
            return;
        }

        if (!$this->core->auth()->check($this->core->auth()->makePermissions([
            $this->core->auth()::PERMISSION_MEDIA,
            $this->core->auth()::PERMISSION_MEDIA_ADMIN,
        ]), $this->core->blog()->id())) {
            throw new UnauthorizedException(__('Permission denied.'));
        }

        $media_file = $this->relpwd !== '' ? Path::clean($this->relpwd . '/' . $file) : Path::clean($file);

        $sql = new DeleteStatement();
        $sql
            ->from($this->table)
            ->where('media_path = ' . $sql->quote($this->path))
            ->and('media_file = ' . $sql->quote($media_file));

        if (!$this->core->auth()->check($this->core->auth()->makePermissions([
            $this->core->auth()::PERMISSION_MEDIA_ADMIN,
        ]), $this->core->blog()->id())) {
            $sql->and('user_id = ' . $sql->quote((string) $this->core->auth()->userID()));
        }

        $sql->delete();

        if ($this->core->db()->con()->changes() == 0) {
            throw new BadRequestException(__('File does not exist in the database.'));
        }

        parent::removeFile($file);

        $this->callFileHandler(Files::getMimeType($media_file), 'remove', $file);
    }

    public function getDBDirs(): array
    {
        $dir = [];

        $sql = new SelectStatement();
        $sql
            ->from($this->table)
            ->column('distinct media_dir')
            ->where('media_path = ' . $sql->quote($this->path));

        if (($rs = $sql->select()) instanceof MetaRecord) {
            while ($rs->fetch()) {
                if (is_dir($this->root . '/' . $rs->media_dir)) {
                    $dir[] = ($rs->media_dir == '.' ? '' : $rs->media_dir);
                }
            }
        }

        return $dir;
    }

    public function inflateZipFile(MediaFile $f, bool $create_dir = true): string
    {
        if ($this->root_missing) {
            return '';
        }

        $zip  = new Unzip($f->file);
        $list = $zip->getList(false, '#(^|/)(__MACOSX|\.svn|\.hg.*|\.git.*|\.DS_Store|\.directory|Thumbs\.db)(/|$)#');

        if ($create_dir) {
            $zip_root_dir = $zip->getRootDir();
            if ($zip_root_dir != false) {
                $destination = $zip_root_dir;
                $target      = $f->dir;
            } else {
                $destination = preg_replace('/\.([^.]+)$/', '', $f->basename);
                $target      = $f->dir . '/' . $destination;
            }

            if (is_dir($f->dir . '/' . $destination)) {
                throw new UnauthorizedException(sprintf(__('Extract destination directory %s already exists.'), dirname($f->relname) . '/' . $destination));
            }
        } else {
            $target      = $f->dir;
            $destination = '';
        }

        $zip->unzipAll($target);
        $zip->close();

        // Clean-up all extracted filenames
        $clean = function ($name) {
            $n = Text::deaccent($name);
            $n = (string) preg_replace('/^[.]/u', '', $n);

            return (string) preg_replace('/[^A-Za-z0-9._\-\/]/u', '_', $n);
        };
        if ($list !== false) {
            foreach ($list as $zk => $zv) {
                // Check if extracted file exists
                $zf = $target . '/' . $zk;
                if (!$zv['is_dir'] && file_exists($zf)) {
                    $zt = $clean($zf);
                    if ($zt != $zf) {
                        rename($zf, $zt);
                    }
                }
            }
        }

        return dirname($f->relname) . '/' . $destination;
    }

    public function getZipContent(MediaFile $f): array
    {
        $zip  = new Unzip($f->file);
        $list = $zip->getList(false, '#(^|/)(__MACOSX|\.svn|\.hg.*|\.git.*|\.DS_Store|\.directory|Thumbs\.db)(/|$)#');
        $zip->close();

        // Return empty array if error occurs
        return $list !== false ? $list : [];
    }

    public function mediaFireRecreateEvent(MediaFile $f): void
    {
        $media_type = Files::getMimeType($f->basename);
        $this->callFileHandler($media_type, 'recreate', null, $f->basename, 0);
    }

    /* Image handlers
    ------------------------------------------------------- */

    public function imageThumbCreate(?Cursor $cur, string $f, int $id, bool $force = true): bool
    {
        if ($this->root_missing) {
            return false;
        }

        $file = $this->pwd . '/' . $f;

        if (!file_exists($file)) {
            return false;
        }

        $p     = Path::info($file);
        $alpha = $this->hasMediaAlphaLayer($p['extension']);
        $thumb = sprintf(
            $this->getThumbnailFilePattern($p['extension']),
            $p['dirname'],
            $p['base'],
            '%s'
        );

        try {
            $img = new ImageTools();
            $img->loadImage($file);

            $w = $img->getW();
            $h = $img->getH();

            if ($force) {
                $this->imageThumbRemove($f);
            }

            foreach ($this->thumb_sizes as $suffix => $s) {
                $thumb_file = sprintf($thumb, $suffix);
                if (!file_exists($thumb_file) && $s[0] > 0 && ($suffix == 'sq' || $w > $s[0] || $h > $s[0])) {
                    $rate = ($s[0] < 100 ? 95 : ($s[0] < 600 ? 90 : 85));
                    $img->resize($s[0], $s[0], $s[1]);
                    $img->output(($alpha ? strtolower($p['extension']) : 'jpeg'), $thumb_file, $rate);
                    $img->loadImage($file);
                }
            }
            $img->close();
        } catch (Throwable $e) {
            if (!$cur instanceof Cursor) {
                # Called only if Cursor is null (public call)
                throw $e;
            }
        }

        return true;
    }

    /**
     * Update image thumbnails
     *
     * @param      MediaFile  $file     The file
     * @param      MediaFile  $newFile  The new file
     */
    protected function imageThumbUpdate(MediaFile $file, MediaFile $newFile): bool
    {
        if ($this->root_missing) {
            return false;
        }

        if ($file->relname !== $newFile->relname) {
            $p         = Path::info($file->relname);
            $pattern   = $this->getThumbnailFilePattern($p['extension']);
            $thumb_old = sprintf(
                $pattern,
                $p['dirname'],
                $p['base'],
                '%s'
            );

            $p         = Path::info($newFile->relname);
            $pattern   = $this->getThumbnailFilePattern($p['extension']);
            $thumb_new = sprintf(
                $pattern,
                $p['dirname'],
                $p['base'],
                '%s'
            );

            foreach (array_keys($this->thumb_sizes) as $suffix) {
                try {
                    parent::moveFile(sprintf($thumb_old, $suffix), sprintf($thumb_new, $suffix));
                } catch (Throwable) {
                }
            }

            return true;
        }

        return false;
    }

    public function imageThumbRemove(string $f): bool
    {
        if ($this->root_missing) {
            return false;
        }

        $p     = Path::info($f);
        $thumb = sprintf(
            $this->getThumbnailFilePattern($p['extension']),
            '',
            $p['base'],
            '%s'
        );

        foreach (array_keys($this->thumb_sizes) as $suffix) {
            try {
                parent::removeFile(sprintf($thumb, $suffix));
            } catch (Throwable) {
            }
        }

        return true;
    }

    /**
     * Create image meta
     *
     * @param      Cursor  $cur    The Cursor
     * @param      string  $f      Image filename
     * @param      int     $id     The media identifier
     */
    protected function imageMetaCreate(Cursor $cur, string $f, int $id): bool
    {
        $file = $this->pwd . '/' . $f;

        if (!file_exists($file)) {
            return false;
        }

        $xml  = new XmlTag('meta');
        $meta = ImageMeta::readMeta($file);
        $xml->insertNode($meta);

        $c             = $this->openMediaCursor();
        $c->media_meta = $xml->toXML();

        // If a non empty Title exists in metatada and
        // - the current media title is empty
        // - or the current media title si equal to the filename
        // then use it instead for media title

        if ($meta['Title'] && ($cur->media_title === '' || $cur->media_title === basename((string) $cur->media_file))) {
            $c->media_title = $meta['Title'];
        }

        if ($meta['DateTimeOriginal'] && (is_null($cur->media_dt) || $cur->media_dt === '')) {
            # We set picture time to user timezone
            $media_ts = strtotime((string) $meta['DateTimeOriginal']);
            if ($media_ts !== false) {
                $o           = Date::getTimeOffset($this->core->auth()->getInfo('user_tz'), $media_ts);
                $c->media_dt = Date::str('%Y-%m-%d %H:%M:%S', $media_ts + $o);
            }
        }

        # --BEHAVIOR-- coreBeforeImageMetaCreate -- Cursor
        $this->core->behavior()->callBehavior('coreBeforeImageMetaCreate', $c);

        $sql = new UpdateStatement();
        $sql->where('media_id = ' . $id);

        $sql->update($c);

        return true;
    }

    public static function audioPlayer(string $type, string $url, ?string $player = null, $args = null, bool $fallback = false, bool $preload = true, string $alt = '', string $descr = ''): string
    {
        $aria_label  = $alt   !== '' ? ' aria-label="' . Html::escapeHTML($alt) . '"' : '';
        $description = $descr !== '' ? '<p>' . $descr . '</p>' : '';

        return
        '<audio controls preload="' . ($preload ? 'auto' : 'none') . '"' . $aria_label . '>' .
        '<source src="' . $url . '">' .
        $description .
        '</audio>';
    }

    public static function videoPlayer(string $type, string $url, ?string $player = null, $args = null, bool $fallback = false, bool $preload = true, string $alt = '', string $descr = ''): string
    {
        $video = '';
        if ($type !== 'video/x-flv') {
            // Cope with width and height, if given
            $width  = 400;
            $height = 300;
            if (is_array($args)) {
                if (!empty($args['width'])) {
                    $width = (int) $args['width'];
                }
                if (!empty($args['height'])) {
                    $height = (int) $args['height'];
                }
            }

            $aria_label  = $alt   !== '' ? ' aria-label="' . Html::escapeHTML($alt) . '"' : '';
            $description = $descr !== '' ? '<p>' . $descr . '</p>' : '';

            $video = '<video controls preload="' . ($preload ? 'auto' : 'none') . '"' .
                ($width !== 0 ? ' width="' . $width . '"' : '') .
                ($height !== 0 ? ' height="' . $height . '"' : '') . $aria_label . '>' .
                '<source src="' . $url . '">' .
                $description .
                '</video>';
        }

        return $video;
    }

    public static function mp3player(string $url, ?string $player = null, $args = null, bool $fallback = false, bool $preload = true): string
    {
        return self::audioPlayer('audio/mp3', $url, $player, $args, false, $preload);
    }
}
