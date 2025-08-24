<?php

declare(strict_types=1);

namespace Dotclear\Helper\File\Image {
    use Closure;
    use GdImage;

    // Holds the mock callback

    if (!\class_exists('Dotclear\Helper\File\Image\FunctionExistsMock')) {
        function function_exists(string $function): bool
        {
            return FunctionExistsMock::$callback
                ? (FunctionExistsMock::$callback)($function)
                : \function_exists($function); // fallback to real function
        }
    }

    function header(string $header, bool $replace = true, int $response_code = 0): void
    {
        HeaderMock::$callback
            ? (HeaderMock::$callback)($header, $replace, $response_code)
            : \header($header, $replace, $response_code); // fallback to real function
    }

    function imagepng(GdImage $image, $file = null, int $quality = -1, int $filters = -1): bool
    {
        return ImagePngMock::$callback
            ? (ImagePngMock::$callback)($image, $file, $quality, $filters)
            : \imagepng($image, $file, $quality, $filters); // fallback to real function
    }

    function imagejpeg(GdImage $image, $file = null, int $quality = -1): bool
    {
        return ImageJpegMock::$callback
            ? (ImageJpegMock::$callback)($image, $file, $quality)
            : \imagejpeg($image, $file, $quality); // fallback to real function
    }

    function imagegif(GdImage $image, $file = null): bool
    {
        return ImageGifMock::$callback
            ? (ImageGifMock::$callback)($image, $file)
            : \imagegif($image, $file); // fallback to real function
    }

    function imagewebp(GdImage $image, $file = null, int $quality = -1): bool
    {
        return ImageWebpMock::$callback
            ? (ImageWebpMock::$callback)($image, $file, $quality)
            : \imagewebp($image, $file, $quality); // fallback to real function
    }

    function imageavif(GdImage $image, $file = null, int $quality = -1, int $speed = -1): bool
    {
        return ImageAvifMock::$callback
            ? (ImageAvifMock::$callback)($image, $file, $quality, $speed)
            : \imageavif($image, $file, $quality, $speed); // fallback to real function
    }

    if (!\class_exists('Dotclear\Helper\File\Image\FunctionExistsMock')) {
        final class FunctionExistsMock
        {
            public static ?Closure $callback = null;
            public static function set(?Closure $callback): void
            {
                self::$callback = $callback;
            }
        }
    }

    final class HeaderMock
    {
        public static ?Closure $callback = null;
        public static function set(?Closure $callback): void
        {
            self::$callback = $callback;
        }
    }

    final class ImagePngMock
    {
        public static ?Closure $callback = null;
        public static function set(?Closure $callback): void
        {
            self::$callback = $callback;
        }
    }

    final class ImageJpegMock
    {
        public static ?Closure $callback = null;
        public static function set(?Closure $callback): void
        {
            self::$callback = $callback;
        }
    }

    final class ImageGifMock
    {
        public static ?Closure $callback = null;
        public static function set(?Closure $callback): void
        {
            self::$callback = $callback;
        }
    }

    final class ImageWebpMock
    {
        public static ?Closure $callback = null;
        public static function set(?Closure $callback): void
        {
            self::$callback = $callback;
        }
    }

    final class ImageAvifMock
    {
        public static ?Closure $callback = null;
        public static function set(?Closure $callback): void
        {
            self::$callback = $callback;
        }
    }
}

namespace Dotclear\Tests\Helper\File\Image {
    use Exception;

    use Dotclear\Helper\File\Image\FunctionExistsMock;
    use Dotclear\Helper\File\Image\HeaderMock;
    use Dotclear\Helper\File\Image\ImageAvifMock;
    use Dotclear\Helper\File\Image\ImageGifMock;
    use Dotclear\Helper\File\Image\ImageJpegMock;
    use Dotclear\Helper\File\Image\ImagePngMock;
    use Dotclear\Helper\File\Image\ImageWebpMock;
    use GdImage;
    use PHPUnit\Framework\Attributes\Depends;
    use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
    use PHPUnit\Framework\TestCase;

    #[RunTestsInSeparateProcesses]
    class ImageToolsTest extends TestCase
    {
        /**
         * @var string Fixtures root folder
         */
        private string $root;

        protected function setUp(): void
        {
            $this->root = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', '..', 'fixtures', 'src', 'Helper', 'Image']));
        }

