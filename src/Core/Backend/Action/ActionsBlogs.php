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
use Dotclear\Helper\Html\Html;
use Exception;
use form;

class ActionsBlogs extends Actions
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
            'status', 'sortby', 'order', 'page', 'nb',
        ];
        $this->field_entries = 'blogs';
        $this->cb_title      = __('Blogs');

        $this->loadDefaults();
        # --BEHAVIOR-- adminBlogsActions -- Actions
        dcCore::app()->callBehavior('adminBlogsActions', $this);
    }

    /**
     * Loads defaults.
     */
    protected function loadDefaults()
    {
        // We could have added a behavior here, but we want default action to be setup first
        ActionsBlogsDefault::adminBlogsActionsPage($this);
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
                __('Blogs'),
                Page::jsLoad('js/_blogs_actions.js') .
                $head
            );
            echo $breadcrumb;
        } else {
            Page::open(
                __('Blogs'),
                Page::jsLoad('js/_blogs_actions.js') .
                $head,
                $breadcrumb
            );
        }
        echo '<p><a class="back" href="' . $this->getRedirection(true) . '">' . __('Back to blogs list') . '</a></p>';
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
     * @param      Exception  $e
     */
    public function error(Exception $e)
    {
        dcCore::app()->error->add($e->getMessage());
        $this->beginPage(
            Page::breadcrumb(
                [
                    Html::escapeHTML(dcCore::app()->blog->name) => '',
                    __('Blogs')                                 => dcCore::app()->admin->url->get('admin.blogs'),
                    __('Blogs actions')                         => '',
                ]
            )
        );
        $this->endPage();
    }

    /**
     * Returns HTML code for selected blogs as a table containing blogs checkboxes
     *
     * @return string the HTML code for checkboxes
     */
    public function getCheckboxes(): string
    {
        $ret = '';
        foreach ($this->entries as $id => $res) {
            $ret .= '<tr>' .
            '<td class="minimal">' . form::checkbox(
                [$this->field_entries . '[]'],
                $id,
                [
                    'checked' => true,
                ]
            ) .
                '</td>' .
                '<td>' . $res['blog'] . '</td>' .
                '<td>' . $res['name'] . '</td>' .
                '</tr>';
        }

        return
        '<table class="blogs-list"><tr>' .
        '<th colspan="2">' . __('Blog id') . '</th><th>' . __('Blog name') . '</th>' .
            '</tr>' . $ret . '</table>';
    }

    /**
     * Fetches entries.
     *
     * @param      ArrayObject  $from   The parameters ($_POST)
     */
    protected function fetchEntries(ArrayObject $from)
    {
        $params = [];
        if (!empty($from['blogs'])) {
            $params['blog_id'] = $from['blogs'];
        }

        $rs = dcCore::app()->getBlogs($params);
        while ($rs->fetch()) {
            $this->entries[$rs->blog_id] = [
                'blog' => $rs->blog_id,
                'name' => $rs->blog_name,
            ];
        }
        $this->rs = $rs;
    }
}
