<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\blogroll;

use Exception;
use Dotclear\App;
use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\DeleteStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Database\Statement\UpdateStatement;
use Dotclear\Interface\Core\BlogInterface;

/**
 * @brief   The module blogroll handler.
 * @ingroup blogroll
 *
 * @todo    Use sqlStatement in plugin blogroll handler
 */
class Blogroll
{
    /**
     * Blogroll permission
     *
     * @var        string
     */
    public const PERMISSION_BLOGROLL = 'blogroll';

    /**
     * Links table name
     *
     * @var        string
     */
    public const LINK_TABLE_NAME = 'link';

    /**
     * Table name.
     *
     * @var     string  $table
     */
    private readonly string $table;

    /**
     * Constructs a new instance.
     *
     * @param   BlogInterface   $blog   Current blog
     */
    public function __construct(
        private readonly BlogInterface $blog
    ) {
        $this->table = App::con()->prefix() . self::LINK_TABLE_NAME;
    }

    /**
     * Gets the links.
     *
     * @param   array<string, mixed>   $params     The parameters
     *
     * @return  MetaRecord  The links.
     */
    public function getLinks(array $params = []): MetaRecord
    {
        $sql = new SelectStatement();
        $sql
            ->columns([
                'link_id',
                'link_title',
                'link_desc',
                'link_href',
                'link_lang',
                'link_xfn',
                'link_position',
            ])
            ->from($this->table)
            ->where('blog_id = ' . $sql->quote($this->blog->id()))
            ->order('link_position');

        if (isset($params['link_id'])) {
            $sql->and('link_id = ' . (int) $params['link_id'] . ' ');
        }

        $rs = $sql->select();
        if ($rs) {
            $rs = $rs->toStatic();

            $this->setLinksData($rs);
        }

        return $rs ?? MetaRecord::newFromArray([]);
    }

    /**
     * Gets the links.
     *
     * @param   array<string, mixed>   $params     The parameters
     *
     * @return  MetaRecord  The links.
     */
    public function getLangs(array $params = []): MetaRecord
    {
        // Use post_lang as an alias of link_lang to be able to use the backend Combos::getLangsCombo() function
        $sql = new SelectStatement();
        $sql
            ->columns([
                $sql->as($sql->count('link_id'), 'nb_link'),
                $sql->as('link_lang', 'post_lang'),
            ])
            ->from($this->table)
            ->where('blog_id = ' . $sql->quote($this->blog->id()))
            ->and("link_lang <> '' ")
            ->and('link_lang IS NOT NULL ')
            ->group('link_lang')
            ->order('link_lang ' . (!empty($params['order']) && preg_match('/^(desc|asc)$/i', (string) $params['order']) ? (string) $params['order'] : 'desc'));

        if (isset($params['lang'])) {
            $sql->and('link_lang = ' . $sql->quote($params['lang']));
        }

        return $sql->select() ?? MetaRecord::newFromArray([]);
    }

    /**
     * Gets a link.
     *
     * @param   string  $id     The identifier
     *
     * @return  MetaRecord  The link.
     */
    public function getLink(string $id): MetaRecord
    {
        return $this->getLinks(['link_id' => $id]);
    }

    /**
     * Adds a link.
     *
     * @param   string  $title  The title
     * @param   string  $href   The href
     * @param   string  $desc   The description
     * @param   string  $lang   The language
     * @param   string  $xfn    The xfn
     *
     * @throws  Exception
     */
    public function addLink(string $title, string $href, string $desc = '', string $lang = '', string $xfn = ''): void
    {
        $cur = App::con()->openCursor($this->table);

        $cur->blog_id    = $this->blog->id();
        $cur->link_title = $title;
        $cur->link_href  = $href;
        $cur->link_desc  = $desc;
        $cur->link_lang  = $lang;
        $cur->link_xfn   = $xfn;

        if ($cur->link_title == '') {
            throw new Exception(__('You must provide a link title'));
        }

        if ($cur->link_href == '') {
            throw new Exception(__('You must provide a link URL'));
        }

        $sql = new SelectStatement();
        $run = $sql
            ->column($sql->max('link_id'))
            ->from($this->table)
            ->select();
        $max = $run ? $run->f(0) : 0;

        $cur->link_id = $max + 1;

        $cur->insert();
        $this->blog->triggerBlog();
    }

