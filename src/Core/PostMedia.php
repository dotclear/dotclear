<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\DeleteStatement;
use Dotclear\Database\Statement\JoinStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Interface\Core\BlogInterface;
use Dotclear\Interface\Core\ConnectionInterface;
use Dotclear\Interface\Core\PostMediaInterface;

/**
 * @brief   Post media database handler.
 *
 * @since   2.28, container services have been added to constructor
 */
class PostMedia implements PostMediaInterface
{
    /**
     * Full table name (including db prefix).
     *
     * @var     string  $table
     */
    protected string $table;

    /**
     * Constructor.
     *
     * @param   BlogInterface           $blog       The blog instance
     * @param   ConnectionInterface     $con        The database connection instance
     */
    public function __construct(
        protected BlogInterface $blog,
        protected ConnectionInterface $con
    ) {
        $this->table = $this->con->prefix() . self::POST_MEDIA_TABLE_NAME;
    }

    public function openPostMediaCursor(): Cursor
    {
        return $this->con->openCursor($this->table);
    }

    /**
     * Gets the post media.
     *
     * @param      array<string, mixed>       $params  The parameters
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
            ->from($sql->as($this->con->prefix() . self::MEDIA_TABLE_NAME, 'M'))
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

        return $sql->select() ?? MetaRecord::newFromArray([]);
    }

    public function addPostMedia(int $post_id, int $media_id, string $link_type = 'attachment'): void
    {
        $f = $this->getPostMedia([
            'post_id'   => $post_id,
            'media_id'  => $media_id,
            'link_type' => $link_type,
        ]);

        if (!$f->isEmpty()) {
            return;
        }

        $cur            = $this->openPostMediaCursor();
        $cur->post_id   = $post_id;
        $cur->media_id  = $media_id;
        $cur->link_type = $link_type;

        $cur->insert();
        $this->blog->triggerBlog();
    }

    public function removePostMedia(int $post_id, int $media_id, ?string $link_type = null): void
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

        $this->blog->triggerBlog();
    }
}
