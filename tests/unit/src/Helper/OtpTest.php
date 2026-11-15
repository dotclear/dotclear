<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class OtpTester extends \Dotclear\Helper\Otp
{
    public function getCredential(): void
    {
    }

    public function setCredential(): void
    {
    }

    public function delCredential(): void
    {
    }
}

#[BackupGlobals(true)]
class OtpTest extends TestCase
{
    public function test(): void
    {
        $otp = new OtpTester();

        $this->assertEquals(
            'totp',
            $otp->getType()
        );
        $this->assertEquals(
            'Undefined',
            $otp->getDomain()
        );
        $this->assertEquals(
            '',
            $otp->getUser()
        );
        $this->assertLessThanOrEqual(
            (new DateTimeImmutable('now'))->getTimestamp(),
            $otp->getTimestamp()
        );
        $this->assertEquals(
            'image/png',
            $otp->getQrCodeMimeType()
        );
        $this->assertStringStartsWith(
            'data:image/png;base64,',
            $otp->getQrCodeImageData()
        );
        $this->assertStringStartsWith(
            '<img src="',
            $otp->getQrCodeImageHtml()->render()
        );
        $this->assertStringEndsWith(
            '====',
            $otp->getSecret()
        );
        $this->assertEquals(
            56,
            mb_strlen($otp->getSecret())
        );
        $this->assertEquals(
            0,
            $otp->getCounter()
        );
        $this->assertFalse(
            $otp->isVerified()
        );
        $this->assertEquals(
            0,
            $otp->getLeeway()
        );
        $code = $otp->getCode($otp->getTimestamp());
        $this->assertEquals(
            6,
            mb_strlen($code)
        );
    }

    public function testVariousSetGet(): void
    {
        $otp = new OtpTester();

        $this->assertEquals(
            'Undefined',
            $otp->getDomain()
        );

        $otp->setDomain('dotclear');

        $this->assertEquals(
            'dotclear',
            $otp->getDomain()
        );

        $this->assertEquals(
            '',
            $otp->getUser()
        );

        $otp->setUser('admin');

        $this->assertEquals(
            'admin',
            $otp->getUser()
        );

        $this->assertEquals(
            0,
            $otp->getLeeway()
        );

        $otp->setLeeway(30);

        $this->assertEquals(
            30,
            $otp->getLeeway()
        );

        $otp->setLeeway(-1);

        $this->assertSame(
            30,
            $otp->getLeeway()
        );
    }

    public function testQRCode(): void
    {
        $otp = new OtpTester();

        $otp->setDomain('dotclear');
        $otp->setUser('admin');

        $otp->setQrCodeCorrection('M');
        $otp->setQrCodeSize(48);
        $otp->setQrCodeMargin(5);
        $otp->setQrCodeImageTitle('QR Code');

        $this->assertStringStartsWith(
            'data:image/png;base64,',
            $otp->getQrCodeImageData()
        );
        $this->assertStringStartsWith(
            '<img src="data:image/png;base64,',
            $otp->getQrCodeImageHtml()->render()
        );
        $this->assertStringEndsWith(
            'alt="QR Code">',
            $otp->getQrCodeImageHtml()->render()
        );

        $otp->setData([
            'secret'    => 'MZXW6YTBOI======',
            'counter'   => 13,
            'period'    => 60,
            'digits'    => 8,
            'algorithm' => 'md5',
            'verified'  => true,
        ]);

        $this->assertStringStartsWith(
            'data:image/png;base64,',
            $otp->getQrCodeImageData()
        );
        $this->assertStringStartsWith(
            '<img src="data:image/png;base64,',
            $otp->getQrCodeImageHtml()->render()
        );
    }

    #[DataProvider('dataProviderQrCodeCorrection')]
    public function testQRCodeCorrection(string $correction): void
    {
        $otp = new OtpTester();
        $otp->setQrCodeCorrection($correction);

        $this->assertStringStartsWith(
            'data:image/png;base64,',
            $otp->getQrCodeImageData()
        );
    }

    /**
     * @return list<array{string}>
     */
    public static function dataProviderQrCodeCorrection(): array
    {
        return [
            ['Q'],
            ['H'],
            ['invalid'],
        ];
    }

