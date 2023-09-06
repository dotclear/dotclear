<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
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
     * Simple text node, only holds its content
     *
     * @var string
     */
    protected $content;

    public function __construct(string $text)
    {
        parent::__construct();
        $this->content = $text;
    }

    /**
     * Compile node text
     *
     * @param  Template     $tpl    The current template engine instance
     *
     * @return     string
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