        public function testPng()
        {
            $tool = new \Dotclear\Helper\File\Image\ImageTools();

            $tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.png']));

            $this->assertEquals(
                65,
                $tool->getH()
            );
            $this->assertEquals(
                240,
                $tool->getW()
            );

            $that = $this;

            // Mock header()
            HeaderMock::set(function (string $header, bool $replace = true, int $response_code = 0) use ($that) {
                $that->assertContains(
                    $header,
                    [
                        'Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
                        'Content-type: image/png',
                    ]
                );
            });

            // Mock imagepng()
            ImagePngMock::set(function (GdImage $image, $file = null, int $quality = -1, int $filters = -1) use ($that) {
                $file ??= implode(DIRECTORY_SEPARATOR, [$that->root, 'output.png']);

                return \imagepng($image, $file, $quality, $filters);
            });

            $this->assertTrue(
                $tool->output('png', implode(DIRECTORY_SEPARATOR, [$this->root, 'file.png']))
            );
            $this->assertTrue(
                $tool->output('png', null)
            );
            $this->assertFileExists(
                implode(DIRECTORY_SEPARATOR, [$this->root, 'file.png'])
            );
            $this->assertFileExists(
                implode(DIRECTORY_SEPARATOR, [$this->root, 'output.png'])
            );

            $tool->close();

            if (file_exists(implode(DIRECTORY_SEPARATOR, [$this->root, 'file.png']))) {
                unlink(implode(DIRECTORY_SEPARATOR, [$this->root, 'file.png']));
            }
            if (file_exists(implode(DIRECTORY_SEPARATOR, [$this->root, 'output.png']))) {
                unlink(implode(DIRECTORY_SEPARATOR, [$this->root, 'output.png']));
            }

            // Unmock header() and imagepng()
            HeaderMock::set(null);
            ImagePngMock::set(null);
        }

        #[Depends('testPng')]
        public function testJpg()
        {
            $tool = new \Dotclear\Helper\File\Image\ImageTools();

            $tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.jpg']));

            $this->assertEquals(
                65,
                $tool->getH()
            );
            $this->assertEquals(
                240,
                $tool->getW()
            );

            $that = $this;

            // Mock header()
            HeaderMock::set(function (string $header, bool $replace = true, int $response_code = 0) use ($that) {
                $that->assertContains(
                    $header,
                    [
                        'Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
                        'Content-type: image/jpeg',
                    ]
                );
            });

            // Mock imagejpeg()
            ImageJpegMock::set(function (GdImage $image, $file = null, int $quality = -1) use ($that) {
                $file ??= implode(DIRECTORY_SEPARATOR, [$that->root, 'output.jpg']);

                return \imagejpeg($image, $file, $quality);
            });

            $this->assertTrue(
                $tool->output('jpg', implode(DIRECTORY_SEPARATOR, [$this->root, 'file.jpg']), 50)
            );
            $this->assertTrue(
                $tool->output('jpg', null, 50)
            );
            $this->assertFileExists(
                implode(DIRECTORY_SEPARATOR, [$this->root, 'file.jpg'])
            );
            $this->assertFileExists(
                implode(DIRECTORY_SEPARATOR, [$this->root, 'output.jpg'])
            );

            $tool->close();

            if (file_exists(implode(DIRECTORY_SEPARATOR, [$this->root, 'file.jpg']))) {
                unlink(implode(DIRECTORY_SEPARATOR, [$this->root, 'file.jpg']));
            }
            if (file_exists(implode(DIRECTORY_SEPARATOR, [$this->root, 'output.jpg']))) {
                unlink(implode(DIRECTORY_SEPARATOR, [$this->root, 'output.jpg']));
            }

            // Unmock header() and imagejpeg()
            HeaderMock::set(null);
            ImageJpegMock::set(null);
        }

