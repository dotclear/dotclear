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

use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\DeleteStatement;
use Dotclear\Database\Statement\JoinStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Interface\Core\BlogInterface;
use Dotclear\Interface\Core\PostMediaInterface;

/**
 * @brief   Post media database handler.
 *
 * @since   2.28, container services have been added to constructor
 * @since   2.36, constructor arguments has been replaced by Core instance
 */
class PostMedia implements PostMediaInterface
{
    /**
     * The working blog instance
     */
    private ?BlogInterface $blog = null;

    /**
     * Full table name (including db prefix).
     */
    protected string $table;

    /**
     * Constructs a new instance.
     *
     * @param   Core    $core   The core container
     */
    public function __construct(
        protected Core $core
    ) {
        $this->table = $this->core->db()->con()->prefix() . self::POST_MEDIA_TABLE_NAME;
    }

    public function loadFromBlog(BlogInterface $blog): PostMediaInterface
    {
        $this->blog = $blog;

        return $this;
    }

    public function openPostMediaCursor(): Cursor
    {
        return $this->core->db()->con()->openCursor($this->table);
    }

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

        if (!empty($params['columns'])) {
            $values = [];
            if (is_array($params['columns'])) {
                $values = array_map(fn (mixed $v): string => is_string($v) ? $v : '', $params['columns']);
            } elseif (is_string($params['columns'])) {
                $values = [$params['columns']];
            }

            $sql->columns($values);
        }

        $sql
            ->from($sql->as($this->core->db()->con()->prefix() . self::MEDIA_TABLE_NAME, 'M'))
            ->join(
                (new JoinStatement())
                ->inner()
                ->from($this->table . ' PM')
                ->on('M.media_id = PM.media_id')
                ->statement()
            );

        if (!empty($params['from']) && is_string($params['from'])) {
            $sql->from($params['from']);
        }

        if (isset($params['link_type'])) {
            $values = $sql->sanitizeIn($params['link_type'], 'string', false);
            if ($values !== []) {
                $sql->where('PM.link_type' . $sql->in($values));
            }
        } else {
            $sql->where('PM.link_type = ' . $sql->quote('attachment'));
        }

        if (isset($params['post_id'])) {
            $values = $sql->sanitizeIn($params['post_id'], 'int', false);
            if ($values !== []) {
                $sql->and('PM.post_id' . $sql->in($values));
            }
        }

        if (isset($params['media_id'])) {
            $values = $sql->sanitizeIn($params['media_id'], 'int', false);
            if ($values !== []) {
                $sql->and('M.media_id' . $sql->in($values));
            }
        }

        if (isset($params['media_path'])) {
            $values = $sql->sanitizeIn($params['media_path'], 'string', false);
            if ($values !== []) {
                $sql->and('M.media_path' . $sql->in($values));
            }
        }

        if (isset($params['sql']) && is_string($params['sql'])) {
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
        $this->blog?->triggerBlog();
    }

    public function removePostMedia(int $post_id, int $media_id, ?string $link_type = null): void
    {
        $sql = new DeleteStatement();
        $sql
            ->from($this->table)
            ->where('post_id = ' . $post_id)
            ->and('media_id = ' . $media_id);

        if ($link_type !== null) {
            $sql->and('link_type = ' . $sql->quote($link_type));
        }

        $sql->delete();

        $this->blog?->triggerBlog();
    }
}
