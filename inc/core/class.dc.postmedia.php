<?php
/**
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\DeleteStatement;
use Dotclear\Database\Statement\JoinStatement;
use Dotclear\Database\Statement\SelectStatement;

class dcPostMedia
{
    // Constants

    /**
     * Post media table name
     *
     * @var        string
     */
    public const POST_MEDIA_TABLE_NAME = 'post_media';

    // Properties

    /**
     * Database connection
     *
     * @var object
     */
    protected $con;

    /**
     * Post-Media table name
     *
     * @var string
     */
    protected $table;

    /**
     * Constructs a new instance.
     */
    public function __construct()
    {
        $this->con   = dcCore::app()->con;
        $this->table = dcCore::app()->prefix . self::POST_MEDIA_TABLE_NAME;
    }

    /**
     * Returns media items attached to a blog post.
     *
     * @param      array   $params  The parameters
     *
     * @return     MetaRecord  The post media.
     */
    public function getPostMedia(array $params = []): MetaRecord
    {
        $sql = new SelectStatement();
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
            ->from($sql->as(dcCore::app()->prefix . dcMedia::MEDIA_TABLE_NAME, 'M'))
            ->join(
                (new JoinStatement())
                ->inner()
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
     * @param      int     $post_id    The post identifier
     * @param      int     $media_id   The media identifier
     * @param      string  $link_type  The link type (default: attachment)
     */
    public function addPostMedia(int $post_id, int $media_id, string $link_type = 'attachment')
    {
        $f = $this->getPostMedia([
            'post_id'   => $post_id,
            'media_id'  => $media_id,
            'link_type' => $link_type,
        ]);

        if (!$f->isEmpty()) {
            return;
        }

        $cur            = $this->con->openCursor($this->table);
        $cur->post_id   = $post_id;
        $cur->media_id  = $media_id;
        $cur->link_type = $link_type;

        $cur->insert();
        dcCore::app()->blog->triggerBlog();
    }

    /**
     * Detaches a media from a post.
     *
     * @param      int      $post_id    The post identifier
     * @param      int      $media_id   The media identifier
     * @param      string   $link_type  The link type
     */
    public function removePostMedia(int $post_id, int $media_id, ?string $link_type = null)
    {
        $post_id  = (int) $post_id;
        $media_id = (int) $media_id;

        $sql = new DeleteStatement();
        $sql
            ->from($this->table)
            ->where('post_id = ' . $post_id)
            ->and('media_id = ' . $media_id);

        if ($link_type !== null) {
            $sql->and('link_type = ' . $sql->quote($link_type));
        }
        $sql->delete();

        dcCore::app()->blog->triggerBlog();
    }
}
