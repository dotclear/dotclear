<?php
// From: https://github.com/nico3333fr/CSP-useful
//
// Note: this script requires PHP ≥ 5.4.
// Inspired from https://mathiasbynens.be/notes/csp-reports

// Dareboost wants it? Not a problem.
header('X-Content-Type-Options: "nosniff"');

// Specify log file
define('LOGFILE',dirname(__FILE__).'/csp_report.txt');

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

    	// search engine extensions ?
    	&& strpos($source_file, 'se-extension://') === false

    	// added by browsers in webviews
    	&& strpos($blocked_uri, 'webviewprogressproxy://') === false

	 ) {
			// Prettify the JSON-formatted data
			$data = json_encode(
					$data,
					JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
					);

			if (!($fp = @fopen(LOGFILE,'a'))) {
				return;
			}
			fprintf($fp,'%s',$data);
		}
}
