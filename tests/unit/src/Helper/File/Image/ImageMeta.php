<?php

/**
 * Unit tests
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace tests\unit\Dotclear\Helper\File\Image;

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', '..', 'bootstrap.php']);

use atoum;

/**
 * @tags Image
 */
class ImageMeta extends atoum
{
    /**
     * @var string Fixtures root folder
     */
    private string $root;

    public function __construct()
    {
        parent::__construct();

        $this->root = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', '..', 'fixtures', 'src', 'Helper', 'Image']));
    }

    public function test()
    {
        $meta = \Dotclear\Helper\File\Image\ImageMeta::readMeta(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.jpg']));

        $this
            ->array($meta)
            ->isEqualTo([
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
            ])
        ;
    }

    public function testExifIptc()
    {
        $meta = \Dotclear\Helper\File\Image\ImageMeta::readMeta(implode(DIRECTORY_SEPARATOR, [$this->root, 'img_exif_iptc.jpg']));

        $this
            ->array($meta)
            ->isEqualTo([
                'Title'             => null,
                'Description'       => 'Well it is a smiley that happens to be green',
                'Creator'           => 'No one',
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
                'Keywords'          => 'Yet another keyword',
                'AltText'           => null,
            ])
        ;
    }

    public function testExifIptcXmp()
    {
        $meta = \Dotclear\Helper\File\Image\ImageMeta::readMeta(implode(DIRECTORY_SEPARATOR, [$this->root, 'img_exif_iptc_xmp.jpg']));

        $this
            ->array($meta)
            ->isEqualTo([
                'Title'             => 'object name here',
                'Description'       => '(This caption in XMP) This is a metadata test file. It has values in most IPTC  fields, including many extended fields, in both the IIM and XMP data blocks. Also contains Exif data, inc. sample GPS data. NOTE that this file is out-of-sync. The Caption/description, Creator and Copyright fields have different values in the IIM, XMP, and Exif data blocks. This file also contains Photoshop log info, Lightroom Develop info, and both Exif and Photoshop thumbnails. The original file can be found at www.carlseibert.com/commons  Carl Seibert / Creative Commons 4.0',
                'Creator'           => 'Carl Seibert (XMP)',
                'Rights'            => '© Copyright 2017 Carl Seibert  metadatamatters.blog (XMP)',
                'Make'              => 'samsung',
                'Model'             => 'SM-G930P',
                'Exposure'          => '1/1600',
                'FNumber'           => '17/10',
                'MaxApertureValue'  => '153/100',
                'ExposureProgram'   => 2,
                'ISOSpeedRatings'   => 40,
                'DateTimeOriginal'  => '2017-05-29 11:11:16',
                'ExposureBiasValue' => '0/10',
                'MeteringMode'      => 2,
                'FocalLength'       => '420/100',
                'Lens'              => 'Samsung Galaxy S7 Rear Camera',
                'CountryCode'       => 'USA',
                'Country'           => 'United States',
                'State'             => 'Florida',
                'City'              => 'Anytown',
                'Keywords'          => 'keywords go here,keywords* test image metadata,Users,carl,Documents,Websites,aa,carlsite,content,blog,sample,templates,metadata,all,fields,w,ps,hist.jpg',
                'AltText'           => null,
            ])
        ;
    }

    public function testUnreadable()
    {
        $this
            ->exception(function () {
                $meta = \Dotclear\Helper\File\Image\ImageMeta::readMeta(implode(DIRECTORY_SEPARATOR, [$this->root, 'none.jpg']));
            })
            ->hasMessage('Unable to read file')
        ;
    }

    public function testIptcParseDisabled()
    {
        $this
            ->if($this->function->function_exists = function (string $method) {
                if ($method === 'iptcparse') {
                    return false;
                }

                return true;
            })
            ->then
            ->array(\Dotclear\Helper\File\Image\ImageMeta::readMeta(implode(DIRECTORY_SEPARATOR, [$this->root, 'img_exif_iptc.jpg'])))
            ->isEqualTo([
                'Title'             => null,
                'Description'       => 'Well it is a smiley that happens to be green',
                'Creator'           => 'No one',
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
            ])
        ;
    }

    public function testGetImageSizeDisabled()
    {
        $this
            ->if($this->function->getimagesize = fn (string $filename, ?array &$image_info = null) => false)
            ->then
            ->array(\Dotclear\Helper\File\Image\ImageMeta::readMeta(implode(DIRECTORY_SEPARATOR, [$this->root, 'img_exif_iptc.jpg'])))
            ->isEqualTo([
                'Title'             => null,
                'Description'       => 'Well it is a smiley that happens to be green',
                'Creator'           => 'No one',
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
            ])
        ;
    }

    public function testExifReadDataDisabled()
    {
        $this
            ->if($this->function->function_exists = function (string $method) {
                if ($method === 'exif_read_data') {
                    return false;
                }

                return true;
            })
            ->then
            ->array(\Dotclear\Helper\File\Image\ImageMeta::readMeta(implode(DIRECTORY_SEPARATOR, [$this->root, 'img_exif_iptc.jpg'])))
            ->isEqualTo([
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
                'Keywords'          => 'Yet another keyword',
                'AltText'           => null,
            ])
        ;
    }

    public function testExifReadDataError()
    {
        $this
            ->if($this->function->exif_read_data = fn ($file, ?string $required_sections = null, bool $as_arrays = false, bool $read_thumbnail = false) => false)
            ->then
            ->array(\Dotclear\Helper\File\Image\ImageMeta::readMeta(implode(DIRECTORY_SEPARATOR, [$this->root, 'img_exif_iptc.jpg'])))
            ->isEqualTo([
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
                'Keywords'          => 'Yet another keyword',
                'AltText'           => null,
            ])
        ;
    }
}
