<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend\Listing;

use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Number;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Ul;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Html\Pager as HelperPager;

/**
 * @brief   List pager form helper.
 *
 * @since   2.20
 */
class Pager extends HelperPager
{
    /**
     * Form-handler.
     *
     * @var     null|string     $form_action
     */
    protected $form_action;

    /**
     * Form hidden fields.
     *
     * @var     array<Hidden>     $form_hidden
     */
    protected $form_hidden = [];

    /**
     * Gets the link.
     *
     * @param   string  $li_class           The li class
     * @param   string  $href               The href
     * @param   string  $img_src            The image source
     * @param   string  $img_src_nolink     The image source nolink
     * @param   string  $img_alt            The image alternate
     * @param   bool    $enable_link        The enable link
     *
     * @return  Li  The link.
     */
    protected function getLink(string $li_class, string $href, string $img_src, string $img_src_nolink, string $img_alt, bool $enable_link): Li
    {
        if ($enable_link) {
            return (new Li())
                ->class(array_filter(['btn', $li_class]))
                ->items([
                    (new Link())
                        ->href($href)
                        ->items([
                            (new Img($img_src))
                                ->alt($img_alt),
                        ]),
                    (new Text('span', $img_alt))
                        ->class('hidden'),
                ]);
        }

        return (new Li())
            ->class(array_filter(['btn', 'no-link', $li_class]))
            ->items([
                (new Img($img_src_nolink))
                    ->alt($img_alt),
            ]);
    }

    /**
     * Sets the url.
     */
    public function setURL(): void
    {
        parent::setURL();
        $url = parse_url((string) $_SERVER['REQUEST_URI']);
        if (isset($url['query'])) {
            parse_str($url['query'], $args);
        } else {
            $args = [];
        }
        # Removing session information
        if (session_id() && isset($args[session_name()])) {
            unset($args[session_name()]);
        }
        if (isset($args[$this->var_page])) {
            unset($args[$this->var_page]);
        }
        if (isset($args['ok'])) {
            unset($args['ok']);
        }

        //$this->form_hidden = '';
        foreach ($args as $k => $v) {
            // Check parameter key (will prevent some forms of XSS)
            if ($k === preg_replace('`[^A-Za-z0-9_-]`', '', (string) $k)) {
                if (is_array($v)) {
                    foreach ($v as $v2) {
                        $this->form_hidden[] = (new Hidden([$k . '[]'], Html::escapeHTML($v2)));
                    }
                } else {
                    $this->form_hidden[] = (new Hidden([$k], Html::escapeHTML($v)));
                }
            }
        }
        $this->form_action = $url['path'] ?? '';
    }

    /**
     * Pager Links.
     *
     * @return  string  The pager links
     */
    public function getLinks(): string
    {
        $this->setURL();

        if ($this->nb_elements === 0) {
            return '';
        }

        $htmlFirst = $this->getLink(
            'first',
            sprintf((string) $this->page_url, 1),
            'images/pagination/first.svg',
            'images/pagination/no-first.svg',
            __('First page'),
            ($this->env > 1)
        );
        $htmlPrev = $this->getLink(
            'prev',
            sprintf((string) $this->page_url, $this->env - 1),
            'images/pagination/previous.svg',
            'images/pagination/no-previous.svg',
            __('Previous page'),
            ($this->env > 1)
        );
        $htmlNext = $this->getLink(
            'next',
            sprintf((string) $this->page_url, $this->env + 1),
            'images/pagination/next.svg',
            'images/pagination/no-next.svg',
            __('Next page'),
            ($this->env < $this->nb_pages)
        );
        $htmlLast = $this->getLink(
            'last',
            sprintf((string) $this->page_url, $this->nb_pages),
            'images/pagination/last.svg',
            'images/pagination/no-last.svg',
            __('Last page'),
            ($this->env < $this->nb_pages)
        );

        $htmlCurrent = (new Li())
            ->class('active')
            ->items([
                (new Text('strong', sprintf(__('Page %s / %s'), $this->env, $this->nb_pages))),
            ]);

        $htmlDirect = $this->nb_pages > 1 ?
        (new Li())
            ->class('direct-access')
            ->items([
                (new Number([$this->var_page], 1, $this->nb_pages, $this->env))
                    ->label((new Label(__('Direct access page:')))->class('classic')),
                (new Submit(['ok'], __('ok')))
                    ->class('reset'),
            ]) :
        (new None());

        return (new Form(['pager']))
            ->method('get')
            ->action($this->form_action)
            ->fields([
                (new Div())
                    ->class('pager')
                    ->items([
                        (new Ul())
                            ->items([
                                $htmlFirst,
                                $htmlPrev,
                                $htmlCurrent,
                                $htmlNext,
                                $htmlLast,
                                $htmlDirect,
                            ]),
                    ]),
                ...$this->form_hidden,
            ])
        ->render();
    }
}
