<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\blogroll\Action;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Action\Actions;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Html;
use Dotclear\Plugin\blogroll\Blogroll;
use Exception;

/**
 * @brief   Handler for action page on selected links.
 */
class ActionsLinks extends Actions
{
    /**
     * Constructs a new instance.
     *
     * @param   null|string             $uri            The form uri
     * @param   array<string, string>   $redir_args     The redirection $_GET arguments,
     *                                                  if any (does not contain ids by default, ids may be merged to it)
     */
    public function __construct(?string $uri, array $redir_args = [])
    {
        parent::__construct($uri, $redir_args);

        // No redirect fields as there is no filter implemented on links list
        $this->redirect_fields = [
        ];

        $this->loadDefaults();
    }

    /**
     * Set posts actions.
     */
    protected function loadDefaults(): void
    {
        // We could have added a behavior here, but we want default action to be setup first
        ActionsLinksDefault::adminLinksActionsPage($this);
        # --BEHAVIOR-- adminPostsActions -- Actions
        App::behavior()->callBehavior('adminLinksActions', $this);
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
            App::backend()->page()->openModule(
                __('Links'),
                $head
            );
            echo $breadcrumb;
        } else {
            App::backend()->page()->open(
                __('Links'),
                $head,
                $breadcrumb
            );
        }
        echo (new Para())
            ->items([
                (new Link())
                    ->class('back')
                    ->href($this->getRedirection(true))
                    ->text(__('Back to links list')),
            ])
            ->render();
    }

    /**
     * Ends a page.
     */
    public function endPage(): void
    {
        if ($this->in_plugin) {
            App::backend()->page()->closeModule();
        } else {
            App::backend()->page()->close();
        }
    }

    /**
     * Cope with error
     */
    public function error(Exception $e): void
    {
        App::error()->add($e->getMessage());
        $this->beginPage(
            App::backend()->page()->breadcrumb(
                [
                    Html::escapeHTML(App::blog()->name()) => '',
                    $this->getCallerTitle()               => $this->getRedirection(true),
                    __('Links actions')                   => '',
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
        if (!empty($from['entries']) && is_array($from['entries'])) {
            /**
             * @var array<array-key, int>
             */
            $ids     = [];
            $entries = $from['entries'];
            foreach ($entries as $v) {
                if (is_numeric($v)) {
                    $ids[] = (int) $v;
                }
            }

            $params['sql'] = 'AND link_id IN(' . implode(',', $ids) . ') ';
        } else {
            $params['sql'] = 'AND 1=0 ';
        }

        $rs = (new Blogroll(App::blog()))->getLinks($params);
        while ($rs->fetch()) {
            if (is_string($rs->link_id) || is_numeric($rs->link_id)) {
                $this->entries[(string) $rs->link_id] = $rs->link_title;
            }
        }
        $this->rs = $rs;
    }
}
