<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\OAuth2\Client;

use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testEmpty(): void
    {
        $user = new \Dotclear\Helper\OAuth2\Client\User();

        $this->assertFalse(
            $user->isConfigured()
        );

        $this->assertSame(
            [
                'user_id'     => '',
                'uid'         => '',
                'displayname' => '',
                'email'       => '',
                'avatar'      => '',
            ],
            $user->getConfiguration()
        );
    }

    public function testFull(): void
    {
        $user = new \Dotclear\Helper\OAuth2\Client\User(
            [
                'user_id'     => 'myself',
                'uid'         => 'sub value',
                'displayname' => 'nickname value',
                'email'       => 'email value',
                'avatar'      => 'picture value',
                'extra'       => true,
            ],
        );

        $this->assertTrue(
            $user->isConfigured()
        );

        $this->assertSame(
            [
                'user_id'     => 'myself',
                'uid'         => 'sub value',
                'displayname' => 'nickname value',
                'email'       => 'email value',
                'avatar'      => 'picture value',
            ],
            $user->getConfiguration()
        );
    }

    public function testParseEmpty(): void
    {
        $user = \Dotclear\Helper\OAuth2\Client\User::parseUser('', []);

        $this->assertFalse(
            $user->isConfigured()
        );

        $this->assertSame(
            [
                'user_id'     => '',
                'uid'         => '',
                'displayname' => '',
                'email'       => '',
                'avatar'      => '',
            ],
            $user->getConfiguration()
        );
    }

    public function testParseFull(): void
    {
        $user = \Dotclear\Helper\OAuth2\Client\User::parseUser(
            [
                'sub'      => 'sub value',
                'nickname' => 'nickname value',
                'email'    => 'email value',
                'picture'  => 'picture value',
                'extra'    => true,
            ],
            [
                'uid'         => 'sub',
                'displayname' => 'nickname',
                'email'       => 'email',
                'avatar'      => 'picture',
            ]
        );

        $this->assertFalse(
            $user->isConfigured()
        );

        $this->assertSame(
            [
                'user_id'     => '',
                'uid'         => 'sub value',
                'displayname' => 'nickname value',
                'email'       => 'email value',
                'avatar'      => 'picture value',
            ],
            $user->getConfiguration()
        );
    }
}
