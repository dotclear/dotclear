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
 * @class TplNodeBlock
 *
 * Block node, for all <tpl:Tag>...</tpl:Tag>
 */
class TplNodeBlock extends TplNode
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
     * @var array<string, mixed>
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
     * @param      string                   $tag    The tag
     * @param      array<string, mixed>     $attr   The attribute
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
     * @param  Template     $tpl    The current template engine instance
     *
     * @return     string
     */
    public function compile(Template $tpl): string
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
