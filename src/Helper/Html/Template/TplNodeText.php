<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\Html\Template;

/**
 * @class TplNodeText
 *
 * Text node, for any non-tpl content
 */
class TplNodeText extends TplNode
{
    /**
     * Constructs a new instance.
     *
     * @param      string  $content  Simple text node, only holds its content
     */
    public function __construct(
        protected string $content
    ) {
        parent::__construct();
    }

    /**
     * Compile node text
     *
     * @param  Template     $tpl    The current template engine instance
     */
    public function compile(Template $tpl): string
    {
        return $this->content;
    }

    /**
     * Gets the tag.
     *
     * @return     string  The tag.
     */
    public function getTag(): string
    {
        return 'TEXT';
    }
}
