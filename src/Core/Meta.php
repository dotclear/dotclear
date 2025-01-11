<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use ArrayObject;
use Dotclear\App;
use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\DeleteStatement;
use Dotclear\Database\Statement\JoinStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Database\Statement\UpdateStatement;
use Dotclear\Exception\UnauthorizedException;
use Dotclear\Helper\Text;
use Dotclear\Interface\Core\AuthInterface;
use Dotclear\Interface\Core\BlogInterface;
use Dotclear\Interface\Core\ConnectionInterface;
use Dotclear\Interface\Core\MetaInterface;

/**
 * @brief   Meta handler.
 *
 * @since   2.28, metadata class instance is provided by App::meta() method.
 * @since   2.28, container services have been added to constructor
 */
class Meta implements MetaInterface
{
    /**
     * The mate table name with prefix.
     */
    private readonly string $table;

    /**
     * Constructor.
     *
     * @param   AuthInterface           $auth       The authentication instance
     * @param   BlogInterface           $blog       The blog instance
     * @param   ConnectionInterface     $con        The database connection instance
     */
    public function __construct(
        protected AuthInterface $auth,
        protected BlogInterface $blog,
        protected ConnectionInterface $con
    ) {
        $this->table = $this->con->prefix() . self::META_TABLE_NAME;
    }

    public function openMetaCursor(): Cursor
    {
        return $this->con->openCursor($this->table);
    }

    public function splitMetaValues(string $str): array
    {
        $res = [];
        foreach (explode(',', $str) as $i => $tag) {
            $tag = self::sanitizeMetaID(trim($tag));
            if ($tag !== '') {
                $res[$i] = $tag;
            }
        }

        return array_unique($res);
    }

    public static function sanitizeMetaID(string $str): string
    {
        return Text::tidyURL($str, false, true);
    }

    public function getMetaArray(?string $str): array
    {
        if (!$str) {
            return [];
        }

        $meta = @unserialize($str);
        if (!is_array($meta)) {
            return [];
        }

        return $meta;
    }

    public function getMetaStr(?string $str, string $type): string
    {
        if (!$str) {
            return '';
        }

        $meta = $this->getMetaArray($str);
        if (!isset($meta[$type])) {
            return '';
        }

        return implode(', ', $meta[$type]);
    }

    public function getMetaRecordset(?string $str, string $type): MetaRecord
    {
        $meta = $this->getMetaArray($str);
        $data = [];

        if (isset($meta[$type])) {
            foreach ($meta[$type] as $v) {
                $data[] = [
                    'meta_id'       => $v,
                    'meta_type'     => $type,
                    'meta_id_lower' => mb_strtolower($v),
                    'count'         => 0,
                    'percent'       => 0,
                    'roundpercent'  => 0,
                ];
            }
        }

        return MetaRecord::newFromArray($data);
    }

    /**
     * Checks whether the current user is allowed to change post meta
     * An exception is thrown if user is not allowed.
     *
     * @param   int|string  $post_id    The post identifier
     *
     * @throws  UnauthorizedException
     */
    private function checkPermissionsOnPost(int|string $post_id): void
    {
        $post_id = (int) $post_id;

        if (!$this->auth->check($this->auth->makePermissions([
            $this->auth::PERMISSION_USAGE,
            $this->auth::PERMISSION_CONTENT_ADMIN,
        ]), $this->blog->id())) {
            throw new UnauthorizedException(__('You are not allowed to change this entry status'));
        }

        # If user can only publish, we need to check the post's owner
        if (!$this->auth->check($this->auth->makePermissions([
            $this->auth::PERMISSION_CONTENT_ADMIN,
        ]), $this->blog->id())) {
            $sql = new SelectStatement();
            $sql
                ->from($this->con->prefix() . $this->blog::POST_TABLE_NAME)
                ->column('post_id')
                ->where('post_id = ' . $post_id)
                ->and('user_id = ' . $sql->quote((string) $this->auth->userID()));

            $rs = $sql->select();

            if (!$rs instanceof MetaRecord || $rs->isEmpty()) {
                throw new UnauthorizedException(__('You are not allowed to change this entry status'));
            }
        }
    }

