<?php
/*
 * Copyright 2013 Rnix Valentine
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

set_include_path(implode(PATH_SEPARATOR, array(
            realpath(dirname(__FILE__)),
            get_include_path(),
        )));

require 'libs/arguments.php';
require 'src/Bridge/Commands2.php';
require 'src/Controllers/Cli/Base.php';
require 'src/Controllers/Cli/Selenium2.php';
require 'src/Controllers/Converter/Base.php';
require 'src/Controllers/Converter/Selenium2.php';

use Combine\Controllers\Cli\Selenium2;

$cmd = arguments($argv);
$controller = new Selenium2;
$controller->run($cmd['arguments'], $cmd['options'], $cmd['flags']);
