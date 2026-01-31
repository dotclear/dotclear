<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\importExport;

use Dotclear\Helper\TraitDynamicProperties;
use Exception;

/**
 * @brief   The module flat backup handler.
 * @ingroup importExport
 */
class FlatBackup
{
    use TraitDynamicProperties;

    protected mixed $fp;

    /**
     * @var string[]   $line_cols
     */
    private array $line_cols = [];

    private ?string $line_name = null;
    private ?int $line_num     = null;

    /**
     * @var array<string, string>   $replacement
     */
    private array $replacement = [
        '/(?<!\\\\)(?>(\\\\\\\\)*+)(\\\\n)/u' => "\$1\n",
        '/(?<!\\\\)(?>(\\\\\\\\)*+)(\\\\r)/u' => "\$1\r",
        '/(?<!\\\\)(?>(\\\\\\\\)*+)(\\\\")/u' => '$1"',
        '/(\\\\\\\\)/'                        => '\\',
    ];

    public function __construct(string $file)
    {
        if (file_exists($file) && is_readable($file)) {
            $this->fp       = fopen($file, 'rb');
            $this->line_num = 1;
        } else {
            throw new Exception(__('No file to read.'));
        }
    }

    public function __destruct()
    {
        if (is_resource($this->fp)) {
            @fclose($this->fp);
        }
    }

    /**
     * Gets the line.
     *
     * @throws     Exception
     *
     * @return     FlatBackupItem|false  The line.
     */
    public function getLine()
    {
        if (($line = $this->nextLine()) === false) {
            return false;
        }

        if (str_starts_with($line, '[')) {
            $this->line_name = substr($line, 1, strpos($line, ' ') - 1);

            $line            = substr($line, strpos($line, ' ') + 1, -1);
            $this->line_cols = explode(',', $line);

            return $this->getLine();
        }

        if (str_starts_with($line, '"')) {
            $line  = (string) preg_replace('/^"|"$/', '', $line);
            $lines = preg_split('/(^"|","|(?<!\\\)\"$)/m', $line);
            if ($lines === false) {
                return false;
            }

            $counter = count($lines);
            if (count($this->line_cols) !== $counter) {
                throw new Exception(sprintf('Invalid row count at line %s', $this->line_num));
            }

            /**
             * @var array<string, string>
             */
            $res = [];
            for ($i = 0; $i < $counter; $i++) {
                $res[$this->line_cols[$i]] = is_string($sanitized_line = preg_replace(array_keys($this->replacement), array_values($this->replacement), $lines[$i])) ? $sanitized_line : '';
            }

            return new FlatBackupItem((string) $this->line_name, $res, (int) $this->line_num);
        }

        return $this->getLine();
    }

    /**
     * Get next line
     *
     * @return     false|string
     */
    private function nextLine(): false|string
    {
        if (!is_resource($this->fp)) {
            return false;
        }

        if (feof($this->fp)) {
            // End of file reached
            return false;
        }
        $this->line_num++;

        $line = fgets($this->fp);
        if ($line === false) {
            // An error occured
            return false;
        }

        $line = trim($line);

        return $line === '' ? $this->nextLine() : $line;
    }
}
