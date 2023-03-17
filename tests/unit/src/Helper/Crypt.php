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

namespace tests\unit\Dotclear\Helper;

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'bootstrap.php']);

use atoum;
use Faker\Factory;

/**
 * @tags Crypt
 */
class Crypt extends atoum
{
    public const BIG_KEY_SIZE = 200;
    public const DATA_SIZE    = 50;

    private $big_key;
    private $data;

    public function __construct()
    {
        parent::__construct();

        $faker         = Factory::create();
        $this->big_key = $faker->text(self::BIG_KEY_SIZE);
        $this->data    = $faker->text(self::DATA_SIZE);
    }

    /**
     *  Test big key. crypt don't allow key > than 64 cars
     */
    public function testHMacBigKeyMD5()
    {
        $this
            ->string(\Dotclear\Helper\Crypt::hmac($this->big_key, $this->data, 'md5'))
            ->isIdenticalTo(hash_hmac('md5', $this->data, $this->big_key));
    }

    /**
     * hmac implicit SHA1 encryption (default argument)
     */
    public function testHMacSHA1Implicit()
    {
        $this
            ->string(\Dotclear\Helper\Crypt::hmac($this->big_key, $this->data))
            ->isIdenticalTo(hash_hmac('sha1', $this->data, $this->big_key));
    }

    /**
     * hmac explicit SHA1 encryption
     */
    public function testHMacSHA1Explicit()
    {
        $this
            ->string(\Dotclear\Helper\Crypt::hmac($this->big_key, $this->data, 'sha1'))
            ->isIdenticalTo(hash_hmac('sha1', $this->data, $this->big_key));
    }

    /**
     * hmac explicit MD5 encryption
     */
    public function testHMacMD5()
    {
        $this
            ->string(\Dotclear\Helper\Crypt::hmac($this->big_key, $this->data, 'md5'))
            ->isIdenticalTo(hash_hmac('md5', $this->data, $this->big_key));
    }

    /**
     * If the encoder is not known, fallback into sha1 encoder (if PHP hash_hmac() exists)
     */
    public function testHMacFallback()
    {
        $this
            ->string(\Dotclear\Helper\Crypt::hmac($this->big_key, $this->data, 'dummyencoder'))
            ->isIdenticalTo(hash_hmac('sha1', $this->data, $this->big_key));
    }

    /**
     * hmacLegacy implicit
     */
    public function testHMacLegacy()
    {
        $this
            ->string(\Dotclear\Helper\Crypt::hmacLegacy($this->big_key, $this->data))
            ->isIdenticalTo(hash_hmac('sha1', $this->data, $this->big_key));
    }

    /**
     * hmacLegacy explicit MD5 encryption
     */
    public function testHMacLegacyMD5()
    {
        $this
            ->string(\Dotclear\Helper\Crypt::hmacLegacy($this->big_key, $this->data, 'md5'))
            ->isIdenticalTo(hash_hmac('md5', $this->data, $this->big_key));
    }

    /**
     * hmacLegacy explicit Sha1 encryption
     */
    public function testHMacLegacySha1()
    {
        $this
            ->string(\Dotclear\Helper\Crypt::hmacLegacy($this->big_key, $this->data, 'sha1'))
            ->isIdenticalTo(hash_hmac('sha1', $this->data, $this->big_key));
    }

    /**
     * If the encoder is not known, fallback into sha1 encoder (if PHP hash_hmac() exists)
     */
    public function testHMacLegacyFallback()
    {
        $this
            ->string(\Dotclear\Helper\Crypt::hmacLegacy($this->big_key, $this->data, 'dummyencoder'))
            ->isIdenticalTo(hash_hmac('md5', $this->data, $this->big_key));
    }

    /**
     * Password must be 8 char size and only contains alpha numerical
     * values
     */
    public function testCreatePassword()
    {
        for ($i = 0; $i < 10; $i++) {
            $this
                ->string(\Dotclear\Helper\Crypt::createPassword())
                ->hasLength(8)
                ->match('/[a-zA-Z0-9@\!\$]/');
        }

        for ($i = 0; $i < 10; $i++) {
            $this
                ->string(\Dotclear\Helper\Crypt::createPassword(10))
                ->hasLength(10)
                ->match('/[a-zA-Z0-9@\!\$]/');
        }

        for ($i = 0; $i < 10; $i++) {
            $this
                ->string(\Dotclear\Helper\Crypt::createPassword(13))
                ->hasLength(13)
                ->match('/[a-zA-Z0-9@\!\$]/');
        }
    }
}
