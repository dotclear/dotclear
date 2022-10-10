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
    /**
     * Filter name
     *
     * @var string
     */
    public $name;

    /**
     * Filter description
     *
     * @var string
     */
    public $description;

    /**
     * Is filter active?
     *
     * @var bool
     */
    public $active = true;

    /**
     * Filter order
     *
     * @var int
     */
    public $order = 100;

    /**
     * Filter auto-delete spam?
     *
     * @var bool
     */
    public $auto_delete = false;

    /**
     * Filter help ID
     *
     * @var        null|string
     */
    public $help = null;

    /**
     * Filter has settings GUI?
     *
     * @var bool
     */
    protected $has_gui = false;

    /**
     * Filter settings GUI URL
     *
     * @var null|string
     */
    protected $gui_url = null;

    /**
     * Constructs a new instance.
     */
    public function __construct()
    {
        $this->setInfo();

        if (!$this->name) {
            $this->name = get_class($this);
        }

        if (isset(dcCore::app()->adminurl)) {
            $this->gui_url = dcCore::app()->adminurl->get('admin.plugin.antispam', ['f' => get_class($this)], '&');
        }
    }

    /**
     * Sets the filter description.
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
     * @param      int     $post_id  The comment post_id
     * @param      string  $status   The comment status
     */
    public function isSpam(string $type, ?string $author, ?string $email, ?string $site, ?string $ip, ?string $content, ?int $post_id, string &$status)
    {
    }

    /**
     * Train the antispam filter
     *
     * @param      string        $status   The comment status
     * @param      string        $filter   The filter
     * @param      string        $type     The comment type
     * @param      string        $author   The comment author
     * @param      string        $email    The comment author email
     * @param      string        $site     The comment author site
     * @param      string        $ip       The comment author IP
     * @param      string        $content  The comment content
     * @param      dcRecord      $rs       The comment record
     */
    public function trainFilter(string $status, string $filter, string $type, ?string $author, ?string $email, ?string $site, ?string $ip, ?string $content, dcRecord $rs)
    {
    }

    /**
     * This method returns filter status message. You can overload this method to
     * return a custom message. Message is shown in comment details and in
     * comments list.
     *
     * @param      string  $status      The status
     * @param      int     $comment_id  The comment identifier
     *
     * @return     string  The status message.
     */
    public function getStatusMessage(string $status, ?int $comment_id)
    {
        return sprintf(__('Filtered by %1$s (%2$s)'), $this->guiLink(), $status);
    }

    /**
     * This method is called when you enter filter configuration. Your class should
     * have $has_gui property set to "true" to enable GUI.
     *
     * @param      string  $url    The GUI url
     *
     * @return     string  The GUI HTML content
     */
    public function gui(string $url): string
    {
        return '';
    }

    /**
     * Determines if filter has a settings GUI.
     *
     * @return     bool  True if gui, False otherwise.
     */
    public function hasGUI(): bool
    {
        if (!dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_ADMIN,
        ]), dcCore::app()->blog->id)) {
            return false;
        }

        if (!$this->has_gui) {
            return false;
        }

        return true;
    }

    /**
     * Get the filter settings GUI URL
     *
     * @return     false|string
     */
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
    public function guiLink(): string
    {
        if (($url = $this->guiURL()) !== false) {
            $url  = html::escapeHTML($url);
            $link = '<a href="%2$s">%1$s</a>';
        } else {
            $link = '%1$s';
        }

        return sprintf($link, $this->name, $url);
    }
}