    /**
     * Update a link.
     *
     * @param   string  $id     The identifier
     * @param   string  $title  The title
     * @param   string  $href   The href
     * @param   string  $desc   The description
     * @param   string  $lang   The language
     * @param   string  $xfn    The xfn
     *
     * @throws  Exception
     */
    public function updateLink(string $id, string $title, string $href, string $desc = '', string $lang = '', string $xfn = ''): void
    {
        $cur = App::con()->openCursor($this->table);

        $cur->link_title = (string) $title;
        $cur->link_href  = (string) $href;
        $cur->link_desc  = (string) $desc;
        $cur->link_lang  = (string) $lang;
        $cur->link_xfn   = (string) $xfn;

        if ($cur->link_title == '') {
            throw new Exception(__('You must provide a link title'));
        }

        if ($cur->link_href == '') {
            throw new Exception(__('You must provide a link URL'));
        }

        $this->updateCursor($cur, $id);
    }

    /**
     * Update a category.
     *
     * @param   string  $id     The identifier
     * @param   string  $desc   The description
     *
     * @throws  Exception
     */
    public function updateCategory(string $id, string $desc): void
    {
        $cur = App::con()->openCursor($this->table);

        $cur->link_desc = $desc;

        if ($cur->link_desc === '') {
            throw new Exception(__('You must provide a category title'));
        }

        $this->updateCursor($cur, $id);
    }

    /**
     * Adds a category.
     *
     * @param   string  $title  The title
     *
     * @throws  Exception
     *
     * @return  int     The category ID
     */
    public function addCategory(string $title): int
    {
        $cur = App::con()->openCursor($this->table);

        $cur->blog_id    = $this->blog->id();
        $cur->link_desc  = $title;
        $cur->link_href  = '';
        $cur->link_title = '';

        if ($cur->link_desc == '') {
            throw new Exception(__('You must provide a category title'));
        }

        $sql = new SelectStatement();
        $run = $sql
            ->column($sql->max('link_id'))
            ->from($this->table)
            ->select();
        $max = $run ? $run->f(0) : 0;

        $cur->link_id = (int) $max + 1;

        $cur->insert();
        $this->blog->triggerBlog();

        return $cur->link_id;
    }

    /**
     * Delete a link.
     *
     * @param   string  $id     The identifier
     */
    public function delItem(string $id): void
    {
        $id = (int) $id;

        $sql = new DeleteStatement();
        $sql
            ->from($this->table)
            ->where('blog_id = ' . $sql->quote($this->blog->id()))
            ->and('link_id = ' . (string) $id)
            ->delete();

        $this->blog->triggerBlog();
    }

    /**
     * Update a link order.
     *
     * @param   string  $id         The identifier
     * @param   string  $position   The position
     */
    public function updateOrder(string $id, string $position): void
    {
        $cur                = App::con()->openCursor($this->table);
        $cur->link_position = (int) $position;

        $this->updateCursor($cur, $id);
    }

    /**
     * Update cursor.
     *
     * @param   Cursor  $cur    The cursor
     * @param   string  $id     The link ID
     */
    private function updateCursor(Cursor $cur, string $id): void
    {
        $sql = new UpdateStatement();
        $sql
            ->where('blog_id = ' . $sql->quote($this->blog->id()))
            ->and('link_id = ' . (string) (int) $id)
            ->update($cur);

        $this->blog->triggerBlog();
    }

    /**
     * Sets the links data.
     *
     * @param   MetaRecord  $rs     The links
     */
    private function setLinksData(MetaRecord $rs): void
    {
        $cat_title = null;
        while ($rs->fetch()) {
            $rs->set('is_cat', !$rs->link_title && !$rs->link_href);

            if ($rs->is_cat) {
                $cat_title = $rs->link_desc;
                $rs->set('cat_title', null);
            } else {
                $rs->set('cat_title', $cat_title);
            }
        }
        $rs->moveStart();
    }

    /**
     * Gets the links hierarchy.
     *
     * @param   MetaRecord  $rs     The links
     *
     * @return  array<string, mixed>   The links hierarchy.
     */
    public function getLinksHierarchy(MetaRecord $rs): array
    {
        $res = [];

        foreach ($rs->rows() as $v) {
            if (!$v['is_cat']) {
                $res[$v['cat_title']][] = $v;
            }
        }

        return $res;    // @phpstan-ignore-line
    }
}
