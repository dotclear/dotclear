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

        $this->assertEquals(
            'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAABmJLR0QA/wD/AP+gvaeTAAAAO0lEQVRIDe3SsQkAMAwDwTj77yyDF/hK3bsWMhyaJHnF+8Xuq/YBCkskEQpgwBVJhAIYcEUSoQAG6itaFy0ELNR2WSAAAAAASUVORK5CYII=',
            $otp->getQrCodeImageData()
        );
        $this->assertEquals(
            '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAABmJLR0QA/wD/AP+gvaeTAAAAO0lEQVRIDe3SsQkAMAwDwTj77yyDF/hK3bsWMhyaJHnF+8Xuq/YBCkskEQpgwBVJhAIYcEUSoQAG6itaFy0ELNR2WSAAAAAASUVORK5CYII=" alt="QR Code">',
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

    public function testQRCodeHotp(): void
    {
        $otp = new OtpTester(false);

        $otp->setDomain('dotclear');
        $otp->setUser('admin');

        $otp->setQrCodeCorrection('M');
        $otp->setQrCodeSize(48);
        $otp->setQrCodeMargin(5);
        $otp->setQrCodeImageTitle('QR Code');

        $this->assertEquals(
            'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAABmJLR0QA/wD/AP+gvaeTAAAAO0lEQVRIDe3SsQkAMAwDwTj77yyDF/hK3bsWMhyaJHnF+8Xuq/YBCkskEQpgwBVJhAIYcEUSoQAG6itaFy0ELNR2WSAAAAAASUVORK5CYII=',
            $otp->getQrCodeImageData()
        );
        $this->assertEquals(
            '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAABmJLR0QA/wD/AP+gvaeTAAAAO0lEQVRIDe3SsQkAMAwDwTj77yyDF/hK3bsWMhyaJHnF+8Xuq/YBCkskEQpgwBVJhAIYcEUSoQAG6itaFy0ELNR2WSAAAAAASUVORK5CYII=" alt="QR Code">',
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

        $code = $otp->getCode($otp->getCounter());
        $this->assertEquals(
            '734211',
            $code
        );

        $this->assertTrue(
            $otp->verifyCode($code)
        );

        // False code
        $this->assertFalse(
            $otp->verifyCode($code . $code)
        );
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
