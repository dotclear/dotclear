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

namespace tests\unit\Dotclear\Helper\Diff;

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'bootstrap.php']);

use atoum;

class TidyDiff extends atoum
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

        $this
            ->array($component->getChunks())
            ->isEqualTo([
                $chunk,
            ])
        ;
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

        $this
            ->array($component->getChunks())
            ->isEqualTo([
                $chunk,
            ])
        ;
    }
}