    public function testQRCodeHotp(): void
    {
        $otp = new OtpTester(false);

        $otp->setDomain('dotclear');
        $otp->setUser('admin');

        $otp->setQrCodeCorrection('M');
        $otp->setQrCodeSize(48);
        $otp->setQrCodeMargin(5);
        $otp->setQrCodeImageTitle('QR Code');

        $this->assertStringStartsWith(
            'data:image/png;base64,',
            $otp->getQrCodeImageData()
        );
        $this->assertStringStartsWith(
            '<img src="data:image/png;base64,',
            $otp->getQrCodeImageHtml()->render()
        );
        $this->assertStringEndsWith(
            'alt="QR Code">',
            $otp->getQrCodeImageHtml()->render()
        );

        $otp->setData([
            'secret'    => 'MZXW6YTBOI======',
            'counter'   => 13,
            'period'    => 60,
            'digits'    => 8,
            'algorithm' => 'md5',
            'verified'  => true,
        ]);

        $this->assertStringStartsWith(
            'data:image/png;base64,',
            $otp->getQrCodeImageData()
        );
        $this->assertStringStartsWith(
            '<img src="data:image/png;base64,',
            $otp->getQrCodeImageHtml()->render()
        );
    }

    public function testVerifyCode(): void
    {
        $otp = new OtpTester();

        $otp->setDomain('dotclear');
        $otp->setUser('admin');

        $otp->setData([
            'secret'    => 'MZXW6YTBOI======',
            'counter'   => 0,
            'period'    => 30,
            'digits'    => 6,
            'algorithm' => 'sha1',
            'verified'  => true,
        ]);

        $code = $otp->getCode($otp->getTimestamp());

        $this->assertTrue(
            $otp->verifyCode($code)
        );

        $otp->setLeeway(25);

        $code = $otp->getCode($otp->getTimestamp());

        $this->assertTrue(
            $otp->verifyCode($code)
        );

        // False code
        $this->assertFalse(
            $otp->verifyCode($code . $code)
        );
    }

    public function testTotpRfc6238(): void
    {
        $otp = new OtpTester();
        $otp->setData([
            'secret'    => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ',
            'period'    => 30,
            'digits'    => 8,
            'algorithm' => 'sha1',
        ]);

        $this->assertSame('94287082', $otp->getCode(59));
    }

    public function testVerifyCodeRejectsLeewayAtLeastAsLongAsPeriod(): void
    {
        $otp = new OtpTester();
        $otp->setData(['period' => 30]);
        $otp->setLeeway(30);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid time leeway');

        $otp->verifyCode('');
    }

    public function testVerifyCodeRejectsLeewayBeforeUnixEpoch(): void
    {
        $otp = new OtpTester();
        $otp->setData(['period' => PHP_INT_MAX]);
        $otp->setLeeway($otp->getTimestamp() + 2);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid time leeway');

        $otp->verifyCode('');
    }

    public function testVerifyCodeHotp(): void
    {
        $otp = new OtpTester(false);

        $this->assertEquals(
            'hotp',
            $otp->getType()
        );

        $otp->setDomain('dotclear');
        $otp->setUser('admin');

        $otp->setData([
            'secret'    => 'MZXW6YTBOI======',
            'counter'   => 13,
            'period'    => 30,
            'digits'    => 6,
            'algorithm' => 'sha1',
            'verified'  => true,
        ]);

        $code = $otp->getCode(0);
        $this->assertEquals(
            '734211',
            $code
        );
        $counterCode = $otp->getCode($otp->getCounter());
        $this->assertNotSame(
            $code,
            $counterCode
        );

        $this->assertTrue(
            $otp->verifyCode($counterCode)
        );

        // False code
        $this->assertFalse(
            $otp->verifyCode($code . $code)
        );
    }

    #[DataProvider('dataProviderHotp')]
    public function testHotpRfc4226(int $counter, string $expected): void
    {
        $otp = new OtpTester(false);
        $otp->setData([
            'secret'    => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ',
            'counter'   => $counter,
            'period'    => 30,
            'digits'    => 6,
            'algorithm' => 'sha1',
        ]);

        $this->assertSame($expected, $otp->getCode($counter));
    }

