<?php
// From: https://github.com/nico3333fr/CSP-useful
//
// Note: this script requires PHP ≥ 5.4.
// Inspired from https://mathiasbynens.be/notes/csp-reports

// Dareboost wants it? Not a problem.
header('X-Content-Type-Options: "nosniff"');

require dirname(__FILE__).'/../inc/admin/prepend.php';

// Specify log file
define('LOGFILE',path::real(DC_VAR).'/csp/csp_report.json');

// Get the raw POST data
$data = file_get_contents('php://input');

// Only continue if it’s valid JSON that is not just `null`, `0`, `false` or an
// empty string, i.e. if it could be a CSP violation report.
if ($data = json_decode($data, true)) {

	// get source-file and blocked-URI to perform some tests
	$source_file   = $data['csp-report']['source-file'];
	$blocked_uri   = $data['csp-report']['blocked-uri'];

	if (

    	// avoid false positives notifications coming from Chrome extensions (Wappalyzer, MuteTab, etc.)
    	// bug here https://code.google.com/p/chromium/issues/detail?id=524356
    	strpos($source_file, 'chrome-extension://') === false

    	// avoid false positives notifications coming from Safari extensions (diigo, evernote, etc.)
    	&& strpos($source_file, 'safari-extension://') === false
		&& strpos($blocked_uri, 'safari-extension://') === false

    	// search engine extensions ?
    	&& strpos($source_file, 'se-extension://') === false

    	// added by browsers in webviews
    	&& strpos($blocked_uri, 'webviewprogressproxy://') === false

		// Google Search App see for details https://github.com/nico3333fr/CSP-useful/commit/ecc8f9b0b379ae643bc754d2db33c8b47e185fd1
		&& strpos($blocked_uri, 'gsa://onpageload') === false

	) {
			// Prettify the JSON-formatted data
			$data = json_encode(
					$data,
					JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
					);

			try {
				// Check report dir (create it if necessary)
				files::makeDir(dirname(LOGFILE),true);

				if (!($fp = @fopen(LOGFILE,'a'))) {
					return;
				}
				// Add an ending comma to JSon encoding data
				// The file content should be enclosed in brackets [] before beeing decoded
				fprintf($fp,'%s,',$data);
			}  catch (Exception $e) {
				return;
			}
		}
}
