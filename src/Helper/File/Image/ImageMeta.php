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
use SimpleXMLElement;

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
     * @var     array<string, mixed>    $xmp
     */
    protected $xmp = [];

    /**
     * Internal IPTC array
     *
     * @var     array<string, mixed>    $iptc
     */
    protected $iptc = [];

    /**
     * Internal EXIF array
     *
     * @var     array<string, mixed>    $exif
     */
    protected $exif = [];

    /**
     * Internal XML array
     *
     * @var     array<string, mixed>    $xml
     */
    protected $xml = [];

    /**
     * array $properties Final properties array
     *
     * @var     array<string, mixed>    $properties
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
            } elseif (!empty($this->xml[$k])) {
                $this->properties[$k] = $this->xml[$k];
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

        if ($this->isXML($filename)) {
            $this->readXML($filename);
        } else {
            $this->readXMP($filename);
            $this->readIPTC($filename);
            $this->readEXIF($filename);
        }
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
        try {
            $start_tag  = '<x:xmpmeta';
            $end_tag    = '</x:xmpmeta>';
            $xmp        = '';
            $has_xmp    = false;
            $chunk_size = 4096;     // Number of bytes read each time

            $file_pointer = fopen($filename, 'r');
            if ($file_pointer !== false) {
                while (($chunk = fread($file_pointer, $chunk_size)) !== false) {
                    if ($chunk === '') {
                        break;
                    }

                    $xmp .= $chunk;

                    $start_position = strpos($xmp, $start_tag);
                    $end_position   = strpos($xmp, $end_tag);

                    if ($start_position !== false && $end_position !== false) {
                        $xmp     = substr($xmp, $start_position, $end_position - $start_position + 12);
                        $has_xmp = true;

                        break;
                    } elseif ($start_position !== false) {
                        $xmp     = substr($xmp, $start_position);
                        $has_xmp = true;
                    } elseif (strlen($xmp) > (strlen($start_tag) * 2)) {
                        $xmp = substr($xmp, strlen($start_tag));
                    }
                }
                fclose($file_pointer);
            }

            if (!$has_xmp) {
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
        } catch (Exception) {
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
        try {
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
        } catch (Exception) {
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
        try {
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
        } catch (Exception) {
        }
    }

    /**
     * Read XML (usually from SVG)
     *
     * Reads XML metadata and assigns values to {@link $xml}.
     *
     * @param string    $filename        Image file path
     */
    protected function readXML(string $filename): void
    {
        try {
            $data = file_get_contents($filename);
            if ($data === false) {
                return;
            }

            $xml = @simplexml_load_string($data);
            if (!$xml instanceof SimpleXMLElement) {
                return;
            }

            foreach ($this->xml_to_property as $k => $v) {
                if ($xml->{$k}) {
                    $this->xml[$v] = Html::decodeEntities(Text::toUTF8((string) $xml->{$k}));
                }
            }
        } catch (Exception) {
        }
    }

    /**
     * Determines whether the specified filename is xml (looks like).
     *
     * @param      string  $filename  The filename
     *
     * @return     bool    True if the specified filename is xml, False otherwise.
     */
    protected function isXML(string $filename): bool
    {
        $is_xml = false;

        try {
            // Check (basically) is the file looks like XML
            $file_pointer = fopen($filename, 'r');
            if ($file_pointer !== false) {
                if (($chunk = fread($file_pointer, 16)) !== false && str_starts_with($chunk, '<')) {
                    $is_xml = true;
                }
                fclose($file_pointer);
            }
        } catch (Exception) {
        }

        return $is_xml;
    }

    /**
     * XML references to properties
     *
     * @var        array<string, string>    $xml_to_property
     */
    protected $xml_to_property = [
        'title' => 'Title',
        'desc'  => 'Description',
    ];

    /**
     * XMP properties
     *
     * @var        array<string, string[]>  $xmp_reg
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
     * @var        array<string, string>    $iptc_ref
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
     * @var        array<string, string>    $iptc_to_property
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
     * @var        array<string, string>    $exif_to_property
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
