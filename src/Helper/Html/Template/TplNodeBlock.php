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
 * @class TplNodeBlock
 *
 * Block node, for all <tpl:Tag>...</tpl:Tag>
 */
class TplNodeBlock extends TplNode
{
    /**
     * Closed node block flag
     *
     * @var bool
     */
    protected $closed = false;

    /**
     * Node block content
     *
     * @var string
     */
    protected $content = '';

    /**
     * Constructs a new instance.
     *
     * @param      string                   $tag    The tag
     * @param      array<string, mixed>     $attr   The attribute
     */
    public function __construct(
        protected string $tag,
        protected array $attr
    ) {
        parent::__construct();
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
