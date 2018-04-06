<?php
/**
 * @brief Error class
 *
 * dcError is a very simple error class, with a stack. Call dcError::add to
 * add an error in stack. In administration area, errors are automatically
 * displayed.
 *
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

class dcError
{
    /** @var array Errors stack */
    protected $errors = array();
    /** @var boolean True if stack is not empty */
    protected $flag = false;
    /** @var string HTML errors list pattern */
    protected $html_list = "<ul>\n%s</ul>\n";
    /** @var string HTML error item pattern */
    protected $html_item = "<li>%s</li>\n";
    /** @var string HTML error single pattern */
    protected $html_single = "<p>%s</p>\n";

    /**
     * Object string representation. Returns errors stack.
     *
     * @return string
     */
    public function __toString()
    {
        $res = '';

        foreach ($this->errors as $msg) {
            $res .= $msg . "\n";
        }

        return $res;
    }

    /**
     * Adds an error to stack.
     *
     * @param string    $msg            Error message
     */
    public function add($msg)
    {
        $this->flag     = true;
        $this->errors[] = $msg;
    }

    /**
     * Returns the value of <var>flag</var> property. True if errors stack is not empty
     *
     * @return boolean
     */
    public function flag()
    {
        return $this->flag;
    }

    /**
     * Resets errors stack.
     */
    public function reset()
    {
        $this->flag   = false;
        $this->errors = array();
    }

    /**
     * Returns <var>errors</var> property.
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Sets <var>list</var> and <var>item</var> properties.
     *
     * @param string    $list        HTML errors list pattern
     * @param string    $item        HTML error item pattern
     */
    public function setHTMLFormat($list, $item, $single = null)
    {
        $this->html_list = $list;
        $this->html_item = $item;
        if ($single) {
            $this->html_single = $single;
        }
    }

    /**
     * Returns errors stack as HTML.
     *
     * @return string
     */
    public function toHTML()
    {
        $res = '';

        if ($this->flag) {
            if (count($this->errors) == 1) {
                $res = sprintf($this->html_single, $this->errors[0]);
            } else {
                foreach ($this->errors as $msg) {
                    $res .= sprintf($this->html_item, $msg);
                }
                $res = sprintf($this->html_list, $res);
            }
        }

        return $res;
    }
}
