<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Diff;

use Exception;
use PHPUnit\Framework\TestCase;

class DiffTest extends TestCase
{
    public function testUniDiff(): void
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

        $this->assertEquals(
            $udiff,
            $patch
        );
    }

    public function testUniPatch(): void
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

        $this->assertEquals(
            $dst,
            $new
        );
    }

    public function testUniCheck(): void
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

        $this->expectNotToPerformAssertions();

        \Dotclear\Helper\Diff\Diff::uniCheck($udiff);
    }

    public function testUniCheckWithError(): void
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

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid diff format');

        \Dotclear\Helper\Diff\Diff::uniCheck($udiff1);

        $udiff2 = implode("\n", [
            '@@ -1,5 +1,17 @@',
            ' Ligne 1',
            '-Ligne 2 ajoutée',
            '-Ligne 3 ajoutée',
            '+Ligne 4 ajoutée',
            '+Ligne 2 (ligne 3 supprimée) ajoutée',
            '+Ligne 4',
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Chunk is out of range');

        \Dotclear\Helper\Diff\Diff::uniCheck($udiff2);

        $udiff3 = implode("\n", [
            '@@ -17,5 +1,4 @@',
            ' Ligne 1',
            '-Ligne 2 ajoutée',
            '-Ligne 3 ajoutée',
            '+Ligne 4 ajoutée',
            '+Ligne 2 (ligne 3 supprimée) ajoutée',
            '+Ligne 4',
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid line number');

        \Dotclear\Helper\Diff\Diff::uniCheck($udiff3);

        $udiff4 = implode("\n", [
            '@@ -0,5 +1,4 @@',
            ' Ligne 1',
            '-Ligne 2 ajoutée',
            '-Ligne 3 ajoutée',
            '+Ligne 4 ajoutée',
            '+Ligne 2 (ligne 3 supprimée) ajoutée',
            '+Ligne 4',
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid range');

        \Dotclear\Helper\Diff\Diff::uniCheck($udiff4);
    }

    public function testUniCheckWithPatch(): void
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

        $this->expectNotToPerformAssertions();

        \Dotclear\Helper\Diff\Diff::uniCheck($patch);
    }

    public function testSES(): void
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

        $this->assertEquals(
            [
                ['d', 1, 1],
                ['d', 2, 1],
                ['d', 3, 1],
                ['i', 4, 1],
                ['i', 4, 2],
            ],
            $ses
        );
    }
}
