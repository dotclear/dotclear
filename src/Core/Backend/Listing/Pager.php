<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend\Listing;

use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Html\Pager as HelperPager;
use form;

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
     * @var     null|string     $form_hidden
     */
    protected $form_hidden;

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
     * @return  string  The link.
     */
    protected function getLink(string $li_class, string $href, string $img_src, string $img_src_nolink, string $img_alt, bool $enable_link): string
    {
        if ($enable_link) {
            $formatter = '<li class="%s btn"><a href="%s"><img src="%s" alt="%s"/></a><span class="hidden">%s</span></li>';

            return sprintf($formatter, $li_class, $href, $img_src, $img_alt, $img_alt);
        }
        $formatter = '<li class="%s no-link btn"><img src="%s" alt="%s"/></li>';

        return sprintf($formatter, $li_class, $img_src_nolink, $img_alt);
    }

    /**
     * Sets the url.
     */
    public function setURL(): void
    {
        parent::setURL();
        $url = parse_url($_SERVER['REQUEST_URI']);
        if (isset($url['query'])) {
            parse_str($url['query'], $args);
        } else {
            $args = [];
        }
        # Removing session information
        if (session_id()) {
            if (isset($args[session_name()])) {
                unset($args[session_name()]);
            }
        }
        if (isset($args[$this->var_page])) {
            unset($args[$this->var_page]);
        }
        if (isset($args['ok'])) {
            unset($args['ok']);
        }

        $this->form_hidden = '';
        foreach ($args as $k => $v) {
            // Check parameter key (will prevent some forms of XSS)
            if ($k === preg_replace('`[^A-Za-z0-9_-]`', '', $k)) {  // @phpstan-ignore-line
                if (is_array($v)) {
                    foreach ($v as $v2) {
                        $this->form_hidden .= form::hidden([$k . '[]'], Html::escapeHTML($v2));
                    }
                } else {
                    $this->form_hidden .= form::hidden([$k], Html::escapeHTML($v));
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
        $htmlFirst = $this->getLink(
            'first',
            sprintf($this->page_url, 1),
            'images/pagination/first.svg',
            'images/pagination/no-first.svg',
            __('First page'),
            ($this->env > 1)
        );
        $htmlPrev = $this->getLink(
            'prev',
            sprintf($this->page_url, $this->env - 1),
            'images/pagination/previous.svg',
            'images/pagination/no-previous.svg',
            __('Previous page'),
            ($this->env > 1)
        );
        $htmlNext = $this->getLink(
            'next',
            sprintf($this->page_url, $this->env + 1),
            'images/pagination/next.svg',
            'images/pagination/no-next.svg',
            __('Next page'),
            ($this->env < $this->nb_pages)
        );
        $htmlLast = $this->getLink(
            'last',
            sprintf($this->page_url, $this->nb_pages),
            'images/pagination/last.svg',
            'images/pagination/no-last.svg',
            __('Last page'),
            ($this->env < $this->nb_pages)
        );
        $htmlCurrent = '<li class="active"><strong>' .
        sprintf(__('Page %s / %s'), $this->env, $this->nb_pages) .
            '</strong></li>';

        $htmlDirect = ($this->nb_pages > 1 ?
            sprintf(
                '<li class="direct-access">' . __('Direct access page %s'),
                form::number([$this->var_page], 1, $this->nb_pages, (string) $this->env)
            ) .
            '<input type="submit" value="' . __('ok') . '" class="reset" ' .
            'name="ok" />' . $this->form_hidden . '</li>' : '');

        $res = '<form action="' . $this->form_action . '" method="get">' .
            '<div class="pager"><ul>' .
            $htmlFirst .
            $htmlPrev .
            $htmlCurrent .
            $htmlNext .
            $htmlLast .
            $htmlDirect .
            '</ul>' .
            '</div>' .
            '</form>'
        ;

        return $this->nb_elements > 0 ? $res : '';
    }
}
