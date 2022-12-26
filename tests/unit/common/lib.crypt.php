<?php

# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------

namespace tests\unit;

require_once __DIR__ . '/../bootstrap.php';

require_once CLEARBRICKS_PATH . '/common/lib.crypt.php';

use atoum;
use Faker;

/**
 * Crypt test.
 */
class crypt extends atoum
{
    public const BIG_KEY_SIZE = 200;
    public const DATA_SIZE    = 50;

    private $big_key;
    private $data;

    public function __construct()
    {
        parent::__construct();

        $faker         = Faker\Factory::create();
        $this->big_key = $faker->text(self::BIG_KEY_SIZE);
        $this->data    = $faker->text(self::DATA_SIZE);
    }

    /**
     *  Test big key. crypt don't allow key > than 64 cars
     */
    public function testHMacBigKeyMD5()
    {
        $this
            ->string(\crypt::hmac($this->big_key, $this->data, 'md5'))
            ->isIdenticalTo(hash_hmac('md5', $this->data, $this->big_key));
    }

    /**
     * hmac implicit SHA1 encryption (default argument)
     */
    public function testHMacSHA1Implicit()
    {
        $this
            ->string(\crypt::hmac($this->big_key, $this->data))
            ->isIdenticalTo(hash_hmac('sha1', $this->data, $this->big_key));
    }

    /**
     * hmac explicit SHA1 encryption
     */
    public function testHMacSHA1Explicit()
    {
        $this
            ->string(\crypt::hmac($this->big_key, $this->data, 'sha1'))
            ->isIdenticalTo(hash_hmac('sha1', $this->data, $this->big_key));
    }

    /**
     * hmac explicit MD5 encryption
     */
    public function testHMacMD5()
    {
        $this
            ->string(\crypt::hmac($this->big_key, $this->data, 'md5'))
            ->isIdenticalTo(hash_hmac('md5', $this->data, $this->big_key));
    }

    /**
     * If the encoder is not known, fallback into sha1 encoder (if PHP hash_hmac() exists)
     */
    public function testHMacFallback()
    {
        $this
            ->string(\crypt::hmac($this->big_key, $this->data, 'dummyencoder'))
            ->isIdenticalTo(hash_hmac('sha1', $this->data, $this->big_key));
    }

    /**
     * hmac_legacy implicit
     */
    public function testHMacLegacy()
    {
        $this
            ->string(\crypt::hmac_legacy($this->big_key, $this->data))
            ->isIdenticalTo(hash_hmac('sha1', $this->data, $this->big_key));
    }

    /**
     * hmac_legacy explicit MD5 encryption
     */
    public function testHMacLegacyMD5()
    {
        $this
            ->string(\crypt::hmac_legacy($this->big_key, $this->data, 'md5'))
            ->isIdenticalTo(hash_hmac('md5', $this->data, $this->big_key));
    }

    /**
     * hmac_legacy explicit Sha1 encryption
     */
    public function testHMacLegacySha1()
    {
        $this
            ->string(\crypt::hmac_legacy($this->big_key, $this->data, 'sha1'))
            ->isIdenticalTo(hash_hmac('sha1', $this->data, $this->big_key));
    }

    /**
     * If the encoder is not known, fallback into sha1 encoder (if PHP hash_hmac() exists)
     */
    public function testHMacLegacyFallback()
    {
        $this
            ->string(\crypt::hmac_legacy($this->big_key, $this->data, 'dummyencoder'))
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
                ->string(\crypt::createPassword())
                ->hasLength(8)
                ->match('/[a-zA-Z0-9@\!\$]/');
        }

        for ($i = 0; $i < 10; $i++) {
            $this
                ->string(\crypt::createPassword(10))
                ->hasLength(10)
                ->match('/[a-zA-Z0-9@\!\$]/');
        }

        for ($i = 0; $i < 10; $i++) {
            $this
                ->string(\crypt::createPassword(13))
                ->hasLength(13)
                ->match('/[a-zA-Z0-9@\!\$]/');
        }
    }
}