    /**
     * @return list<array{int, string}>
     */
    public static function dataProviderHotp(): array
    {
        return [
            [0, '755224'],
            [1, '287082'],
            [2, '359152'],
            [3, '969429'],
            [4, '338314'],
            [5, '254676'],
            [6, '287922'],
            [7, '162583'],
            [8, '399871'],
            [9, '520489'],
        ];
    }

    public function testSetDataUsesDefaultsForInvalidValues(): void
    {
        $otp = new OtpTester();
        $otp->setData([
            'secret'    => 123,
            'counter'   => 'not numeric',
            'period'    => null,
            'digits'    => [],
            'algorithm' => false,
            'verified'  => false,
        ]);

        $this->assertSame(0, $otp->getCounter());
        $this->assertSame(\Dotclear\Helper\Otp::DEFAULT_PERIOD, $otp->getPeriod());
        $this->assertSame(\Dotclear\Helper\Otp::DEFAULT_DIGITS, $otp->getDigits());
        $this->assertSame(\Dotclear\Helper\Otp::DEFAULT_ALGORITHM, $otp->getAlgorithm());
        $this->assertFalse($otp->isVerified());
        $this->assertSame(56, strlen($otp->getSecret()));
    }

    public function testGetUrlIncludesNonDefaultOptions(): void
    {
        $otp = new OtpTester(false);
        $otp->setDomain('Example Org');
        $otp->setUser('alice@example.test');
        $otp->setData([
            'secret'    => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ',
            'counter'   => 7,
            'period'    => 45,
            'digits'    => 8,
            'algorithm' => 'sha256',
        ]);

        $url = $otp->getUrl();

        $this->assertStringStartsWith(
            'otpauth://hotp/Example%20Org%3Aalice%40example.test?',
            $url
        );
        parse_str((string) parse_url($url, PHP_URL_QUERY), $params);
        $this->assertSame('Example Org', $params['issuer']);
        $this->assertSame('45', $params['period']);
        $this->assertSame('8', $params['digits']);
        $this->assertSame('SHA256', $params['algorithm']);
        $this->assertSame('7', $params['counter']);
    }

    #[DataProvider('dataProviderBase32')]
    public function testBase32(string $value, string $base32): void
    {
        $otp = new OtpTester();

        $this->assertEquals(
            $base32,
            $otp->base32Encode($value)
        );
        $this->assertEquals(
            rtrim($base32, '='),
            $otp->base32Encode($value, false)
        );

        // Converting a binary (\0 terminated) string to a PHP string - candidate to be done in method, one day
        $decode = rtrim($otp->base32Decode($base32), "\0");
        $this->assertEquals(
            $value,
            $decode
        );
    }

    #[DataProvider('dataProviderInvalidBase32')]
    public function testBase32RejectsInvalidInput(string $input): void
    {
        $this->assertSame('', (new OtpTester())->base32Decode($input));
    }

    /**
     * @return list<array{string}>
     */
    public static function dataProviderInvalidBase32(): array
    {
        return [
            ['MZ====='],
            ['MZ======A'],
            ['M!======'],
        ];
    }

    /**
     * @return list<array{string, string}>
     */
    public static function dataProviderBase32(): array
    {
        return [
            // RFC 4648 examples
            ['', ''],
            ['f', 'MY======'],
            ['fo', 'MZXQ===='],
            ['foo', 'MZXW6==='],
            ['foob', 'MZXW6YQ='],
            ['fooba', 'MZXW6YTB'],
            ['foobar', 'MZXW6YTBOI======'],

            // Wikipedia examples, converted to base32
            ['sure.', 'ON2XEZJO'],
            ['sure', 'ON2XEZI='],
            ['sur', 'ON2XE==='],
            ['su', 'ON2Q===='],
            ['leasure.', 'NRSWC43VOJSS4==='],
            ['easure.', 'MVQXG5LSMUXA===='],
            ['asure.', 'MFZXK4TFFY======'],
            ['sure.', 'ON2XEZJO'],
        ];
    }
}
