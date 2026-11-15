<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Diff;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DiffTest extends TestCase
{
    public function testUniDiffWithoutChanges(): void
    {
        $this->assertSame(
            '',
            \Dotclear\Helper\Diff\Diff::uniDiff('Ligne inchangée', 'Ligne inchangée')
        );
    }

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

    public function testUniDiffWithSeparatedChanges(): void
    {
        $src = 'a
b
c
d
e
f
g
h
i
j';
        $dst = 'a
B
c
d
e
F
g
h
i
j';
        $udiff = implode("\n", [
            '@@ -1,3 +1,3 @@',
            ' a',
            '-b',
            '+B',
            ' c',
            '@@ -5,3 +5,3 @@',
            ' e',
            '-f',
            '+F',
            ' g',
            '',
        ]);

        $patch = \Dotclear\Helper\Diff\Diff::uniDiff($src, $dst, 1);

        $this->assertSame($udiff, $patch);
        $this->assertSame($dst, \Dotclear\Helper\Diff\Diff::uniPatch($src, $patch));
        \Dotclear\Helper\Diff\Diff::uniCheck($patch);
    }

    public function testUniDiffNormalizesSourceLineEndings(): void
    {
        $patch = \Dotclear\Helper\Diff\Diff::uniDiff("avant\r\nancien\r\naprès", "avant\nnouveau\naprès");

        $this->assertSame(
            implode("\n", [
                '@@ -1,3 +1,3 @@',
                ' avant',
                '-ancien',
                '+nouveau',
                ' après',
                '',
            ]),
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

    public function testUniPatchNormalizesSourceLineEndingsAndAcceptsEmptyDiff(): void
    {
        $this->assertSame(
            "Ligne 1\nLigne 2",
            \Dotclear\Helper\Diff\Diff::uniPatch("Ligne 1\r\nLigne 2", '')
        );
    }

    #[DataProvider('dataProviderInvalidPatches')]
    public function testUniPatchWithInvalidPatch(string $src, string $patch, string $message): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($message);

        \Dotclear\Helper\Diff\Diff::uniPatch($src, $patch);
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function dataProviderInvalidPatches(): array
    {
        return [
            'range beyond source' => ['a', "@@ -3,1 +1,1 @@\n-x\n+y", 'Bad range.'],
            'range before previous chunk' => [
                "a\nb",
                "@@ -1,1 +1,1 @@\n a\n@@ -1,0 +2,0 @@",
                'Invalid range.',
            ],
            'inconsistent new line number' => ['a', '@@ -1,0 +2,0 @@', 'Invalid line number.'],
            'unfinished chunk' => ['a', "@@ -1,1 +1,1 @@\n", 'Chunk is out of range.'],
            'new chunk before previous one ends' => [
                'a',
                "@@ -1,1 +1,1 @@\n@@ -1,1 +1,1 @@",
                'Chunk is out of range.',
            ],
            'bad context' => ['a', "@@ -1,1 +1,1 @@\n b", 'Bad context.'],
            'bad deletion context' => ['a', "@@ -1,1 +1,0 @@\n-b", 'Bad context (in deletion).'],
            'invalid format' => ['a', '*invalid', 'Invalid diff format.'],
        ];
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

    #[DataProvider('dataProviderInvalidDiffs')]
    public function testUniCheckWithInvalidDiff(string $diff, string $message): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($message);

        \Dotclear\Helper\Diff\Diff::uniCheck($diff);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function dataProviderInvalidDiffs(): array
    {
        return [
            'invalid range' => ['@@ -0,0 +1,0 @@', 'Invalid range.'],
            'invalid line number' => ['@@ -1,0 +2,0 @@', 'Invalid line number.'],
            'unfinished chunk' => ['@@ -1,1 +1,1 @@', 'Chunk is out of range.'],
            'new chunk before previous one ends' => [
                "@@ -1,1 +1,1 @@\n@@ -1,1 +1,1 @@",
                'Chunk is out of range.',
            ],
            'invalid format' => ['*invalid', 'Invalid diff format.'],
        ];
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
