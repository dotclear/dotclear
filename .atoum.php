<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

use atoum\atoum;

use atoum\atoum\reports;

// Enable extension
$extension = new reports\extension($script);
$extension->addToRunner($runner);

// Write all on stdout.
$stdOutWriter = new atoum\writers\std\out();

// Generate a CLI report.
$cliReport = new atoum\reports\realtime\cli();
$cliReport->addWriter($stdOutWriter);

// Xunit report
$xunit = new atoum\reports\asynchronous\xunit();
$runner->addReport($xunit);

// Xunit writer
$writer = new atoum\writers\file('tests/atoum.xunit.xml');
$xunit->addWriter($writer);

$runner->addTestsFromDirectory('tests/unit/');
$runner->addReport($cliReport);
