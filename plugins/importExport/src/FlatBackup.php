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
     * @var array<string>
     */
    private array $line_cols = [];

    private ?string $line_name = null;
    private ?int $line_num     = null;

    /**
     * @var array<string>
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
        } elseif (str_starts_with($line, '"')) {
            $line = (string) preg_replace('/^"|"$/', '', $line);    // @phpstan-ignore-line
            $line = preg_split('/(^"|","|(?<!\\\)\"$)/m', $line);
            if ($line === false) {
                return false;
            }

            if (count($this->line_cols) != count($line)) {
                throw new Exception(sprintf('Invalid row count at line %s', $this->line_num));
            }

            $res = [];

            for ($i = 0; $i < count($line); $i++) {
                $res[$this->line_cols[$i]] = preg_replace(array_keys($this->replacement), array_values($this->replacement), $line[$i]); // @phpstan-ignore-line
            }

            return new FlatBackupItem((string) $this->line_name, $res, (int) $this->line_num);
        }

        return $this->getLine();
    }

    /**
     * Get next line
     *
     * @return     bool|mixed
     */
    private function nextLine(): mixed
    {
        if (feof($this->fp)) {
            return false;
        }
        $this->line_num++;

        $line = fgets($this->fp);
        $line = trim((string) $line);

        return empty($line) ? $this->nextLine() : $line;
    }
}
