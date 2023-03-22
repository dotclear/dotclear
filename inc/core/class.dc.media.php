<?php
/**
 * @brief Dotclear media manage
 *
 * This class handles Dotclear media items.
 *
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

use Dotclear\Helper\Html\XmlTag;
use Dotclear\Helper\Text;

class dcMedia extends filemanager
{
    // Constants

    /**
     * Media table name
     *
     * @var        string
     */
    public const MEDIA_TABLE_NAME = 'media';

    // Properties

    /**
     * Database connection
     *
     * @var object
     */
    protected $con;

    /**
     * Media table name
     *
     * @var string
     */
    protected $table;

    /**
     * Media type filter
     *
     * Correspond, if set, to the base mimetype (ex: "image" for "image/jpg" mimetype)
     * Might be image, audio, text, vidéo, application
     *
     * @var string
     */
    protected $type;

    /**
     * Sort criteria
     *
     * @var string
     */
    protected $file_sort = 'name-asc';

    /**
     * Current path
     *
     * @var string
     */
    protected $path;

    /**
     * Ccurrent relative path
     *
     * @var string
     */
    protected $relpwd;

    /**
     * Stack of callbacks
     *
     * @var array
     */
    protected $file_handler = [];

    /**
     * Post media instance
     *
     * @var dcPostMedia
     */
    protected $postmedia;

    /**
     * Thumbnail file pattern
     *
     * @var string
     */
    public $thumb_tp = '%s/.%s_%s.jpg';

    /**
     * Thumbnail file pattern (PNG with alpha layer)
     *
     * @var string
     */
    public $thumb_tp_alpha = '%s/.%s_%s.png';

    /**
     * Thumbnail file pattern (WebP)
     *
     * @var string
     */
    public $thumb_tp_webp = '%s/.%s_%s.webp';

    /**
     * Tubmnail sizes:
     * - m: medium image
     * - s: small image
     * - t: thumbnail image
     * - sq: square image
     *
     * @var array(<string>, <array>(<int>, <string>, <string>))
     */
    public $thumb_sizes = [
        'm'  => [448, 'ratio', 'medium'],
        's'  => [240, 'ratio', 'small'],
        't'  => [100, 'ratio', 'thumbnail'],
        'sq' => [48, 'crop', 'square'],
    ];

    /**
     * Icon file pattern
     *
     * @var string
     */
    public $icon_img = 'images/media/%s.svg';

    /**
     * Constructs a new instance.
     *
     * @param      string     $type   The media type filter
     *
     * @throws     Exception
     */
    public function __construct(string $type = '')
    {
        $this->con       = dcCore::app()->con;
        $this->postmedia = new dcPostMedia();

        if (dcCore::app()->blog == null) {
            throw new Exception(__('No blog defined.'));
        }

        $this->table = dcCore::app()->prefix . self::MEDIA_TABLE_NAME;
        $root        = dcCore::app()->blog->public_path;

        if (preg_match('#^http(s)?://#', (string) dcCore::app()->blog->settings->system->public_url)) {
            $root_url = rawurldecode(dcCore::app()->blog->settings->system->public_url);
        } else {
            $root_url = rawurldecode(dcCore::app()->blog->host . path::clean(dcCore::app()->blog->settings->system->public_url));
        }

        if (!is_dir($root)) {
            # Check public directory
            if (dcCore::app()->auth->isSuperAdmin()) {
                throw new Exception(__('There is no writable directory /public/ at the location set in about:config "public_path". You must create this directory with sufficient rights (or change this setting).'));
            }

            throw new Exception(__('There is no writable root directory for the media manager. You should contact your administrator.'));
        }

        $this->type = $type;

        parent::__construct($root, $root_url);
        $this->chdir('');

        $this->path = dcCore::app()->blog->settings->system->public_path;

        $this->addExclusion(DC_RC_PATH);
        $this->addExclusion(__DIR__ . '/../');

        $this->exclude_pattern = dcCore::app()->blog->settings->system->media_exclusion;

        # Event handlers
        $this->addFileHandler('image/jpeg', 'create', [$this, 'imageThumbCreate']);
        $this->addFileHandler('image/png', 'create', [$this, 'imageThumbCreate']);
        $this->addFileHandler('image/gif', 'create', [$this, 'imageThumbCreate']);
        $this->addFileHandler('image/webp', 'create', [$this, 'imageThumbCreate']);

        $this->addFileHandler('image/png', 'update', [$this, 'imageThumbUpdate']);
        $this->addFileHandler('image/jpeg', 'update', [$this, 'imageThumbUpdate']);
        $this->addFileHandler('image/gif', 'update', [$this, 'imageThumbUpdate']);
        $this->addFileHandler('image/webp', 'update', [$this, 'imageThumbUpdate']);

        $this->addFileHandler('image/png', 'remove', [$this, 'imageThumbRemove']);
        $this->addFileHandler('image/jpeg', 'remove', [$this, 'imageThumbRemove']);
        $this->addFileHandler('image/gif', 'remove', [$this, 'imageThumbRemove']);
        $this->addFileHandler('image/webp', 'remove', [$this, 'imageThumbRemove']);

        $this->addFileHandler('image/jpeg', 'create', [$this, 'imageMetaCreate']);
        $this->addFileHandler('image/webp', 'create', [$this, 'imageMetaCreate']);

        $this->addFileHandler('image/jpeg', 'recreate', [$this, 'imageThumbCreate']);
        $this->addFileHandler('image/png', 'recreate', [$this, 'imageThumbCreate']);
        $this->addFileHandler('image/gif', 'recreate', [$this, 'imageThumbCreate']);
        $this->addFileHandler('image/webp', 'recreate', [$this, 'imageThumbCreate']);

        # Thumbnails sizes
        $this->thumb_sizes['m'][0] = abs(dcCore::app()->blog->settings->system->media_img_m_size);
        $this->thumb_sizes['s'][0] = abs(dcCore::app()->blog->settings->system->media_img_s_size);
        $this->thumb_sizes['t'][0] = abs(dcCore::app()->blog->settings->system->media_img_t_size);

        # Thumbnails sizes names
        $this->thumb_sizes['m'][2]  = __($this->thumb_sizes['m'][2]);
        $this->thumb_sizes['s'][2]  = __($this->thumb_sizes['s'][2]);
        $this->thumb_sizes['t'][2]  = __($this->thumb_sizes['t'][2]);
        $this->thumb_sizes['sq'][2] = __($this->thumb_sizes['sq'][2]);

        # --BEHAVIOR-- coreMediaConstruct
        dcCore::app()->callBehavior('coreMediaConstruct', $this);
    }

    /**
     * Changes working directory.
     *
     * @param      string  $dir    The directory name
     */
    public function chdir(?string $dir): void
    {
        parent::chdir($dir);
        $this->relpwd = preg_replace('/^' . preg_quote($this->root, '/') . '\/?/', '', $this->pwd);
    }

    /**
     * Adds a new file handler for a given media type and event.
     *
     * Available events are:
     * - create: file creation
     * - update: file update
     * - remove: file deletion
     *
     * @param      string           $type      The media type
     * @param      string           $event     The event
     * @param      callable|array   $function  The callback
     */
    public function addFileHandler(string $type, string $event, $function)
    {
        if (is_callable($function)) {
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
    protected function callFileHandler(string $type, string $event, ...$args)
    {
        if (!empty($this->file_handler[$type][$event])) {
            foreach ($this->file_handler[$type][$event] as $f) {
                $f(...$args);
            }
        }
    }

    /**
     * Returns HTML breadCrumb for media manager navigation.
     *
     * @param      string  $href   The URL pattern
     * @param      string  $last   The last item pattern
     *
     * @return     string  HTML code
     */
    public function breadCrumb(string $href, string $last = ''): string
    {
        $res = '';
        if ($this->relpwd && $this->relpwd != '.') {
            $pwd   = '';
            $arr   = explode('/', $this->relpwd);
            $count = count($arr);
            foreach ($arr as $v) {
                if (($last != '') && (0 === --$count)) {
                    $res .= sprintf($last, $v);
                } else {
                    $pwd .= rawurlencode($v) . '/';
                    $res .= '<a href="' . sprintf($href, $pwd) . '">' . $v . '</a> / ';
                }
            }
        }

        return $res;
    }

    /**
     * Get media file information from recordset
     *
     * @param      dcRecord    $rs  The recordset
     *
     * @return     fileItem  The file item.
     */
    protected function fileRecord(dcRecord $rs): ?fileItem
    {
        if ($rs->isEmpty()) {
            return null;
        }

        if (!$this->isFileExclude($this->root . '/' . $rs->media_file) && is_file($this->root . '/' . $rs->media_file)) {
            $fi = new fileItem($this->root . '/' . $rs->media_file, $this->root, $this->root_url);

            if ($this->type && $fi->type_prefix !== $this->type) {
                // Check file mimetype base (before 1st /)
                return null;
            }

            $meta = @simplexml_load_string((string) $rs->media_meta);

            $fi->editable    = true;
            $fi->media_id    = $rs->media_id;
            $fi->media_title = $rs->media_title;
            $fi->media_meta  = $meta instanceof SimpleXMLElement ? $meta : simplexml_load_string('<meta></meta>');
            $fi->media_user  = $rs->user_id;
            $fi->media_priv  = (bool) $rs->media_private;
            $fi->media_dt    = strtotime($rs->media_dt);
            $fi->media_dtstr = dt::str('%Y-%m-%d %H:%M', $fi->media_dt);

            $fi->media_image   = false;
            $fi->media_preview = false;

            if (!dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                dcAuth::PERMISSION_MEDIA_ADMIN,
            ]), dcCore::app()->blog->id)
                && dcCore::app()->auth->userID() != $fi->media_user) {
                $fi->del      = false;
                $fi->editable = false;
            }

            $type_prefix = explode('/', $fi->type);
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
            $p               = path::info($fi->relname);

            $alpha = strtolower($p['extension']) === 'png';
            $webp  = strtolower($p['extension']) === 'webp';

            $thumb = sprintf(
                ($alpha ? $this->thumb_tp_alpha :
                    ($webp ? $this->thumb_tp_webp : $this->thumb_tp)),
                $this->root . '/' . $p['dirname'],
                $p['base'],
                '%s'
            );
            $thumb_url = sprintf(
                ($alpha ? $this->thumb_tp_alpha :
                    ($webp ? $this->thumb_tp_webp : $this->thumb_tp)),
                $this->root_url . $p['dirname'],
                $p['base'],
                '%s'
            );

            # Cleaner URLs
            $thumb_url = preg_replace('#\./#', '/', $thumb_url);
            $thumb_url = preg_replace('#(?<!:)/+#', '/', $thumb_url);

            $thumb_alt     = '';
            $thumb_url_alt = '';

            if ($alpha || $webp) {
                $thumb_alt     = sprintf($this->thumb_tp, $this->root . '/' . $p['dirname'], $p['base'], '%s');
                $thumb_url_alt = sprintf($this->thumb_tp, $this->root_url . $p['dirname'], $p['base'], '%s');
                # Cleaner URLs
                $thumb_url_alt = preg_replace('#\./#', '/', $thumb_url_alt);
                $thumb_url_alt = preg_replace('#(?<!:)/+#', '/', $thumb_url_alt);
            }

            foreach (array_keys($this->thumb_sizes) as $suffix) {
                if (file_exists(sprintf($thumb, $suffix))) {
                    $fi->media_thumb[$suffix] = sprintf($thumb_url, $suffix);
                } elseif (($alpha || $webp) && file_exists(sprintf($thumb_alt, $suffix))) {
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

    /**
     * Sets the file sort.
     *
     * @param      string  $type   The type
     */
    public function setFileSort(string $type = 'name')
    {
        if (in_array($type, ['size-asc', 'size-desc', 'name-asc', 'name-desc', 'date-asc', 'date-desc'])) {
            $this->file_sort = $type;
        }
    }

    /**
     * Sort calllback
     *
     * @param      fileItem  $a      1st media
     * @param      fileItem  $b      2nd media
     *
     * @return     int
     */
    protected function sortFileHandler(?fileItem $a, ?fileItem $b): int
    {
        if (is_null($a) || is_null($b)) {
            return (is_null($a) ? 1 : -1);
        }
        switch ($this->file_sort) {
            case 'size-asc':
                if ($a->size == $b->size) {
                    return 0;
                }

                return ($a->size < $b->size) ? -1 : 1;
            case 'size-desc':
                if ($a->size == $b->size) {
                    return 0;
                }

                return ($a->size > $b->size) ? -1 : 1;
            case 'date-asc':
                if ($a->media_dt == $b->media_dt) {
                    return 0;
                }

                return ($a->media_dt < $b->media_dt) ? -1 : 1;
            case 'date-desc':
                if ($a->media_dt == $b->media_dt) {
                    return 0;
                }

                return ($a->media_dt > $b->media_dt) ? -1 : 1;
            case 'name-desc':
                return strcasecmp($b->basename, $a->basename);
            case 'name-asc':
            default:
                return strcasecmp($a->basename, $b->basename);
        }
    }

    /**
     * Gets current working directory content (using filesystem).
     */
    public function getFSDir()
    {
        parent::getDir();
    }

    /**
     * Gets current working directory content.
     *
     * @param      mixed      $type   The media type filter
     *
     * @throws     Exception
     */
    public function getDir($type = null): void
    {
        if ($type) {
            $this->type = $type;
        }

        $media_dir = $this->relpwd ?: '.';

        $sql = new dcSelectStatement();
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

        if (!dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_MEDIA_ADMIN,
        ]), dcCore::app()->blog->id)) {
            $list = ['media_private <> 1'];
            if ($user_id = dcCore::app()->auth->userID()) {
                $list[] = 'user_id = ' . $sql->quote($user_id);
            }
            $sql->and($sql->orGroup($list));
        }

        $rs = $sql->select();

        // Get list of private files in dir
        $sql = new dcSelectStatement();
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

        $rsp      = $sql->select();
        $privates = [];
        while ($rsp->fetch()) {
            # File in subdirectory, forget about it!
            if (dirname($rsp->media_file) != '.' && dirname($rsp->media_file) != $this->relpwd) {
                continue;
            }
            if ($f = $this->fileRecord($rsp)) {
                $privates[] = $f->relname;
            }
        }

        parent::getDir();

        $f_res = [];
        $p_dir = $this->dir;

        # If type is set, remove items from p_dir
        if ($this->type) {
            foreach ($p_dir['files'] as $k => $f) {
                if ($f->type_prefix != $this->type) {
                    unset($p_dir['files'][$k]);
                }
            }
        }

        $f_reg = [];

        while ($rs->fetch()) {
            # File in subdirectory, forget about it!
            if (dirname($rs->media_file) != '.' && dirname($rs->media_file) != $this->relpwd) {
                continue;
            }

            if ($this->inFiles($rs->media_file)) {
                if ($f = $this->fileRecord($rs)) {
                    if (isset($f_reg[$rs->media_file])) {
                        # That media is duplicated in the database,
                        # time to do a bit of house cleaning.
                        $sql = new dcDeleteStatement();
                        $sql
                            ->from($this->table)
                            ->where('media_id = ' . $f->media_id);

                        $sql->delete();
                    } else {
                        $f_res[]                = $f;
                        $f_reg[$rs->media_file] = 1;
                    }
                }
            } elseif (!empty($p_dir['files']) && $this->relpwd == '') {
                # Physical file does not exist remove it from DB
                # Because we don't want to erase everything on
                # dotclear upgrade, do it only if there are files
                # in directory and directory is root
                $sql = new dcDeleteStatement();
                $sql
                    ->from($this->table)
                    ->where('media_path = ' . $sql->quote($this->path))
                    ->and('media_file = ' . $sql->quote($rs->media_file));

                $sql->delete();
                $this->callFileHandler(files::getMimeType($rs->media_file), 'remove', $this->pwd . '/' . $rs->media_file);
            }
        }

        $this->dir['files'] = $f_res;
        foreach ($this->dir['dirs'] as $k => $v) {
            $v->media_icon = sprintf($this->icon_img, ($v->parent ? 'folder-up' : 'folder'));
        }

        # Check files that don't exist in database and create them
        if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_MEDIA,
            dcAuth::PERMISSION_MEDIA_ADMIN,
        ]), dcCore::app()->blog->id)) {
            foreach ($p_dir['files'] as $f) {
                // Warning a file may exist in DB but in private mode for the user, so we don't have to recreate it
                if (!isset($f_reg[$f->relname]) && !in_array($f->relname, $privates)) {
                    if (($id = $this->createFile($f->basename, null, false, null, false)) !== false) {
                        $this->dir['files'][] = $this->getFile($id);
                    }
                }
            }
        }

        try {
            usort($this->dir['files'], [$this, 'sortFileHandler']);
        } catch (Exception $e) {
            // Ignore exceptions
        }
    }

    /**
     * Gets file by its id. Returns a filteItem object.
     *
     * @param      int     $id     The file identifier
     *
     * @return     fileItem  The file.
     */
    public function getFile(int $id): ?fileItem
    {
        $sql = new dcSelectStatement();
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
            ->and('media_id = ' . (int) $id);

        if (!dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_MEDIA_ADMIN,
        ]), dcCore::app()->blog->id)) {
            $list = ['media_private <> 1'];
            if ($user_id = dcCore::app()->auth->userID()) {
                $list[] = 'user_id = ' . $sql->quote($user_id);
            }
            $sql->and($sql->orGroup($list));
        }

        $rs = $sql->select();

        return $this->fileRecord($rs);
    }

    /**
     * Search into media db (only).
     *
     * @param      string  $query  The search query
     *
     * @return     bool    true or false if nothing found
     */
    public function searchMedia(string $query): bool
    {
        if ($query === '') {
            return false;
        }

        $sql = new dcSelectStatement();
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
            ]));

        if (!dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_MEDIA_ADMIN,
        ]), dcCore::app()->blog->id)) {
            $list = ['media_private <> 1'];
            if ($user_id = dcCore::app()->auth->userID()) {
                $list[] = 'user_id = ' . $sql->quote($user_id);
            }
            $sql->and($sql->orGroup($list));
        }

        $rs = $sql->select();

        $this->dir = ['dirs' => [], 'files' => []];
        $f_res     = [];
        while ($rs->fetch()) {
            if ($fr = $this->fileRecord($rs)) {
                $f_res[] = $fr;
            }
        }
        $this->dir['files'] = $f_res;

        try {
            usort($this->dir['files'], [$this, 'sortFileHandler']);
        } catch (Exception $e) {
            // Ignore exceptions
        }

        return (count($f_res) ? true : false);
    }

    /**
     * Returns media items attached to a blog post. Result is an array containing
     * fileItems objects.
     *
     * @param      int      $post_id    The post identifier
     * @param      mixed    $media_id   The media identifier(s)
     * @param      mixed    $link_type  The link type(s)
     *
     * @return     array   Array of fileItems.
     */
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
        $rs = $this->postmedia->getPostMedia($params);

        $res = [];

        while ($rs->fetch()) {
            if ($f = $this->fileRecord($rs)) {
                $res[] = $f;
            }
        }

        return $res;
    }

    /**
     * Rebuilds database items collection. Optional <var>$pwd</var> parameter is
     * the path where to start rebuild.
     *
     * @param      string     $pwd          The directory to rebuild
     * @param      bool       $recursive    If true rebuild also sub-directories
     *
     * @throws     Exception
     */
    public function rebuild(string $pwd = '', bool $recursive = false): void
    {
        if (!dcCore::app()->auth->isSuperAdmin()) {
            throw new Exception(__('You are not a super administrator.'));
        }

        $this->chdir($pwd);
        parent::getDir();

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

    /**
     * Rebuilds thumbnails. Optional <var>$pwd</var> parameter is
     * the path where to start rebuild.
     *
     * @param      string     $pwd          The directory to rebuild
     * @param      bool       $force        Recreate existing thumbnails if True
     * @param      bool       $recursive    If true rebuild also sub-directories
     *
     * @throws     Exception
     */
    public function rebuildThumbnails(string $pwd = '', bool $recursive = false, bool $force = false): void
    {
        if (!dcCore::app()->auth->isSuperAdmin()) {
            throw new Exception(__('You are not a super administrator.'));
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
                $this->callFileHandler(files::getMimeType($f->basename), 'recreate', null, $f->basename, $force);
            } catch (Exception $e) {
                // Ignore errors on trying to rebuild thumbnails
            }
        }
    }

    /**
     * Rebuilds database items collection. Optional <var>$pwd</var> parameter is
     * the path where to start rebuild else its the current directory
     *
     * @param      string     $pwd    The directory to rebuild
     *
     * @throws     Exception
     */
    protected function rebuildDB(?string $pwd)
    {
        $media_dir = $pwd ?: '.';

        $sql = new dcSelectStatement();
        $sql
            ->from($this->table)
            ->columns([
                'media_file',
                'media_id',
            ])
            ->where('media_path = ' . $sql->quote($this->path))
            ->and('media_dir = ' . $sql->quote($media_dir));

        $rs = $sql->select();

        $del_ids = [];
        while ($rs->fetch()) {
            if (!is_file($this->root . '/' . $rs->media_file)) {
                $del_ids[] = (int) $rs->media_id;
            }
        }
        if (!empty($del_ids)) {
            $sql = new dcDeleteStatement();
            $sql
                ->from($this->table)
                ->where('media_id' . $sql->in($del_ids));

            $sql->delete();
        }
    }

    /**
     * Makes a dir.
     *
     * @param      string  $directory      the directory to create
     */
    public function makeDir(?string $directory): void
    {
        $directory = files::tidyFileName($directory);
        parent::makeDir($directory);

        # --BEHAVIOR-- coreAfterMediaDirCreate
        dcCore::app()->callBehavior('coreAfterMediaDirCreate', $directory);
    }

    /**
     * Remove a dir.
     *
     * Removes a directory which is relative to working directory.
     *
     * @param string    $directory            Directory to remove
     */
    public function removeDir(?string $directory): void
    {
        parent::removeDir($directory);

        # --BEHAVIOR-- coreAfterMediaDirDelete
        dcCore::app()->callBehavior('coreAfterMediaDirDelete', $directory);
    }

    /**
     * Creates or updates a file in database. Returns new media ID or false if
     * file does not exist.
     *
     * @param      string     $name     The file name (relative to working directory)
     * @param      string     $title    The file title
     * @param      bool       $private  File is private
     * @param      mixed      $dt       File date
     * @param      bool       $force    The force flag
     *
     * @throws     Exception
     *
     * @return     integer|bool     New media ID or false
     */
    public function createFile(string $name, ?string $title = null, bool $private = false, $dt = null, bool $force = true)
    {
        if (!dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_MEDIA,
            dcAuth::PERMISSION_MEDIA_ADMIN,
        ]), dcCore::app()->blog->id)) {
            throw new Exception(__('Permission denied.'));
        }

        $file = $this->pwd . '/' . $name;
        if (!file_exists($file)) {
            return false;
        }

        $media_file = $this->relpwd ? path::clean($this->relpwd . '/' . $name) : path::clean($name);
        $media_type = files::getMimeType($name);

        $cur = $this->con->openCursor($this->table);

        $sql = new dcSelectStatement();
        $sql
            ->from($this->table)
            ->column('media_id')
            ->where('media_path = ' . $sql->quote($this->path))
            ->and('media_file = ' . $sql->quote($media_file));

        $rs = $sql->select();

        if ($rs->isEmpty()) {
            $this->con->writeLock($this->table);

            try {
                $sql = new dcSelectStatement();
                $sql
                    ->from($this->table)
                    ->column($sql->max('media_id'));

                $rs       = $sql->select();
                $media_id = (int) $rs->f(0) + 1;

                $cur->media_id     = $media_id;
                $cur->user_id      = (string) dcCore::app()->auth->userID();
                $cur->media_path   = (string) $this->path;
                $cur->media_file   = (string) $media_file;
                $cur->media_dir    = (string) dirname($media_file);
                $cur->media_creadt = date('Y-m-d H:i:s');
                $cur->media_upddt  = date('Y-m-d H:i:s');

                $cur->media_title   = !$title ? (string) $name : (string) $title;
                $cur->media_private = (int) (bool) $private;

                if ($dt) {
                    $cur->media_dt = (string) $dt;
                } else {
                    $cur->media_dt = dt::strftime('%Y-%m-%d %H:%M:%S', filemtime($file));
                }

                try {
                    $cur->insert();
                } catch (Exception $e) {
                    @unlink($name);

                    throw $e;
                }
                $this->con->unlock();
            } catch (Exception $e) {
                $this->con->unlock();

                throw $e;
            }
        } else {
            $media_id = (int) $rs->media_id;

            $cur->media_upddt = date('Y-m-d H:i:s');

            $sql = new dcUpdateStatement();
            $sql->where('media_id = ' . $media_id);

            $sql->update($cur);
        }

        $this->callFileHandler($media_type, 'create', $cur, $name, $media_id, $force);

        return $media_id;
    }

    /**
     * Updates a file in database.
     *
     * @param      fileItem     $file     The file
     * @param      fileItem     $newFile  The new file
     *
     * @throws     Exception
     */
    public function updateFile(fileItem $file, fileItem $newFile)
    {
        if (!dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_MEDIA,
            dcAuth::PERMISSION_MEDIA_ADMIN,
        ]), dcCore::app()->blog->id)) {
            throw new Exception(__('Permission denied.'));
        }

        $id = (int) $file->media_id;

        if (!$id) {
            throw new Exception('No file ID');
        }

        if (!dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_MEDIA_ADMIN,
        ]), dcCore::app()->blog->id)
            && dcCore::app()->auth->userID() != $file->media_user) {
            throw new Exception(__('You are not the file owner.'));
        }

        $cur = $this->con->openCursor($this->table);

        # We need to tidy newFile basename. If dir isn't empty, concat to basename
        $newFile->relname = files::tidyFileName($newFile->basename);
        if ($newFile->dir) {
            $newFile->relname = $newFile->dir . '/' . $newFile->relname;
        }

        if ($file->relname != $newFile->relname) {
            $newFile->file = $this->root . '/' . $newFile->relname;

            if ($this->isFileExclude($newFile->relname)) {
                throw new Exception(__('This file is not allowed.'));
            }

            if (file_exists($newFile->file)) {
                throw new Exception(__('New file already exists.'));
            }

            $this->moveFile($file->relname, $newFile->relname);

            $cur->media_file = (string) $newFile->relname;
            $cur->media_dir  = (string) dirname($newFile->relname);
        }

        $cur->media_title   = (string) $newFile->media_title;
        $cur->media_dt      = (string) $newFile->media_dtstr;
        $cur->media_upddt   = date('Y-m-d H:i:s');
        $cur->media_private = (int) $newFile->media_priv;

        if ($newFile->media_meta instanceof SimpleXMLElement) {
            $cur->media_meta = $newFile->media_meta->asXML();
        }

        $sql = new dcUpdateStatement();
        $sql->where('media_id = ' . $id);

        $sql->update($cur);

        $this->callFileHandler($file->type, 'update', $file, $newFile);
    }

    /**
     * Uploads a file.
     *
     * @param      string     $tmp        The full path of temporary uploaded file
     * @param      string     $name       The file name (relative to working directory)me
     * @param      bool       $overwrite  File should be overwrite
     * @param      string     $title      The file title (should be string|null)
     * @param      bool       $private    File is private
     *
     * @throws     Exception
     *
     * @return     mixed      New media ID or false (should be int|false)
     */
    public function uploadFile(string $tmp, string $name, bool $overwrite = false, ?string $title = null, bool $private = false)
    {
        if (!dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_MEDIA,
            dcAuth::PERMISSION_MEDIA_ADMIN,
        ]), dcCore::app()->blog->id)) {
            throw new Exception(__('Permission denied.'));
        }

        $name = files::tidyFileName($name);

        parent::uploadFile($tmp, $name, $overwrite);

        return $this->createFile($name, $title, $private);
    }

    /**
     * Creates a file from binary content.
     *
     * @param      string     $name   The file name (relative to working directory)
     * @param      string     $bits   The binary file contentits
     *
     * @throws     Exception
     *
     * @return     string     New media ID or false
     */
    public function uploadBits(string $name, string $bits): string
    {
        if (!dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_MEDIA,
            dcAuth::PERMISSION_MEDIA_ADMIN,
        ]), dcCore::app()->blog->id)) {
            throw new Exception(__('Permission denied.'));
        }

        $name = files::tidyFileName($name);

        parent::uploadBits($name, $bits);

        $id = $this->createFile($name, null, false);

        return $id === false ? '' : (string) $id;
    }

    /**
     * Removes a file.
     *
     * @param      string     $name      filename
     *
     * @throws     Exception
     */
    public function removeFile(?string $name): void
    {
        if (!dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_MEDIA,
            dcAuth::PERMISSION_MEDIA_ADMIN,
        ]), dcCore::app()->blog->id)) {
            throw new Exception(__('Permission denied.'));
        }

        $media_file = $this->relpwd ? path::clean($this->relpwd . '/' . $name) : path::clean($name);

        $sql = new dcDeleteStatement();
        $sql
            ->from($this->table)
            ->where('media_path = ' . $sql->quote($this->path))
            ->and('media_file = ' . $sql->quote($media_file));

        if (!dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_MEDIA_ADMIN,
        ]), dcCore::app()->blog->id)) {
            $sql->and('user_id = ' . $sql->quote(dcCore::app()->auth->userID()));
        }

        $sql->delete();

        if ($this->con->changes() == 0) {
            throw new Exception(__('File does not exist in the database.'));
        }

        parent::removeFile($name);

        $this->callFileHandler(files::getMimeType($media_file), 'remove', $name);
    }

    /**
     * Root directories
     *
     * Returns an array of directory under {@link $root} directory.
     *
     * @uses fileItem
     *
     * @return array
     */
    public function getDBDirs(): array
    {
        $dir = [];

        $sql = new dcSelectStatement();
        $sql
            ->from($this->table)
            ->column('distinct media_dir')
            ->where('media_path = ' . $sql->quote($this->path));

        $rs = $sql->select();
        while ($rs->fetch()) {
            if (is_dir($this->root . '/' . $rs->media_dir)) {
                $dir[] = ($rs->media_dir == '.' ? '' : $rs->media_dir);
            }
        }

        return $dir;
    }

    /**
     * Extract zip file in current location.
     *
     * @param      fileItem     $f           fileItem object
     * @param      bool         $create_dir  Create dir
     *
     * @throws     Exception
     *
     * @return     string     destination
     */
    public function inflateZipFile(fileItem $f, bool $create_dir = true): string
    {
        $zip = new fileUnzip($f->file);
        $zip->setExcludePattern($this->exclude_pattern);
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
                throw new Exception(sprintf(__('Extract destination directory %s already exists.'), dirname($f->relname) . '/' . $destination));
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
            $n = preg_replace('/^[.]/u', '', $n);

            return preg_replace('/[^A-Za-z0-9._\-\/]/u', '_', $n);
        };
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

        return dirname($f->relname) . '/' . $destination;
    }

    /**
     * Gets the zip content.
     *
     * @param      fileItem  $f      fileItem object
     *
     * @return     array  The zip content.
     */
    public function getZipContent(fileItem $f): array
    {
        $zip  = new fileUnzip($f->file);
        $list = $zip->getList(false, '#(^|/)(__MACOSX|\.svn|\.hg.*|\.git.*|\.DS_Store|\.directory|Thumbs\.db)(/|$)#');
        $zip->close();

        return $list;
    }

    /**
     * Calls file handlers registered for recreate event.
     *
     * @param      fileItem  $f      fileItem object
     */
    public function mediaFireRecreateEvent(fileItem $f): void
    {
        $media_type = files::getMimeType($f->basename);
        $this->callFileHandler($media_type, 'recreate', null, $f->basename); // Args list to be completed as necessary (Franck)
    }

    /* Image handlers
    ------------------------------------------------------- */
    /**
     * Create image thumbnails
     *
     * @param      cursor  $cur    The cursor
     * @param      string  $f      Image filename
     * @param      bool    $force  Force creation
     *
     * @return     bool
     */
    public function imageThumbCreate(?cursor $cur, string $f, bool $force = true): bool
    {
        $file = $this->pwd . '/' . $f;

        if (!file_exists($file)) {
            return false;
        }

        $p     = path::info($file);
        $alpha = strtolower($p['extension']) === 'png';
        $webp  = strtolower($p['extension']) === 'webp';
        $thumb = sprintf(
            ($alpha ? $this->thumb_tp_alpha :
            ($webp ? $this->thumb_tp_webp :
                $this->thumb_tp)),
            $p['dirname'],
            $p['base'],
            '%s'
        );

        try {
            $img = new imageTools();
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
                    $img->output(($alpha || $webp ? strtolower($p['extension']) : 'jpeg'), $thumb_file, $rate);
                    $img->loadImage($file);
                }
            }
            $img->close();
        } catch (Exception $e) {
            if ($cur === null) {
                # Called only if cursor is null (public call)
                throw $e;
            }
        }

        return true;
    }

    /**
     * Update image thumbnails
     *
     * @param      fileItem  $file     The file
     * @param      fileItem  $newFile  The new file
     *
     * @return  bool
     */
    protected function imageThumbUpdate(fileItem $file, fileItem $newFile): bool
    {
        if ($file->relname !== $newFile->relname) {
            $p         = path::info($file->relname);
            $alpha     = strtolower($p['extension']) === 'png';
            $webp      = strtolower($p['extension']) === 'webp';
            $thumb_old = sprintf(
                ($alpha ? $this->thumb_tp_alpha :
                ($webp ? $this->thumb_tp_webp :
                    $this->thumb_tp)),
                $p['dirname'],
                $p['base'],
                '%s'
            );

            $p         = path::info($newFile->relname);
            $alpha     = strtolower($p['extension']) === 'png';
            $webp      = strtolower($p['extension']) === 'webp';
            $thumb_new = sprintf(
                ($alpha ? $this->thumb_tp_alpha :
                ($webp ? $this->thumb_tp_webp :
                    $this->thumb_tp)),
                $p['dirname'],
                $p['base'],
                '%s'
            );

            foreach ($this->thumb_sizes as $suffix => $s) {
                try {
                    parent::moveFile(sprintf($thumb_old, $suffix), sprintf($thumb_new, $suffix));
                } catch (Exception $e) {
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Remove image thumbnails
     *
     * @param      string  $f      Image filename
     *
     * @return  bool
     */
    public function imageThumbRemove(string $f): bool
    {
        $p     = path::info($f);
        $alpha = strtolower($p['extension']) === 'png';
        $webp  = strtolower($p['extension']) === 'webp';
        $thumb = sprintf(
            ($alpha ? $this->thumb_tp_alpha :
            ($webp ? $this->thumb_tp_webp :
                $this->thumb_tp)),
            '',
            $p['base'],
            '%s'
        );

        foreach (array_keys($this->thumb_sizes) as $suffix) {
            try {
                parent::removeFile(sprintf($thumb, $suffix));
            } catch (Exception $e) {
            }
        }

        return true;
    }

    /**
     * Create image meta
     *
     * @param      cursor  $cur    The cursor
     * @param      string  $f      Image filename
     * @param      int     $id     The media identifier
     *
     * @return     bool
     */
    protected function imageMetaCreate(cursor $cur, string $f, int $id): bool
    {
        $file = $this->pwd . '/' . $f;

        if (!file_exists($file)) {
            return false;
        }

        $xml  = new XmlTag('meta');
        $meta = imageMeta::readMeta($file);
        $xml->insertNode($meta);

        $c             = dcCore::app()->con->openCursor($this->table);
        $c->media_meta = $xml->toXML();

        if ($cur->media_title !== null && $cur->media_title == basename($cur->media_file)) {
            if ($meta['Title']) {
                $c->media_title = $meta['Title'];
            }
        }

        if ($meta['DateTimeOriginal'] && $cur->media_dt === '') {
            # We set picture time to user timezone
            $media_ts = strtotime($meta['DateTimeOriginal']);
            if ($media_ts !== false) {
                $o           = dt::getTimeOffset(dcCore::app()->auth->getInfo('user_tz'), $media_ts);
                $c->media_dt = dt::str('%Y-%m-%d %H:%M:%S', $media_ts + $o);
            }
        }

        # --BEHAVIOR-- coreBeforeImageMetaCreate
        dcCore::app()->callBehavior('coreBeforeImageMetaCreate', $c);

        $sql = new dcUpdateStatement();
        $sql->where('media_id = ' . $id);

        $sql->update($c);

        return true;
    }

    /**
     * Returns HTML code for audio player (HTML5)
     *
     * @param      string  $type      The audio mime type
     * @param      string  $url       The audio URL to play
     * @param      string  $player    The player URL
     * @param      mixed   $args      The player arguments
     * @param      bool    $fallback  The fallback
     * @param      bool    $preload   Add preload="auto" attribute if true, else preload="none"
     *
     * @return     string
     */
    public static function audioPlayer(string $type, string $url, ?string $player = null, $args = null, bool $fallback = false, bool $preload = true): string
    {
        return
            '<audio controls preload="' . ($preload ? 'auto' : 'none') . '">' .
            '<source src="' . $url . '">' .
            '</audio>';
    }

    /**
     * Returns HTML code for video player (HTML5)
     *
     * @param      string  $type      The video mime type
     * @param      string  $url       The video URL to play
     * @param      string  $player    The player URL
     * @param      mixed   $args      The player arguments
     * @param      bool    $fallback  The fallback (not more used)
     * @param      bool    $preload   Add preload="auto" attribute if true, else preload="none"
     *
     * @return     string
     */
    public static function videoPlayer(string $type, string $url, ?string $player = null, $args = null, bool $fallback = false, bool $preload = true): string
    {
        $video = '';

        if ($type != 'video/x-flv') {
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

            $video = '<video controls preload="' . ($preload ? 'auto' : 'none') . '"' .
                ($width ? ' width="' . $width . '"' : '') .
                ($height ? ' height="' . $height . '"' : '') . '>' .
                '<source src="' . $url . '">' .
                '</video>';
        }

        return $video;
    }

    /**
     * Returns HTML code for MP3 player (HTML5)
     *
     * @param      string  $url       The audio URL to play
     * @param      string  $player    The player URL
     * @param      mixed   $args      The player arguments
     * @param      bool    $fallback  The fallback (not more used)
     * @param      bool    $preload   Add preload="auto" attribute if true, else preload="none"
     *
     * @return     string
     */
    public static function mp3player(string $url, ?string $player = null, $args = null, bool $fallback = false, bool $preload = true): string
    {
        return self::audioPlayer('audio/mp3', $url, $player, $args, false, $preload);
    }

    /**
     * Returns HTML code for FLV player
     *
     * @param      string  $url     The url
     * @param      string  $player  The player
     * @param      mixed   $args    The arguments
     *
     * @return     string
     *
     * @deprecated since 2.15
     */
    public static function flvplayer(string $url, ?string $player = null, $args = null): string
    {
        return '';
    }
}
