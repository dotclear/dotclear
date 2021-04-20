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
if (!defined('DC_RC_PATH')) {
    return;
}

class dcBlogroll
{
    private $blog;
    private $con;
    private $table;

    public function __construct($blog)
    {
        $this->blog  = &$blog;
        $this->con   = &$blog->con;
        $this->table = $this->blog->prefix . 'link';
    }

    public function getLinks($params = [])
    {
        $strReq = 'SELECT link_id, link_title, link_desc, link_href, ' .
        'link_lang, link_xfn, link_position ' .
        'FROM ' . $this->table . ' ' .
        "WHERE blog_id = '" . $this->con->escape($this->blog->id) . "' ";

        if (isset($params['link_id'])) {
            $strReq .= 'AND link_id = ' . (integer) $params['link_id'] . ' ';
        }

        $strReq .= 'ORDER BY link_position ';

        $rs = $this->con->select($strReq);
        $rs = $rs->toStatic();

        $this->setLinksData($rs);

        return $rs;
    }

    public function getLangs($params = [])
    {
        // Use post_lang as an alias of link_lang to be able to use the dcAdminCombos::getLangsCombo() function
        $strReq = 'SELECT COUNT(link_id) as nb_link, link_lang as post_lang ' .
        'FROM ' . $this->table . ' ' .
        "WHERE blog_id = '" . $this->con->escape($this->blog->id) . "' " .
            "AND link_lang <> '' " .
            'AND link_lang IS NOT NULL ';

        if (isset($params['lang'])) {
            $strReq .= "AND link_lang = '" . $this->con->escape($params['lang']) . "' ";
        }

        $strReq .= 'GROUP BY link_lang ';

        $order = 'desc';
        if (!empty($params['order']) && preg_match('/^(desc|asc)$/i', $params['order'])) {
            $order = $params['order'];
        }
        $strReq .= 'ORDER BY link_lang ' . $order . ' ';

        return $this->con->select($strReq);
    }

    public function getLink($id)
    {
        $params['link_id'] = $id;

        $rs = $this->getLinks($params);

        return $rs;
    }

    public function addLink($title, $href, $desc = '', $lang = '', $xfn = '')
    {
        $cur = $this->con->openCursor($this->table);

        $cur->blog_id    = (string) $this->blog->id;
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

        $strReq       = 'SELECT MAX(link_id) FROM ' . $this->table;
        $rs           = $this->con->select($strReq);
        $cur->link_id = (integer) $rs->f(0) + 1;

        $cur->insert();
        $this->blog->triggerBlog();
    }

    public function updateLink($id, $title, $href, $desc = '', $lang = '', $xfn = '')
    {
        $cur = $this->con->openCursor($this->table);

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

        $cur->update('WHERE link_id = ' . (integer) $id .
            " AND blog_id = '" . $this->con->escape($this->blog->id) . "'");
        $this->blog->triggerBlog();
    }

    public function updateCategory($id, $desc)
    {
        $cur = $this->con->openCursor($this->table);

        $cur->link_desc = (string) $desc;

        if ($cur->link_desc == '') {
            throw new Exception(__('You must provide a category title'));
        }

        $cur->update('WHERE link_id = ' . (integer) $id .
            " AND blog_id = '" . $this->con->escape($this->blog->id) . "'");
        $this->blog->triggerBlog();
    }

    public function addCategory($title)
    {
        $cur = $this->con->openCursor($this->table);

        $cur->blog_id    = (string) $this->blog->id;
        $cur->link_desc  = (string) $title;
        $cur->link_href  = '';
        $cur->link_title = '';

        if ($cur->link_desc == '') {
            throw new Exception(__('You must provide a category title'));
        }

        $strReq       = 'SELECT MAX(link_id) FROM ' . $this->table;
        $rs           = $this->con->select($strReq);
        $cur->link_id = (integer) $rs->f(0) + 1;

        $cur->insert();
        $this->blog->triggerBlog();

        return $cur->link_id;
    }

    public function delItem($id)
    {
        $id = (integer) $id;

        $strReq = 'DELETE FROM ' . $this->table . ' ' .
        "WHERE blog_id = '" . $this->con->escape($this->blog->id) . "' " .
            'AND link_id = ' . $id . ' ';

        $this->con->execute($strReq);
        $this->blog->triggerBlog();
    }

    public function updateOrder($id, $position)
    {
        $cur                = $this->con->openCursor($this->table);
        $cur->link_position = (integer) $position;

        $cur->update('WHERE link_id = ' . (integer) $id .
            " AND blog_id = '" . $this->con->escape($this->blog->id) . "'");
        $this->blog->triggerBlog();
    }

    private function setLinksData($rs)
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

    public function getLinksHierarchy($rs)
    {
        $res = [];

        foreach ($rs->rows() as $k => $v) {
            if (!$v['is_cat']) {
                $res[$v['cat_title']][] = $v;
            }
        }

        return $res;
    }
}
