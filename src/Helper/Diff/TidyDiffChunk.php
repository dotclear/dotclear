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
 * @class TidyDiffChunk
 * @brief TIDY diff chunk
 *
 * A diff chunk representation. Used by a TIDY diff.
 */
class TidyDiffChunk
{
    /**
     * Chunk information array
     *
     * @var array<string, mixed>
     */
    protected $__info = [
        'context' => 0,
        'delete'  => 0,
        'insert'  => 0,
        'range'   => [
            'start' => [],
            'end'   => [],
        ],
    ];

    /**
     * Chunk data array
     *
     * @var array<TidyDiffLine>
     */
    protected $__data = [];

    /**
     * Set chunk range
     *
     * Sets chunk range in TIDY chunk object.
     *
     * @param int    $line_start        Old start line number
     * @param int    $offest_start        Old offset number
     * @param int    $line_end            new start line number
     * @param int    $offset_end        New offset number
     */
    public function setRange(int $line_start, int $offest_start, int $line_end, int $offset_end): void
    {
        $this->__info['range']['start'] = [$line_start, $offest_start];
        $this->__info['range']['end']   = [$line_end, $offset_end];
    }

    /**
     * Add line
     *
     * Adds TIDY line object for TIDY chunk object.
     *
     * @param string        $type        Tine type
     * @param array<int>    $lines       Line number for old and new context
     * @param string        $content     Line content
     */
    public function addLine(string $type, array $lines, string $content): void
    {
        $tidy_line = new TidyDiffLine($type, $lines, $content);

        $this->__data[] = $tidy_line;
        $this->__info[$type]++;
    }

    /**
     * All lines
     *
     * Returns all lines defined.
     *
     * @return array<TidyDiffLine>
     */
    public function getLines(): array
    {
        return $this->__data;
    }

    /**
     * Chunk information
     *
     * Returns chunk information according to the given name, null otherwise.
     *
     * @param string    $n            Info name
     *
     * @return mixed
     */
    public function getInfo($n)
    {
        return $this->__info[$n] ?? null;
    }

    /**
     * Find changes
     *
     * Finds changes inside lines for each groups of diff lines. Wraps changes
     * by string \0 and \1
     */
    public function findInsideChanges(): void
    {
        $groups = $this->getGroups();

        foreach ($groups as $group) {
            $middle = count($group) / 2;
            for ($i = 0; $i < $middle; $i++) {
                $from      = $group[$i];
                $to        = $group[$i + $middle];
                $threshold = $this->getChangeExtent($from->content, $to->content);

                if ($threshold['start'] != 0 || $threshold['end'] != 0) {
                    $start  = $threshold['start'];
                    $end    = $threshold['end'] + strlen($from->content);
                    $offset = $end - $start;
                    $from->overwrite(
                        substr($from->content, 0, $start) . '\0' .
                        substr($from->content, $start, $offset) . '\1' .
                        substr($from->content, $end, strlen($from->content))
                    );
                    $end    = $threshold['end'] + strlen($to->content);
                    $offset = $end - $start;
                    $to->overwrite(
                        substr($to->content, 0, $start) . '\0' .
                        substr($to->content, $start, $offset) . '\1' .
                        substr($to->content, $end, strlen($to->content))
                    );
                }
            }
        }
    }

    /**
     * Gets the groups.
     *
     * @return     array<array<TidyDiffLine>>  The groups.
     */
    private function getGroups(): array
    {
        /**
         * @var        array<array<TidyDiffLine>>
         */
        $res = [];

        /**
         * @var        array<TidyDiffLine>
         */
        $group = [];

        /**
         * @var        array<string>
         */
        $allowed_types = ['delete', 'insert'];

        /**
         * @var        int
         */
        $delete = 0;

        /**
         * @var        int
         */
        $insert = 0;

        foreach ($this->__data as $line) {
            if (in_array($line->type, $allowed_types)) {
                $group[] = $line;
                ${$line->type}++;
            } else {
                if ($delete === $insert && count($group) > 0) {
                    $res[] = $group;
                }
                $delete = $insert = 0;
                $group  = [];
            }
        }
        if ($delete === $insert && count($group) > 0) {
            $res[] = $group;
        }

        return $res;
    }

    /**
     * Gets the change extent.
     *
     * @param      string  $str1   The string 1
     * @param      string  $str2   The string 2
     *
     * @return     array<string, int>   The change extent.
     */
    private function getChangeExtent(string $str1, string $str2): array
    {
        $start = 0;
        $limit = min(strlen($str1), strlen($str2));
        while ($start < $limit && $str1[$start] === $str2[$start]) {
            $start++;
        }

        $end = -1;
        $limit -= $start;

        while (-$end <= $limit && $str1[strlen($str1) + $end] === $str2[strlen($str2) + $end]) {
            $end--;
        }

        return ['start' => $start, 'end' => $end + 1];
    }
}
