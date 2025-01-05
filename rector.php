<?php

/**
 * This file implements rector.
 *
 * Run:
 * rector process --dry-run --verbose --memory-limit=8G
 *
 * Or:
 * rector process --clear-cache --dry-run --verbose --memory-limit=8G
 *
 * @author     Franck
 * @since      2023
 */

declare(strict_types=1);

use Rector\Config\RectorConfig;
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
        LevelSetList::UP_TO_PHP_84,
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
;
