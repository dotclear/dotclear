<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\antispam;

use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Html;

/**
 * @brief   The module spam filter handler.
 * @ingroup antispam
 */
class SpamFilter
{
    /**
     * Filter id.
     */
    public string $id;

    /**
     * Filter name.
     */
    public string $name;

    /**
     * Filter description.
     */
    public string $description;

    /**
     * Is filter active.
     */
    public bool $active = true;

    /**
     * Filter order.
     */
    public int $order = 100;

    /**
     * Filter auto-delete spam?
     */
    public bool $auto_delete = false;

    /**
     * Filter help ID.
     */
    public ?string $help = null;

    /**
     * Filter has settings GUI?
     */
    protected bool $has_gui = false;

    /**
     * Filter settings GUI URL.
     */
    protected ?string $gui_url = null;

    /**
     * Constructs a new instance.
     */
    public function __construct()
    {
        $this->setInfo();

        $path = explode('\\', static::class);

        $id       = array_pop($path);
        $this->id = $id;

        $this->name ??= $this->id;

        if (App::task()->checkContext('BACKEND') && !App::task()->checkContext('INSTALL')) {
            $this->gui_url = App::backend()->url()->get('admin.plugin.antispam', ['f' => $this->id], '&');
        }
    }

    /**
     * Sets the filter description.
     */
    protected function setInfo(): void
    {
        $this->description = __('No description');
    }

    /**
     * This method should return if a comment is a spam or not.
     *
     * If it returns true or false, execution of next filters will be stoped.
     * If should return nothing to let next filters apply.
     *
     * Your filter should also fill $status variable with its own information if
     * comment is a spam.
     *
     * @param   string  $type       The comment type (comment / trackback)
     * @param   string  $author     The comment author
     * @param   string  $email      The comment author email
     * @param   string  $site       The comment author site
     * @param   string  $ip         The comment author IP
     * @param   string  $content    The comment content
     * @param   int     $post_id    The comment post_id
     * @param   string  $status     The comment status
     *
     * @return  mixed
     */
    public function isSpam(string $type, ?string $author, ?string $email, ?string $site, ?string $ip, ?string $content, ?int $post_id, string &$status)
    {
    }

    /**
     * Train the antispam filter.
     *
     * @param   string      $status     The comment status
     * @param   string      $filter     The filter
     * @param   string      $type       The comment type
     * @param   string      $author     The comment author
     * @param   string      $email      The comment author email
     * @param   string      $site       The comment author site
     * @param   string      $ip         The comment author IP
     * @param   string      $content    The comment content
     * @param   MetaRecord  $rs         The comment record
     *
     * @return  mixed
     */
    public function trainFilter(string $status, string $filter, string $type, ?string $author, ?string $email, ?string $site, ?string $ip, ?string $content, MetaRecord $rs)
    {
    }

    /**
     * This method returns filter status message.
     *
     * You can overload this method to return a custom message.
     * Message is shown in comment details and in comments list.
     *
     * @param   string  $status         The status
     * @param   int     $comment_id     The comment identifier
     *
     * @return  string  The status message.
     */
    public function getStatusMessage(string $status, ?int $comment_id): string
    {
        return sprintf(__('Filtered by %1$s (%2$s)'), $this->guiLink(), $status);
    }

    /**
     * This method is called when you enter filter configuration.
     *
     * Your class should have $has_gui property set to "true" to enable GUI.
     *
     * @param   string  $url    The GUI url
     *
     * @return  string  The GUI HTML content
     */
    public function gui(string $url): string
    {
        return '';
    }

    /**
     * Determines if filter has a settings GUI.
     *
     * @return  bool    True if gui, False otherwise.
     */
    public function hasGUI(): bool
    {
        if (!App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_ADMIN,
        ]), App::blog()->id())) {
            return false;
        }

        return $this->has_gui;
    }

    /**
     * Get the filter settings GUI URL.
     */
    public function guiURL(): false|string
    {
        if (!$this->hasGui()) {
            return false;
        }

        return is_null($this->gui_url) ? false : $this->gui_url;
    }

    /**
     * Returns a link to filter GUI if exists
     * or only filter name if has_gui property is false.
     */
    public function guiLink(): string
    {
        if (($url = $this->guiURL()) !== false) {
            return (new Link())->href(Html::escapeHTML($url))->text($this->name)->render();
        }

        return $this->name;
    }
}
