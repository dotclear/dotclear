<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\File\Image;

use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Text;
use Exception;

/**
 * @class ImageMeta
 *
 * This class reads EXIF, IPTC and XMP metadata from a JPEG file.
 *
 * - Contributor: Mathieu Lecarme.
 */
class ImageMeta
{
    /**
     * Internal XMP array
     *
     * @var     array<string, mixed>
     */
    protected $xmp = [];

    /**
     * Internal IPTC array
     *
     * @var     array<string, mixed>
     */
    protected $iptc = [];

    /**
     * Internal EXIF array
     *
     * @var     array<string, mixed>
     */
    protected $exif = [];

    /**
     * array $properties Final properties array
     *
     * @var     array<string, mixed>
     */
    protected $properties = [
        'Title'             => null,
        'Description'       => null,
        'Creator'           => null,
        'Rights'            => null,
        'Make'              => null,
        'Model'             => null,
        'Exposure'          => null,
        'FNumber'           => null,
        'MaxApertureValue'  => null,
        'ExposureProgram'   => null,
        'ISOSpeedRatings'   => null,
        'DateTimeOriginal'  => null,
        'ExposureBiasValue' => null,
        'MeteringMode'      => null,
        'FocalLength'       => null,
        'Lens'              => null,
        'CountryCode'       => null,
        'Country'           => null,
        'State'             => null,
        'City'              => null,
        'Keywords'          => null,
        'AltText'           => null,
    ];

    /**
     * Read metadata
     *
     * Returns all image metadata in an array as defined in {@link $properties}.
     *
     * @param string    $filename        Image file path
     *
     * @return array<string, mixed>
     */
    public static function readMeta(string $filename): array
    {
        $instance = new self();
        $instance->loadFile($filename);

        return $instance->getMeta();
    }

    /**
     * Get metadata
     *
     * Returns all image metadata in an array as defined in {@link $properties}.
     * Should call {@link loadFile()} before.
     *
     * @return array<string, mixed>
     */
    public function getMeta(): array
    {
        foreach (array_keys($this->properties) as $k) {
            if (!empty($this->xmp[$k])) {
                $this->properties[$k] = $this->xmp[$k];
            } elseif (!empty($this->iptc[$k])) {
                $this->properties[$k] = $this->iptc[$k];
            } elseif (!empty($this->exif[$k])) {
                $this->properties[$k] = $this->exif[$k];
            }
        }

        # Fix date format
        if ($this->properties['DateTimeOriginal'] !== null) {
            $this->properties['DateTimeOriginal'] = preg_replace(
                '/^(\d{4}):(\d{2}):(\d{2})/',
                '$1-$2-$3',
                (string) $this->properties['DateTimeOriginal']
            );
        }

        return $this->properties;
    }

    /**
     * Load file
     *
     * Loads a file and read its metadata.
     *
     * @param string    $filename        Image file path
     */
    public function loadFile(string $filename): void
    {
        if (!is_file($filename) || !is_readable($filename)) {
            throw new Exception('Unable to read file');
        }

        $this->readXMP($filename);
        $this->readIPTC($filename);
        $this->readExif($filename);
    }

    /**
     * Read XMP
     *
     * Reads XML metadata and assigns values to {@link $xmp}.
     *
     * @param string    $filename        Image file path
     */
    protected function readXMP(string $filename): void
    {
        if (($fp = @fopen($filename, 'rb')) === false) {
            throw new Exception('Unable to open image file');
        }

        $inside = false;
        $done   = false;
        $xmp    = null;

        while (!feof($fp)) {
            $buffer = fgets($fp, 4096);
            if ($buffer !== false) {
                $xmp_start = strpos($buffer, '<x:xmpmeta');

                if ($xmp_start !== false) {
                    $buffer = substr($buffer, $xmp_start);
                    $inside = true;
                }

                if ($inside) {
                    $xmp_end = strpos($buffer, '</x:xmpmeta>');
                    if ($xmp_end !== false) {
                        $buffer = substr($buffer, $xmp_end, 12);
                        $inside = false;
                        $done   = true;
                    }

                    $xmp .= $buffer;
                }

                if ($done) {
                    break;
                }
            } else {
                break;
            }
        }
        fclose($fp);

        if (!$xmp) {
            return;
        }

        foreach ($this->xmp_reg as $code => $patterns) {
            foreach ($patterns as $p) {
                if (preg_match($p, $xmp, $matches)) {
                    $this->xmp[$code] = $matches[1];

                    break;
                }
            }
        }

        if (preg_match('%<dc:subject>\s*<rdf:Bag>(.+?)</rdf:Bag%msu', $xmp, $rdf_bag)
            && preg_match_all('%<rdf:li>(.+?)</rdf:li>%msu', $rdf_bag[1], $rdf_bag_li)) {
            $this->xmp['Keywords'] = implode(',', $rdf_bag_li[1]);
        }

        foreach ($this->xmp as $k => $v) {
            $this->xmp[$k] = Html::decodeEntities(Text::toUTF8($v));
        }
    }

