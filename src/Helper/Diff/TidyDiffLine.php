<?php
/**
 * @class TidyDiffLine
 * @brief TIDY diff line
 *
 * A diff line representation. Used by a TIDY chunk.
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Helper\Diff;

class TidyDiffLine
{
    /**
     * Line type
     *
     * @var string
     */
    protected $type;

    /**
     * Line number for old and new context
     *
     * @var array
     */
    protected $lines;

    /**
     * Line content
     *
     * @var string
     */
    protected $content;

    /**
     * Constructor
     *
     * Creates a line representation for a tidy chunk.
     *
     * @param string    $type        Tine type
     * @param array     $lines       Line number for old and new context
     * @param string    $content     Line content
     */
    public function __construct(string $type, ?array $lines, ?string $content)
    {
        $allowed_type = ['context', 'delete', 'insert'];

        if (in_array($type, $allowed_type) && is_array($lines) && is_string($content)) {
            $this->type    = $type;
            $this->lines   = $lines;
            $this->content = $content;
        }
    }

    /**
     * Magic get
     *
     * Returns field content according to the given name, null otherwise.
     *
     * @param string    $n            Field name
     *
     * @return mixed
     */
    public function __get(string $n)
    {
        return $this->{$n} ?? null;
    }

    /**
     * Overwrite
     *
     * Overwrites content for the current line.
     *
     * @param string    $content        Line content
     */
    public function overwrite(?string $content): void
    {
        if (is_string($content)) {
            $this->content = $content;
        }
    }
}
