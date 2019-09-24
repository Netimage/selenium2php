<?php

set_include_path(implode(PATH_SEPARATOR, array(
	realpath(dirname(__FILE__)),
	get_include_path(),
)));

require 'libs/arguments.php';
require 'src/Controllers/Cli/SeleniumIDE3.php';

use Combine\Controllers\Cli\SeleniumIDE3;

$cmd = arguments($argv);
$controller = new SeleniumIDE3();
$controller->run($cmd['arguments'], $cmd['options'], $cmd['flags']);
