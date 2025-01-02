<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\Diff;

use Exception;

/**
 * @class Diff
 * @brief Unified diff
 *
 * Diff utilities
 */
class Diff
{
    // Constants

    private const US_RANGE = "@@ -%s,%s +%s,%s @@\n";
    private const US_CTX   = " %s\n";
    private const US_INS   = "+%s\n";
    private const US_DEL   = "-%s\n";

    private const UP_RANGE = '/^@@ -([\d]+),([\d]+) \+([\d]+),([\d]+) @@/';
    private const UP_CTX   = '/^ (.*)$/';
    private const UP_INS   = '/^\+(.*)$/';
    private const UP_DEL   = '/^-(.*)$/';

    /**
     * Finds the shortest edit script using a fast algorithm taken from paper
     * "An O(ND) Difference Algorithm and Its Variations" by Eugene W.Myers,
     * 1986.
     *
     * @param array<mixed>        $src            Original data
     * @param array<mixed>        $dst            New data
     *
     * @return array<array<mixed>>
     */
    public static function SES(array $src, array $dst): array
    {
        $x = $y = $k = 0;

        $cx = count($src);
        $cy = count($dst);

        $stack       = [];
        $V           = [1 => 0];
        $end_reached = false;

        # Find LCS length
        for ($D = 0; $D < $cx + $cy + 1 && !$end_reached; $D++) {
            for ($k = -$D; $k <= $D; $k += 2) {
                $x = ($k == -$D || $k != $D && $V[$k - 1] < $V[$k + 1])
                ? $V[$k + 1] : $V[$k - 1] + 1;
                $y = $x - $k;

                while ($x < $cx && $y < $cy && $src[$x] == $dst[$y]) {
                    $x++;
                    $y++;
                }

                $V[$k] = $x;

                if ($x == $cx && $y == $cy) {
                    $end_reached = true;

                    break;
                }
            }
            $stack[] = $V;
        }
        $D--;

        # Recover edit path
        $res = [];
        for (; $D > 0; $D--) {
            $V  = array_pop($stack);
            $cx = $x;
            $cy = $y;

            # Try right diagonal
            $k++;
            $x = array_key_exists($k, $V) ? $V[$k] : 0; // @phpstan-ignore-line
            $y = $x - $k;
            $y++;

            while ($x < $cx && $y < $cy
                            && isset($src[$x]) && isset($dst[$y]) && $src[$x] == $dst[$y]) {
                $x++;
                $y++;
            }

            if ($x == $cx && $y === $cy) {
                $x = $V[$k];    // @phpstan-ignore-line
                $y = $x - $k;

                $res[] = ['i', $x, $y];

                continue;
            }

            # Right diagonal wasn't the solution, use left diagonal
            $k -= 2;
            $x     = $V[$k];    // @phpstan-ignore-line
            $y     = $x - $k;
            $res[] = ['d', $x, $y];
        }

        return array_reverse($res);
    }

    /**
     * Returns unified diff from source $src to destination $dst.
     *
     * @param string        $src        Original data
     * @param string        $dst        New data
     * @param int           $ctx        Context length
     */
    public static function uniDiff(string $src, string $dst, int $ctx = 2): string
    {
        // Unify EOL in source
        $src = str_replace("\r\n", "\n", $src);

        [$src, $dst] = [explode("\n", $src), explode("\n", $dst)];

        $ses = self::SES($src, $dst);
        $res = '';

        $pos_x     = 0;
        $pos_y     = 0;
        $old_lines = 0;
        $new_lines = 0;
        $buffer    = '';

        foreach ($ses as $cmd) {
            [$cmd, $x, $y] = [$cmd[0], $cmd[1], $cmd[2]];

            # New chunk
            if ($x - $pos_x > 2 * $ctx || $pos_x == 0 && $x > $ctx) {
                # Footer for current chunk
                for ($i = 0; $buffer && $i < $ctx; $i++) {
                    $buffer .= sprintf(self::US_CTX, $src[$pos_x + $i]);
                }

                # Header for current chunk
                $res .= sprintf(
                    self::US_RANGE,
                    $pos_x     + 1 - $old_lines,
                    $old_lines + $i,
                    $pos_y     + 1 - $new_lines,
                    $new_lines + $i
                ) . $buffer;

                $pos_x     = $x;
                $pos_y     = $y;
                $old_lines = 0;
                $new_lines = 0;
                $buffer    = '';

                # Header for next chunk
                for ($i = $ctx; $i > 0; $i--) {
                    $buffer .= sprintf(self::US_CTX, $src[$pos_x - $i]);
                    $old_lines++;
                    $new_lines++;
                }
            }

            # Context
            while ($x > $pos_x) {
                $old_lines++;
                $new_lines++;
                $buffer .= sprintf(self::US_CTX, $src[$pos_x]);
                $pos_x++;
                $pos_y++;
            }
            # Deletion
            if ($cmd == 'd') {
                $old_lines++;
                $buffer .= sprintf(self::US_DEL, $src[$x]);
                $pos_x++;
            }
            # Insertion
            elseif ($cmd == 'i') {
                $new_lines++;
                $buffer .= sprintf(self::US_INS, $dst[$y]);
                $pos_y++;
            }
        }

        # Remaining chunk
        if ($buffer !== '') {
            # Footer
            for ($i = 0; $i < $ctx; $i++) {
                if (!isset($src[$pos_x + $i])) {
                    break;
                }
                $buffer .= sprintf(self::US_CTX, $src[$pos_x + $i]);
            }

            # Header for current chunk
            $res .= sprintf(
                self::US_RANGE,
                $pos_x     + 1 - $old_lines,
                $old_lines + $i,
                $pos_y     + 1 - $new_lines,
                $new_lines + $i
            ) . $buffer;
        }

        return $res;
    }

