<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\Html\Template;

use ArrayObject;

/**
 * @class TplNode
 *
 * Template nodes, for parsing purposes
 * Generic list node, this one may only be instanciated once for root element
 */
class TplNode
{
    /**
     * Basic tree structure : links to parent, children forrest
     *
     * @var null|TplNode|TplNodeBlock|TplNodeBlockDefinition|TplNodeText|TplNodeValue|TplNodeValueParent
     */
    protected $parentNode;

    /**
     * Node children
     *
     * @var ArrayObject<int, TplNode|TplNodeBlock|TplNodeBlockDefinition|TplNodeText|TplNodeValue|TplNodeValueParent>
     */
    protected ArrayObject $children;

    /**
     * Constructs a new instance.
     */
    public function __construct()
    {
        $this->children = new ArrayObject();
    }

    /**
     * Indicates that the node is closed.
     */
    public function setClosing(): void
    {
        // Nothing to do at this level
    }

    /**
     * Returns compiled block
     *
     * @param  Template     $tpl    The current template engine instance
     */
    public function compile(Template $tpl): string
    {
        $res = '';
        foreach ($this->children as $child) {
            $res .= $child->compile($tpl);
        }

        return $res;
    }

    /**
     * Add a children to current node.
     *
     * @param      TplNode|TplNodeBlock|TplNodeBlockDefinition|TplNodeText|TplNodeValue|TplNodeValueParent  $child  The child
     */
    public function addChild($child): void
    {
        $this->children->append($child);
        $child->setParent($this);
    }

    /**
     * Set current node children.
     *
     * @param      ArrayObject<int, TplNode|TplNodeBlock|TplNodeBlockDefinition|TplNodeText|TplNodeValue|TplNodeValueParent>  $children  The children
     */
    public function setChildren(ArrayObject $children): void
    {
        $this->children = $children;
        foreach ($this->children as $child) {
            $child->setParent($this);
        }
    }

    #

    /**
     * Defines parent for current node.
     *
     * @param      null|TplNode|TplNodeBlock|TplNodeBlockDefinition|TplNodeValue|TplNodeValueParent  $parent  The parent
     */
    protected function setParent($parent): void
    {
        $this->parentNode = $parent;
    }

    /**
     * Retrieves current node parent.
     *
     * If parent is root node, null is returned
     *
     * @return     null|TplNode|TplNodeBlock|TplNodeBlockDefinition|TplNodeValue|TplNodeValueParent  The parent.
     */
    public function getParent()
    {
        return $this->parentNode;
    }

    /**
     * Gets the tag.
     *
     * @return     string  The tag.
     */
    public function getTag(): string
    {
        return 'ROOT';
    }
}
