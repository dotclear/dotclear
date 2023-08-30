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
use Dotclear\App;
use Dotclear\Core\Backend\Page;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Html;
use Exception;

class ActionsPosts extends Actions
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
            'user_id', 'cat_id', 'status', 'selected', 'attachment', 'month', 'lang', 'sortby', 'order', 'page', 'nb',
        ];

        $this->loadDefaults();
    }

    /**
     * Set posts actions
     */
    protected function loadDefaults()
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
    public function beginPage(string $breadcrumb = '', string $head = '')
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
        App::error()->add($e->getMessage());
        $this->beginPage(
            Page::breadcrumb(
                [
                    Html::escapeHTML(App::blog()->name) => '',
                    $this->getCallerTitle()              => $this->getRedirection(true),
                    __('Posts actions')                  => '',
                ]
            )
        );
        $this->endPage();
    }

    /**
     * Fetches entries.
     *
     * @param      ArrayObject  $from   The parameters ($_POST)
     */
    protected function fetchEntries(ArrayObject $from)
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
            $this->entries[$rs->post_id] = $rs->post_title;
        }
        $this->rs = $rs;
    }
}
