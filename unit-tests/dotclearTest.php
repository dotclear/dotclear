<?php
# error reporting
ini_set('display_errors',1);
error_reporting(E_ALL|E_STRICT);

# include PHPUnit
require 'PHPUnit/Autoload.php';

// Root path
define('NAME_UNIT_TEST','unit-tests');
define('ROOT_UNIT_TEST',substr(dirname(__FILE__),0,strpos(dirname(__FILE__),'/'.NAME_UNIT_TEST)));

?>