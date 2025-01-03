<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\pages;

use Dotclear\App;
use Dotclear\Core\Backend\Action\ActionsPosts;
use Dotclear\Core\Backend\Page;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @brief   The module backend pages actions.
 * @ingroup pages
 */
class BackendActions extends ActionsPosts
{
    /**
     * Use render method.
     */
    protected bool $use_render = true;

    /**
     * Constructs a new instance.
     *
     * @param      null|string              $uri            The uri
     * @param      array<string, mixed>     $redirect_args  The redirect arguments
     */
    public function __construct(?string $uri, array $redirect_args = [])
    {
        parent::__construct($uri, $redirect_args);

        $this->redirect_fields = [];
        $this->caller_title    = __('Pages');
    }

    public function error(Exception $e): void
    {
        App::error()->add($e->getMessage());
        $this->beginPage(
            Page::breadcrumb(
                [
                    Html::escapeHTML(App::blog()->name()) => '',
                    __('Pages')                           => $this->getRedirection(true),
                    __('Pages actions')                   => '',
                ]
            )
        );
        $this->endPage();
    }

    public function beginPage(string $breadcrumb = '', string $head = ''): void
    {
        Page::openModule(
            __('Pages'),
            Page::jsLoad('js/_posts_actions.js') .
            $head
        );
        echo
        $breadcrumb;

        echo (new Para())
            ->items([
                (new Link())
                    ->class('back')
                    ->href($this->getRedirection(true))
                    ->text(__('Back to pages list')),
            ])
        ->render();
    }

    public function endPage(): void
    {
        Page::closeModule();
    }

    /**
     * Set pages actions.
     */
    public function loadDefaults(): void
    {
        // We could have added a behavior here, but we want default action to be setup first
        BackendDefaultActions::adminPagesActionsPage($this);
        # --BEHAVIOR-- adminPagesActions -- Actions
        App::behavior()->callBehavior('adminPagesActions', $this);
    }

    public function process()
    {
        // fake action for pages reordering
        if (!empty($this->from['reorder'])) {
            $this->from['action'] = 'reorder';
        }
        $this->from['post_type'] = 'page';

        return parent::process();
    }
}
