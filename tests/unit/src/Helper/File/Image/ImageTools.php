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
class ImageTools extends atoum
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

    public function testPng()
    {
        $tool = new \Dotclear\Helper\File\Image\ImageTools();

        $that = $this;
        $this
            ->given($tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.png'])))
            ->integer($tool->getH())
            ->isEqualTo(65)
            ->integer($tool->getW())
            ->isEqualTo(240)
            ->if($this->function->header = function (string $header, bool $replace = true, int $response_code = 0) use ($that) {
                $that
                    ->boolean(in_array($header, [
                        'Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
                        'Content-type: image/png',
                    ]))
                    ->isTrue()
                ;
            })
            ->and($this->function->imagepng = function ($image, $file = null, int $quality = -1, int $filters = -1) use ($that) {
                $file ??= implode(DIRECTORY_SEPARATOR, [$that->root, 'output.png']);
                \imagepng($image, $file, $quality, $filters);
            })
            ->boolean($tool->output('png', implode(DIRECTORY_SEPARATOR, [$this->root, 'file.png'])))
            ->isTrue()
            ->boolean($tool->output('png', null))
            ->isTrue()
            ->boolean(file_exists(implode(DIRECTORY_SEPARATOR, [$this->root, 'file.png'])))
            ->isTrue()
            ->boolean(file_exists(implode(DIRECTORY_SEPARATOR, [$this->root, 'output.png'])))
            ->isTrue()
            ->then($tool->close())
        ;

        if (file_exists(implode(DIRECTORY_SEPARATOR, [$this->root, 'file.png']))) {
            unlink(implode(DIRECTORY_SEPARATOR, [$this->root, 'file.png']));
        }
        if (file_exists(implode(DIRECTORY_SEPARATOR, [$this->root, 'output.png']))) {
            unlink(implode(DIRECTORY_SEPARATOR, [$this->root, 'output.png']));
        }
    }

    public function testJpg()
    {
        $tool = new \Dotclear\Helper\File\Image\ImageTools();

        $that = $this;
        $this
            ->given($tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.jpg'])))
            ->integer($tool->getH())
            ->isEqualTo(65)
            ->integer($tool->getW())
            ->isEqualTo(240)
            ->if($this->function->header = function (string $header, bool $replace = true, int $response_code = 0) use ($that) {
                $that
                    ->boolean(in_array($header, [
                        'Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
                        'Content-type: image/jpeg',
                    ]))
                    ->isTrue()
                ;
            })
            ->and($this->function->imagejpeg = function ($image, $file = null, ?int $quality = null) use ($that) {
                $file ??= implode(DIRECTORY_SEPARATOR, [$that->root, 'output.jpg']);
                \imagejpeg($image, $file, $quality);
            })
            ->boolean($tool->output('jpg', implode(DIRECTORY_SEPARATOR, [$this->root, 'file.jpg']), 50))
            ->isTrue()
            ->boolean($tool->output('jpg', null, 50))
            ->isTrue()
            ->boolean(file_exists(implode(DIRECTORY_SEPARATOR, [$this->root, 'file.jpg'])))
            ->isTrue()
            ->boolean(file_exists(implode(DIRECTORY_SEPARATOR, [$this->root, 'output.jpg'])))
            ->isTrue()
            ->then($tool->close())
        ;

        if (file_exists(implode(DIRECTORY_SEPARATOR, [$this->root, 'file.jpg']))) {
            unlink(implode(DIRECTORY_SEPARATOR, [$this->root, 'file.jpg']));
        }
        if (file_exists(implode(DIRECTORY_SEPARATOR, [$this->root, 'output.jpg']))) {
            unlink(implode(DIRECTORY_SEPARATOR, [$this->root, 'output.jpg']));
        }
    }

    public function testGif()
    {
        $tool = new \Dotclear\Helper\File\Image\ImageTools();

        $that = $this;
        $this
            ->given($tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.gif'])))
            ->integer($tool->getH())
            ->isEqualTo(65)
            ->integer($tool->getW())
            ->isEqualTo(240)
            ->if($this->function->header = function (string $header, bool $replace = true, int $response_code = 0) use ($that) {
                $that
                    ->boolean(in_array($header, [
                        'Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
                        'Content-type: image/gif',
                    ]))
                    ->isTrue()
                ;
            })
            ->and($this->function->imagegif = function ($image, $file = null) use ($that) {
                $file ??= implode(DIRECTORY_SEPARATOR, [$that->root, 'output.gif']);
                \imagegif($image, $file);
            })
            ->boolean($tool->output('gif', implode(DIRECTORY_SEPARATOR, [$this->root, 'file.gif'])))
            ->isTrue()
            ->boolean($tool->output('gif', null))
            ->isTrue()
            ->boolean(file_exists(implode(DIRECTORY_SEPARATOR, [$this->root, 'file.gif'])))
            ->isTrue()
            ->boolean(file_exists(implode(DIRECTORY_SEPARATOR, [$this->root, 'output.gif'])))
            ->isTrue()
            ->then($tool->close())
        ;

        if (file_exists(implode(DIRECTORY_SEPARATOR, [$this->root, 'file.gif']))) {
            unlink(implode(DIRECTORY_SEPARATOR, [$this->root, 'file.gif']));
        }
        if (file_exists(implode(DIRECTORY_SEPARATOR, [$this->root, 'output.gif']))) {
            unlink(implode(DIRECTORY_SEPARATOR, [$this->root, 'output.gif']));
        }
    }

    public function testWebp()
    {
        $tool = new \Dotclear\Helper\File\Image\ImageTools();

        $that = $this;
        $this
            ->given($tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.webp'])))
            ->integer($tool->getH())
            ->isEqualTo(65)
            ->integer($tool->getW())
            ->isEqualTo(240)
            ->if($this->function->header = function (string $header, bool $replace = true, int $response_code = 0) use ($that) {
                $that
                    ->boolean(in_array($header, [
                        'Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
                        'Content-type: image/webp',
                    ]))
                    ->isTrue()
                ;
            })
            ->and($this->function->imagewebp = function ($image, $file = null, int $quality = 80) use ($that) {
                $file ??= implode(DIRECTORY_SEPARATOR, [$that->root, 'output.webp']);
                \imagewebp($image, $file, $quality);
            })
            ->boolean($tool->output('webp', null, 90))
            ->isTrue()
            ->boolean($tool->output('webp', implode(DIRECTORY_SEPARATOR, [$this->root, 'file.webp']), 90))
            ->isTrue()
            ->boolean(file_exists(implode(DIRECTORY_SEPARATOR, [$this->root, 'file.webp'])))
            ->isTrue()
            ->boolean(file_exists(implode(DIRECTORY_SEPARATOR, [$this->root, 'output.webp'])))
            ->isTrue()
            ->then($tool->close())
        ;

        if (file_exists(implode(DIRECTORY_SEPARATOR, [$this->root, 'file.webp']))) {
            unlink(implode(DIRECTORY_SEPARATOR, [$this->root, 'file.webp']));
        }
        if (file_exists(implode(DIRECTORY_SEPARATOR, [$this->root, 'output.webp']))) {
            unlink(implode(DIRECTORY_SEPARATOR, [$this->root, 'output.webp']));
        }
    }

    public function testAvif()
    {
        $tool = new \Dotclear\Helper\File\Image\ImageTools();

        $that = $this;
        $this
            ->given($tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.avif'])))
            ->integer($tool->getH())
            ->isEqualTo(65)
            ->integer($tool->getW())
            ->isEqualTo(240)
            ->if($this->function->header = function (string $header, bool $replace = true, int $response_code = 0) use ($that) {
                $that
                    ->boolean(in_array($header, [
                        'Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
                        'Content-type: image/avif',
                    ]))
                    ->isTrue()
                ;
            })
            ->and($this->function->imageavif = function ($image, $file = null, int $quality = -1, int $speed = -1) use ($that) {
                $file ??= implode(DIRECTORY_SEPARATOR, [$that->root, 'output.avif']);
                \imageavif($image, $file, $quality, $speed);
            })
            ->boolean($tool->output('avif', implode(DIRECTORY_SEPARATOR, [$this->root, 'file.avif'])))
            ->isTrue()
            ->boolean($tool->output('avif', null))
            ->isTrue()
            ->boolean(file_exists(implode(DIRECTORY_SEPARATOR, [$this->root, 'file.avif'])))
            ->isTrue()
            ->boolean(file_exists(implode(DIRECTORY_SEPARATOR, [$this->root, 'output.avif'])))
            ->isTrue()
            ->then($tool->close())
        ;

        if (file_exists(implode(DIRECTORY_SEPARATOR, [$this->root, 'file.avif']))) {
            unlink(implode(DIRECTORY_SEPARATOR, [$this->root, 'file.avif']));
        }
        if (file_exists(implode(DIRECTORY_SEPARATOR, [$this->root, 'output.avif']))) {
            unlink(implode(DIRECTORY_SEPARATOR, [$this->root, 'output.avif']));
        }
    }

    public function testBmp()
    {
        $tool = new \Dotclear\Helper\File\Image\ImageTools();

        $this
            ->exception(function () use ($tool) {
                $tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.bmp']));
            })
            ->hasMessage('Unable to load image')
        ;
    }

    public function testUnknown()
    {
        $tool = new \Dotclear\Helper\File\Image\ImageTools();

        $this
            ->exception(function () use ($tool) {
                $tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'unknown.jpg']));
            })
            ->hasMessage('Image doest not exists')
        ;
    }

    public function testNoGD()
    {
        $this
            ->if($this->function->function_exists = function (string $function) {
                if ($function === 'imagegd2') {
                    return false;
                }

                return true;
            })
            ->exception(function () {
                $tool = new \Dotclear\Helper\File\Image\ImageTools();
            })
            ->hasMessage('GD is not installed')
        ;
    }

    public function testWebpDisabled()
    {
        $tool = new \Dotclear\Helper\File\Image\ImageTools();

        $this
            ->if($this->function->function_exists = function (string $function) {
                if ($function === 'imagecreatefromwebp') {
                    return false;
                }

                return true;
            })
            ->exception(function () use ($tool) {
                $tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.webp']));
            })
            ->hasMessage('WebP image format not supported')
        ;
    }

    public function testAvifDisabled()
    {
        $tool = new \Dotclear\Helper\File\Image\ImageTools();

        $this
            ->if($this->function->function_exists = function (string $function) {
                if ($function === 'imagecreatefromavif') {
                    return false;
                }

                return true;
            })
            ->exception(function () use ($tool) {
                $tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.avif']));
            })
            ->hasMessage('AVIF image format not supported')
        ;
    }

    public function testResizeRatio()
    {
        $tool = new \Dotclear\Helper\File\Image\ImageTools();

        $this
            ->given($tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.jpg'])))
            ->integer($tool->getH())
            ->isEqualTo(65)
            ->integer($tool->getW())
            ->isEqualTo(240)
            ->boolean($tool->resize(120, 32))
            ->isTrue()
            ->integer($tool->getH())
            ->isEqualTo(32)
            ->integer($tool->getW())
            ->isEqualTo(118)    // Ratio applied
            ->then($tool->close())
        ;
    }

    public function testResizePercent()
    {
        $tool = new \Dotclear\Helper\File\Image\ImageTools();

        $this
            ->given($tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.jpg'])))
            ->integer($tool->getH())
            ->isEqualTo(65)
            ->integer($tool->getW())
            ->isEqualTo(240)
            ->boolean($tool->resize('50%', '50%'))
            ->isTrue()
            ->integer($tool->getH())
            ->isEqualTo(32)
            ->integer($tool->getW())
            ->isEqualTo(120)
            ->then($tool->close())
        ;
    }

    public function testResizeCrop()
    {
        $tool = new \Dotclear\Helper\File\Image\ImageTools();

        $this
            ->given($tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.jpg'])))
            ->integer($tool->getH())
            ->isEqualTo(65)
            ->integer($tool->getW())
            ->isEqualTo(240)
            ->boolean($tool->resize('48', '48', 'crop'))
            ->isTrue()
            ->integer($tool->getH())
            ->isEqualTo(48)
            ->integer($tool->getW())
            ->isEqualTo(48)
            ->then($tool->close())
        ;
    }

    public function testResizeForce()
    {
        $tool = new \Dotclear\Helper\File\Image\ImageTools();

        $this
            ->given($tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.jpg'])))
            ->integer($tool->getH())
            ->isEqualTo(65)
            ->integer($tool->getW())
            ->isEqualTo(240)
            ->boolean($tool->resize(120, 32, 'force'))
            ->isTrue()
            ->integer($tool->getH())
            ->isEqualTo(32)
            ->integer($tool->getW())
            ->isEqualTo(120)
            ->then($tool->close())
        ;
    }

    public function testResizeRatioWidth()
    {
        $tool = new \Dotclear\Helper\File\Image\ImageTools();

        $this
            ->given($tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.jpg'])))
            ->integer($tool->getH())
            ->isEqualTo(65)
            ->integer($tool->getW())
            ->isEqualTo(240)
            ->boolean($tool->resize(100, 0))
            ->isTrue()
            ->integer($tool->getH())
            ->isEqualTo(27)     // Ratio applied
            ->integer($tool->getW())
            ->isEqualTo(100)
            ->then($tool->close())
        ;
    }

    public function testResizeRatioHeight()
    {
        $tool = new \Dotclear\Helper\File\Image\ImageTools();

        $this
            ->given($tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.jpg'])))
            ->integer($tool->getH())
            ->isEqualTo(65)
            ->integer($tool->getW())
            ->isEqualTo(240)
            ->boolean($tool->resize(0, 28))
            ->isTrue()
            ->integer($tool->getH())
            ->isEqualTo(28)
            ->integer($tool->getW())
            ->isEqualTo(103)    // Ratio applied
            ->then($tool->close())
        ;
    }

    public function testResizeRatioWidthForce()
    {
        $tool = new \Dotclear\Helper\File\Image\ImageTools();

        $this
            ->given($tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.jpg'])))
            ->integer($tool->getH())
            ->isEqualTo(65)
            ->integer($tool->getW())
            ->isEqualTo(240)
            ->boolean($tool->resize(100, 0, 'force'))
            ->isTrue()
            ->integer($tool->getH())
            ->isEqualTo(27)     // Ratio applied
            ->integer($tool->getW())
            ->isEqualTo(100)
            ->then($tool->close())
        ;
    }

    public function testResizeRatioHeightForce()
    {
        $tool = new \Dotclear\Helper\File\Image\ImageTools();

        $this
            ->given($tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.jpg'])))
            ->integer($tool->getH())
            ->isEqualTo(65)
            ->integer($tool->getW())
            ->isEqualTo(240)
            ->boolean($tool->resize(0, 28, 'force'))
            ->isTrue()
            ->integer($tool->getH())
            ->isEqualTo(28)
            ->integer($tool->getW())
            ->isEqualTo(103)    // Ratio applied
            ->then($tool->close())
        ;
    }

    public function testResizeRatioWidthForceNotExpand()
    {
        $tool = new \Dotclear\Helper\File\Image\ImageTools();

        $this
            ->given($tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.jpg'])))
            ->integer($tool->getH())
            ->isEqualTo(65)
            ->integer($tool->getW())
            ->isEqualTo(240)
            ->boolean($tool->resize(0, 480, 'force'))
            ->isTrue()
            ->integer($tool->getH())
            ->isEqualTo(65)     // Ratio applied
            ->integer($tool->getW())
            ->isEqualTo(240)
            ->then($tool->close())
        ;
    }

    public function testResizeRatioNotExpand()
    {
        $tool = new \Dotclear\Helper\File\Image\ImageTools();

        $this
            ->given($tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.jpg'])))
            ->integer($tool->getH())
            ->isEqualTo(65)
            ->integer($tool->getW())
            ->isEqualTo(240)
            ->boolean($tool->resize(0, 480))
            ->isTrue()
            ->integer($tool->getH())
            ->isEqualTo(65)
            ->integer($tool->getW())
            ->isEqualTo(240)    // Ratio applied
            ->then($tool->close())
        ;
    }

    public function testResizeRatioExpand()
    {
        $tool = new \Dotclear\Helper\File\Image\ImageTools();

        $this
            ->given($tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.jpg'])))
            ->integer($tool->getH())
            ->isEqualTo(65)
            ->integer($tool->getW())
            ->isEqualTo(240)
            ->boolean($tool->resize(0, 480, 'ratio', true))
            ->isTrue()
            ->integer($tool->getH())
            ->isEqualTo(480)
            ->integer($tool->getW())
            ->isEqualTo(1772)    // Ratio applied
            ->then($tool->close())
        ;
    }

    public function testResizeLargeRatioExpand()
    {
        $tool = new \Dotclear\Helper\File\Image\ImageTools();

        $this
            ->given($tool->loadImage(implode(DIRECTORY_SEPARATOR, [$this->root, 'image.jpg'])))
            ->integer($tool->getH())
            ->isEqualTo(65)
            ->integer($tool->getW())
            ->isEqualTo(240)    // Ratio = 3,69
            ->boolean($tool->resize(800, 200, 'crop', true))
            ->isTrue()
            ->integer($tool->getH())
            ->isEqualTo(200)
            ->integer($tool->getW())
            ->isEqualTo(800)    // Ratio = 4
            ->then($tool->close())
        ;
    }
}