    /**
     * Updates serialized post_meta information with dc_meta table information.
     *
     * @param   int|string  $post_id    The post identifier
     */
    private function updatePostMeta(int|string $post_id): void
    {
        $post_id = (int) $post_id;

        $sql = new SelectStatement();
        $sql
            ->from($this->table)
            ->columns([
                'meta_id',
                'meta_type',
            ])
            ->where('post_id = ' . $post_id);

        if (($rs = $sql->select()) instanceof MetaRecord) {
            $meta = [];
            while ($rs->fetch()) {
                $meta[$rs->meta_type][] = $rs->meta_id;
            }

            $post_meta = serialize($meta);

            $cur            = $this->blog->openPostCursor();
            $cur->post_meta = $post_meta;

            $sql = new UpdateStatement();
            $sql->where('post_id = ' . $post_id);

            $sql->update($cur);

            $this->blog->triggerBlog();
        }
    }

    /**
     * Gets the posts by meta.
     *
     * @param      array<string, mixed>     $params      The parameters
     * @param      bool                     $count_only  The count only
     * @param      SelectStatement|null     $ext_sql     The extent sql
     *
     * @return     MetaRecord                                              The posts by meta.
     */
    public function getPostsByMeta(array $params = [], bool $count_only = false, ?SelectStatement $ext_sql = null): MetaRecord
    {
        if (!isset($params['meta_id'])) {
            return MetaRecord::newFromArray([]);
        }

        $sql = $ext_sql instanceof SelectStatement ? clone $ext_sql : new SelectStatement();

        $sql
            ->from($this->table . ' META')
            ->and('META.post_id = P.post_id')
            ->and('META.meta_id = ' . $sql->quote($params['meta_id']));

        if (!empty($params['meta_type'])) {
            $sql->and('META.meta_type = ' . $sql->quote($params['meta_type']));

            unset($params['meta_type']);
        }

        unset($params['meta_id']);

        return $this->blog->getPosts($params, $count_only, $sql);
    }

    /**
     * Gets the comments by meta.
     *
     * @param      array<string, mixed>     $params      The parameters
     * @param      bool                     $count_only  The count only
     * @param      SelectStatement|null     $ext_sql     The extent sql
     *
     * @return     MetaRecord                                              The comments by meta.
     */
    public function getCommentsByMeta(array $params = [], bool $count_only = false, ?SelectStatement $ext_sql = null): MetaRecord
    {
        if (!isset($params['meta_id'])) {
            return MetaRecord::newFromArray([]);
        }

        $sql = $ext_sql instanceof SelectStatement ? clone $ext_sql : new SelectStatement();

        $sql
            ->from($this->table . ' META')
            ->and('META.post_id = P.post_id')
            ->and('META.meta_id = ' . $sql->quote($params['meta_id']));

        if (!empty($params['meta_type'])) {
            $sql->and('META.meta_type = ' . $sql->quote($params['meta_type']));

            unset($params['meta_type']);
        }

        return $this->blog->getComments($params, $count_only, $sql);
    }

    /**
     * Gets the metadata.
     *
     * @param   array<string, mixed>    $params         The parameters
     * @param   bool                    $count_only     Only counts results
     * @param   SelectStatement|null    $ext_sql        Optional SqlStatement instance
     *
     * @return     MetaRecord                                              The metadata.
     */
    public function getMetadata(array $params = [], bool $count_only = false, ?SelectStatement $ext_sql = null): MetaRecord
    {
        $params = new ArrayObject($params);
        $sql = $ext_sql instanceof SelectStatement ? clone $ext_sql : new SelectStatement();

        if ($count_only) {
            $sql->column($sql->count($sql->unique('M.meta_id')));
        } else {
            $sql->columns([
                'M.meta_id',
                'M.meta_type',
                $sql->count('M.post_id', 'count'),
                $sql->max('P.post_dt', 'latest'),
                $sql->min('P.post_dt', 'oldest'),
            ]);
        }

        $sql
            ->from($sql->as($this->table, 'M'))
            ->join(
                (new JoinStatement())
                ->left()
                ->from($sql->as($this->con->prefix() . $this->blog::POST_TABLE_NAME, 'P'))
                ->on('M.post_id = P.post_id')
                ->statement()
            )
            ->where('P.blog_id = ' . $sql->quote($this->blog->id()));

        if (isset($params['meta_type'])) {
            $sql->and('meta_type = ' . $sql->quote($params['meta_type']));
        }

        if (isset($params['meta_id'])) {
            $sql->and('meta_id = ' . $sql->quote($params['meta_id']));
        }

        if (isset($params['post_id'])) {
            $sql->and('P.post_id' . $sql->in($params['post_id']));
        }

        App::blog()->getPostsAddingParameters($params, $sql);

        if (!$count_only) {
            if (!isset($params['order'])) {
                $params['order'] = 'count DESC';
            }

            $sql
                ->group([
                    'meta_id',
                    'meta_type',
                    'P.blog_id',
                ])
                ->order($params['order']);

            if (isset($params['limit'])) {
                $sql->limit($params['limit']);
            }
        }

        return $sql->select() ?? MetaRecord::newFromArray([]);
    }

