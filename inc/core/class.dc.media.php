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
if (!defined('DC_RC_PATH')) {
    return;
}

class dcMedia extends filemanager
{
    protected $core;  ///< <b>dcCore</b> dcCore instance
    protected $con;   ///< <b>connection</b> Database connection
    protected $table; ///< <b>string</b> Media table name
    protected $type;  ///< <b>string</b> Media type filter
    protected $file_sort = 'name-asc';

    protected $path;
    protected $relpwd;

    protected $file_handler = []; ///< <b>array</b> Array of callbacks

    protected $postmedia;

    public $thumb_tp       = '%s/.%s_%s.jpg';  ///< <b>string</b> Thumbnail file pattern
    public $thumb_tp_alpha = '%s/.%s_%s.png';  ///< <b>string</b> Thumbnail file pattern (with alpha layer)
    public $thumb_tp_webp  = '%s/.%s_%s.webp'; ///< <b>string</b> Thumbnail file pattern (webp)
    /**
     * Tubmnail sizes:
     * - m: medium image
     * - s: small image
     * - t: thumbnail image
     * - sq: square image
     *
     * @var        array
     */
    public $thumb_sizes = [
        'm'  => [448, 'ratio', 'medium'],
        's'  => [240, 'ratio', 'small'],
        't'  => [100, 'ratio', 'thumbnail'],
        'sq' => [48, 'crop', 'square']
    ];

    public $icon_img = 'images/media/%s.png'; ///< <b>string</b> Icon file pattern

    /**
     * Constructs a new instance.
     *
     * @param      dcCore     $core   The core
     * @param      string     $type   The media type filter
     *
     * @throws     Exception  (description)
     */
    public function __construct(dcCore $core, $type = '')
    {
        $this->core      = &$core;
        $this->con       = &$core->con;
        $this->postmedia = new dcPostMedia($core);

        if ($this->core->blog == null) {
            throw new Exception(__('No blog defined.'));
        }

        $this->table = $this->core->prefix . 'media';
        $root        = $this->core->blog->public_path;

        if (preg_match('#^http(s)?://#', $this->core->blog->settings->system->public_url)) {
            $root_url = rawurldecode($this->core->blog->settings->system->public_url);
        } else {
            $root_url = rawurldecode($this->core->blog->host . path::clean($this->core->blog->settings->system->public_url));
        }

        if (!is_dir($root)) {
            # Check public directory
            if ($core->auth->isSuperAdmin()) {
                throw new Exception(__('There is no writable directory /public/ at the location set in about:config "public_path". You must create this directory with sufficient rights (or change this setting).'));
            }

            throw new Exception(__('There is no writable root directory for the media manager. You should contact your administrator.'));
        }

        $this->type = $type;

        parent::__construct($root, $root_url);
        $this->chdir('');

        $this->path = $this->core->blog->settings->system->public_path;

        $this->addExclusion(DC_RC_PATH);
        $this->addExclusion(dirname(__FILE__) . '/../');

        $this->exclude_pattern = $core->blog->settings->system->media_exclusion;

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
        $this->thumb_sizes['m'][0] = abs($core->blog->settings->system->media_img_m_size);
        $this->thumb_sizes['s'][0] = abs($core->blog->settings->system->media_img_s_size);
        $this->thumb_sizes['t'][0] = abs($core->blog->settings->system->media_img_t_size);

        # Thumbnails sizes names
        $this->thumb_sizes['m'][2]  = __($this->thumb_sizes['m'][2]);
        $this->thumb_sizes['s'][2]  = __($this->thumb_sizes['s'][2]);
        $this->thumb_sizes['t'][2]  = __($this->thumb_sizes['t'][2]);
        $this->thumb_sizes['sq'][2] = __($this->thumb_sizes['sq'][2]);

        # --BEHAVIOR-- coreMediaConstruct
        $this->core->callBehavior('coreMediaConstruct', $this);
    }

    /**
     * Changes working directory.
     *
     * @param      string  $dir    The directory name
     */
    public function chdir($dir)
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
     * @param      string    $type      The media type
     * @param      string    $event     The event
     * @param      callable  $function  The callback
     */
    public function addFileHandler($type, $event, $function)
    {
        if (is_callable($function)) {
            $this->file_handler[$type][$event][] = $function;
        }
    }

