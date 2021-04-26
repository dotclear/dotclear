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
if (!defined('DC_RC_PATH')) {
    return;
}

class dcSpamFilter
{
    public $name;
    public $description;
    public $active      = true;
    public $order       = 100;
    public $auto_delete = false;
    public $help        = null;

    protected $has_gui = false;
    protected $gui_url = null;

    protected $core;

    /**
     * Constructs a new instance.
     *
     * @param      dcCore  $core   The core
     */
    public function __construct(dcCore $core)
    {
        $this->core = &$core;
        $this->setInfo();

        if (!$this->name) {
            $this->name = get_class($this);
        }

        if (isset($core->adminurl)) {
            $this->gui_url = $core->adminurl->get('admin.plugin.antispam', ['f' => get_class($this)], '&');
        }
    }

    /**
    This method is called by the constructor and allows you to change some
    object properties without overloading object constructor.
     */
    protected function setInfo()
    {
        $this->description = __('No description');
    }

    /**
     * This method should return if a comment is a spam or not. If it returns true
     * or false, execution of next filters will be stoped. If should return nothing
     * to let next filters apply.
     *
     * Your filter should also fill $status variable with its own information if
     * comment is a spam.
     *
     * @param      string  $type     The comment type (comment / trackback)
     * @param      string  $author   The comment author
     * @param      string  $email    The comment author email
     * @param      string  $site     The comment author site
     * @param      string  $ip       The comment author IP
     * @param      string  $content  The comment content
     * @param      integer $post_id  The comment post_id
     * @param      integer $status   The comment status
     */
    public function isSpam($type, $author, $email, $site, $ip, $content, $post_id, &$status)
    {
    }

    /**
     * { function_description }
     *
     * @param      string  $status   The comment status
     * @param      string  $filter   The filter
     * @param      string  $type     The comment type
     * @param      string  $author   The comment author
     * @param      string  $email    The comment author email
     * @param      string  $site     The comment author site
     * @param      string  $ip       The comment author IP
     * @param      string  $content  The comment content
     * @param      record  $rs       The comment record
     */
    public function trainFilter($status, $filter, $type, $author, $email, $site, $ip, $content, $rs)
    {
    }

    /**
     * This method returns filter status message. You can overload this method to
     * return a custom message. Message is shown in comment details and in
     * comments list.
     *
     * @param      string  $status      The status
     * @param      integer $comment_id  The comment identifier
     *
     * @return     string  The status message.
     */
    public function getStatusMessage($status, $comment_id)
    {
        return sprintf(__('Filtered by %1$s (%2$s)'), $this->guiLink(), $status);
    }

    /**
     * This method is called when you enter filter configuration. Your class should
     * have $has_gui property set to "true" to enable GUI.
     *
     * @param      string  $url    The GUI url
     */
    public function gui($url)
    {
    }

    public function hasGUI()
    {
        if (!$this->core->auth->check('admin', $this->core->blog->id)) {
            return false;
        }

        if (!$this->has_gui) {
            return false;
        }

        return true;
    }

    public function guiURL()
    {
        if (!$this->hasGui()) {
            return false;
        }

        return $this->gui_url;
    }

    /**
     * Returns a link to filter GUI if exists or only filter name if has_gui
     * property is false.
     *
     * @return     string
     */
    public function guiLink()
    {
        if (($url = $this->guiURL()) !== false) {
            $url  = html::escapeHTML($url);
            $link = '<a href="%2$s">%1$s</a>';
        } else {
            $link = '%1$s';
        }

        return sprintf($link, $this->name, $url);
    }

    public function help()
    {
    }
}
