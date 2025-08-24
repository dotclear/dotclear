<?php

declare(strict_types=1);

namespace Dotclear\Helper\File\Image {
    use Closure;

    // Holds the mock callback

    function function_exists(string $function): bool
    {
        return FunctionExistsMock::$callback
            ? (FunctionExistsMock::$callback)($function)
            : \function_exists($function); // fallback to real function
    }

    function getimagesize(string $filename, &$image_info): array|false
    {
        return GetImageSizeMock::$callback
            ? (GetImageSizeMock::$callback)($filename, $image_info)
            : \getimagesize($filename, $image_info); // fallback to real function
    }

    function exif_read_data($file, ?string $required_sections = null, bool $as_arrays = false, bool $read_thumbnail = false): array|false
    {
        return ExifReadDataMock::$callback
            ? (ExifReadDataMock::$callback)($file, $required_sections, $as_arrays, $read_thumbnail)
            : \exif_read_data($file, $required_sections, $as_arrays, $read_thumbnail); // fallback to real function
    }

    final class FunctionExistsMock
    {
        public static ?Closure $callback = null;
        public static function set(?Closure $callback): void
        {
            self::$callback = $callback;
        }
    }

    final class GetImageSizeMock
    {
        public static ?Closure $callback = null;
        public static function set(?Closure $callback): void
        {
            self::$callback = $callback;
        }
    }

    final class ExifReadDataMock
    {
        public static ?Closure $callback = null;
        public static function set(?Closure $callback): void
        {
            self::$callback = $callback;
        }
    }
}

namespace Dotclear\Tests\Helper\File\Image {
    use Dotclear\Helper\File\Image\ExifReadDataMock;
    use Dotclear\Helper\File\Image\FunctionExistsMock;
    use Dotclear\Helper\File\Image\GetImageSizeMock;
    use Exception;
    use PHPUnit\Framework\Attributes\Depends;
    use PHPUnit\Framework\TestCase;

    class ImageMetaTest extends TestCase
    {
        /**
         * @var string Fixtures root folder
         */
        private string $root;

        protected function setUp(): void
        {
            $this->root = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', '..', 'fixtures', 'src', 'Helper', 'Image']));
        }

        public function test()
        {
            $meta = \Dotclear\Helper\File\Image\ImageMeta::readMeta(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.jpg']));

            $this->assertEquals(
                [
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
                ],
                $meta
            );
        }

        public function testExifIptc()
        {
            $meta = \Dotclear\Helper\File\Image\ImageMeta::readMeta(implode(DIRECTORY_SEPARATOR, [$this->root, 'img_exif_iptc.jpg']));

            $this->assertEquals(
                [
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
                ],
                $meta
            );
        }

        public function testExifIptcXmp()
        {
            $meta = \Dotclear\Helper\File\Image\ImageMeta::readMeta(implode(DIRECTORY_SEPARATOR, [$this->root, 'img_exif_iptc_xmp.jpg']));

            $this->assertEquals(
                [
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
                ],
                $meta
            );
        }

        public function testUnreadable()
        {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Unable to read file');

            $meta = \Dotclear\Helper\File\Image\ImageMeta::readMeta(implode(DIRECTORY_SEPARATOR, [$this->root, 'none.jpg']));
        }

        public function testIptcParseDisabled()
        {
            // Mock function_exists()
            FunctionExistsMock::set(fn (string $function): bool => $function === 'iptcparse' ? false : \function_exists($function));

            $meta = \Dotclear\Helper\File\Image\ImageMeta::readMeta(implode(DIRECTORY_SEPARATOR, [$this->root, 'img_exif_iptc.jpg']));

            $this->assertEquals(
                [
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
                ],
                $meta
            );

            // Unmock function_exists()
            FunctionExistsMock::set(null);
        }

        #[Depends('testIptcParseDisabled')]
        public function testExifReadDataDisabled()
        {
            // Mock function_exists()
            FunctionExistsMock::set(fn (string $function): bool => $function === 'exif_read_data' ? false : \function_exists($function));

            $meta = \Dotclear\Helper\File\Image\ImageMeta::readMeta(implode(DIRECTORY_SEPARATOR, [$this->root, 'img_exif_iptc.jpg']));

            $this->assertEquals(
                [
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
                ],
                $meta
            );

            // Unmock function_exists()
            FunctionExistsMock::set(null);
        }

        public function testGetImageSizeDisabled()
        {
            // Mock getimagesize()
            GetImageSizeMock::set(fn (string $function, &$image_info): array|false => false);

            $meta = \Dotclear\Helper\File\Image\ImageMeta::readMeta(implode(DIRECTORY_SEPARATOR, [$this->root, 'img_exif_iptc.jpg']));

            $this->assertEquals(
                [
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
                ],
                $meta
            );

            // Unmock getimagesize()
            GetImageSizeMock::set(null);
        }

        public function testExifReadDataError()
        {
            // Mock exif_read_data()
            ExifReadDataMock::set(fn ($file, ?string $required_sections = null, bool $as_arrays = false, bool $read_thumbnail = false): array|false => false);

            $meta = \Dotclear\Helper\File\Image\ImageMeta::readMeta(implode(DIRECTORY_SEPARATOR, [$this->root, 'img_exif_iptc.jpg']));

            $this->assertEquals(
                [
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
                ],
                $meta
            );

            // Unmock exif_read_data()
            ExifReadDataMock::set(null);
        }
    }
}
