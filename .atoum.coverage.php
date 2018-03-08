<?php

use mageekguy\atoum\reports;
use mageekguy\atoum\reports\coverage;
use mageekguy\atoum\writers\std;

$script->addDefaultReport();

$coverage = new coverage\html();
$coverage->addWriter(new std\out());
$coverage->setOutPutDirectory(__DIR__ . '/coverage/html');
$runner->addReport($coverage);
