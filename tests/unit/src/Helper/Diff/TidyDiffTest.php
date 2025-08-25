<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Diff;

use PHPUnit\Framework\TestCase;

class TidyDiffTest extends TestCase
{
    public function test()
    {
        $udiff = '@@ -1,3 +1,3 @@
-Ligne 1
-Ligne 2 (ligne 3 supprimée) ajoutée
+Ligne 1
+Ligne 2 ajoutée
 Ligne 4 ajoutée
';
        $component = new \Dotclear\Helper\Diff\TidyDiff($udiff);

        // Tested in TidyDiffChunk
        $chunk = new \Dotclear\Helper\Diff\TidyDiffChunk();
        $chunk->setRange(1, 3, 1, 3);
        $chunk->addLine('delete', [1, 1], 'Ligne 1');
        $chunk->addLine('delete', [2, 1], 'Ligne 2 (ligne 3 supprimée) ajoutée');
        $chunk->addLine('insert', [3, 1], 'Ligne 1');
        $chunk->addLine('insert', [3, 2], 'Ligne 2 ajoutée');
        $chunk->addLine('context', [3, 3], 'Ligne 4 ajoutée');

        $this->assertEquals(
            [
                $chunk,
            ],
            $component->getChunks()
        );
    }

    public function testWithInlineChange()
    {
        $udiff = '@@ -1,3 +1,3 @@
-Ligne 1
-Ligne 2 (ligne 3 supprimée) ajoutée
+Ligne 1
+Ligne 2 ajoutée
 Ligne 4 ajoutée
';
        $component = new \Dotclear\Helper\Diff\TidyDiff($udiff, true);

        // Tested in TidyDiffChunk
        $chunk = new \Dotclear\Helper\Diff\TidyDiffChunk();
        $chunk->setRange(1, 3, 1, 3);
        $chunk->addLine('delete', [1, 1], 'Ligne 1');
        $chunk->addLine('delete', [2, 1], 'Ligne 2 (ligne 3 supprimée) ajoutée');
        $chunk->addLine('insert', [3, 1], 'Ligne 1');
        $chunk->addLine('insert', [3, 2], 'Ligne 2 ajoutée');
        $chunk->addLine('context', [3, 3], 'Ligne 4 ajoutée');
        $chunk->findInsideChanges();

        $this->assertEquals(
            [
                $chunk,
            ],
            $component->getChunks()
        );
    }
}
