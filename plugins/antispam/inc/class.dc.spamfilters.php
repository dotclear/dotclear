<?php
/**
 * @brief antispam, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

class dcSpamFilters
{
    private $filters     = array();
    private $filters_opt = array();
    private $core;

    public function __construct($core)
    {
        $this->core = &$core;
    }

    public function init($filters)
    {
        foreach ($filters as $f) {
            if (!class_exists($f)) {
                continue;
            }

            $r = new ReflectionClass($f);
            $p = $r->getParentClass();

            if (!$p || $p->name != 'dcSpamFilter') {
                continue;
            }

            $this->filters[$f] = new $f($this->core);
        }

        $this->setFilterOpts();
        if (!empty($this->filters_opt)) {
            uasort($this->filters, array($this, 'orderCallBack'));
        }
    }

    public function getFilters()
    {
        return $this->filters;
    }

    public function isSpam($cur)
    {
        foreach ($this->filters as $fid => $f) {
            if (!$f->active) {
                continue;
            }

            $type    = $cur->comment_trackback ? 'trackback' : 'comment';
            $author  = $cur->comment_author;
            $email   = $cur->comment_email;
            $site    = $cur->comment_site;
            $ip      = $cur->comment_ip;
            $content = $cur->comment_content;
            $post_id = $cur->post_id;

            $is_spam = $f->isSpam($type, $author, $email, $site, $ip, $content, $post_id, $status);

            if ($is_spam === true) {
                if ($f->auto_delete) {
                    $cur->clean();
                } else {
                    $cur->comment_status      = -2;
                    $cur->comment_spam_status = $status;
                    $cur->comment_spam_filter = $fid;
                }
                return true;
            } elseif ($is_spam === false) {
                return false;
            }
        }

        return false;
    }

    public function trainFilters($rs, $status, $filter_name)
    {
        foreach ($this->filters as $fid => $f) {
            if (!$f->active) {
                continue;
            }

            $type    = $rs->comment_trackback ? 'trackback' : 'comment';
            $author  = $rs->comment_author;
            $email   = $rs->comment_email;
            $site    = $rs->comment_site;
            $ip      = $rs->comment_ip;
            $content = $rs->comment_content;

            $f->trainFilter($status, $filter_name, $type, $author, $email, $site, $ip, $content, $rs);
        }
    }

    public function statusMessage($rs, $filter_name)
    {
        $f = isset($this->filters[$filter_name]) ? $this->filters[$filter_name] : null;

        if ($f === null) {
            return __('Unknown filter.');
        } else {
            $status = $rs->exists('comment_spam_status') ? $rs->comment_spam_status : null;

            return $f->getStatusMessage($status, $rs->comment_id);
        }
    }

    public function saveFilterOpts($opts, $global = false)
    {
        $this->core->blog->settings->addNamespace('antispam');
        if ($global) {
            $this->core->blog->settings->antispam->drop('antispam_filters');
        }
        $this->core->blog->settings->antispam->put('antispam_filters', $opts, 'array', 'Antispam Filters', true, $global);
    }

    private function setFilterOpts()
    {
        if ($this->core->blog->settings->antispam->antispam_filters !== null) {
            $this->filters_opt = $this->core->blog->settings->antispam->antispam_filters;
        }

        # Create default options if needed
        if (!is_array($this->filters_opt)) {
            $this->saveFilterOpts(array(), true);
            $this->filters_opt = array();
        }

        foreach ($this->filters_opt as $k => $o) {
            if (isset($this->filters[$k]) && is_array($o)) {
                $this->filters[$k]->active      = isset($o[0]) ? $o[0] : false;
                $this->filters[$k]->order       = isset($o[1]) ? $o[1] : 0;
                $this->filters[$k]->auto_delete = isset($o[2]) ? $o[2] : false;
            }
        }
    }

    private function orderCallBack($a, $b)
    {
        if ($a->order == $b->order) {
            return 0;
        }

        return $a->order > $b->order ? 1 : -1;
    }
}
