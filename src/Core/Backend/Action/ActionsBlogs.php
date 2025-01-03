<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend\Action;

use ArrayObject;
use Dotclear\App;
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

/**
 * @brief   Handler for action page on selected blogs.
 */
class ActionsBlogs extends Actions
{
    /**
     * Constructs a new instance.
     *
     * @param   null|string             $uri            The form uri
     * @param   array<string, mixed>    $redir_args     The redirection $_GET arguments,
     *                                                  if any (does not contain ids by default, ids may be merged to it)
     */
    public function __construct(?string $uri, array $redir_args = [])
    {
        parent::__construct($uri, $redir_args);

        $this->redirect_fields = [
            'status', 'sortby', 'order', 'page', 'nb',
        ];
        $this->field_entries = 'blogs';
        $this->cb_title      = __('Blogs');

        $this->loadDefaults();
        # --BEHAVIOR-- adminBlogsActions -- Actions
        App::behavior()->callBehavior('adminBlogsActions', $this);
    }

    /**
     * Loads defaults.
     */
    protected function loadDefaults(): void
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
    public function beginPage(string $breadcrumb = '', string $head = ''): void
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
    public function endPage(): void
    {
        if ($this->in_plugin) {
            Page::closeModule();
        } else {
            Page::close();
        }
    }

    /**
     * Cope with error
     */
    public function error(Exception $e): void
    {
        App::error()->add($e->getMessage());
        $this->beginPage(
            Page::breadcrumb(
                [
                    Html::escapeHTML(App::blog()->name()) => '',
                    __('Blogs')                           => App::backend()->url()->get('admin.blogs'),
                    __('Blogs actions')                   => '',
                ]
            )
        );
        $this->endPage();
    }

    /**
     * Returns Table elments code for selected blogs as a table containing blogs checkboxes.
     *
     * @return  Table   The Table elements code for checkboxes
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
                ...$items,
            ]);
    }

    /**
     * Fetches entries.
     *
     * @param      ArrayObject<int|string, mixed>  $from   The from
     */
    protected function fetchEntries(ArrayObject $from): void
    {
        $params = [];
        if (!empty($from['blogs'])) {
            $params['blog_id'] = $from['blogs'];
        }

        $rs = App::blogs()->getBlogs($params);
        while ($rs->fetch()) {
            $this->entries[$rs->blog_id] = [    // @phpstan-ignore-line
                'blog' => $rs->blog_id,
                'name' => $rs->blog_name,
            ];
        }
        $this->rs = $rs;
    }
}
