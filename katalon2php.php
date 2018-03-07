<?php
set_include_path(implode(PATH_SEPARATOR, array(
	realpath(dirname(__FILE__)),
	get_include_path(),
)));

require 'libs/arguments.php';
require 'src/Controllers/Cli/Katalon.php';

use Combine\Controllers\Cli\Katalon;

$cmd = arguments($argv);
$controller = new Katalon();
$controller->run($cmd['arguments'], $cmd['options'], $cmd['flags']);
