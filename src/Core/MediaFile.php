<?php

/**
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Helper\File\File;
use SimpleXMLElement;

/**
 * @brief   Media file (or dir) descriptor
 */
class MediaFile extends File
{
    /**
     * Media ID, positive integer
     */
    public int $media_id;

    /**
     * Media title, may be empty ('')
     */
    public string $media_title;

    /**
     * Media metadata (Exif, IPTC, XMP, ...) will be stored here
     */
    public ?SimpleXMLElement $media_meta = null;

    /**
     * Associated user ID
     */
    public string $media_user;

    /**
     * True if it is a private media
     */
    public bool $media_priv;

    /**
     * Datetime of the media
     */
    public int $media_dt;

    /**
     * String representation of the datetime of media
     */
    public string $media_dtstr;

    /**
     * True if the media is an image
     */
    public bool $media_image;

    /**
     * True if the media can be previewed
     */
    public bool $media_preview;

    /**
     * Type of media ('image', 'audio', 'video', ...)
     */
    public string $media_type;

    public string $media_icon;

    public bool $editable;

    /**
     * List of thumbnails
     * Key is thunbnail size code
     * Value is URL
     *
     * @var array<string, string>
     */
    public array $media_thumb;
}