    /**
     * Read IPTC
     *
     * Reads IPTC metadata and assigns values to {@link $iptc}.
     *
     * @param string    $filename        Image file path
     */
    protected function readIPTC(string $filename): void
    {
        if (!function_exists('iptcparse')) {
            return;
        }

        $imageinfo = null;
        @getimagesize($filename, $imageinfo);

        if (!is_array($imageinfo) || !isset($imageinfo['APP13'])) {
            return;
        }

        $iptc = @iptcparse($imageinfo['APP13']);

        if (!is_array($iptc)) {
            return;
        }

        foreach ($this->iptc_ref as $k => $v) {
            if (isset($iptc[$k]) && isset($this->iptc_to_property[$v])) {
                $this->iptc[$this->iptc_to_property[$v]] = Text::toUTF8(trim(implode(',', $iptc[$k])));
            }
        }
    }

    /**
     * Read EXIF
     *
     * Reads EXIF metadata and assigns values to {@link $exif}.
     *
     * @param string    $filename        Image file path
     */
    protected function readEXIF(string $filename): void
    {
        if (!function_exists('exif_read_data')) {
            return;
        }

        $data = @exif_read_data($filename, 'ANY_TAG');

        if (!is_array($data)) {
            return;
        }

        foreach ($this->exif_to_property as $k => $v) {
            if (isset($data[$k])) {
                if (is_array($data[$k])) {
                    foreach ($data[$k] as $kk => $vv) {
                        $this->exif[$v . '.' . $kk] = is_string($vv) ? Text::toUTF8($vv) : $vv;
                    }
                } else {
                    $this->exif[$v] = is_string($data[$k]) ? Text::toUTF8($data[$k]) : $data[$k];
                }
            }
        }
    }

    /**
     * XMP properties
     *
     * @var        array<string, array<string>>
     */
    protected $xmp_reg = [
        'Title' => [
            '%<dc:title>\s*<rdf:Alt>\s*<rdf:li.*?>(.+?)</rdf:li>%msu',
        ],
        'Description' => [
            '%<dc:description>\s*<rdf:Alt>\s*<rdf:li.*?>(.+?)</rdf:li>%msu',
        ],
        'Creator' => [
            '%<dc:creator>\s*<rdf:Seq>\s*<rdf:li>(.+?)</rdf:li>%msu',
        ],
        'Rights' => [
            '%<dc:rights>\s*<rdf:Alt>\s*<rdf:li.*?>(.+?)</rdf:li>%msu',
        ],
        'Make' => [
            '%<tiff:Make>(.+?)</tiff:Make>%msu',
            '%tiff:Make="(.+?)"%msu',
        ],
        'Model' => [
            '%<tiff:Model>(.+?)</tiff:Model>%msu',
            '%tiff:Model="(.+?)"%msu',
        ],
        'Exposure' => [
            '%<exif:ExposureTime>(.+?)</exif:ExposureTime>%msu',
            '%exif:ExposureTime="(.+?)"%msu',
        ],
        'FNumber' => [
            '%<exif:FNumber>(.+?)</exif:FNumber>%msu',
            '%exif:FNumber="(.+?)"%msu',
        ],
        'MaxApertureValue' => [
            '%<exif:MaxApertureValue>(.+?)</exif:MaxApertureValue>%msu',
            '%exif:MaxApertureValue="(.+?)"%msu',
        ],
        'ExposureProgram' => [
            '%<exif:ExposureProgram>(.+?)</exif:ExposureProgram>%msu',
            '%exif:ExposureProgram="(.+?)"%msu',
        ],
        'ISOSpeedRatings' => [
            '%<exif:ISOSpeedRatings>\s*<rdf:Seq>\s*<rdf:li>(.+?)</rdf:li>%msu',
        ],
        'DateTimeOriginal' => [
            '%<exif:DateTimeOriginal>(.+?)</exif:DateTimeOriginal>%msu',
            '%exif:DateTimeOriginal="(.+?)"%msu',
        ],
        'ExposureBiasValue' => [
            '%<exif:ExposureBiasValue>(.+?)</exif:ExposureBiasValue>%msu',
            '%exif:ExposureBiasValue="(.+?)"%msu',
        ],
        'MeteringMode' => [
            '%<exif:MeteringMode>(.+?)</exif:MeteringMode>%msu',
            '%exif:MeteringMode="(.+?)"%msu',
        ],
        'FocalLength' => [
            '%<exif:FocalLength>(.+?)</exif:FocalLength>%msu',
            '%exif:FocalLength="(.+?)"%msu',
        ],
        'Lens' => [
            '%<aux:Lens>(.+?)</aux:Lens>%msu',
            '%aux:Lens="(.+?)"%msu',
        ],
        'CountryCode' => [
            '%<Iptc4xmpCore:CountryCode>(.+?)</Iptc4xmpCore:CountryCode>%msu',
            '%Iptc4xmpCore:CountryCode="(.+?)"%msu',
        ],
        'Country' => [
            '%<photoshop:Country>(.+?)</photoshop:Country>%msu',
            '%photoshop:Country="(.+?)"%msu',
        ],
        'State' => [
            '%<photoshop:State>(.+?)</photoshop:State>%msu',
            '%photoshop:State="(.+?)"%msu',
        ],
        'City' => [
            '%<photoshop:City>(.+?)</photoshop:City>%msu',
            '%photoshop:City="(.+?)"%msu',
        ],
        'AltText' => [
            '%<Iptc4xmpCore:AltTextAccessibility>\s*<rdf:Alt>\s*<rdf:li.*?>(.+?)</rdf:li>%msu',
        ],
    ];

