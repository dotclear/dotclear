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

class Diff extends atoum
{
    public function testUniDiff()
    {
        $udiff = implode("\n", [
            '@@ -1,5 +1,4 @@',
            ' Ligne 1',
            '-Ligne 2 ajoutée',
            '-Ligne 3 ajoutée',
            '-Ligne 4 ajoutée',
            '+Ligne 2 (ligne 3 supprimée) ajoutée',
            '+Ligne 4',
            ' ',
            '',
        ]);

        $src = 'Ligne 1
Ligne 2 ajoutée
Ligne 3 ajoutée
Ligne 4 ajoutée
';

        $dst = 'Ligne 1
Ligne 2 (ligne 3 supprimée) ajoutée
Ligne 4
';

        $patch = \Dotclear\Helper\Diff\Diff::uniDiff($src, $dst);

        $this
            ->string($patch)
            ->isEqualTo($udiff)
        ;
    }

    public function testUniPatch()
    {
        $src = 'Ligne 1
Ligne 2 ajoutée
Ligne 3 ajoutée
Ligne 4 ajoutée
';

        $dst = 'Ligne 1
Ligne 2 (ligne 3 supprimée) ajoutée
Ligne 4
';

        $patch = \Dotclear\Helper\Diff\Diff::uniDiff($src, $dst);
        $new   = \Dotclear\Helper\Diff\Diff::uniPatch($src, $patch);

        $this
            ->string($new)
            ->isEqualTo($dst)
        ;
    }

    public function testUniCheck()
    {
        $udiff = implode("\n", [
            '@@ -1,5 +1,4 @@',
            ' Ligne 1',
            '-Ligne 2 ajoutée',
            '-Ligne 3 ajoutée',
            '-Ligne 4 ajoutée',
            '+Ligne 2 (ligne 3 supprimée) ajoutée',
            '+Ligne 4',
            ' ',
            '',
        ]);

        $this
            ->when(
                function () use ($udiff) {
                    \Dotclear\Helper\Diff\Diff::uniCheck($udiff);
                }
            )
            ->error()
            ->notExists()
        ;
    }

    public function testUniCheckWithError()
    {
        $udiff1 = implode("\n", [
            '@@ -1,5 +1,4 @@',
            ' Ligne 1',
            '-Ligne 2 ajoutée',
            '-Ligne 3 ajoutée',
            '*Ligne 4 ajoutée',
            '+Ligne 2 (ligne 3 supprimée) ajoutée',
            '+Ligne 4',
        ]);

        $this
            ->exception(
                function () use ($udiff1) {
                    \Dotclear\Helper\Diff\Diff::uniCheck($udiff1);
                }
            )
            ->hasMessage('Invalid diff format')
        ;

        $udiff2 = implode("\n", [
            '@@ -1,5 +1,17 @@',
            ' Ligne 1',
            '-Ligne 2 ajoutée',
            '-Ligne 3 ajoutée',
            '+Ligne 4 ajoutée',
            '+Ligne 2 (ligne 3 supprimée) ajoutée',
            '+Ligne 4',
        ]);

        $this
            ->exception(
                function () use ($udiff2) {
                    \Dotclear\Helper\Diff\Diff::uniCheck($udiff2);
                }
            )
            ->hasMessage('Chunk is out of range')
        ;

        $udiff3 = implode("\n", [
            '@@ -17,5 +1,4 @@',
            ' Ligne 1',
            '-Ligne 2 ajoutée',
            '-Ligne 3 ajoutée',
            '+Ligne 4 ajoutée',
            '+Ligne 2 (ligne 3 supprimée) ajoutée',
            '+Ligne 4',
        ]);

        $this
            ->exception(
                function () use ($udiff3) {
                    \Dotclear\Helper\Diff\Diff::uniCheck($udiff3);
                }
            )
            ->hasMessage('Invalid line number')
        ;

        $udiff4 = implode("\n", [
            '@@ -0,5 +1,4 @@',
            ' Ligne 1',
            '-Ligne 2 ajoutée',
            '-Ligne 3 ajoutée',
            '+Ligne 4 ajoutée',
            '+Ligne 2 (ligne 3 supprimée) ajoutée',
            '+Ligne 4',
        ]);

        $this
            ->exception(
                function () use ($udiff4) {
                    \Dotclear\Helper\Diff\Diff::uniCheck($udiff4);
                }
            )
            ->hasMessage('Invalid range')
        ;
    }

    public function testUniCheckWithPatch()
    {
        $src = 'Ligne 1
Ligne 2 ajoutée
Ligne 3 ajoutée
Ligne 4 ajoutée
';

        $dst = 'Ligne 1
Ligne 2 (ligne 3 supprimée) ajoutée
Ligne 4
';

        $patch = \Dotclear\Helper\Diff\Diff::uniDiff($src, $dst);

        $this
            ->when(
                function () use ($patch, $src, $dst) {
                    \Dotclear\Helper\Diff\Diff::uniCheck($patch);
                }
            )
            ->error()
            ->notExists()
        ;
    }

    public function testSES()
    {
        $src = 'Ligne 1
Ligne 2 ajoutée
Ligne 3 ajoutée
Ligne 4 ajoutée
';

        $dst = 'Ligne 1
Ligne 2 (ligne 3 supprimée) ajoutée
Ligne 4
';

        [$src, $dst] = [explode("\n", $src), explode("\n", $dst)];

        $ses = \Dotclear\Helper\Diff\Diff::SES($src, $dst);

        $this
            ->array($ses)
            ->isEqualTo([
                ['d', 1, 1],
                ['d', 2, 1],
                ['d', 3, 1],
                ['i', 4, 1],
                ['i', 4, 2],
            ])
        ;
    }
}
