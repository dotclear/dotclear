<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Diff;

use PHPUnit\Framework\TestCase;

class TidyDiffLineTest extends TestCase
{
    public function test(): void
    {
        $component = new \Dotclear\Helper\Diff\TidyDiffLine('context', [3, 4], 'content');

        $this->assertEquals(
            'context',
            $component->type
        );

        $this->assertEquals(
            [3, 4],
            $component->lines
        );

        $this->assertEquals(
            'content',
            $component->content
        );

        $this->assertNull(
            // @phpstan-ignore property.notFound
            $component->unknown
        );
    }

    public function testWithUnknownType(): void
    {
        $component = new \Dotclear\Helper\Diff\TidyDiffLine('unknown', [3, 4], 'content');

        $this->assertEquals(
            '',
            $component->type
        );

        // @phpstan-ignore method.impossibleType
        $this->assertNull(
            $component->lines
        );

        // @phpstan-ignore method.impossibleType
        $this->assertNull(
            $component->content
        );
    }

    public function testWithNullLines(): void
    {
        $component = new \Dotclear\Helper\Diff\TidyDiffLine('unknown', null, 'content');

        // @phpstan-ignore method.impossibleType
        $this->assertNull(
            $component->type
        );

        // @phpstan-ignore method.impossibleType
        $this->assertNull(
            $component->lines
        );

        // @phpstan-ignore method.impossibleType
        $this->assertNull(
            $component->content
        );
    }

    public function testWithNullContent(): void
    {
        $component = new \Dotclear\Helper\Diff\TidyDiffLine('unknown', [3, 4], null);

        // @phpstan-ignore method.impossibleType
        $this->assertNull(
            $component->type
        );

        // @phpstan-ignore method.impossibleType
        $this->assertNull(
            $component->lines
        );

        // @phpstan-ignore method.impossibleType
        $this->assertNull(
            $component->content
        );
    }

    public function testOverwrite(): void
    {
        $component = new \Dotclear\Helper\Diff\TidyDiffLine('context', [3, 4], 'content');

        $component->overwrite('new content');

        $this->assertEquals(
            'new content',
            $component->content
        );
    }

    public function testOverwriteWithNull(): void
    {
        $component = new \Dotclear\Helper\Diff\TidyDiffLine('context', [3, 4], 'content');

        $component->overwrite(null);

        $this->assertEquals(
            'content',
            $component->content
        );
    }
}
