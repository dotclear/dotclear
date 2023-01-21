<?php
/**
 * @brief blogroll, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\blogroll;

use Exception;
use dcBlog;
use dcCore;
use dcRecord;

class Blogroll
{
    // Constants

    /**
     * Links table name
     *
     * @var        string
     */
    public const LINK_TABLE_NAME = 'link';

    /**
     * Current blog
     *
     * @var        dcBlog
     */
    private $blog;

    /**
     * Table name
     */
    private string $table;

    /**
     * Constructs a new instance.
     *
     * @param      dcBlog  $blog   The blog
     */
    public function __construct(dcBlog $blog)
    {
        $this->blog  = $blog;
        $this->table = dcCore::app()->prefix . self::LINK_TABLE_NAME;
    }

    /**
     * Gets the links.
     *
     * @param      array   $params  The parameters
     *
     * @return     dcRecord  The links.
     */
    public function getLinks(array $params = []): dcRecord
    {
        $strReq = 'SELECT link_id, link_title, link_desc, link_href, ' .
        'link_lang, link_xfn, link_position ' .
        'FROM ' . $this->table . ' ' .
        "WHERE blog_id = '" . $this->blog->con->escape($this->blog->id) . "' ";

        if (isset($params['link_id'])) {
            $strReq .= 'AND link_id = ' . (int) $params['link_id'] . ' ';
        }

        $strReq .= 'ORDER BY link_position ';

        $rs = new dcRecord($this->blog->con->select($strReq));
        $rs = $rs->toStatic();

        $this->setLinksData($rs);

        return $rs;
    }

    /**
     * Gets the links.
     *
     * @param      array   $params  The parameters
     *
     * @return     dcRecord  The links.
     */
    public function getLangs(array $params = []): dcRecord
    {
        // Use post_lang as an alias of link_lang to be able to use the dcAdminCombos::getLangsCombo() function
        $strReq = 'SELECT COUNT(link_id) as nb_link, link_lang as post_lang ' .
        'FROM ' . $this->table . ' ' .
        "WHERE blog_id = '" . $this->blog->con->escape($this->blog->id) . "' " .
            "AND link_lang <> '' " .
            'AND link_lang IS NOT NULL ';

        if (isset($params['lang'])) {
            $strReq .= "AND link_lang = '" . $this->blog->con->escape($params['lang']) . "' ";
        }

        $strReq .= 'GROUP BY link_lang ';

        $order = 'desc';
        if (!empty($params['order']) && preg_match('/^(desc|asc)$/i', (string) $params['order'])) {
            $order = (string) $params['order'];
        }
        $strReq .= 'ORDER BY link_lang ' . $order . ' ';

        return new dcRecord($this->blog->con->select($strReq));
    }

    /**
     * Gets a link.
     *
     * @param      string  $id     The identifier
     *
     * @return     dcRecord  The link.
     */
    public function getLink(string $id): dcRecord
    {
        return $this->getLinks(['link_id' => $id]);
    }

    /**
     * Adds a link.
     *
     * @param      string     $title  The title
     * @param      string     $href   The href
     * @param      string     $desc   The description
     * @param      string     $lang   The language
     * @param      string     $xfn    The xfn
     *
     * @throws     Exception
     */
    public function addLink(string $title, string $href, string $desc = '', string $lang = '', string $xfn = ''): void
    {
        $cur = $this->blog->con->openCursor($this->table);

        $cur->blog_id    = (string) $this->blog->id;
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

        $strReq       = 'SELECT MAX(link_id) FROM ' . $this->table;
        $rs           = new dcRecord($this->blog->con->select($strReq));
        $cur->link_id = (int) $rs->f(0) + 1;

        $cur->insert();
        $this->blog->triggerBlog();
    }

    /**
     * Update a link
     *
     * @param      string     $id     The identifier
     * @param      string     $title  The title
     * @param      string     $href   The href
     * @param      string     $desc   The description
     * @param      string     $lang   The language
     * @param      string     $xfn    The xfn
     *
     * @throws     Exception
     */
    public function updateLink(string $id, string $title, string $href, string $desc = '', string $lang = '', string $xfn = ''): void
    {
        $cur = $this->blog->con->openCursor($this->table);

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

        $cur->update('WHERE link_id = ' . (int) $id .
            " AND blog_id = '" . $this->blog->con->escape($this->blog->id) . "'");
        $this->blog->triggerBlog();
    }

    /**
     * Update a category
     *
     * @param      string     $id     The identifier
     * @param      string     $desc   The description
     *
     * @throws     Exception
     */
    public function updateCategory(string $id, string $desc): void
    {
        $cur = $this->blog->con->openCursor($this->table);

        $cur->link_desc = $desc;

        if ($cur->link_desc === '') {
            throw new Exception(__('You must provide a category title'));
        }

        $cur->update('WHERE link_id = ' . (int) $id .
            " AND blog_id = '" . $this->blog->con->escape($this->blog->id) . "'");
        $this->blog->triggerBlog();
    }

    /**
     * Adds a category.
     *
     * @param      string     $title  The title
     *
     * @throws     Exception
     *
     * @return     int     The category ID
     */
    public function addCategory(string $title): int
    {
        $cur = $this->blog->con->openCursor($this->table);

        $cur->blog_id    = (string) $this->blog->id;
        $cur->link_desc  = (string) $title;
        $cur->link_href  = '';
        $cur->link_title = '';

        if ($cur->link_desc == '') {
            throw new Exception(__('You must provide a category title'));
        }

        $strReq       = 'SELECT MAX(link_id) FROM ' . $this->table;
        $rs           = new dcRecord($this->blog->con->select($strReq));
        $cur->link_id = (int) $rs->f(0) + 1;

        $cur->insert();
        $this->blog->triggerBlog();

        return $cur->link_id;
    }

    /**
     * Delete a link
     *
     * @param      string  $id     The identifier
     */
    public function delItem(string $id): void
    {
        $id = (int) $id;

        $strReq = 'DELETE FROM ' . $this->table . ' ' .
        "WHERE blog_id = '" . $this->blog->con->escape($this->blog->id) . "' " .
            'AND link_id = ' . $id . ' ';

        $this->blog->con->execute($strReq);
        $this->blog->triggerBlog();
    }

    /**
     * Update a link order
     *
     * @param      string  $id        The identifier
     * @param      string  $position  The position
     */
    public function updateOrder(string $id, string $position): void
    {
        $cur                = $this->blog->con->openCursor($this->table);
        $cur->link_position = (int) $position;

        $cur->update('WHERE link_id = ' . (int) $id .
            " AND blog_id = '" . $this->blog->con->escape($this->blog->id) . "'");
        $this->blog->triggerBlog();
    }

    /**
     * Sets the links data.
     *
     * @param      dcRecord  $rs     The links
     */
    private function setLinksData(dcRecord $rs): void
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
     * @param      dcRecord  $rs     The links
     *
     * @return     array   The links hierarchy.
     */
    public function getLinksHierarchy(dcRecord $rs): array
    {
        $res = [];

        foreach ($rs->rows() as $v) {
            if (!$v['is_cat']) {
                $res[$v['cat_title']][] = $v;
            }
        }

        return $res;
    }
}
