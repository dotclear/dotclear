<?php

/**
 * This file implements rector.
 *
 * Run:
 * rector process --dry-run --memory-limit=8G
 *
 * Or:
 * rector process --clear-cache --dry-run --memory-limit=8G
 *
 * @author     Franck
 * @since      2023
 */

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use Rector\Set\ValueObject\LevelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/admin',
        __DIR__ . '/inc',
        __DIR__ . '/locales',
        __DIR__ . '/plugins',
        __DIR__ . '/src',
        __DIR__ . '/themes',
    ])
    // PHP sets
    ->withPhpSets()
    ->withSets([
        LevelSetList::UP_TO_PHP_85,
    ])
    // Prepared sets
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        //naming: true,
        instanceOf: true,
        //earlyReturn: true,
    )
    // Configure parallel (if too much code to analyze), see https://getrector.com/documentation/troubleshooting-parallel
    ->withParallel(
        240,    // Timeout in seconds (default = 120)
        8,      // Max number of processes (default = 16)
        5       // Job size (default = 10)
    )
    ->withCache(
        // ensure file system caching is used instead of in-memory
        cacheClass: FileCacheStorage::class,

        // specify a path that works locally as well as on CI job runners
        cacheDirectory: '/tmp/dotclear/core/rector'
    )
    ->withSkip([
        RenameMethodRector::class => [
            /* src/Schema/Database/PdoSqlite/Handler.php:44
                -        $handle->sqliteCreateFunction('now', $this->now(...), 0);
                +        $handle->createFunction('now', $this->now(...), 0);
                ...
                -            if (!$handle->sqliteCreateCollation('utf8_unicode_ci', $this->utf8_unicode_ci->compare(...))) {
                +            if (!$handle->createCollation('utf8_unicode_ci', $this->utf8_unicode_ci->compare(...))) {
            */
            __DIR__ . '/src/Schema/Database/PdoSqlite/Handler.php',
        ],
    ])
;
