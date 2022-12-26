<?php
/**
 * @class tplNodeBlock
 * @brief Block node, for all <tpl:Tag>...</tpl:Tag>
 *
 * @package Clearbricks
 * @subpackage Template
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class tplNodeBlock extends tplNode
{
    /**
     * Node block tag name
     *
     * @var string
     */
    protected $tag;

    /**
     * Node block tag attributes
     *
     * @var array
     */
    protected $attr;

    /**
     * Closed node block flag
     *
     * @var bool
     */
    protected $closed;

    /**
     * Node block content
     *
     * @var string
     */
    protected $content;

    /**
     * Constructs a new instance.
     *
     * @param      string  $tag    The tag
     * @param      array   $attr   The attribute
     */
    public function __construct(string $tag, array $attr)
    {
        parent::__construct();

        $this->content = '';
        $this->tag     = $tag;
        $this->attr    = $attr;
        $this->closed  = false;
    }

    /**
     * Indicates that the node block is closed.
     */
    public function setClosing(): void
    {
        $this->closed = true;
    }

    /**
     * Determines if node block is closed.
     *
     * @return     bool  True if closed, False otherwise.
     */
    public function isClosed(): bool
    {
        return $this->closed;
    }

    /**
     * Compile the node block
     *
     * @param  template     $tpl    The current template engine instance
     *
     * @return     string
     */
    public function compile(template $tpl): string
    {
        if ($this->closed) {
            $content = parent::compile($tpl);

            return $tpl->compileBlockNode($this->tag, $this->attr, $content);
        }
        // if tag has not been closed, silently ignore its content...
        return '';
    }

    /**
     * Gets the tag.
     *
     * @return     string  The tag.
     */
    public function getTag(): string
    {
        return $this->tag;
    }
}
