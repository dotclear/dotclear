<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Helper\Html\Template;

use ArrayObject;

/**
 * @class TplNodeBlockDefinition
 *
 * Block node, for all <tpl:Tag>...</tpl:Tag>
 */
class TplNodeBlockDefinition extends TplNodeBlock
{
    /**
     * Stack of blocks
     *
     * @var        array
     */
    protected static $stack = [];

    /**
     * Current block
     *
     * @var string|null
     */
    protected static $current_block = null;

    /**
     * Block name
     *
     * @var string
     */
    protected $name;

    /**
     * Renders the parent block of currently being displayed block
     *
     * @param  Template     $tpl    The current template engine instance
     *
     * @return string      The compiled parent block
     */
    public static function renderParent(Template $tpl)
    {
        return self::getStackBlock(self::$current_block, $tpl);
    }

    /**
     * resets blocks stack
     */
    public static function reset()
    {
        self::$stack         = [];
        self::$current_block = null;
    }

    /**
     * Retrieves block defined in call stack
     *
     * @param  string       $name   The block name
     * @param  Template     $tpl    The current template engine instance
     *
     * @return string       The block (empty string if unavailable)
     */
    public static function getStackBlock(string $name, Template $tpl)
    {
        $stack = &self::$stack[$name];
        $pos   = $stack['pos'];

        // First check if block position is correct
        if (isset($stack['blocks'][$pos])) {
            self::$current_block = $name;
            if (!is_string($stack['blocks'][$pos])) {
                // Not a string ==> need to compile the tree

                // Go deeper 1 level in stack, to enable calls to parent
                $stack['pos']++;
                $ret = '';
                // Compile each and every children
                foreach ($stack['blocks'][$pos] as $child) {
                    $ret .= $child->compile($tpl);
                }
                $stack['pos']--;
                $stack['blocks'][$pos] = $ret;
            } else {
                // Already compiled, nice ! Simply return string
                $ret = $stack['blocks'][$pos];
            }

            return $ret;
        }

        // Not found => return empty
        return '';
    }

    /**
     * Block definition specific constructor : keep block name in mind
     *
     * @param string    $tag  Current tag (might be "Block")
     * @param array     $attr Tag attributes (must contain "name" attribute)
     */
    public function __construct(string $tag, array $attr)
    {
        parent::__construct($tag, $attr);
        $this->name = '';
        if (isset($attr['name'])) {
            $this->name = $attr['name'];
        }
    }

    /**
     * Override tag closing processing. Here we enrich the block stack to
     * keep block history.
     */
    public function setClosing(): void
    {
        if (!isset(self::$stack[$this->name])) {
            self::$stack[$this->name] = [
                'pos'    => 0, // pos is the pointer to the current block being rendered
                'blocks' => [], ];
        }
        parent::setClosing();
        self::$stack[$this->name]['blocks'][] = $this->children;
        $this->children                       = new ArrayObject();
    }

    /**
     * Compile the block definition : grab latest block content being defined
     *
     * @param  Template     $tpl    The current template engine instance
     *
     * @return string       The compiled block
     */
    public function compile(Template $tpl): string
    {
        return $tpl->compileBlockNode(
            $this->tag,
            $this->attr,
            self::getStackBlock($this->name, $tpl)
        );
    }
}