        #[Depends('testJpg')]
        public function testGif()
        {
            $tool = new \Dotclear\Helper\File\Image\ImageTools();

            $tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.gif']));

            $this->assertEquals(
                65,
                $tool->getH()
            );
            $this->assertEquals(
                240,
                $tool->getW()
            );

            $that = $this;

            // Mock header()
            HeaderMock::set(function (string $header, bool $replace = true, int $response_code = 0) use ($that) {
                $that->assertContains(
                    $header,
                    [
                        'Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
                        'Content-type: image/gif',
                    ]
                );
            });

            // Mock imagegif()
            ImageGifMock::set(function (GdImage $image, $file = null) use ($that) {
                $file ??= implode(DIRECTORY_SEPARATOR, [$that->root, 'output.gif']);

                return \imagegif($image, $file);
            });

            $this->assertTrue(
                $tool->output('gif', implode(DIRECTORY_SEPARATOR, [$this->root, 'file.gif']))
            );
            $this->assertTrue(
                $tool->output('gif', null)
            );
            $this->assertFileExists(
                implode(DIRECTORY_SEPARATOR, [$this->root, 'file.gif'])
            );
            $this->assertFileExists(
                implode(DIRECTORY_SEPARATOR, [$this->root, 'output.gif'])
            );

            $tool->close();

            if (file_exists(implode(DIRECTORY_SEPARATOR, [$this->root, 'file.gif']))) {
                unlink(implode(DIRECTORY_SEPARATOR, [$this->root, 'file.gif']));
            }
            if (file_exists(implode(DIRECTORY_SEPARATOR, [$this->root, 'output.gif']))) {
                unlink(implode(DIRECTORY_SEPARATOR, [$this->root, 'output.gif']));
            }

            // Unmock header() and imagegif()
            HeaderMock::set(null);
            ImageGifMock::set(null);
        }

        #[Depends('testGif')]
        public function testWebp()
        {
            $tool = new \Dotclear\Helper\File\Image\ImageTools();

            $tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.webp']));

            $this->assertEquals(
                65,
                $tool->getH()
            );
            $this->assertEquals(
                240,
                $tool->getW()
            );

            $that = $this;

            // Mock header()
            HeaderMock::set(function (string $header, bool $replace = true, int $response_code = 0) use ($that) {
                $that->assertContains(
                    $header,
                    [
                        'Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
                        'Content-type: image/webp',
                    ]
                );
            });

            // Mock imagewebp()
            ImageWebpMock::set(function (GdImage $image, $file = null, int $quality = -1) use ($that) {
                $file ??= implode(DIRECTORY_SEPARATOR, [$that->root, 'output.webp']);

                return \imagewebp($image, $file, $quality);
            });

            $this->assertTrue(
                $tool->output('webp', implode(DIRECTORY_SEPARATOR, [$this->root, 'file.webp']), 90)
            );
            $this->assertTrue(
                $tool->output('webp', null, 90)
            );
            $this->assertFileExists(
                implode(DIRECTORY_SEPARATOR, [$this->root, 'file.webp'])
            );
            $this->assertFileExists(
                implode(DIRECTORY_SEPARATOR, [$this->root, 'output.webp'])
            );

            $tool->close();

            if (file_exists(implode(DIRECTORY_SEPARATOR, [$this->root, 'file.webp']))) {
                unlink(implode(DIRECTORY_SEPARATOR, [$this->root, 'file.webp']));
            }
            if (file_exists(implode(DIRECTORY_SEPARATOR, [$this->root, 'output.webp']))) {
                unlink(implode(DIRECTORY_SEPARATOR, [$this->root, 'output.webp']));
            }

            // Unmock header() and imagewebp()
            HeaderMock::set(null);
            ImageWebpMock::set(null);
        }

        #[Depends('testWebp')]
        public function testAvif()
        {
            $tool = new \Dotclear\Helper\File\Image\ImageTools();

            $tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.avif']));

            $this->assertEquals(
                65,
                $tool->getH()
            );
            $this->assertEquals(
                240,
                $tool->getW()
            );

            $that = $this;

            // Mock header()
            HeaderMock::set(function (string $header, bool $replace = true, int $response_code = 0) use ($that) {
                $that->assertContains(
                    $header,
                    [
                        'Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
                        'Content-type: image/avif',
                    ]
                );
            });

            // Mock imageavif()
            ImageAvifMock::set(function (GdImage $image, $file = null, int $quality = -1, int $speed = -1) use ($that) {
                $file ??= implode(DIRECTORY_SEPARATOR, [$that->root, 'output.avif']);

                return \imageavif($image, $file, $quality, $speed);
            });

            $this->assertTrue(
                $tool->output('avif', implode(DIRECTORY_SEPARATOR, [$this->root, 'file.avif']), 90)
            );
            $this->assertTrue(
                $tool->output('avif', null)
            );
            $this->assertFileExists(
                implode(DIRECTORY_SEPARATOR, [$this->root, 'file.avif'])
            );
            $this->assertFileExists(
                implode(DIRECTORY_SEPARATOR, [$this->root, 'output.avif'])
            );

            $tool->close();

            if (file_exists(implode(DIRECTORY_SEPARATOR, [$this->root, 'file.avif']))) {
                unlink(implode(DIRECTORY_SEPARATOR, [$this->root, 'file.avif']));
            }
            if (file_exists(implode(DIRECTORY_SEPARATOR, [$this->root, 'output.avif']))) {
                unlink(implode(DIRECTORY_SEPARATOR, [$this->root, 'output.avif']));
            }

            // Unmock header() and imageavif()
            HeaderMock::set(null);
            ImageAvifMock::set(null);
        }

