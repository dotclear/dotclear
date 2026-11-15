<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\OAuth2\Client;

use Dotclear\Helper\OAuth2\Client\Scope;
use PHPUnit\Framework\TestCase;

class ScopeTest extends TestCase
{
    public function testScopeConversionsAndMembership(): void
    {
        $scope = new Scope('read write read', ['profile', 'read'], ' ');

        $this->assertSame([0 => 'profile', 1 => 'read', 3 => 'write'], $scope->scope);
        $this->assertSame('profile read write', $scope->toString());
        $this->assertSame([0 => 'profile', 1 => 'read', 3 => 'write'], $scope->toArray(null, $scope->scope));
        $this->assertSame(['read', 'write'], $scope->toArray('read write'));
        $this->assertTrue($scope->inRequiredScope(['profile', 'read']));
        $this->assertFalse($scope->inRequiredScope(['missing']));
        $this->assertTrue($scope->inScope(['profile', 'read', 'write']));
        $this->assertFalse($scope->inScope(['read']));
    }

    public function testArrayScopeAndDefaultDelimiter(): void
    {
        $scope = new Scope(['one', 'two', 'one'], [], '');

        $this->assertSame(['one', 'two'], $scope->scope);
        $this->assertSame('onetwo', $scope->toString());
        $this->assertSame(['one', 'two'], $scope->toArray('one,two'));
        $this->assertSame([], $scope->toArray(null));
        $this->assertTrue($scope->inRequiredScope('one,two'));
        $this->assertTrue($scope->inScope('one,two'));
    }
}
