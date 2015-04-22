<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2014 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------

use \mageekguy\atoum;

// Write all on stdout.
$stdOutWriter = new atoum\writers\std\out();

// Generate a CLI report.
$cliReport = new atoum\reports\realtime\cli();
$cliReport->addWriter($stdOutWriter);

// Coverage
$coverageField = new atoum\report\fields\runner\coverage\html('Clearbricks', '/var/www/coverage/dotclear');
$coverageField->setRootUrl('http://localhost/coverage/dotclear');
$cliReport->addField($coverageField);

$runner->addReport($cliReport);