        public function testBmp()
        {
            $tool = new \Dotclear\Helper\File\Image\ImageTools();

            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Unable to load image');

            $tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.bmp']));
        }

        public function testUnknown()
        {
            $tool = new \Dotclear\Helper\File\Image\ImageTools();

            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Image doest not exists');

            $tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'unknown.jpg']));
        }

        public function testNoGD()
        {
            // Mock function_exists()
            FunctionExistsMock::set(fn (string $function): bool => $function === 'imagegd2' ? false : \function_exists($function));

            $this->expectException(Exception::class);
            $this->expectExceptionMessage('GD is not installed');

            $tool = new \Dotclear\Helper\File\Image\ImageTools();

            // Unmock function_exists()
            FunctionExistsMock::set(null);
        }

        #[Depends('testNoGD')]
        public function testWebpDisabled()
        {
            $tool = new \Dotclear\Helper\File\Image\ImageTools();

            // Mock function_exists()
            FunctionExistsMock::set(fn (string $function): bool => $function === 'imagecreatefromwebp' ? false : \function_exists($function));

            $this->expectException(Exception::class);
            $this->expectExceptionMessage('WebP image format not supported');

            $tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.webp']));

            // Unmock function_exists()
            FunctionExistsMock::set(null);
        }

        #[Depends('testWebpDisabled')]
        public function testAvifDisabled()
        {
            $tool = new \Dotclear\Helper\File\Image\ImageTools();

            // Mock function_exists()
            FunctionExistsMock::set(fn (string $function): bool => $function === 'imagecreatefromavif' ? false : \function_exists($function));

            $this->expectException(Exception::class);
            $this->expectExceptionMessage('AVIF image format not supported');

            $tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.avif']));

            // Unmock function_exists()
            FunctionExistsMock::set(null);
        }

        public function testResizeRatio()
        {
            $tool = new \Dotclear\Helper\File\Image\ImageTools();

            $tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.jpg']));

            $this->assertEquals(
                65,
                $tool->getH()
            );
            $this->assertEquals(
                240,
                $tool->getW()
            );
            $this->assertTrue(
                $tool->resize(120, 32)
            );
            $this->assertEquals(
                32,
                $tool->getH()
            );
            $this->assertEquals(
                118,    // Ratio applied
                $tool->getW()
            );

            $tool->close();
        }

        public function testResizePercent()
        {
            $tool = new \Dotclear\Helper\File\Image\ImageTools();

            $tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.jpg']));

            $this->assertEquals(
                65,
                $tool->getH()
            );
            $this->assertEquals(
                240,
                $tool->getW()
            );
            $this->assertTrue(
                $tool->resize('50%', '50%')
            );
            $this->assertEquals(
                32,
                $tool->getH()
            );
            $this->assertEquals(
                120,
                $tool->getW()
            );
            $tool->close();
        }

        public function testResizeCrop()
        {
            $tool = new \Dotclear\Helper\File\Image\ImageTools();

            $tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.jpg']));

            $this->assertEquals(
                65,
                $tool->getH()
            );
            $this->assertEquals(
                240,
                $tool->getW()
            );
            $this->assertTrue(
                $tool->resize('48', '48', 'crop')
            );
            $this->assertEquals(
                48,
                $tool->getH()
            );
            $this->assertEquals(
                48,
                $tool->getW()
            );
            $tool->close();
        }

        public function testResizeForce()
        {
            $tool = new \Dotclear\Helper\File\Image\ImageTools();

            $tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.jpg']));

            $this->assertEquals(
                65,
                $tool->getH()
            );
            $this->assertEquals(
                240,
                $tool->getW()
            );
            $this->assertTrue(
                $tool->resize(120, 32, 'force')
            );
            $this->assertEquals(
                32,
                $tool->getH()
            );
            $this->assertEquals(
                120,
                $tool->getW()
            );
            $tool->close();
        }

        public function testResizeRatioWidth()
        {
            $tool = new \Dotclear\Helper\File\Image\ImageTools();

            $tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.jpg']));

            $this->assertEquals(
                65,
                $tool->getH()
            );
            $this->assertEquals(
                240,
                $tool->getW()
            );
            $this->assertTrue(
                $tool->resize(100, 0)
            );
            $this->assertEquals(
                27,     // Ratio applied
                $tool->getH()
            );
            $this->assertEquals(
                100,
                $tool->getW()
            );
            $tool->close();
        }

        public function testResizeRatioHeight()
        {
            $tool = new \Dotclear\Helper\File\Image\ImageTools();

            $tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.jpg']));

            $this->assertEquals(
                65,
                $tool->getH()
            );
            $this->assertEquals(
                240,
                $tool->getW()
            );
            $this->assertTrue(
                $tool->resize(0, 28)
            );
            $this->assertEquals(
                28,
                $tool->getH()
            );
            $this->assertEquals(
                103,    // Ratio applied
                $tool->getW()
            );

            $tool->close();
        }

        public function testResizeRatioWidthForce()
        {
            $tool = new \Dotclear\Helper\File\Image\ImageTools();

            $tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.jpg']));

            $this->assertEquals(
                65,
                $tool->getH()
            );
            $this->assertEquals(
                240,
                $tool->getW()
            );
            $this->assertTrue(
                $tool->resize(100, 0, 'force')
            );
            $this->assertEquals(
                27,     // Ratio applied
                $tool->getH()
            );
            $this->assertEquals(
                100,
                $tool->getW()
            );
            $tool->close();
        }

        public function testResizeRatioHeightForce()
        {
            $tool = new \Dotclear\Helper\File\Image\ImageTools();

            $tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.jpg']));

            $this->assertEquals(
                65,
                $tool->getH()
            );
            $this->assertEquals(
                240,
                $tool->getW()
            );
            $this->assertTrue(
                $tool->resize(0, 28, 'force')
            );
            $this->assertEquals(
                28,
                $tool->getH()
            );
            $this->assertEquals(
                103,    // Ratio applied
                $tool->getW()
            );

            $tool->close();
        }

        public function testResizeRatioWidthForceNotExpand()
        {
            $tool = new \Dotclear\Helper\File\Image\ImageTools();

            $tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.jpg']));

            $this->assertEquals(
                65,
                $tool->getH()
            );
            $this->assertEquals(
                240,
                $tool->getW()
            );
            $this->assertTrue(
                $tool->resize(0, 480, 'force')
            );
            $this->assertEquals(
                65,     // Ratio applied
                $tool->getH()
            );
            $this->assertEquals(
                240,
                $tool->getW()
            );
            $tool->close();
        }

        public function testResizeRatioNotExpand()
        {
            $tool = new \Dotclear\Helper\File\Image\ImageTools();

            $tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.jpg']));

            $this->assertEquals(
                65,
                $tool->getH()
            );
            $this->assertEquals(
                240,
                $tool->getW()
            );
            $this->assertTrue(
                $tool->resize(0, 480)
            );
            $this->assertEquals(
                65,
                $tool->getH()
            );
            $this->assertEquals(
                240,    // Ratio applied
                $tool->getW()
            );

            $tool->close();
        }

        public function testResizeRatioExpand()
        {
            $tool = new \Dotclear\Helper\File\Image\ImageTools();

            $tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.jpg']));

            $this->assertEquals(
                65,
                $tool->getH()
            );
            $this->assertEquals(
                240,
                $tool->getW()
            );
            $this->assertTrue(
                $tool->resize(0, 480, 'ratio', true)
            );
            $this->assertEquals(
                480,
                $tool->getH()
            );
            $this->assertEquals(
                1772,   // Ratio applied
                $tool->getW()
            );

            $tool->close();
        }

        public function testResizeLargeRatioExpand()
        {
            $tool = new \Dotclear\Helper\File\Image\ImageTools();

            $tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.jpg']));

            $this->assertEquals(
                65,
                $tool->getH()
            );
            $this->assertEquals(
                240,    // Ratio = 3,69
                $tool->getW()
            );
            $this->assertTrue(
                $tool->resize(800, 200, 'crop', true)
            );
            $this->assertEquals(
                200,
                $tool->getH()
            );
            $this->assertEquals(
                800,    // Ratio = 4
                $tool->getW()
            );

            $tool->close();
        }
    }
}
