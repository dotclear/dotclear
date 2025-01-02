<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\Diff;

/**
 * @class TidyDiff
 * @brief TIDY diff
 *
 * A TIDY diff representation
 */
class TidyDiff
{
    // Constants

    private const UP_RANGE = '/^@@ -([\d]+),([\d]+) \+([\d]+),([\d]+) @@/m';
    private const UP_CTX   = '/^ (.*)$/';
    private const UP_INS   = '/^\+(.*)$/';
    private const UP_DEL   = '/^-(.*)$/';

    /**
     * Chunks array
     *
     * @var        array<TidyDiffChunk>
     */
    protected $__data = [];

    /**
     * Constructor
     *
     * Creates a diff representation from unified diff.
     *
     * @param string    $udiff            Unified diff
     * @param bool      $inline_changes   Find inline changes
     */
    public function __construct(string $udiff, bool $inline_changes = false)
    {
        Diff::uniCheck($udiff);

        preg_match_all(self::UP_RANGE, $udiff, $context);

        $chunks = preg_split(self::UP_RANGE, $udiff, -1, PREG_SPLIT_NO_EMPTY);

        if ($chunks) {
            foreach ($chunks as $k => $chunk) {
                $tidy_chunk = new TidyDiffChunk();
                $tidy_chunk->setRange(
                    (int) $context[1][$k],
                    (int) $context[2][$k],
                    (int) $context[3][$k],
                    (int) $context[4][$k]
                );

                $old_line = (int) $context[1][$k];
                $new_line = (int) $context[3][$k];

                foreach (explode("\n", $chunk) as $line) {
                    # context
                    if (preg_match(self::UP_CTX, $line, $m)) {
                        $tidy_chunk->addLine('context', [$old_line, $new_line], $m[1]);
                        $old_line++;
                        $new_line++;
                    }
                    # insertion
                    if (preg_match(self::UP_INS, $line, $m)) {
                        $tidy_chunk->addLine('insert', [$old_line, $new_line], $m[1]);
                        $new_line++;
                    }
                    # deletion
                    if (preg_match(self::UP_DEL, $line, $m)) {
                        $tidy_chunk->addLine('delete', [$old_line, $new_line], $m[1]);
                        $old_line++;
                    }
                }

                if ($inline_changes) {
                    $tidy_chunk->findInsideChanges();
                }

                $this->__data[] = $tidy_chunk;
            }
        }
    }

    /**
     * All chunks
     *
     * Returns all chunks defined.
     *
     * @return array<TidyDiffChunk>
     */
    public function getChunks(): array
    {
        return $this->__data;
    }
}
