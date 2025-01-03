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
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @brief   Handler for action page on selected posts.
 */
class ActionsPosts extends Actions
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
            'user_id', 'cat_id', 'status', 'selected', 'attachment', 'month', 'lang', 'sortby', 'order', 'page', 'nb',
        ];

        $this->loadDefaults();
    }

    /**
     * Set posts actions.
     */
    protected function loadDefaults(): void
    {
        // We could have added a behavior here, but we want default action to be setup first
        ActionsPostsDefault::adminPostsActionsPage($this);
        # --BEHAVIOR-- adminPostsActions -- Actions
        App::behavior()->callBehavior('adminPostsActions', $this);
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
                __('Posts'),
                Page::jsLoad('js/_posts_actions.js') .
                $head
            );
            echo $breadcrumb;
        } else {
            Page::open(
                __('Posts'),
                Page::jsLoad('js/_posts_actions.js') .
                $head,
                $breadcrumb
            );
        }
        echo (new Para())
            ->items([
                (new Link())
                    ->class('back')
                    ->href($this->getRedirection(true))
                    ->text(__('Back to entries list')),
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
                    $this->getCallerTitle()               => $this->getRedirection(true),
                    __('Posts actions')                   => '',
                ]
            )
        );
        $this->endPage();
    }

    /**
     * Fetches entries.
     *
     * @param      ArrayObject<int|string, mixed>  $from   The from
     */
    protected function fetchEntries(ArrayObject $from): void
    {
        $params = [];
        if (!empty($from['entries'])) {
            $entries = $from['entries'];

            foreach ($entries as $k => $v) {
                $entries[$k] = (int) $v;
            }

            $params['sql'] = 'AND P.post_id IN(' . implode(',', $entries) . ') ';
        } else {
            $params['sql'] = 'AND 1=0 ';
        }

        if (!isset($from['full_content']) || empty($from['full_content'])) {
            $params['no_content'] = true;
        }

        if (isset($from['post_type'])) {
            $params['post_type'] = $from['post_type'];
        }

        $rs = App::blog()->getPosts($params);
        while ($rs->fetch()) {
            $this->entries[$rs->post_id] = $rs->post_title; // @phpstan-ignore-line
        }
        $this->rs = $rs;
    }
}
