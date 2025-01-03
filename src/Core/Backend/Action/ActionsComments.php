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
 * @brief   Handler for action page on selected comments.
 */
class ActionsComments extends Actions
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
            'type', 'author', 'status', 'sortby', 'ip', 'order', 'page', 'nb', 'section',
        ];
        $this->field_entries = 'comments';
        $this->cb_title      = __('Comments');

        $this->loadDefaults();
        # --BEHAVIOR-- adminCommentsActions -- Actions
        App::behavior()->callBehavior('adminCommentsActions', $this);
    }

    /**
     * Loads comments actions.
     */
    protected function loadDefaults(): void
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
    public function beginPage(string $breadcrumb = '', string $head = ''): void
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
        echo (new Para())
            ->items([
                (new Link())
                    ->class('back')
                    ->href($this->getRedirection(true))
                    ->text(__('Back to comments list')),
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
                    __('Comments')                        => App::backend()->url()->get('admin.comments'),
                    __('Comments actions')                => '',
                ]
            )
        );
        $this->endPage();
    }

    /**
     * Returns Table elments code for selected comments as a table containing comments checkboxes
     *
     * @return  Table   The Table elements code for checkboxes
     */
    public function checkboxes(): Table
    {
        $items = [];
        foreach ($this->entries as $id => $description) {
            $items[] = (new Tr())
                ->items([
                    (new Td())
                        ->class('minimal')
                        ->items([
                            (new Checkbox([$this->field_entries . '[]'], true))
                                ->value($id),
                        ]),
                    (new Td())
                        ->text(Html::escapeHTML($description['author'])),
                    (new Td())
                        ->text(Html::escapeHTML($description['title'])),
                ]);
        }

        return (new Table())
            ->class('posts-list')
            ->items([
                (new Tr())
                    ->items([
                        (new Th())
                            ->colspan(2)
                            ->text(__('Author')),
                        (new Th())
                            ->text(__('Title')),
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
        $rs = App::blog()->getComments($params);
        while ($rs->fetch()) {
            $this->entries[$rs->comment_id] = [ // @phpstan-ignore-line
                'title'  => $rs->post_title,
                'author' => $rs->comment_author,
            ];
        }
        $this->rs = $rs;
    }
}
