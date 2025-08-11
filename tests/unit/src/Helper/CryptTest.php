<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper;

use Faker\Factory;
use PHPUnit\Framework\TestCase;

class CryptTest extends TestCase
{
    public const BIG_KEY_SIZE = 200;
    public const DATA_SIZE    = 50;

    private string $big_key;
    private string $data;

    protected function setUp(): void
    {
        $faker         = Factory::create();
        $this->big_key = $faker->text(self::BIG_KEY_SIZE);
        $this->data    = $faker->text(self::DATA_SIZE);
    }

    /**
     *  Test big key. crypt don't allow key > than 64 cars
     */
    public function testHMacBigKeyMD5()
    {
        $this->assertSame(
            hash_hmac('md5', $this->data, $this->big_key),
            \Dotclear\Helper\Crypt::hmac($this->big_key, $this->data, 'md5')
        );
    }

    /**
     * hmac implicit SHA1 encryption (default argument)
     */
    public function testHMacSHA1Implicit()
    {
        $this->assertSame(
            hash_hmac('sha1', $this->data, $this->big_key),
            \Dotclear\Helper\Crypt::hmac($this->big_key, $this->data)
        );
    }

    /**
     * hmac explicit SHA1 encryption
     */
    public function testHMacSHA1Explicit()
    {
        $this->assertSame(
            hash_hmac('sha1', $this->data, $this->big_key),
            \Dotclear\Helper\Crypt::hmac($this->big_key, $this->data, 'sha1')
        );
    }

    /**
     * hmac explicit MD5 encryption
     */
    public function testHMacMD5()
    {
        $this->assertSame(
            hash_hmac('md5', $this->data, $this->big_key),
            \Dotclear\Helper\Crypt::hmac($this->big_key, $this->data, 'md5')
        );
    }

    /**
     * If the encoder is not known, fallback into sha1 encoder (if PHP hash_hmac() exists)
     */
    public function testHMacFallback()
    {
        $this->assertSame(
            hash_hmac('sha1', $this->data, $this->big_key),
            \Dotclear\Helper\Crypt::hmac($this->big_key, $this->data, 'dummyencoder')
        );
    }

    /**
     * hmacLegacy implicit
     */
    public function testHMacLegacy()
    {
        $this->assertSame(
            hash_hmac('sha1', $this->data, $this->big_key),
            \Dotclear\Helper\Crypt::hmacLegacy($this->big_key, $this->data)
        );
    }

    /**
     * hmacLegacy explicit MD5 encryption
     */
    public function testHMacLegacyMD5()
    {
        $this->assertSame(
            hash_hmac('md5', $this->data, $this->big_key),
            \Dotclear\Helper\Crypt::hmacLegacy($this->big_key, $this->data, 'md5')
        );
    }

    /**
     * hmacLegacy explicit Sha1 encryption
     */
    public function testHMacLegacySha1()
    {
        $this->assertSame(
            hash_hmac('sha1', $this->data, $this->big_key),
            \Dotclear\Helper\Crypt::hmacLegacy($this->big_key, $this->data, 'sha1')
        );
    }

    /**
     * If the encoder is not known, fallback into sha1 encoder (if PHP hash_hmac() exists)
     */
    public function testHMacLegacyFallback()
    {
        $this->assertSame(
            hash_hmac('md5', $this->data, $this->big_key),
            \Dotclear\Helper\Crypt::hmacLegacy($this->big_key, $this->data, 'dummyencoder')
        );
    }

    /**
     * Password must be 8 char size and only contains alpha numerical
     * values
     */
    public function testCreatePassword()
    {
        for ($i = 0; $i < 10; $i++) {
            $password = \Dotclear\Helper\Crypt::createPassword();
            $this->assertEquals(
                8,
                mb_strlen($password)
            );
            $this->assertMatchesRegularExpression(
                '/[a-zA-Z0-9@\!\$]/',
                $password
            );
        }

        for ($i = 0; $i < 10; $i++) {
            $password = \Dotclear\Helper\Crypt::createPassword(10);
            $this->assertEquals(
                10,
                mb_strlen($password)
            );
            $this->assertMatchesRegularExpression(
                '/[a-zA-Z0-9@\!\$]/',
                $password
            );
        }

        for ($i = 0; $i < 10; $i++) {
            $password = \Dotclear\Helper\Crypt::createPassword(13);
            $this->assertEquals(
                13,
                mb_strlen($password)
            );
            $this->assertMatchesRegularExpression(
                '/[a-zA-Z0-9@\!\$]/',
                $password
            );
        }
    }
}