    protected function callFileHandler($type, $event, ...$args)
    {
        if (!empty($this->file_handler[$type][$event])) {
            foreach ($this->file_handler[$type][$event] as $f) {
                call_user_func_array($f, $args);
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
    public function breadCrumb($href, $last = '')
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

    protected function fileRecord($rs)
    {
        if ($rs->isEmpty()) {
            return;
        }

        if (!$this->isFileExclude($this->root . '/' . $rs->media_file) && is_file($this->root . '/' . $rs->media_file)) {
            $f = new fileItem($this->root . '/' . $rs->media_file, $this->root, $this->root_url);

            if ($this->type && $f->type_prefix != $this->type) {
                return;
            }

            $meta = @simplexml_load_string($rs->media_meta);

            $f->editable    = true;
            $f->media_id    = $rs->media_id;
            $f->media_title = $rs->media_title;
            $f->media_meta  = $meta instanceof SimpleXMLElement ? $meta : simplexml_load_string('<meta></meta>');
            $f->media_user  = $rs->user_id;
            $f->media_priv  = (boolean) $rs->media_private;
            $f->media_dt    = strtotime($rs->media_dt);
            $f->media_dtstr = dt::str('%Y-%m-%d %H:%M', $f->media_dt);

            $f->media_image = false;

            if (!$this->core->auth->check('media_admin', $this->core->blog->id)
                && $this->core->auth->userID() != $f->media_user) {
                $f->del      = false;
                $f->editable = false;
            }

            $type_prefix = explode('/', $f->type);
            $type_prefix = $type_prefix[0];

            switch ($type_prefix) {
                case 'image':
                    $f->media_image = true;
                    $f->media_icon  = 'image';

                    break;
                case 'audio':
                    $f->media_icon = 'audio';

                    break;
                case 'text':
                    $f->media_icon = 'text';

                    break;
                case 'video':
                    $f->media_icon = 'video';

                    break;
                default:
                    $f->media_icon = 'blank';
            }
            switch ($f->type) {
                case 'application/msword':
                case 'application/vnd.oasis.opendocument.text':
                case 'application/vnd.sun.xml.writer':
                case 'application/pdf':
                case 'application/postscript':
                    $f->media_icon = 'document';

                    break;
                case 'application/msexcel':
                case 'application/vnd.oasis.opendocument.spreadsheet':
                case 'application/vnd.sun.xml.calc':
                    $f->media_icon = 'spreadsheet';

                    break;
                case 'application/mspowerpoint':
                case 'application/vnd.oasis.opendocument.presentation':
                case 'application/vnd.sun.xml.impress':
                    $f->media_icon = 'presentation';

                    break;
                case 'application/x-debian-package':
                case 'application/x-bzip':
                case 'application/x-gzip':
                case 'application/x-java-archive':
                case 'application/rar':
                case 'application/x-redhat-package-manager':
                case 'application/x-tar':
                case 'application/x-gtar':
                case 'application/zip':
                    $f->media_icon = 'package';

                    break;
                case 'application/octet-stream':
                    $f->media_icon = 'executable';

                    break;
                case 'application/x-shockwave-flash':
                    $f->media_icon = 'video';

                    break;
                case 'application/ogg':
                    $f->media_icon = 'audio';

                    break;
                case 'text/html':
                    $f->media_icon = 'html';

                    break;
            }

            $f->media_type = $f->media_icon;
            $f->media_icon = sprintf($this->icon_img, $f->media_icon);

            # Thumbnails
            $f->media_thumb = [];
            $p              = path::info($f->relname);

            $alpha = strtolower($p['extension']) === 'png';
            $webp  = strtolower($p['extension']) === 'webp';

            $thumb = sprintf(
                ($alpha ? $this->thumb_tp_alpha :
                    ($webp ? $this->thumb_tp_webp : $this->thumb_tp)),
                $this->root . '/' . $p['dirname'], $p['base'], '%s');
            $thumb_url = sprintf(
                ($alpha ? $this->thumb_tp_alpha :
                    ($webp ? $this->thumb_tp_webp : $this->thumb_tp)),
                $this->root_url . $p['dirname'], $p['base'], '%s');

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

            foreach ($this->thumb_sizes as $suffix => $s) {
                if (file_exists(sprintf($thumb, $suffix))) {
                    $f->media_thumb[$suffix] = sprintf($thumb_url, $suffix);
                } elseif (($alpha || $webp) && file_exists(sprintf($thumb_alt, $suffix))) {
                    $f->media_thumb[$suffix] = sprintf($thumb_url_alt, $suffix);
                }
            }

            if (isset($f->media_thumb['sq']) && $f->media_type == 'image') {
                $f->media_icon = $f->media_thumb['sq'];
            }

            return $f;
        }
    }

    /**
     * Sets the file sort.
     *
     * @param      string  $type   The type
     */
    public function setFileSort($type = 'name')
    {
        if (in_array($type, ['size-asc', 'size-desc', 'name-asc', 'name-desc', 'date-asc', 'date-desc'])) {
            $this->file_sort = $type;
        }
    }

    protected function sortFileHandler($a, $b)
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
    public function getDir($type = null)
    {
        if ($type) {
            $this->type = $type;
        }

        $media_dir = $this->relpwd ?: '.';

        $strReq = 'SELECT media_file, media_id, media_path, media_title, media_meta, media_dt, ' .
        'media_creadt, media_upddt, media_private, user_id ' .
        'FROM ' . $this->table . ' ' .
        "WHERE media_path = '" . $this->path . "' " .
        "AND media_dir = '" . $this->con->escape($media_dir) . "' ";

        if (!$this->core->auth->check('media_admin', $this->core->blog->id)) {
            $strReq .= 'AND (media_private <> 1 ';

            if ($this->core->auth->userID()) {
                $strReq .= "OR user_id = '" . $this->con->escape($this->core->auth->userID()) . "'";
            }
            $strReq .= ') ';
        }

        $rs = $this->con->select($strReq);

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
                $f = $this->fileRecord($rs);
                if ($f !== null) {
                    if (isset($f_reg[$rs->media_file])) {
                        # That media is duplicated in the database,
                        # time to do a bit of house cleaning.
                        $this->con->execute(
                            'DELETE FROM ' . $this->table . ' ' .
                            'WHERE media_id = ' . $this->fileRecord($rs)->media_id
                        );
                    } else {
                        $f_res[]                = $this->fileRecord($rs);
                        $f_reg[$rs->media_file] = 1;
                    }
                }
            } elseif (!empty($p_dir['files']) && $this->relpwd == '') {
                # Physical file does not exist remove it from DB
                # Because we don't want to erase everything on
                # dotclear upgrade, do it only if there are files
                # in directory and directory is root
                $this->con->execute(
                    'DELETE FROM ' . $this->table . ' ' .
                    "WHERE media_path = '" . $this->con->escape($this->path) . "' " .
                    "AND media_file = '" . $this->con->escape($rs->media_file) . "' "
                );
                $this->callFileHandler(files::getMimeType($rs->media_file), 'remove', $this->pwd . '/' . $rs->media_file);
            }
        }

        $this->dir['files'] = $f_res;
        foreach ($this->dir['dirs'] as $k => $v) {
            $v->media_icon = sprintf($this->icon_img, ($v->parent ? 'folder-up' : 'folder'));
        }

        # Check files that don't exist in database and create them
        if ($this->core->auth->check('media,media_admin', $this->core->blog->id)) {
            foreach ($p_dir['files'] as $f) {
                if (!isset($f_reg[$f->relname])) {
                    if (($id = $this->createFile($f->basename, null, false, null, false)) !== false) {
                        $this->dir['files'][] = $this->getFile($id);
                    }
                }
            }
        }

        try {
            usort($this->dir['files'], [$this, 'sortFileHandler']);
        } catch (Exception $e) {
        }
    }

    /**
     * Gets file by its id. Returns a filteItem object.
     *
     * @param      mixed  $id     The file identifier
     *
     * @return     fileItem  The file.
     */
    public function getFile($id)
    {
        $strReq = 'SELECT media_id, media_path, media_title, ' .
        'media_file, media_meta, media_dt, media_creadt, ' .
        'media_upddt, media_private, user_id ' .
        'FROM ' . $this->table . ' ' .
        "WHERE media_path = '" . $this->path . "' " .
        'AND media_id = ' . (integer) $id . ' ';

        if (!$this->core->auth->check('media_admin', $this->core->blog->id)) {
            $strReq .= 'AND (media_private <> 1 ';

            if ($this->core->auth->userID()) {
                $strReq .= "OR user_id = '" . $this->con->escape($this->core->auth->userID()) . "'";
            }
            $strReq .= ') ';
        }

        $rs = $this->con->select($strReq);

        return $this->fileRecord($rs);
    }

    /**
     * Search into media db (only).
     *
     * @param      string  $query  The search query
     *
     * @return     bool    true or false if nothing found
     */
    public function searchMedia($query)
    {
        if ($query == '') {
            return false;
        }

        $strReq = 'SELECT media_file, media_id, media_path, media_title, media_meta, media_dt, ' .
        'media_creadt, media_upddt, media_private, user_id ' .
        'FROM ' . $this->table . ' ' .
        "WHERE media_path = '" . $this->path . "' " .
        "AND (media_title LIKE '%" . $this->con->escape($query) . "%' " .
        "   OR media_file LIKE '%" . $this->con->escape($query) . "%' " .
        "   OR media_meta LIKE '<Description>%" . $this->con->escape($query) . "%</Description>')";

        if (!$this->core->auth->check('media_admin', $this->core->blog->id)) {
            $strReq .= 'AND (media_private <> 1 ';

            if ($this->core->auth->userID()) {
                $strReq .= "OR user_id = '" . $this->con->escape($this->core->auth->userID()) . "'";
            }
            $strReq .= ') ';
        }

        $rs = $this->con->select($strReq);

        $this->dir = ['dirs' => [], 'files' => []];
        $f_res     = [];
        while ($rs->fetch()) {
            $fr = $this->fileRecord($rs);
            if ($fr) {
                $f_res[] = $fr;
            }
        }
        $this->dir['files'] = $f_res;

        try {
            usort($this->dir['files'], [$this, 'sortFileHandler']);
        } catch (Exception $e) {
        }

        return (count($f_res) > 0 ? true : false);
    }

    /**
     * Returns media items attached to a blog post. Result is an array containing
     * fileItems objects.
     *
     * @param      integer  $post_id    The post identifier
     * @param      mixed    $media_id   The media identifier
     * @param      mixed    $link_type  The link type
     *
     * @return     array   Array of fileItems.
     */
    public function getPostMedia($post_id, $media_id = null, $link_type = null)
    {
        $params = [
            'post_id'    => $post_id,
            'media_path' => $this->path
        ];
        if ($media_id) {
            $params['media_id'] = (integer) $media_id;
        }
        if ($link_type) {
            $params['link_type'] = $link_type;
        }
        $rs = $this->postmedia->getPostMedia($params);

        $res = [];

        while ($rs->fetch()) {
            $f = $this->fileRecord($rs);
            if ($f !== null) {
                $res[] = $f;
            }
        }

        return $res;
    }

    /**
     * Rebuilds database items collection. Optional <var>$pwd</var> parameter is
     * the path where to start rebuild.
     *
     * @param      string     $pwd    The directory to rebuild
     *
     * @throws     Exception
     */
    public function rebuild($pwd = '')
    {
        if (!$this->core->auth->isSuperAdmin()) {
            throw new Exception(__('You are not a super administrator.'));
        }

        $this->chdir($pwd);
        parent::getDir();

        $dir = $this->dir;

        foreach ($dir['dirs'] as $d) {
            if (!$d->parent) {
                $this->rebuild($d->relname);
            }
        }

        foreach ($dir['files'] as $f) {
            $this->chdir(dirname($f->relname));
            $this->createFile($f->basename);
        }

        $this->rebuildDB($pwd);
    }

    protected function rebuildDB($pwd)
    {
        $media_dir = $pwd ?: '.';

        $strReq = 'SELECT media_file, media_id ' .
        'FROM ' . $this->table . ' ' .
        "WHERE media_path = '" . $this->path . "' " .
        "AND media_dir = '" . $this->con->escape($media_dir) . "' ";

        $rs = $this->con->select($strReq);

        $delReq = 'DELETE FROM ' . $this->table . ' ' .
            'WHERE media_id IN (%s) ';
        $del_ids = [];

        while ($rs->fetch()) {
            if (!is_file($this->root . '/' . $rs->media_file)) {
                $del_ids[] = (integer) $rs->media_id;
            }
        }

        if (!empty($del_ids)) {
            $this->con->execute(sprintf($delReq, implode(',', $del_ids)));
        }
    }

    /**
     * Makes a dir.
     *
     * @param      string  $d      the directory to create
     */
    public function makeDir($d)
    {
        $d = files::tidyFileName($d);
        parent::makeDir($d);
    }

    /**
     * Creates or updates a file in database. Returns new media ID or false if
     * file does not exist.
     *
     * @param      string     $name     The file name (relative to working directory)
     * @param      mixed      $title    The file title
     * @param      bool       $private  File is private
     * @param      mixed      $dt       File date
     * @param      bool       $force    The force flag
     *
     * @throws     Exception
     *
     * @return     integer|bool     New media ID or false
     */
    public function createFile($name, $title = null, $private = false, $dt = null, $force = true)
    {
        if (!$this->core->auth->check('media,media_admin', $this->core->blog->id)) {
            throw new Exception(__('Permission denied.'));
        }

        $file = $this->pwd . '/' . $name;
        if (!file_exists($file)) {
            return false;
        }

        $media_file = $this->relpwd ? path::clean($this->relpwd . '/' . $name) : path::clean($name);
        $media_type = files::getMimeType($name);

        $cur = $this->con->openCursor($this->table);

        $strReq = 'SELECT media_id ' .
        'FROM ' . $this->table . ' ' .
        "WHERE media_path = '" . $this->con->escape($this->path) . "' " .
        "AND media_file = '" . $this->con->escape($media_file) . "' ";

        $rs = $this->con->select($strReq);

        if ($rs->isEmpty()) {
            $this->con->writeLock($this->table);

            try {
                $rs       = $this->con->select('SELECT MAX(media_id) FROM ' . $this->table);
                $media_id = (integer) $rs->f(0) + 1;

                $cur->media_id     = $media_id;
                $cur->user_id      = (string) $this->core->auth->userID();
                $cur->media_path   = (string) $this->path;
                $cur->media_file   = (string) $media_file;
                $cur->media_dir    = (string) dirname($media_file);
                $cur->media_creadt = date('Y-m-d H:i:s');
                $cur->media_upddt  = date('Y-m-d H:i:s');

                $cur->media_title   = !$title ? (string) $name : (string) $title;
                $cur->media_private = (integer) (boolean) $private;

                if ($dt) {
                    $cur->media_dt = (string) $dt;
                } else {
                    $cur->media_dt = strftime('%Y-%m-%d %H:%M:%S', filemtime($file));
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
            $media_id = (integer) $rs->media_id;

            $cur->media_upddt = date('Y-m-d H:i:s');

            $cur->update('WHERE media_id = ' . $media_id);
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
    public function updateFile($file, $newFile)
    {
        if (!$this->core->auth->check('media,media_admin', $this->core->blog->id)) {
            throw new Exception(__('Permission denied.'));
        }

        $id = (integer) $file->media_id;

        if (!$id) {
            throw new Exception('No file ID');
        }

        if (!$this->core->auth->check('media_admin', $this->core->blog->id)
            && $this->core->auth->userID() != $file->media_user) {
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
        $cur->media_private = (integer) $newFile->media_priv;

        if ($newFile->media_meta instanceof SimpleXMLElement) {
            $cur->media_meta = $newFile->media_meta->asXML();
        }

        $cur->update('WHERE media_id = ' . $id);

        $this->callFileHandler($file->type, 'update', $file, $newFile);
    }

    /**
     * Uploads a file.
     *
     * @param      string     $tmp        The full path of temporary uploaded file
     * @param      string     $name       The file name (relative to working directory)me
     * @param      mixed      $title      The file title
     * @param      bool       $private    File is private
     * @param      bool       $overwrite  File should be overwrite
     *
     * @throws     Exception
     *
     * @return     mixed      New media ID or false
     */
    public function uploadFile($tmp, $name, $title = null, $private = false, $overwrite = false)
    {
        if (!$this->core->auth->check('media,media_admin', $this->core->blog->id)) {
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
     * @param      mixed      $bits   The binary file contentits
     *
     * @throws     Exception
     *
     * @return     mixed      New media ID or false
     */
    public function uploadBits($name, $bits)
    {
        if (!$this->core->auth->check('media,media_admin', $this->core->blog->id)) {
            throw new Exception(__('Permission denied.'));
        }

        $name = files::tidyFileName($name);

        parent::uploadBits($name, $bits);

        return $this->createFile($name, null, false);
    }

    /**
     * Removes a file.
     *
     * @param      string     $f      filename
     *
     * @throws     Exception
     */
    public function removeFile($f)
    {
        if (!$this->core->auth->check('media,media_admin', $this->core->blog->id)) {
            throw new Exception(__('Permission denied.'));
        }

        $media_file = $this->relpwd ? path::clean($this->relpwd . '/' . $f) : path::clean($f);

        $strReq = 'DELETE FROM ' . $this->table . ' ' .
        "WHERE media_path = '" . $this->con->escape($this->path) . "' " .
        "AND media_file = '" . $this->con->escape($media_file) . "' ";

        if (!$this->core->auth->check('media_admin', $this->core->blog->id)) {
            $strReq .= "AND user_id = '" . $this->con->escape($this->core->auth->userID()) . "'";
        }

        $this->con->execute($strReq);

        if ($this->con->changes() == 0) {
            throw new Exception(__('File does not exist in the database.'));
        }

        parent::removeFile($f);

        $this->callFileHandler(files::getMimeType($media_file), 'remove', $f);
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
    public function getDBDirs()
    {
        $dir       = [];
        $media_dir = $this->relpwd ?: '.';

        $strReq = 'SELECT distinct media_dir ' .
        'FROM ' . $this->table . ' ' .
        "WHERE media_path = '" . $this->path . "'";
        $rs = $this->con->select($strReq);
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
    public function inflateZipFile($f, $create_dir = true)
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
            $n = text::deaccent($name);
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
    public function getZipContent($f)
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
    public function mediaFireRecreateEvent($f)
    {
        $media_type = files::getMimeType($f->basename);
        $this->callFileHandler($media_type, 'recreate', null, $f->basename); // Args list to be completed as necessary (Franck)
    }

    /* Image handlers
    ------------------------------------------------------- */
    /**
     * Create image thumbnails
     *
     * @param      mixed   $cur    The cursor
     * @param      string  $f      Image filename
     * @param      bool    $force  Force creation
     *
     * @return     mixed
     */
    public function imageThumbCreate($cur, $f, $force = true)
    {
        $file = $this->pwd . '/' . $f;

        if (!file_exists($file)) {
            return false;
        }

        $p     = path::info($file);
        $alpha = strtolower($p['extension']) === 'png';
        $webp  = strtolower($p['extension']) === 'webp';
        $thumb = sprintf(($alpha ? $this->thumb_tp_alpha :
            ($webp ? $this->thumb_tp_webp :
                $this->thumb_tp)),
            $p['dirname'], $p['base'], '%s');

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
    }

    /**
     * Update image thumbnails
     *
     * @param      fileItem  $file     The file
     * @param      fileItem  $newFile  The new file
     */
    protected function imageThumbUpdate($file, $newFile)
    {
        if ($file->relname != $newFile->relname) {
            $p         = path::info($file->relname);
            $alpha     = strtolower($p['extension']) === 'png';
            $webp      = strtolower($p['extension']) === 'webp';
            $thumb_old = sprintf(($alpha ? $this->thumb_tp_alpha :
                ($webp ? $this->thumb_tp_webp :
                    $this->thumb_tp)),
                $p['dirname'], $p['base'], '%s');

            $p         = path::info($newFile->relname);
            $alpha     = strtolower($p['extension']) === 'png';
            $webp      = strtolower($p['extension']) === 'webp';
            $thumb_new = sprintf(($alpha ? $this->thumb_tp_alpha :
                ($webp ? $this->thumb_tp_webp :
                    $this->thumb_tp)),
                $p['dirname'], $p['base'], '%s');

            foreach ($this->thumb_sizes as $suffix => $s) {
                try {
                    parent::moveFile(sprintf($thumb_old, $suffix), sprintf($thumb_new, $suffix));
                } catch (Exception $e) {
                }
            }
        }
    }

    /**
     * Remove image thumbnails
     *
     * @param      string  $f      Image filename
     */
    public function imageThumbRemove($f)
    {
        $p     = path::info($f);
        $alpha = strtolower($p['extension']) === 'png';
        $webp  = strtolower($p['extension']) === 'webp';
        $thumb = sprintf(($alpha ? $this->thumb_tp_alpha :
            ($webp ? $this->thumb_tp_webp :
                $this->thumb_tp)),
            '', $p['base'], '%s');

        foreach ($this->thumb_sizes as $suffix => $s) {
            try {
                parent::removeFile(sprintf($thumb, $suffix));
            } catch (Exception $e) {
            }
        }
    }

    /**
     * Create image meta
     *
     * @param      cursor  $cur    The cursor
     * @param      string  $f      Image filename
     * @param      mixed   $id     The media identifier
     *
     * @return     mixed
     */
    protected function imageMetaCreate($cur, $f, $id)
    {
        $file = $this->pwd . '/' . $f;

        if (!file_exists($file)) {
            return false;
        }

        $xml  = new xmlTag('meta');
        $meta = imageMeta::readMeta($file);
        $xml->insertNode($meta);

        $c             = $this->core->con->openCursor($this->table);
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
                $o           = dt::getTimeOffset($this->core->auth->getInfo('user_tz'), $media_ts);
                $c->media_dt = dt::str('%Y-%m-%d %H:%M:%S', $media_ts + $o);
            }
        }

        # --BEHAVIOR-- coreBeforeImageMetaCreate
        $this->core->callBehavior('coreBeforeImageMetaCreate', $c);

        $c->update('WHERE media_id = ' . $id);
    }

    /**
     * Returns HTML code for audio player (HTML5)
     *
     * @param      string  $type      The audio mime type (not used)
     * @param      string  $url       The audio URL to play
     * @param      mixed   $player    The player URL (not used)
     * @param      mixed   $args      The player arguments (not used)
     * @param      bool    $fallback  The fallback (not more used)
     * @param      bool    $preload   Add preload="auto" attribute if true, else preload="none"
     *
     * @return     string
     */
    public static function audioPlayer($type, $url, $player = null, $args = null, $fallback = false, $preload = true)
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
     * @param      mixed   $player    The player URL
     * @param      mixed   $args      The player arguments
     * @param      bool    $fallback  The fallback (not more used)
     * @param      bool    $preload   Add preload="auto" attribute if true, else preload="none"
     *
     * @return     string
     */
    public static function videoPlayer($type, $url, $player = null, $args = null, $fallback = false, $preload = true)
    {
        $video = '';

        if ($type != 'video/x-flv') {
            // Cope with width and height, if given
            $width  = 400;
            $height = 300;
            if (is_array($args)) {
                if (!empty($args['width']) && $args['width']) {
                    $width = (int) $args['width'];
                }
                if (!empty($args['height']) && $args['height']) {
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
     * @param      mixed   $player    The player URL
     * @param      mixed   $args      The player arguments
     * @param      bool    $fallback  The fallback (not more used)
     * @param      bool    $preload   Add preload="auto" attribute if true, else preload="none"
     *
     * @return     string
     */
    public static function mp3player($url, $player = null, $args = null, $fallback = false, $preload = true)
    {
        return self::audioPlayer('audio/mp3', $url, $player, $args, false, $preload);
    }

    /**
     * Returns HTML code for FLV player
     *
     * @obsolete since 2.15
     *
     * @param      string  $url     The url
     * @param      mixed   $player  The player
     * @param      mixed   $args    The arguments
     *
     * @return     string
     */
    public static function flvplayer($url, $player = null, $args = null)
    {
        return '';
    }
}
