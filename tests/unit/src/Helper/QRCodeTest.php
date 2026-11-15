<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper;

use Dotclear\Helper\QRCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(QRCode::class)]
class QRCodeTest extends TestCase
{
    public function testInitializationAndMagicAccessors(): void
    {
        $qr = QRCode::init('payload', ['sf' => 3]);

        $this->assertSame('payload', $qr->data);
        $this->assertSame(['s' => 'qrl', 'sf' => 3], $qr->options);
        $this->assertSame(3, $qr->sf);
        $this->assertNull($qr->unknown);

        $qr->data('updated');
        $qr->sf = 5;
        $qr->unknown = 'ignored';
        $qr->w = 'invalid';
        $qr->h = [];

        $this->assertSame('updated', $qr->data);
        $this->assertSame(5, $qr->sf);
        $this->assertNull($qr->unknown);
        $this->assertNull($qr->w);
        $this->assertNull($qr->h);
    }

    public function testMagicSetterAcceptsDeclaredScalarTypes(): void
    {
        $qr = new QRCode();
        $options = [
            'w' => 10, 'h' => 11, 'sf' => 2, 'sx' => 3, 'sy' => 4,
            'p' => 1, 'pv' => 2, 'ph' => 3, 'pt' => 4, 'pl' => 5,
            'pr' => 6, 'pb' => 7, 'bc' => '#FFFFFF', 'dc' => '#CCCCCC',
            'md' => 0.5, 'wq' => 1, 'wm' => 2, 's' => 'qrm',
        ];

        foreach ($options as $name => $value) {
            $qr->{$name} = $value;
            $this->assertSame($value, $qr->{$name});
        }
    }

    public function testFluentSettersAndUnsetters(): void
    {
        $qr = new QRCode('initial');

        $this->assertSame($qr, $qr->data('next'));
        $this->assertSame($qr, $qr->size(64));
        $this->assertSame($qr, $qr->scale(3));
        $this->assertSame($qr, $qr->padding(2));
        $this->assertSame($qr, $qr->color('#123456'));
        $this->assertSame($qr, $qr->background('#ABCDEF'));
        $this->assertSame($qr, $qr->quietZone(0));
        $this->assertSame($qr, $qr->density(0.5));

        $this->assertSame('next', $qr->data);
        $this->assertSame(64, $qr->w);
        $this->assertSame(64, $qr->h);
        $this->assertSame(3, $qr->sf);
        $this->assertSame(2, $qr->p);
        $this->assertSame('#123456', $qr->fc);
        $this->assertSame('#ABCDEF', $qr->bc);
        $this->assertSame(0, $qr->wq);
        $this->assertSame(0.5, $qr->md);

        $qr->data(null)->size(null)->scale(null)->padding(null)
            ->color(null)->background(null)->quietZone(null)->density(null);

        $this->assertFalse((new \ReflectionProperty(QRCode::class, 'data'))->isInitialized($qr));
        $this->assertNull($qr->w);
        $this->assertNull($qr->h);
        $this->assertNull($qr->sf);
        $this->assertNull($qr->p);
        $this->assertNull($qr->fc);
        $this->assertNull($qr->bc);
        $this->assertNull($qr->wq);
        $this->assertNull($qr->md);
    }

    public function testErrorCorrectionLevelsAndOptions(): void
    {
        $qr = new QRCode();

        $this->assertSame('qrl', $qr->levelLow()->s);
        $this->assertSame('qrm', $qr->levelMid()->s);
        $this->assertSame('qrq', $qr->levelQrt()->s);
        $this->assertSame('qrh', $qr->levelHigh()->s);
        $qr->levelAuto();
        $this->assertNull($qr->s);

        $this->assertSame($qr, $qr->options(['sf' => 2]));
        $this->assertSame(['sf' => 2], $qr->options);
        $this->assertSame(2, $qr->sf);
        $this->assertNull($qr->s);
    }

    public function testEncodeDispatchLevelsAndFallback(): void
    {
        $qr = new TestableQRCode();

        foreach (['qrl', 'QRM', 'QR-Q', 'qrh', 'unknown'] as $level) {
            $code = $qr->invokeProtected('dispatch_encode', 'payload', ['s' => $level]);
            $this->assertSame('m', $code['g']);
            $this->assertSame([4, 4, 4, 4], $code['q']);
            $this->assertSame($code['s'][0], $code['s'][1]);
        }

        $this->assertSame(
            $qr->invokeProtected('dispatch_encode', 'payload', ['s' => 'qrl']),
            $qr->invokeProtected('dispatch_encode', 'payload', [])
        );

        $largerCode = $qr->invokeProtected('dispatch_encode', str_repeat('A', 30), ['s' => 'qrl']);
        $this->assertGreaterThan(21, $largerCode['s'][0]);
    }

