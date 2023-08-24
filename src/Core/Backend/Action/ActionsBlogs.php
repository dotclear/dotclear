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
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Th;
use Dotclear\Helper\Html\Form\Tr;
use Dotclear\Helper\Html\Html;
use Exception;

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
        echo (new Para())
            ->items([
                (new Link())
                    ->class('back')
                    ->href($this->getRedirection(true))
                    ->text(__('Back to blogs list')),
            ])
            ->render();
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
     * Returns Table elments code for selected blogs as a table containing blogs checkboxes
     *
     * @return Table The Table elements code for checkboxes
     */
    public function checkboxes(): Table
    {
        $items = [];
        foreach ($this->entries as $id => $res) {
            $items[] = (new Tr())
                ->items([
                    (new Td())
                        ->class('minimal')
                        ->items([
                            (new Checkbox([$this->field_entries . '[]'], true))
                                ->value($id),
                        ]),
                    (new Td())
                        ->text(Html::escapeHTML($res['blog'])),
                    (new Td())
                        ->text(Html::escapeHTML($res['name'])),
                ]);
        }

        return (new Table())
            ->class('blogs-list')
            ->items([
                (new Tr())
                    ->items([
                        (new Th())
                            ->colspan(2)
                            ->text(__('Blog id')),
                        (new Th())
                            ->text(__('Blog name')),
                    ]),
                ... $items,
            ]);
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
