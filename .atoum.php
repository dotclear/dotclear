<?php

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

$runner->addTestsFromDirectory('tests/unit/');
$runner->addReport($cliReport);