    public function testModeDetectionAndVersionSelection(): void
    {
        $qr = new TestableQRCode();

        $this->assertSame(0, $qr->invokeProtected('qr_detect_mode', '012345'));
        $this->assertSame(1, $qr->invokeProtected('qr_detect_mode', 'HELLO WORLD'));
        $this->assertSame(2, $qr->invokeProtected('qr_detect_mode', 'lowercase'));
        $this->assertSame(3, $qr->invokeProtected('qr_detect_mode', "\x81\x40"));
        $this->assertSame(1, $qr->invokeProtected('qr_detect_version', '', 0, 0));
        $this->assertSame(1, $qr->invokeProtected('qr_detect_version', "\x81\x40", 3, 0));
        $this->assertSame(40, $qr->invokeProtected('qr_detect_version', str_repeat('1', 10000), 0, 0));
    }

    public function testDataEncodersForAllVersionGroups(): void
    {
        $qr = new TestableQRCode();

        foreach ([0, 1, 2] as $group) {
            $this->assertNotEmpty($qr->invokeProtected('qr_encode_numeric', '1234567', $group));
            $this->assertNotEmpty($qr->invokeProtected('qr_encode_alphanumeric', 'AB12', $group));
            $this->assertNotEmpty($qr->invokeProtected('qr_encode_binary', 'a', $group));
            $this->assertNotEmpty($qr->invokeProtected('qr_encode_kanji', "\x81\x40", $group));
        }

        $this->assertNotEmpty($qr->invokeProtected('qr_encode_numeric', '12', 0));
        $this->assertNotEmpty($qr->invokeProtected('qr_encode_numeric', '1', 0));
        $this->assertNotEmpty($qr->invokeProtected('qr_encode_alphanumeric', 'A', 0));
        $this->assertNotEmpty($qr->invokeProtected('qr_encode_kanji', "\xE0\x40", 0));
        $this->assertNotEmpty($qr->invokeProtected('qr_encode_kanji', "\xEB\x40", 0));
        $this->assertNotEmpty($qr->invokeProtected('qr_encode_kanji', "\x00\x00", 0));
    }

    public function testFullEncodingAcrossModesAndCorrectionLevels(): void
    {
        $qr = new TestableQRCode();

        foreach (['123456', 'HELLO WORLD', 'lowercase', "\x81\x40"] as $data) {
            foreach ([0, 1, 2, 3] as $level) {
                $encoded = $qr->invokeProtected('qr_encode_data', $data, $level);
                $this->assertContains($encoded[0], [0, 1, 2, 3]);
                $this->assertGreaterThanOrEqual(1, $encoded[1]);
                $this->assertNotEmpty($encoded[2]);
                $this->assertNotEmpty($encoded[3]);
            }
        }
    }

    public function testSizeCalculationOptions(): void
    {
        $qr = new TestableQRCode();

        $defaults = $qr->invokeProtected('encode_and_calculate_size', 'data', []);
        $this->assertSame(1, $defaults[1][0]);
        $this->assertSame(1, $defaults[1][1]);
        $this->assertGreaterThan(0, $defaults[2]);
        $this->assertGreaterThan(0, $defaults[3]);

        $custom = $qr->invokeProtected('encode_and_calculate_size', 'data', [
            'wq' => 2, 'wm' => 3, 'sf' => 2, 'sx' => 4, 'sy' => 5,
            'p' => 1, 'pv' => 2, 'ph' => 3, 'pt' => 4, 'pl' => 5,
            'pr' => 6, 'pb' => 7, 'w' => 80, 'h' => 90,
        ]);

        $this->assertSame([2, 3], $custom[1]);
        $this->assertSame([80, 90, 5, 4, 69, 79], [$custom[2], $custom[3], $custom[4], $custom[5], $custom[6], $custom[7]]);
        $this->assertSame([27, 26], $qr->invokeProtected('calculate_size', ['q' => [1, 2, 3, 4], 's' => [5, 6]], [2, 3]));
    }

