<?php
/**
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_RC_PATH')) {
    return;
}

class dcPostMedia
{
    protected $core;  ///< <b>dcCore</b> dcCore instance
    protected $con;   ///< <b>connection</b> Database connection
    protected $table; ///< <b>string</b> Post-Media table name

    /**
     * Constructs a new instance.
     *
     * @param      dcCore  $core   The core
     */
    public function __construct(dcCore $core)
    {
        $this->core  = &$core;
        $this->con   = &$core->con;
        $this->table = $this->core->prefix . 'post_media';
    }

    /**
     * Returns media items attached to a blog post.
     *
     * @param      array   $params  The parameters
     *
     * @return     record  The post media.
     */
    public function getPostMedia($params = [])
    {
        $sql = new dcSelectStatement($this->core);
        $sql
            ->columns([
                'M.media_file',
                'M.media_id',
                'M.media_path',
                'M.media_title',
                'M.media_meta',
                'M.media_dt',
                'M.media_creadt',
                'M.media_upddt',
                'M.media_private',
                'M.user_id',
                'PM.post_id',
            ]);

        if (!empty($params['columns']) && is_array($params['columns'])) {
            $sql->columns($params['columns']);
        }

        $sql
            ->from($sql->core->prefix . 'media M')
            ->join(
                (new dcJoinStatement($this->core))
                ->type('INNER')
                ->from($this->table . ' PM')
                ->on('M.media_id = PM.media_id')
                ->statement()
            );

        if (!empty($params['from'])) {
            $sql->from($params['from']);
        }

        if (isset($params['link_type'])) {
            $sql->where('PM.link_type' . $sql->in($params['link_type']));
        } else {
            $sql->where('PM.link_type = ' . $sql->quote('attachment'));
        }

        if (isset($params['post_id'])) {
            $sql->and('PM.post_id' . $sql->in($params['post_id']));
        }
        if (isset($params['media_id'])) {
            $sql->and('M.media_id' . $sql->in($params['media_id']));
        }
        if (isset($params['media_path'])) {
            $sql->and('M.media_path' . $sql->in($params['media_path']));
        }

        if (isset($params['sql'])) {
            $sql->sql($params['sql']);
        }

        $rs = $sql->select();

        return $rs;
    }

    /**
     * Attaches a media to a post.
     *
     * @param      mixed   $post_id    The post identifier
     * @param      mixed   $media_id   The media identifier
     * @param      string  $link_type  The link type (default: attachment)
     */
    public function addPostMedia($post_id, $media_id, $link_type = 'attachment')
    {
        $post_id  = (int) $post_id;
        $media_id = (int) $media_id;

        $f = $this->getPostMedia(['post_id' => $post_id, 'media_id' => $media_id, 'link_type' => $link_type]);

        if (!$f->isEmpty()) {
            return;
        }

        $cur            = $this->con->openCursor($this->table);
        $cur->post_id   = $post_id;
        $cur->media_id  = $media_id;
        $cur->link_type = $link_type;

        $cur->insert();
        $this->core->blog->triggerBlog();
    }

    /**
     * Detaches a media from a post.
     *
     * @param      mixed   $post_id    The post identifier
     * @param      mixed   $media_id   The media identifier
     * @param      mixed   $link_type  The link type
     */
    public function removePostMedia($post_id, $media_id, $link_type = null)
    {
        $post_id  = (int) $post_id;
        $media_id = (int) $media_id;

        $sql = new dcDeleteStatement($this->core);
        $sql
            ->from($this->table)
            ->where('post_id = ' . $post_id)
            ->and('media_id = ' . $media_id);

        if ($link_type != null) {
            $sql->and('link_type = ' . $sql->quote($link_type, true));
        }
        $sql->delete();

        $this->core->blog->triggerBlog();
    }
}
