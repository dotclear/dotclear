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

class TidyDiffChunk extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Diff\TidyDiffChunk();

        $this
            ->array($component->getLines())
            ->isEmpty()
        ;

        $this
            ->integer($component->getInfo('context'))
            ->isEqualTo(0)
        ;

        $this
            ->integer($component->getInfo('delete'))
            ->isEqualTo(0)
        ;

        $this
            ->integer($component->getInfo('insert'))
            ->isEqualTo(0)
        ;

        $this
            ->array($component->getInfo('range'))
            ->isEqualTo(['start' => [], 'end' => []])
        ;

        $this
            ->variable($component->getInfo('unknown'))
            ->isNull()
        ;
    }

    public function testSetRange()
    {
        $component = new \Dotclear\Helper\Diff\TidyDiffChunk();
        $component->setRange(1, 2, 3, 4);

        $this
            ->array($component->getInfo('range'))
            ->isEqualTo(['start' => [1, 2], 'end' => [3, 4]])
        ;
    }

    public function testAddLine()
    {
        $component = new \Dotclear\Helper\Diff\TidyDiffChunk();
        $component->addLine('context', [3, 4], '@@ -1,3 +1,4 @@');

        $this
            ->array($component->getLines())
            ->isEqualTo([
                new \Dotclear\Helper\Diff\TidyDiffLine('context', [3, 4], '@@ -1,3 +1,4 @@'),
            ])
            ->integer($component->getInfo('context'))
            ->isEqualTo(1)
        ;

        $component->addLine('context', [5, 6], '@@ -1,5 +1,6 @@');
        $this
            ->array($component->getLines())
            ->isEqualTo([
                new \Dotclear\Helper\Diff\TidyDiffLine('context', [3, 4], '@@ -1,3 +1,4 @@'),
                new \Dotclear\Helper\Diff\TidyDiffLine('context', [5, 6], '@@ -1,5 +1,6 @@'),
            ])
            ->integer($component->getInfo('context'))
            ->isEqualTo(2)
        ;
    }

    public function testUniDiff()
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

        $this
            ->array($component->getLines())
            ->isEqualTo([
                new \Dotclear\Helper\Diff\TidyDiffLine('delete', [1, 1], 'Ligne 1'),
                new \Dotclear\Helper\Diff\TidyDiffLine('delete', [2, 1], 'Ligne 2 (ligne 3 supprimée) ajoutée'),
                new \Dotclear\Helper\Diff\TidyDiffLine('insert', [3, 1], 'Ligne 1'),
                new \Dotclear\Helper\Diff\TidyDiffLine('insert', [3, 2], 'Ligne 2 ajoutée'),
                new \Dotclear\Helper\Diff\TidyDiffLine('context', [3, 3], 'Ligne 4 ajoutée'),
            ])
            ->integer($component->getInfo('context'))
            ->isEqualTo(1)
            ->integer($component->getInfo('delete'))
            ->isEqualTo(2)
            ->integer($component->getInfo('insert'))
            ->isEqualTo(2)
            ->array($component->getInfo('range'))
            ->isEqualTo(['start' => [1, 3], 'end' => [1, 3]])
        ;
    }

    public function testInsideChange()
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

        $this
            ->array($component->getLines())
            ->isEqualTo([
                new \Dotclear\Helper\Diff\TidyDiffLine('delete', [1, 1], 'Ligne 1\0\1'),
                new \Dotclear\Helper\Diff\TidyDiffLine('delete', [2, 1], 'Ligne 2 \0(ligne 3 supprimée) \1ajoutée'),
                new \Dotclear\Helper\Diff\TidyDiffLine('insert', [3, 1], 'Ligne 1\0\1'),
                new \Dotclear\Helper\Diff\TidyDiffLine('insert', [3, 2], 'Ligne 2 \0\1ajoutée'),
                new \Dotclear\Helper\Diff\TidyDiffLine('context', [3, 3], 'Ligne 4 ajoutée'),
            ])
            ->integer($component->getInfo('context'))
            ->isEqualTo(1)
            ->integer($component->getInfo('delete'))
            ->isEqualTo(2)
            ->integer($component->getInfo('insert'))
            ->isEqualTo(2)
            ->array($component->getInfo('range'))
            ->isEqualTo(['start' => [1, 3], 'end' => [1, 3]])
        ;
    }
}