    /**
     * Calculates the meta statistics from metadata recordset.
     *
     * @param      MetaRecord             $rs     Metadata recordset
     *
     * Will add these fields of each record of given recordset:
     *
     * - meta_id_lower = metadata id in lowercase without any diacritics
     * - percent = Usage frequency of this metadata upon all metadata of same type
     * - roundpercent = Decile usage (0 to 100 by 10 step)
     *
     * The percent (and roundpercent) will be calculate based on metadata usage (most used = 100%)
     *
     * Ex: A "photo" tag (assuming it's the the most used) is used 476 times (in 476 entries), its frequency will be 100%,
     * then a "blog" tag which is used in 327 entries will have a 69% frequency (327 ÷ 476 * 100).
     */
    public function computeMetaStats(MetaRecord $rs): MetaRecord
    {
        $rs_static = $rs->toStatic();

        /**
         * Maximum usage of metadata for each type (tag, …)
         *
         * @var        array<string, int>
         */
        $max = [];
        while ($rs_static->fetch()) {
            $type = $rs_static->meta_type;
            if (!isset($max[$type])) {
                $max[$type] = $rs_static->count;
            } elseif ($rs_static->count > $max[$type]) {
                $max[$type] = $rs_static->count;
            }
        }

        $rs_static->moveStart();
        while ($rs_static->fetch()) {   // @phpstan-ignore-line
            $rs_static->set('meta_id_lower', Text::removeDiacritics(mb_strtolower($rs_static->meta_id)));

            $percent = ((int) $rs_static->count) * 100 / $max[$rs_static->meta_type];

            $rs_static->set('percent', (int) round($percent));          // Usage frequency of this metadata upon all metadata of same type
            $rs_static->set('roundpercent', round($percent / 10) * 10); // Decile usage (0 to 100 by 10 step)
        }

        return $rs_static;
    }

    public function setPostMeta(int|string $post_id, ?string $type, ?string $value): void
    {
        $this->checkPermissionsOnPost($post_id);

        $value = trim((string) $value);
        if ($value === '') {
            return;
        }

        $cur = $this->openMetaCursor();

        $cur->post_id   = (int) $post_id;
        $cur->meta_id   = $value;
        $cur->meta_type = (string) $type;

        $cur->insert();
        $this->updatePostMeta((int) $post_id);
    }

    public function delPostMeta(int|string $post_id, ?string $type = null, ?string $meta_id = null): void
    {
        $post_id = (int) $post_id;

        $this->checkPermissionsOnPost($post_id);

        $sql = new DeleteStatement();
        $sql
            ->from($this->table)
            ->where('post_id = ' . $post_id);

        if ($type !== null) {
            $sql->and('meta_type = ' . $sql->quote($type));
        }

        if ($meta_id !== null) {
            $sql->and('meta_id = ' . $sql->quote($meta_id));
        }

        $sql->delete();

        $this->updatePostMeta($post_id);
    }