    /**
     * IPTC references
     *
     * @var        array<string, string>
     */
    protected $iptc_ref = [
        '1#090' => 'Iptc.Envelope.CharacterSet', // Character Set used (32 chars max)
        '2#005' => 'Iptc.ObjectName',            // Title (64 chars max)
        '2#015' => 'Iptc.Category',              // (3 chars max)
        '2#020' => 'Iptc.Supplementals',         // Supplementals categories (32 chars max)
        '2#025' => 'Iptc.Keywords',              // (64 chars max)
        '2#040' => 'Iptc.SpecialsInstructions',  // (256 chars max)
        '2#055' => 'Iptc.DateCreated',           // YYYYMMDD (8 num chars max)
        '2#060' => 'Iptc.TimeCreated',           // HHMMSS+/-HHMM (11 chars max)
        '2#062' => 'Iptc.DigitalCreationDate',   // YYYYMMDD (8 num chars max)
        '2#063' => 'Iptc.DigitalCreationTime',   // HHMMSS+/-HHMM (11 chars max)
        '2#080' => 'Iptc.ByLine',                // Author (32 chars max)
        '2#085' => 'Iptc.ByLineTitle',           // Author position (32 chars max)
        '2#090' => 'Iptc.City',                  // (32 chars max)
        '2#092' => 'Iptc.Sublocation',           // (32 chars max)
        '2#095' => 'Iptc.ProvinceState',         // (32 chars max)
        '2#100' => 'Iptc.CountryCode',           // (32 alpha chars max)
        '2#101' => 'Iptc.CountryName',           // (64 chars max)
        '2#105' => 'Iptc.Headline',              // (256 chars max)
        '2#110' => 'Iptc.Credits',               // (32 chars max)
        '2#115' => 'Iptc.Source',                // (32 chars max)
        '2#116' => 'Iptc.Copyright',             // Copyright Notice (128 chars max)
        '2#118' => 'Iptc.Contact',               // (128 chars max)
        '2#120' => 'Iptc.Caption',               // Caption/Abstract (2000 chars max)
        '2#122' => 'Iptc.CaptionWriter',         // Caption Writer/Editor (32 chars max)
    ];

    /**
     * IPTC references to properties
     *
     * @var        array<string, string>
     */
    protected $iptc_to_property = [
        'Iptc.ObjectName'    => 'Title',
        'Iptc.Caption'       => 'Description',
        'Iptc.ByLine'        => 'Creator',
        'Iptc.Copyright'     => 'Rights',
        'Iptc.CountryCode'   => 'CountryCode',
        'Iptc.CountryName'   => 'Country',
        'Iptc.ProvinceState' => 'State',
        'Iptc.City'          => 'City',
        'Iptc.Keywords'      => 'Keywords',
    ];

    /**
     * EXIF properties
     *
     * @var        array<string, string>
     */
    protected $exif_to_property = [
        //'' => 'Title',
        'ImageDescription'  => 'Description',
        'Artist'            => 'Creator',
        'Copyright'         => 'Rights',
        'Make'              => 'Make',
        'Model'             => 'Model',
        'ExposureTime'      => 'Exposure',
        'FNumber'           => 'FNumber',
        'MaxApertureValue'  => 'MaxApertureValue',
        'ExposureProgram'   => 'ExposureProgram',
        'ISOSpeedRatings'   => 'ISOSpeedRatings',
        'DateTimeOriginal'  => 'DateTimeOriginal',
        'ExposureBiasValue' => 'ExposureBiasValue',
        'MeteringMode'      => 'MeteringMode',
        'FocalLength'       => 'FocalLength',
        //'' => 'Lens',
        //'' => 'CountryCode',
        //'' => 'Country',
        //'' => 'State',
        //'' => 'City',
        //'' => 'Keywords'
    ];
}
