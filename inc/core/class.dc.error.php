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

use Dotclear\Core\Backend\Notices;

class dcError
{
    /**
     * Errors stack
     *
     * @var        array
     */
    protected $errors = [];

    /**
     * True if stack is not empty
     *
     * @var        bool
     */
    protected $flag = false;

    /**
     * Adds an error to stack.
     *
     * @param string    $msg            Error message
     */
    public function add(string $msg): void
    {
        $this->flag     = true;
        $this->errors[] = $msg;
    }

    /**
     * Returns the value of <var>flag</var> property. True if errors stack is not empty
     *
     * @return bool
     */
    public function flag(): bool
    {
        return $this->flag;
    }

    /**
     * Resets errors stack.
     */
    private function reset()
    {
        $this->flag   = false;
        $this->errors = [];
    }

    /**
     * Return number of stacked errors
     *
     * @return     int
     */
    public function count(): int
    {
        return count($this->errors);
    }

    /**
     * Returns errors stack as HTML and reset it.
     *
     * @param   bool    $reset  True if error stack should be reset
     *
     * @return string
     */
    public function toHTML(bool $reset = true): string
    {
        $res = '';

        if ($this->flag) {
            foreach ($this->errors as $msg) {
                $res .= Notices::error($msg, true, false, false);
            }
            if ($reset) {
                $this->reset();
            }
        }

        return $res;
    }
}