    public function testMatrixCreationAndFinalization(): void
    {
        $qr = new TestableQRCode();
        $data = array_fill(0, 10000, 1);

        foreach ([1, 2, 7] as $version) {
            [$size, $matrix] = $qr->invokeProtected('qr_create_matrix', $version, $data);
            $this->assertSame($version * 4 + 17, $size);
            $this->assertCount($size, $matrix);
            $this->assertCount($size, $matrix[0]);

            [$mask, $masked] = $qr->invokeProtected('qr_apply_best_mask', $matrix, $size);
            $this->assertGreaterThanOrEqual(0, $mask);
            $this->assertLessThanOrEqual(7, $mask);
            $this->assertCount($size, $masked);

            $final = $qr->invokeProtected('qr_finalize_matrix', $masked, $size, 0, $mask, $version);
            $this->assertContains($final[8][0], [0, 1]);
            $this->assertContains($final[0][0], [0, 1]);
        }

        foreach (range(0, 7) as $mask) {
            $this->assertIsBool($qr->invokeProtected('qr_mask', $mask, 2, 3));
        }
        $this->assertFalse($qr->invokeProtected('qr_mask', 8, 2, 3));
    }

    public function testErrorCorrectionBlockHelpers(): void
    {
        $qr = new TestableQRCode();

        $this->assertSame([[1, 2], [3, 4, 5]], $qr->invokeProtected('qr_ec_split', [1, 2, 3, 4, 5], [0, 2, 1, 2, 1, 3]));
        $this->assertSame([[], []], $qr->invokeProtected('qr_ec_split', [], [0, 2, 1, 2, 1, 3]));
        $this->assertSame([1, 3, 2], $qr->invokeProtected('qr_ec_interleave', [[1, 2], [3]]));
        $this->assertSame([], $qr->invokeProtected('qr_ec_interleave', []));
        $this->assertCount(7, $qr->invokeProtected('qr_ec_divide', [1, 2, 3], [0, 7]));
    }

    public function testPenaltyScoringPatterns(): void
    {
        $qr = new TestableQRCode();
        $matrix = array_fill(0, 11, array_fill(0, 11, 4));

        $this->assertGreaterThan(0, $qr->invokeProtected('qr_penalty_1', $matrix, 11));
        $this->assertGreaterThan(0, $qr->invokeProtected('qr_penalty_2', $matrix, 11));
        $this->assertGreaterThanOrEqual(0, $qr->invokeProtected('qr_penalty_4', $matrix, 11));

        foreach (['10111010000', '00001011101'] as $pattern) {
            $patternMatrix = array_fill(0, 11, array_fill(0, 11, 4));
            foreach (str_split($pattern) as $index => $bit) {
                $patternMatrix[0][$index] = $bit === '1' ? 5 : 4;
                $patternMatrix[$index][1] = $bit === '1' ? 5 : 4;
            }

            $this->assertGreaterThan(0, $qr->invokeProtected('qr_penalty_3', $patternMatrix, 11));
        }

        $this->assertGreaterThanOrEqual(0, $qr->invokeProtected('qr_penalty', $matrix, 11));
    }

    public function testPngRenderingAndConvenienceMethods(): void
    {
        $qr = new QRCode('render me', ['w' => 80, 'h' => 50, 'bc' => '#FFFFFF', 'fc' => '#123456', 'md' => 0.5]);
        $image = $qr->render_image();

        $this->assertInstanceOf(\GdImage::class, $image);
        $this->assertSame(80, imagesx($image));
        $this->assertSame(50, imagesy($image));

        $zeroSizeImage = (new ZeroSizeQRCode())->render_image();
        $this->assertInstanceOf(\GdImage::class, $zeroSizeImage);
        $this->assertSame(1, imagesx($zeroSizeImage));
        $this->assertSame(1, imagesy($zeroSizeImage));

        $this->assertSame('', (new FailedRenderQRCode())->raw());

        $raw = $qr->raw();
        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $raw);
        $this->assertStringStartsWith('data:image/png;base64,', $qr->base64());
        $this->assertStringStartsWith('data:image/png;base64,', QRCode::png('render me'));
    }
}

class TestableQRCode extends QRCode
{
    public function invokeProtected(string $method, mixed ...$arguments): mixed
    {
        return $this->{$method}(...$arguments);
    }
}

class ZeroSizeQRCode extends TestableQRCode
{
    protected function encode_and_calculate_size(string $data, array $options): array
    {
        return [['g' => 'm', 'q' => [0, 0, 0, 0], 's' => [0, 0], 'b' => []], [1, 1], 1, 1, 0, 0, 1, 1];
    }
}

class FailedRenderQRCode extends TestableQRCode
{
    public function render_image(): \GdImage|false
    {
        return false;
    }
}
