<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Diff;

use PHPUnit\Framework\TestCase;

class TidyDiffChunkTest extends TestCase
{
    public function test(): void
    {
        $component = new \Dotclear\Helper\Diff\TidyDiffChunk();

        $this->assertEmpty(
            $component->getLines()
        );

        $this->assertEquals(
            0,
            $component->getInfo('context')
        );

        $this->assertEquals(
            0,
            $component->getInfo('delete')
        );

        $this->assertEquals(
            0,
            $component->getInfo('insert')
        );

        $this->assertEquals(
            ['start' => [], 'end' => []],
            $component->getInfo('range')
        );

        $this->assertNull(
            $component->getInfo('unknown')
        );
    }

    public function testSetRange(): void
    {
        $component = new \Dotclear\Helper\Diff\TidyDiffChunk();
        $component->setRange(1, 2, 3, 4);

        $this->assertEquals(
            ['start' => [1, 2], 'end' => [3, 4]],
            $component->getInfo('range')
        );
    }

    public function testAddLine(): void
    {
        $component = new \Dotclear\Helper\Diff\TidyDiffChunk();
        $component->addLine('context', [3, 4], '@@ -1,3 +1,4 @@');

        $this->assertEquals(
            [
                new \Dotclear\Helper\Diff\TidyDiffLine('context', [3, 4], '@@ -1,3 +1,4 @@'),
            ],
            $component->getLines()
        );

        $this->assertEquals(
            1,
            $component->getInfo('context')
        );

        $component->addLine('context', [5, 6], '@@ -1,5 +1,6 @@');

        $this->assertEquals(
            [
                new \Dotclear\Helper\Diff\TidyDiffLine('context', [3, 4], '@@ -1,3 +1,4 @@'),
                new \Dotclear\Helper\Diff\TidyDiffLine('context', [5, 6], '@@ -1,5 +1,6 @@'),
            ],
            $component->getLines()
        );

        $this->assertEquals(
            2,
            $component->getInfo('context')
        );
    }

    public function testUniDiff(): void
    {
        /* Will test with this chunk:
            @@ -1,3 +1,3 @@
            -Ligne 1
            -Ligne 2 (ligne 3 supprimée) ajoutée
            +Ligne 1
            +Ligne 2 ajoutée
             Ligne 4 ajoutée
        */

        $component = new \Dotclear\Helper\Diff\TidyDiffChunk();
        $component->setRange(1, 3, 1, 3);
        $component->addLine('delete', [1, 1], 'Ligne 1');
        $component->addLine('delete', [2, 1], 'Ligne 2 (ligne 3 supprimée) ajoutée');
        $component->addLine('insert', [3, 1], 'Ligne 1');
        $component->addLine('insert', [3, 2], 'Ligne 2 ajoutée');
        $component->addLine('context', [3, 3], 'Ligne 4 ajoutée');

        $this->assertEquals(
            [
                new \Dotclear\Helper\Diff\TidyDiffLine('delete', [1, 1], 'Ligne 1'),
                new \Dotclear\Helper\Diff\TidyDiffLine('delete', [2, 1], 'Ligne 2 (ligne 3 supprimée) ajoutée'),
                new \Dotclear\Helper\Diff\TidyDiffLine('insert', [3, 1], 'Ligne 1'),
                new \Dotclear\Helper\Diff\TidyDiffLine('insert', [3, 2], 'Ligne 2 ajoutée'),
                new \Dotclear\Helper\Diff\TidyDiffLine('context', [3, 3], 'Ligne 4 ajoutée'),
            ],
            $component->getLines()
        );
        $this->assertEquals(
            1,
            $component->getInfo('context')
        );
        $this->assertEquals(
            2,
            $component->getInfo('delete')
        );
        $this->assertEquals(
            2,
            $component->getInfo('insert')
        );
        $this->assertEquals(
            ['start' => [1, 3], 'end' => [1, 3]],
            $component->getInfo('range')
        );
    }

    public function testInsideChange(): void
    {
        /* Will test with this chunk:
            @@ -1,3 +1,3 @@
            -Ligne 1
            -Ligne 2 (ligne 3 supprimée) ajoutée
            +Ligne 1
            +Ligne 2 ajoutée
             Ligne 4 ajoutée
        */

        $component = new \Dotclear\Helper\Diff\TidyDiffChunk();
        $component->setRange(1, 3, 1, 3);
        $component->addLine('delete', [1, 1], 'Ligne 1');
        $component->addLine('delete', [2, 1], 'Ligne 2 (ligne 3 supprimée) ajoutée');
        $component->addLine('insert', [3, 1], 'Ligne 1');
        $component->addLine('insert', [3, 2], 'Ligne 2 ajoutée');
        $component->addLine('context', [3, 3], 'Ligne 4 ajoutée');
        $component->findInsideChanges();

        $this->assertEquals(
            [
                new \Dotclear\Helper\Diff\TidyDiffLine('delete', [1, 1], 'Ligne 1\0\1'),
                new \Dotclear\Helper\Diff\TidyDiffLine('delete', [2, 1], 'Ligne 2 \0(ligne 3 supprimée) \1ajoutée'),
                new \Dotclear\Helper\Diff\TidyDiffLine('insert', [3, 1], 'Ligne 1\0\1'),
                new \Dotclear\Helper\Diff\TidyDiffLine('insert', [3, 2], 'Ligne 2 \0\1ajoutée'),
                new \Dotclear\Helper\Diff\TidyDiffLine('context', [3, 3], 'Ligne 4 ajoutée'),
            ],
            $component->getLines()
        );
        $this->assertEquals(
            1,
            $component->getInfo('context')
        );
        $this->assertEquals(
            2,
            $component->getInfo('delete')
        );
        $this->assertEquals(
            2,
            $component->getInfo('insert')
        );
        $this->assertEquals(
            ['start' => [1, 3], 'end' => [1, 3]],
            $component->getInfo('range')
        );
    }
}
