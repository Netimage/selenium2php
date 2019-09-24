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

namespace Combine\Controllers\Cli;

use \Combine\Controllers\Converter\SeleniumIDE3Converter;

/**
 * Handles CLI commands.
 */
class SeleniumIDE3 {

	/**
	 *
	 * @var Converter
	 */
	protected $_converter;
	protected $_htmlPattern = "*.html";
	protected $_recursive = false;
	protected $_phpFilePrefix = '';
	protected $_phpFilePostfix = 'Test';
	protected $_destFolder = '';
	protected $_sourceBaseDir = '';
	protected $_useHashFilePostfix = false;
	protected $_tplFile = '';
	protected $_browsers = '';

	/**
	 * Build ID specification
	 * @var string 
	 */
	protected $_projectBuild = null;

	/**
	 *
	 * @var string Project name
	 */
	protected $_projectName = null;

	/**
	 *
	 * @var string Name or reference by ID to test suite in JSON file
	 */
	protected $_suiteReference = null;

	public function __construct() {
		require_once __DIR__ . '/../Converter/SeleniumIDE3.php';
		$this->_converter = new \Combine\Controllers\Converter\SeleniumIDE3Converter();
	}

	protected function _printTitle() {
		print "Seleniumide32php converts Selenium IDE 3 JSON tests into PHPUnit test case code.";
		print "\n";
		print "\n";
	}

	protected function _printHelp() {
		print "Usage: seleniumide32php [switches] Test.side [Test.php]";
		print "\n";
		print "       seleniumide32php [switches] <directory>";
		print "\n";
		print "\n";
		print "  --dest=<path>                  Destination folder.\n";
		print "  --selenium2                    Use Selenium2 tests format.\n";
		print "  --php-prefix=<string>          Add prefix to php filenames.\n";
		print "  --php-postfix=<string>         Add postfix to php filenames.\n";
		print "  --browser=<browsers string>    Set browser for tests.\n";
		print "  --browser-url=<url>            Set URL for tests.\n";
		print "  --remote-host=<host>           Set Selenium server address for tests.\n";
		print "  --remote-port=<port>           Set Selenium server port for tests.\n";
		print "  -r|--recursive                 Use subdirectories for converting.\n";
		print "  --class-prefix=<prefix>        Set TestCase class prefix.\n";
		print "  --use-hash-postfix             Add hash part to output filename.\n";
		print "  --files-pattern=<pattern>      Glob pattern for input test files (*.html).\n";
		print "  --output-tpl=<file>            Template for result file. See TestExampleTpl.\n";
		print "  --custom-param1=<value>        Assign value to \$customParam1 in template.\n";
		print "  --custom-param2=<value>        Assign value to \$customParam2 in template.\n";
		print "  --browsers=<value>				Comma-separated list of browser names to use (from browsers.ini)\n";
		print "  --project-build=<value>		The build triggering this test (e.g. 42)\n";
		print "  --project-name=<value>			The project name triggering this test (e.g. Specsavers)\n";
		print "  --test-suite=<value>			The test suite reference by ID or name in the JSON file\n";
		print "  --screenshotsOnEveryStep=<value> Take screenshots on every `click` or `open` event in the test\n";
		print "  --browserstackLocal=<value>	Execute local testing from BrowserStack\n";
		print "  --browserstackLocalIdentifier=<value>	Specify a local identifier (if any)\n";
		print "  --video=<value>				Record video(1|0)\n";
		print "  --override-selenium-params=<key,value\$key,value...>	Specify a list of stores parameters to always override. E.g var_ORIGIN,http://localhost\n";
	}

	protected function _applyOptionsAndFlags($options, $flags) {
		if (is_array($options)) {
			foreach ($options as $opt) {
				if (is_string($opt)) {
					switch ($opt) {
						case 'recursive':
							$this->_recursive = true;
							break;
						case 'use-hash-postfix':
							$this->_useHashFilePostfix = true;
							break;
						case 'selenium2':
							$this->_converter->useSelenium2();
							break;
						default:
							print "Unknown option \"$opt\".\n";
							exit(1);
					}
				} else if (is_array($opt)) {
					switch ($opt[0]) {
						case 'php-prefix':
							$this->_phpFilePrefix = $opt[1];
							break;
						case 'php-postfix':
							$this->_phpFilePostfix = $opt[1];
							break;
						case 'browser':
							$this->_converter->setBrowser($opt[1]);
							break;
						case 'browser-url':
							$this->_converter->setTestUrl($opt[1]);
							break;
						case 'remote-host':
							$this->_converter->setRemoteHost($opt[1]);
							break;
						case 'remote-port':
							$this->_converter->setRemotePort($opt[1]);
							break;
						case 'dest':
							$this->_destFolder = $opt[1];
							break;
						case 'class-prefix':
							$this->_converter->setTplClassPrefix($opt[1]);
							break;
						case 'use-hash-postfix':
							$this->_useHashFilePostfix = true;
							break;
						case 'files-pattern':
							$this->_htmlPattern = $opt[1];
							break;
						case 'output-tpl':
							$this->_tplFile = $opt[1];
							break;
						case 'custom-param1':
							$this->_converter->setTplCustomParam1($opt[1]);
							break;
						case 'custom-param2':
							$this->_converter->setTplCustomParam2($opt[1]);
							break;
						case 'browsers':
							$this->_browsers = $opt[1];
							$this->_converter->browsers = $this->_browsers;
							break;
						case 'project-build':
							$this->_projectBuild = $opt[1];
							$this->_converter->_projectBuild = $this->_projectBuild;
							break;
						case 'project-name':
							$this->_projectName = $opt[1];
							$this->_converter->_projectName = $this->_projectName;
							break;
						case 'test-suite':
							$this->_suiteReference = $opt[1];
							$this->_converter->_suiteReference = $this->_suiteReference;
							break;
						case 'screenshotsOnEveryStep':
							$this->_converter->screenshotsOnEveryStep = $opt[1];
							break;
						case 'browserstackLocal':
							$this->_converter->browserstackLocal = $opt[1];
							break;
						case 'browserstackLocalIdentifier':
							$this->_converter->browserstackLocalIdentifier = $opt[1];
							break;
						case 'override-selenium-params':
							if (isset($opt[1])) {
								$this->_converter->overrideSeleniumParams = $opt[1];
							}
							break;
						case 'video':
							$this->_converter->video = $opt[1] == 'false' || $opt[1] == '0' ? false : true;
							break;
						case 'single-test':
							if (isset($opt[1])) {
								$this->_converter->singleTest = true;
							}
							break;
						default:
							print "Unknown option \"{$opt[0]}\".\n";
							exit(1);
					}
				}
			}
		}

		if (is_array($flags)) {
			foreach ($flags as $flag) {
				switch ($flag) {
					case 'r':
						$this->_recursive = true;
						break;
					default:
						print "Unknown flag \"$flag\".\n";
						exit(1);
				}
			}
		}
	}

