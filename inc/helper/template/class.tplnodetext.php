<?php
/**
 * @class tplNodeText
 * @brief Text node, for any non-tpl content
 *
 * @package Clearbricks
 * @subpackage Template
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class tplNodeText extends tplNode
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
     * @param  template     $tpl    The current template engine instance
     *
     * @return     string
     */
    public function compile(template $tpl): string
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
