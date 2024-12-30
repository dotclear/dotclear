<?php

/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Process\Backend;

use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Helper\File\Files;
use Exception;

/**
 * @since 2.27 Before as admin/csp_report.php
 *
 * From: https://github.com/nico3333fr/CSP-useful
 *
 * Note: this script requires PHP ≥ 5.4.
 * Inspired from https://mathiasbynens.be/notes/csp-reports
 */
class CspReport extends Process
{
    public static function init(): bool
    {
        // Dareboost wants it? Not a problem.
        header('X-Content-Type-Options: "nosniff"');

        return self::status(true);
    }

    public static function process(): bool
    {
        // Get the raw POST data
        $data = file_get_contents('php://input');

        // Only continue if it’s valid JSON that is not just `null`, `0`, `false` or an
        // empty string, i.e. if it could be a CSP violation report.
        if ($data = json_decode((string) $data, true, 512, JSON_THROW_ON_ERROR)) {
            // get source-file and blocked-URI to perform some tests
            $source_file        = $data['csp-report']['source-file']        ?? '';
            $line_number        = $data['csp-report']['line-number']        ?? '';
            $blocked_uri        = $data['csp-report']['blocked-uri']        ?? '';
            $document_uri       = $data['csp-report']['document-uri']       ?? '';
            $violated_directive = $data['csp-report']['violated-directive'] ?? '';

            if (
                // avoid false positives notifications coming from Chrome extensions (Wappalyzer, MuteTab, etc.)
                // bug here https://code.google.com/p/chromium/issues/detail?id=524356
                !str_contains((string) $source_file, 'chrome-extension://')

                // avoid false positives notifications coming from Safari extensions (diigo, evernote, etc.)
                && !str_contains((string) $source_file, 'safari-extension://')
                && !str_contains((string) $blocked_uri, 'safari-extension://')

                // search engine extensions ?
                && !str_contains((string) $source_file, 'se-extension://')

                // added by browsers in webviews
                && !str_contains((string) $blocked_uri, 'webviewprogressproxy://')

                // Google Search App see for details https://github.com/nico3333fr/CSP-useful/commit/ecc8f9b0b379ae643bc754d2db33c8b47e185fd1
                && !str_contains((string) $blocked_uri, 'gsa://onpageload')

            ) {
                // Prepare report data (hash => info)
                $hash = hash('md5', $blocked_uri . $document_uri . $source_file . $line_number . $violated_directive);

                try {
                    // Check report dir (create it if necessary)
                    Files::makeDir(dirname(App::config()->cspReportFile()), true);

                    // Check if report is not already stored in log file
                    $contents = '';
                    if (file_exists(App::config()->cspReportFile())) {
                        $contents = file_get_contents(App::config()->cspReportFile());
                        if ($contents) {
                            if (str_ends_with($contents, ',')) {
                                // Remove final comma if present
                                $contents = substr($contents, 0, -1);
                            }
                            if ($contents !== '') {
                                $list = json_decode('[' . $contents . ']', true);
                                if (is_array($list)) {
                                    foreach ($list as $value) {
                                        if (isset($value['hash']) && $value['hash'] == $hash) {
                                            // Already stored, ignore
                                            return true;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // Add report to the file
                    if (!($fp = @fopen(App::config()->cspReportFile(), 'a'))) {
                        // Unable to open file, ignore
                        return false;
                    }

                    // Prettify the JSON-formatted data
                    $violation = array_merge(['hash' => $hash], $data['csp-report']);
                    $output    = json_encode($violation, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

                    // The file content will have to be enclosed in brackets [] before
                    // beeing decoded with json_decoded(<content>,true);
                    fprintf($fp, ($contents != '' ? ',' : '') . '%s', $output);
                } catch (Exception) {
                    return false;
                }
            }
        }

        return true;
    }
}