	public function run($arguments, $options, $flags) {
		$this->_printTitle();
		$this->_applyOptionsAndFlags($options, $flags);
		if (empty($arguments)) {
			$this->_printHelp();
		} else if (!empty($arguments)) {
			$first = array_shift($arguments);
			$second = array_shift($arguments);
			if ($first && is_string($first)) {
				if (is_file($first)) {
					$jsonFileName = $first;
					if (is_readable($jsonFileName)) {
						$phpFileName = '';
						if ($second && is_string($second)) {
							$phpFileName = $second;
						}
						$this->_sourceBaseDir = rtrim(dirname($jsonFileName), "\\/") . "/";
						$this->parseFile($jsonFileName, $phpFileName);
						print "OK.\n";
						exit(0);
					} else {
						print "Cannot open file \"$jsonFileName\".\n";
						exit(1);
					}
				} else {
					print "\"$first\" is not an existing file.\n";
					exit(1);
				}
			}
		}
	}

	protected function parseFile($jsonFileName, $phpFileName) {
		$jsonContent = file_get_contents($jsonFileName);
		
		if ($jsonContent) {
			$dir = dirname(realpath($jsonFileName));
			$result = $this->_converter->convertJSON($jsonContent, $this->_makeTestName($jsonFileName), $this->_tplFile, $dir);

			if (!$phpFileName) {
				$phpFileName = $this->_makeOutputFilename($this->_suiteReference, $jsonContent);
			}
			
			file_put_contents($phpFileName, $result);
			print $phpFileName . "\n";
		}
	}

	protected function _makeOutputFilename($jsonFileName, $htmlContent) {
		$fileName = $this->_makeTestName($jsonFileName);

		if ($this->_destFolder) {
			$filePath = rtrim($this->_destFolder, "\\/") . "/";
			if (!realpath($filePath)) {
				//path is not absolute
				$filePath = $this->_sourceBaseDir . $filePath;
				if (!realpath($filePath)) {
					print "Directory \"$filePath\" not found.\n";
					exit(1);
				}
			}
		} else {
			$filePath = dirname($jsonFileName) . "/";
		}

		if ($this->_useHashFilePostfix) {
			$hashPostfix = '_' . substr(md5($htmlContent), 0, 8) . '_';
		} else {
			$hashPostfix = '';
		}

		$phpFileName = $filePath . $this->_phpFilePrefix
				. preg_replace("/\..+$/", '', $fileName)
				. $hashPostfix
				. $this->_phpFilePostfix . ".php";
		return $phpFileName;
	}

	/**
	 * Makes output test name considering path.
	 * 
	 * If destination folder is not defined
	 * returns base name of html file without extension.
	 * Example: 
	 * auth/login/simple.html -> simple
	 * 
	 * If destination folder is defined
	 * returns base name of html file prefixed with
	 * name of folder accordingly to destination folder.
	 * Example:
	 * auth/login/simple.html -> Auth_login_simple
	 * 
	 * @param string $jsonFileName input file name
	 * @return string output test name
	 */
	protected function _makeTestName($jsonFileName) {
		/* get from file if this is empty */
		$testName = basename($jsonFileName);

		if ($this->_destFolder) {
			$absPath = str_replace('\\', '_', $jsonFileName);
			$absPath = str_replace('/', '_', $absPath);
			$destPath = str_replace('\\', '_', $this->_sourceBaseDir);
			$destPath = str_replace('/', '_', $destPath);
			$testName = str_replace($destPath, '', $absPath);
		}

		$testName = preg_replace('/[^A-Za-z0-9]/', '_', ucwords($testName));
		return $testName;
	}

	protected function globRecursive($pattern, $flags) {
		$files = glob($pattern, $flags);
		foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
			$files = array_merge($files, $this->globRecursive($dir . '/' . basename($pattern), $flags));
		}

		return $files;
	}

}