    /**
     * Applies a unified patch to a piece of text.
     * Throws an exception on invalid or not applicable diff.
     *
     * @param string        $src        Source text
     * @param string        $diff       Patch to apply
     */
    public static function uniPatch(string $src, string $diff): string
    {
        // Unify EOL in source
        $src = str_replace("\r\n", "\n", $src);

        $dst  = [];
        $src  = explode("\n", $src);
        $diff = explode("\n", $diff);

        $t          = count($src);
        $old_length = $new_length = 0;

        foreach ($diff as $line) {
            # New chunk
            if (preg_match(self::UP_RANGE, $line, $m)) {
                $m[1]--;
                $m[3]--;

                if ($m[1] > $t) {
                    throw new Exception(__('Bad range'));
                }

                if ($t - count($src) > $m[1]) {
                    throw new Exception(__('Invalid range'));
                }

                while ($t - count($src) < $m[1]) {
                    $dst[] = array_shift($src);
                }

                if (count($dst) !== $m[3]) {
                    throw new Exception(__('Invalid line number'));
                }

                if ($old_length || $new_length) {   // @phpstan-ignore-line
                    throw new Exception(__('Chunk is out of range'));
                }

                $old_length = (int) $m[2];
                $new_length = (int) $m[4];
            }
            # Context
            elseif (preg_match(self::UP_CTX, $line, $m)) {
                if (array_shift($src) !== $m[1]) {
                    throw new Exception(__('Bad context'));
                }
                $dst[] = $m[1];
                $old_length--;
                $new_length--;
            }
            # Addition
            elseif (preg_match(self::UP_INS, $line, $m)) {
                $dst[] = $m[1];
                $new_length--;
            }
            # Deletion
            elseif (preg_match(self::UP_DEL, $line, $m)) {
                if (array_shift($src) !== $m[1]) {
                    throw new Exception(__('Bad context (in deletion)'));
                }
                $old_length--;
            } elseif ($line !== '') {
                throw new Exception(__('Invalid diff format'));
            }
        }

        if ($old_length || $new_length) {
            throw new Exception(__('Chunk is out of range'));
        }

        return implode("\n", [...$dst, ...$src]);
    }

    /**
     * Throws an exception on invalid unified diff.
     *
     * @param string        $diff        Diff text to check
     */
    public static function uniCheck(string $diff): void
    {
        $diff = explode("\n", $diff);

        $cur_line  = 1;
        $ins_lines = 0;

        # Chunk length
        $old_length = $new_length = 0;

        foreach ($diff as $line) {
            # New chunk
            if (preg_match(self::UP_RANGE, $line, $m)) {
                if ($cur_line > $m[1]) {
                    throw new Exception(__('Invalid range'));
                }
                while ($cur_line < $m[1]) {
                    $ins_lines++;
                    $cur_line++;
                }
                if ($ins_lines + 1 != $m[3]) {
                    throw new Exception(__('Invalid line number'));
                }

                if ($old_length || $new_length) {
                    throw new Exception(__('Chunk is out of range'));
                }

                $old_length = $m[2];
                $new_length = $m[4];
            }
            # Context
            elseif (preg_match(self::UP_CTX, $line, $m)) {
                $ins_lines++;
                $cur_line++;
                $old_length--;
                $new_length--;
            }
            # Addition
            elseif (preg_match(self::UP_INS, $line, $m)) {
                $ins_lines++;
                $new_length--;
            }
            # Deletion
            elseif (preg_match(self::UP_DEL, $line, $m)) {
                $cur_line++;
                $old_length--;
            }
            # Skip empty lines
            elseif ($line === '') {
                continue;
            }
            # Unrecognized diff format
            else {
                throw new Exception(__('Invalid diff format'));
            }
        }

        if ($old_length || $new_length) {
            throw new Exception(__('Chunk is out of range'));
        }
    }
}
