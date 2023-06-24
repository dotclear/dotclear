<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend\Action;

use ArrayObject;
use dcCore;
use Dotclear\Core\Backend\Page;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Html;
use Dotclear\Plugin\antispam\Filters\Ip as dcFilterIP;
use Dotclear\Plugin\antispam\Filters\IpV6 as dcFilterIPv6;
use Exception;

class ActionsComments extends Actions
{
    /**
     * Constructs a new instance.
     *
     * @param      null|string  $uri            The uri
     * @param      array        $redirect_args  The redirect arguments
     */
    public function __construct(?string $uri, array $redirect_args = [])
    {
        parent::__construct($uri, $redirect_args);

        $this->redirect_fields = [
            'type', 'author', 'status', 'sortby', 'ip', 'order', 'page', 'nb', 'section',
        ];
        $this->field_entries = 'comments';
        $this->cb_title      = __('Comments');

        $this->loadDefaults();
        # --BEHAVIOR-- adminCommentsActions -- Actions
        dcCore::app()->callBehavior('adminCommentsActions', $this);
    }

    /**
     * Loads comments actions.
     */
    protected function loadDefaults()
    {
        // We could have added a behavior here, but we want default action to be setup first
        ActionsCommentsDefault::adminCommentsActionsPage($this);
    }

    /**
     * Begins a page.
     *
     * @param      string  $breadcrumb  The breadcrumb
     * @param      string  $head        The head
     */
    public function beginPage(string $breadcrumb = '', string $head = '')
    {
        if ($this->in_plugin) {
            Page::openModule(
                __('Comments'),
                Page::jsLoad('js/_comments_actions.js') .
                $head
            );
            echo $breadcrumb;
        } else {
            Page::open(
                __('Comments'),
                Page::jsLoad('js/_comments_actions.js') .
                $head,
                $breadcrumb
            );
        }
        echo '<p><a class="back" href="' . $this->getRedirection(true) . '">' . __('Back to comments list') . '</a></p>';
    }

    /**
     * Ends a page.
     */
    public function endPage()
    {
        if ($this->in_plugin) {
            Page::closeModule();
        } else {
            Page::close();
        }
    }

    /**
     * Display error page
     *
     * @param      Exception  $e      { parameter_description }
     */
    public function error(Exception $e)
    {
        dcCore::app()->error->add($e->getMessage());
        $this->beginPage(
            Page::breadcrumb(
                [
                    Html::escapeHTML(dcCore::app()->blog->name) => '',
                    __('Comments')                              => dcCore::app()->adminurl->get('admin.comments'),
                    __('Comments actions')                      => '',
                ]
            )
        );
        $this->endPage();
    }

    /**
     * Returns HTML code for selected entries as a table containing entries checkboxes
     *
     * @return string the HTML code for checkboxes
     */
    public function getCheckboxes(): string
    {
        $ret = '<table class="posts-list"><tr>' .
        '<th colspan="2">' . __('Author') . '</th><th>' . __('Title') . '</th>' .
            '</tr>';
        foreach ($this->entries as $id => $description) {
            $ret .= '<tr><td class="minimal">' .
            (new Checkbox([$this->field_entries . '[]'], true))->value($id)->render() .
                '</td>' .
                '<td>' . $description['author'] . '</td><td>' . $description['title'] . '</td></tr>';
        }
        $ret .= '</table>';

        return $ret;
    }

    /**
     * Fetches entries.
     *
     * @param      ArrayObject  $from   The parameters ($_POST)
     */
    protected function fetchEntries(ArrayObject $from)
    {
        $params = [];
        if (!empty($from['comments'])) {
            $comments = $from['comments'];

            foreach ($comments as $k => $v) {
                $comments[$k] = (int) $v;
            }

            $params['sql'] = 'AND C.comment_id IN(' . implode(',', $comments) . ') ';
        } else {
            $params['sql'] = 'AND 1=0 ';
        }

        if (!isset($from['full_content']) || empty($from['full_content'])) {
            $params['no_content'] = true;
        }
        $rs = dcCore::app()->blog->getComments($params);
        while ($rs->fetch()) {
            $this->entries[$rs->comment_id] = [
                'title'  => $rs->post_title,
                'author' => $rs->comment_author,
            ];
        }
        $this->rs = $rs;
    }
}