    public function updateMeta(string $meta_id, string $new_meta_id, ?string $type = null, ?string $post_type = null): bool
    {
        $new_meta_id = self::sanitizeMetaID($new_meta_id);

        if ($new_meta_id === $meta_id) {
            return true;
        }

        $sql = new SelectStatement();
        $sql
            ->from([
                $sql->as($this->table, 'M'),
                $sql->as($this->con->prefix() . $this->blog::POST_TABLE_NAME, 'P'),
            ])
            ->column('M.post_id')
            ->where('P.post_id = M.post_id')
            ->and('P.blog_id = ' . $sql->quote($this->blog->id()));

        if (!$this->auth->check($this->auth->makePermissions([
            $this->auth::PERMISSION_CONTENT_ADMIN,
        ]), $this->blog->id())) {
            $sql->and('P.user_id = ' . $sql->quote((string) $this->auth->userID()));
        }
        if ($post_type !== null) {
            $sql->and('P.post_type = ' . $sql->quote($post_type));
        }

        if ($type !== null) {
            $sql->and('meta_type = ' . $sql->quote($type));
        }

        $to_update = $to_remove = [];

        // Clone $sql object in order to do the same select query but with another meta_id
        $sqlNew = clone $sql;

        $sql->and('meta_id = ' . $sql->quote($meta_id));

        if (($rs = $sql->select()) instanceof MetaRecord) {
            while ($rs->fetch()) {
                $to_update[] = $rs->post_id;
            }

            if ($to_update === []) {
                return false;
            }
        }

        $sqlNew->and('meta_id = ' . $sqlNew->quote($new_meta_id));

        if (($rs = $sqlNew->select()) instanceof MetaRecord) {
            while ($rs->fetch()) {
                if (in_array($rs->post_id, $to_update)) {
                    $to_remove[] = $rs->post_id;
                    unset($to_update[array_search($rs->post_id, $to_update)]);
                }
            }
        }

        # Delete duplicate meta
        if ($to_remove !== []) {
            $sqlDel = new DeleteStatement();
            $sqlDel
                ->from($this->table)
                ->where('post_id' . $sqlDel->in($to_remove, 'int'))      // Note: will cast all values to integer
                ->and('meta_id = ' . $sqlDel->quote($meta_id));

            if ($type !== null) {
                $sqlDel->and('meta_type = ' . $sqlDel->quote($type));
            }

            $sqlDel->delete();

            foreach ($to_remove as $post_id) {
                $this->updatePostMeta($post_id);
            }
        }

        # Update meta
        if ($to_update !== []) {
            $sqlUpd = new UpdateStatement();
            $sqlUpd
                ->from($this->table)
                ->set('meta_id = ' . $sqlUpd->quote($new_meta_id))
                ->where('post_id' . $sqlUpd->in($to_update, 'int'))     // Note: will cast all values to integer
                ->and('meta_id = ' . $sqlUpd->quote($meta_id));

            if ($type !== null) {
                $sqlUpd->and('meta_type = ' . $sqlUpd->quote($type));
            }

            $sqlUpd->update();

            foreach ($to_update as $post_id) {
                $this->updatePostMeta($post_id);
            }
        }

        return true;
    }

    public function delMeta(string $meta_id, ?string $type = null, ?string $post_type = null): array
    {
        $sql = new SelectStatement();
        $sql
            ->column('M.post_id')
            ->from([
                $sql->as($this->table, 'M'),
                $sql->as($this->con->prefix() . $this->blog::POST_TABLE_NAME, 'P'),
            ])
            ->where('P.post_id = M.post_id')
            ->and('P.blog_id = ' . $sql->quote($this->blog->id()))
            ->and('meta_id = ' . $sql->quote($meta_id));

        if ($type !== null) {
            $sql->and('meta_type = ' . $sql->quote($type));
        }

        if ($post_type !== null) {
            $sql->and('P.post_type = ' . $sql->quote($post_type));
        }

        $rs = $sql->select();

        if (!$rs instanceof MetaRecord || $rs->isEmpty()) {
            return [];
        }

        $ids = [];
        while ($rs->fetch()) {
            $ids[] = $rs->post_id;
        }

        $sql = new DeleteStatement();
        $sql
            ->from($this->table)
            ->where('post_id' . $sql->in($ids, 'int'))
            ->and('meta_id = ' . $sql->quote($meta_id));

        if ($type !== null) {
            $sql->and('meta_type = ' . $sql->quote($type));
        }

        $sql->delete();

        foreach ($ids as $k => $post_id) {
            $this->updatePostMeta($post_id);
            $ids[$k] = (int) $post_id;
        }

        return $ids;
    }
}
