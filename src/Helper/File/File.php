<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\File;

#[\AllowDynamicProperties]
/**
 * @class File item class
 *
 * File item class used by {@link Manager}. In this class {@link $file} could
 * be either a file or a directory.
 */
class File
{
    /**
     * Complete path to file
     *
     * @var string
     */
    public $file;

    /**
     * File basename
     *
     * @var string
     */
    public $basename;

    /**
     * File directory name
     *
     * @var string
     */
    public $dir;

    /**
     * File URL
     *
     * @var string
     */
    public $file_url;

    /**
     * File directory URL
     *
     * @var string
     */
    public $dir_url;

    /**
     * File extension
     *
     * @var string
     */
    public $extension;

    /**
     * File path relative to <var>$root</var> given in constructor
     *
     * @var string
     */
    public $relname;

    /**
     * Parent directory (ie. "..")
     *
     * @var        bool
     */
    public $parent = false;

    /**
     * File MimeType
     *
     * @see {@link Files::getMimeType()}
     *
     * @var string|null
     */
    public $type;

    /**
     * File MimeType prefix
     *
     * @var string
     */
    public $type_prefix;

    /**
     * File modification timestamp
     *
     * @var int
     */
    public $mtime;

    /**
     * File size
     *
     * @var int
     */
    public $size;

    /**
     * File permissions mode
     *
     * @var int
     */
    public $mode;

    /**
     * File owner ID
     *
     * @var int
     */
    public $uid;

    /**
     * File group ID
     *
     * @var int
     */
    public $gid;

    /**
     * True if file or directory is writable
     *
     * @var bool
     */
    public $w;

    /**
     * True if file is a directory
     *
     * @var bool
     */
    public $d;

    /**
     * True if file file is executable or directory is traversable
     *
     * @var bool
     */
    public $x;

    /**
     * True if file is a file
     *
     * @var bool
     */
    public $f;

    /**
     * True if file or directory is deletable
     *
     * @var bool
     */
    public $del;

    /**
     * Constructor
     *
     * Creates an instance of File object.
     *
     * @param string    $file           Absolute file or directory path
     * @param string    $root           File root path
     * @param string    $root_url       File root URL
     */
    public function __construct(string $file, ?string $root, ?string $root_url = '')
    {
        $file = Path::real($file);
        if (!$file) {
            // File does not exist
            return;
        }

        $stat = stat($file);
        $path = Path::info($file);

        $rel = (string) preg_replace('/^' . preg_quote((string) $root, '/') . '\/?/', '', (string) $file);

        // Properties
        $this->file     = $file;
        $this->basename = $path['basename'];
        $this->dir      = $path['dirname'];
        $this->relname  = $rel;

        // URL
        $this->file_url = $root_url . str_replace('%2F', '/', rawurlencode($rel));
        $this->dir_url  = dirname($this->file_url);

        // File type
        $this->extension   = $path['extension'];
        $this->type        = $this->d ? null : Files::getMimeType($file);
        $this->type_prefix = (string) preg_replace('/^(.+?)\/.+$/', '$1', (string) $this->type);

        // Filesystem infos
        $this->mtime = $stat ? $stat[9] : 0;
        $this->size  = $stat ? $stat[7] : 0;
        $this->mode  = $stat ? $stat[2] : 0;
        $this->uid   = $stat ? $stat[4] : 0;
        $this->gid   = $stat ? $stat[5] : 0;

        // Flags
        $this->w   = is_writable($file);
        $this->d   = is_dir($file);
        $this->f   = is_file($file);
        $this->x   = $this->d && file_exists($file . '/.');
        $this->del = Files::isDeletable($file);
    }
}
