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

namespace Selenium2php;

use Exception;
use SebastianBergmann\RecursionContext\Exception as Exception2;
use SimpleXMLElement;
use Zend_Config_Ini;

/**
 * Converts HTML text of Selenium test case recorded from Selenium IDE into
 * PHP code for PHPUnit_Extensions_SeleniumTestCase as TestCase file.
 */
class Converter {

	protected $_testName = '';
	protected $_lastTestName = '';
	protected $_testIndex = 0;
	protected $_testUrl = '';
	protected $_defaultTestName = 'some';
	protected $_defaultTestUrl = 'http://example.com';
	protected $_selenium2 = false;
	protected $_commands = array();
	protected $_tplEOL = PHP_EOL;
	protected $_tplCommandEOL = '';
	protected $_tplFirstLine = '<?php';
	protected $methodNames = array();

	/**
	 * Build ID specification
	 * @var string
	 */
	public $_projectBuild = null;

	/**
	 *
	 * @var string Project name
	 */
	public $_projectName = null;

	/**
	 *
	 * @var string
	 */
	public $browsers;

	/**
	 *
	 * @var boolean
	 */
	public $screenshotsOnEveryStep = false;

	/**
	 *
	 * @var boolean
	 */
	public $browserstackLocal = false;

	/**
	 *
	 * @var string
	 */
	public $browserstackLocalIdentifier = false;

	/**
	 *
	 * @var string
	 */
	public $singleTest = false;

	/**
	 * Should we record video of the session?
	 * @var bool
	 */
	public $video = true;

	/**
	 *
	 * @var string
	 */
	public $overrideSeleniumParams = null;

	/**
	 * Array of strings with text before class definition
	 * @var string[]
	 */
	protected $_tplPreClass = array();

	/**
	 * The class prefix to use when generating test classes
	 * @var string
	 */
	protected $_tplClassPrefix = '';

	/**
	 * The parent class to inherit from when generating test classes
	 * @var string
	 */
	protected $_tplParentClass = 'NI_Test_PHPUnit_Selenium2TestCase';

	/**
	 * Array of strings with some methods in class
	 * @var array
	 */
	protected $_tplAdditionalClassContent = array();

	/**
	 * The default browser to use when testing
	 * @var string
	 */
	protected $_browser = 'firefox';

	/**
	 * Address of Selenium Server
	 * @var string
	 */
	protected $_remoteHost = '';

	/**
	 * Port of Selenium Server
	 * @var string
	 */
	protected $_remotePort = '';

	/**
	 *
	 * @var string
	 */
	protected $_tplCustomParam1 = '';

	/**
	 *
	 * @var string
	 */
	protected $_tplCustomParam2 = '';

	/**
	 * Parses HTML string into array of commands,
	 * determines testHost and testName.
	 *
	 * @param string $htmlStr
	 * @throws Exception
	 */
	protected function _parseHtml($htmlStr) {
		require_once 'libs/simple_html_dom.php';
		$html = str_get_html($htmlStr);
		if ($html && $html->find('link')) {

			if (!$this->_testUrl) {
				$this->_testUrl = $html->find('link', 0)->href;
			}
			if (!$this->_testName) {
				$title = $html->find('title', 0)->innertext;
				$title = preg_replace('/[^A-Za-z0-9]/', '_', ucwords($title));
				$this->_testName = preg_replace('/[^A-Za-z0-9]/', '_', ucwords($title));
			}

			foreach ($html->find('table tr') as $row) {
				if ($row->find('td', 2)) {
					$command = $row->find('td', 0)->innertext;
					$target = $row->find('td', 1)->innertext;
					$value = $row->find('td', 2)->innertext;

					$this->_commands[] = array(
						'command' => $command,
						'target' => $target,
						'value' => $value
					);
				}
			}
		} else {
			throw new Exception("HTML parse error");
		}
	}

	/**
	 * Parses HTML from a suite file
	 * @param string $htmlStr
	 */
	protected function _parseSuiteHtml($htmlStr, $suitePath) {
		$xml = simplexml_load_string($htmlStr);

		$results = [];
		$links = $xml->body->table->tbody;
		/* @var $links SimpleXMLElement */
		foreach ($links->children() as $link) {
			/* @var $link SimpleXMLElement */
			if ($link->td->a) {
				$linkAttributes = $link->td->a->attributes();
				$href = (string) $linkAttributes['href'];
				$fileName = $suitePath . DIRECTORY_SEPARATOR . $href;
				$results[] = $this->convert(file_get_contents($fileName), '', '', true);
			}
		}
		return $results;
	}

	/**
	 * Converts HTML text of Selenium test case into PHP code
	 *
	 * @param string $htmlStr content of html file with Selenium test case
	 * @param string $testName test class name (leave blank for auto)
	 * @param boolean $functionOnly
	 * @return string PHP test case file content
	 */
	public function convert($htmlStr, $testName = '', $tplFile = '', $functionOnly = false) {
		$this->_testName = $testName;
		$this->_commands = array();
		$this->_parseHtml($htmlStr);
		if ($tplFile) {
			if (is_file($tplFile)) {
				$content = $this->_convertToTpl($tplFile);
			} else {
				echo "Template file $tplFile is not accessible.";
				exit;
			}
		} else {
			$lines = $this->_composeLines($functionOnly);
			$content = $this->_composeStrWithIndents($lines, 4);
		}
		return $content;
	}

	/**
	 * @param $htmlStr
	 * @param string $testName
	 * @param string $tplFile
	 * @param string $suitePath
	 * @return string
	 */
	public function convertSuite($htmlStr, $testName = '', $tplFile = '', $suitePath = '') {
		$testContent = $this->_parseSuiteHtml($htmlStr, $suitePath);
		$testStringContent = implode("\n\n", $testContent);
		if ($tplFile) {
			if (is_file($tplFile)) {
				$this->_testName = $testName;
				$content = $this->_convertToTpl($tplFile, $testStringContent);
			} else {
				echo "Template file $tplFile is not accessible.";
				exit;
			}
		} else {
			$content = $this->_composeStr($this->_composeLines(false, $testStringContent));
		}

		return $content;
	}

	/**
	 * Implodes lines of file into one string
	 *
	 * @param array $lines
	 * @return string
	 */
	protected function _composeStr($lines) {
		return implode($this->_tplEOL, $lines);
	}

	/**
	 * Adds indents to each line except first
	 * and implodes lines into one string
	 *
	 * @param array $lines array of strings
	 * @param int $indentSize
	 * @return string
	 */
	protected function _composeStrWithIndents($lines, $indentSize) {
		foreach ($lines as $i => $line) {
			if ($i != 0) {
				$lines[$i] = $this->_indent($indentSize) . $line;
			}
		}
		$methodContent = $this->_composeStr($lines);
		// Match variables
		$re = '/(\\${[a-zA-Z0-9_]*\\})/';
		$replaceTemplate = '" . $this->getStoredValue("[value]") . "';
		preg_match_all($re, $methodContent, $matches);
		if (count($matches) > 0) {
			$search = $replace = [];
			$matchList = reset($matches);
			foreach ($matchList as $match) {
				$search[] = $match;
				$replace[] = str_replace(['[value]', '${', '}'], [$match, '', ''], $replaceTemplate);
			}
		}
		return str_replace($search, $replace, $methodContent);
	}

	/**
	 * Use template file for output result.
	 *
	 * @param string $tplFile filepath
	 * @param string $testMethodContent
	 * @return string output content
	 */
	protected function _convertToTpl(string $tplFile, string $testMethodContent = null) {
		$tpl = file_get_contents($tplFile);
		$testMethodName = $testMethodContent ? 'noop' : $this->_composeTestMethodName();
		$replacements = array(
			'{$comment}' => $this->_composeComment(),
			'{$className}' => $this->_composeClassName(),
			'{$browser}' => $this->_browser,
			'{$testUrl}' => $this->_testUrl ? $this->_testUrl : $this->_defaultTestUrl,
			'{$remoteHost}' => $this->_remoteHost ? $this->_remoteHost : '127.0.0.1',
			'{$remotePort}' => $this->_remotePort ? $this->_remotePort : '4444',
			'{$testMethodName}' => $testMethodName,
			'{$testMethodContent}' => $testMethodContent ? '' : $this->_composeStrWithIndents($this->_composeTestMethodContent(), 8),
			'{$testMethods}' => $testMethodContent,
			'{$customParam1}' => $this->_tplCustomParam1,
			'{$customParam2}' => $this->_tplCustomParam2,
			'{$browsers}' => $this->_createBrowsers()
		);
		if ($this->_lastTestName != $testMethodName) {
			$replacements['{$depends}'] = '@depends ' . $this->_lastTestName;
		}
		foreach ($replacements as $s => $r) {
			$tpl = str_replace($s, $r, $tpl);
		}
		return $tpl;
	}

	/**
	 * Creates the set of browsers to use. Returned as a string of PHP code which creates the test browser array.
	 *
	 * @return string
	 * @throws Exception
	 */
	protected function _createBrowsers() {

		$browsers = parse_ini_file(__DIR__ . DIRECTORY_SEPARATOR . 'conf' . DIRECTORY_SEPARATOR . 'browsers.ini', true);

		if (empty($this->browsers)) {
			return '';
		}
		$capabilities = $this->_createArrayParam('project', $this->_projectName) .
			$this->_createArrayParam('build', $this->_projectBuild) .
			$this->_createArrayParam('name', $this->_testName);
		if (intval($this->browserstackLocal)) {
			$capabilities .= $this->_createArrayParam('browserstack.local', (bool) $this->browserstackLocal);
			if ($this->browserstackLocalIdentifier) {
				$capabilities .= $this->_createArrayParam('browserstack.localIdentifier', $this->browserstackLocalIdentifier);
			}
		}

		if (!$this->video) {
			$capabilities .= $this->_createArrayParam('browserstack.video', 'false');
		}

		$template = "array(
			'browserName'				=> '{browserName}',
			'host'						=> 'hub.browserstack.com',
			'port'						=> 80,
			'sessionStrategy'			=> 'shared',
			'desiredCapabilities'		=> array(
				{$capabilities}
				'version'			 => '{version}',
				'browserstack.user'	 => BROWSERSTACK_USER,
				'browserstack.key'	 => BROWSERSTACK_KEY,
				'os'				 => '{os}',
				'os_version'		 => '{osVersion}',
				'resolution'		 => '{resolution}'
			)
		)";
		$browserArr = array();

		$browserList = explode(',', $this->browsers);
		foreach ($browserList as $browserName) {
			if (!isset($browsers[$browserName])) {
				throw new Exception("Unsupported browser with name $browserName specified");
			}
			$browser = $browsers[$browserName];
			$this->setDefaultValues($browser, ['browserName' => null, 'version' => null, 'os' => null, 'osVersion' => null, 'resolution' => null]);
			$browserArr[] = str_replace(['{browserName}', '{version}', '{os}', '{osVersion}', '{resolution}'], [$browser['browserName'], $browser['version'], $browser['os'], $browser['osVersion'], $browser['resolution']], $template);
		}


		return implode(',', $browserArr);
	}

	/**
	 * Creates a string representation of a key-value pair, if value is set
	 *
	 * @param string $name
	 * @param string|bool $value
	 * @return string
	 */
	protected function _createArrayParam(string $name, $value) {
		$return = '';
		if ($value) {
			if (is_bool($value)) {
				$return = "'{$name}'					 => " . ($value ? 'true' : 'false') . ",\n";
			} else {
				$return = "'{$name}'					 => '{$value}',\n";
			}
		}
		return $return;
	}

	/**
	 * Sets non-set values to default values
	 * @param array $array
	 * @param array $values key => default
	 */
	protected function setDefaultValues(array &$array, array $values) {
		foreach ($values as $value => $default) {
			if (!isset($array[$value])) {
				$array[$value] = $default;
			}
		}
	}

	/**
	 * Composes lines for the final class
	 * @param boolean $functionOnly If true, only the function contents will be created
	 * @param string $functionContent Extra content to be added instead the first function
	 * @return string
	 */
	protected function _composeLines($functionOnly = false, string $functionContent = null) {
		$lines = array();

		if (!$functionOnly) {
			$lines[] = $this->_tplFirstLine;
			$lines[] = $this->_composeComment();

			if (count($this->_tplPreClass)) {
				$lines[] = "";
				foreach ($this->_tplPreClass as $mLine) {
					$lines[] = $mLine;
				}
				$lines[] = "";
			}

			$lines[] = "class " . $this->_composeClassName() . " extends " . $this->_tplParentClass . "{";
			$lines[] = "";

			if (count($this->_tplAdditionalClassContent)) {
				foreach ($this->_tplAdditionalClassContent as $mLine) {
					$lines[] = $this->_indent(4) . $mLine;
				}
				$lines[] = "";
			}


			$lines[] = $this->_indent(4) . "function setUp(){";
			foreach ($this->_composeSetupMethodContent() as $mLine) {
				$lines[] = $this->_indent(8) . $mLine;
			}
			$lines[] = $this->_indent(4) . "}";
			$lines[] = "";
		}
		if ($functionContent) {
			$lines[] = $functionContent;
		} else {
			$methodName = $this->_composeTestMethodName();
			if ($this->_lastTestName && $this->_lastTestName != $methodName) {
				$lines[] = $this->_indent(4) . "/**";
				$lines[] = $this->_indent(4) . "* @depends " . $this->_lastTestName;
				$lines[] = $this->_indent(4) . "*/";
			}
			$lines[] = $this->_indent(4) . "function " . $methodName . "() {";
			$lines[] = $this->_indent(4) . "\$this->testIndex = {$this->_testIndex};";
			$lines[] = $this->_indent(4) . "\$this->log('Running {$methodName}');";

			$lines[] = $this->_indent(4) . 'try {';

			foreach ($this->_composeTestMethodContent() as $mLine) {
				$lines[] = $this->_indent(8) . $mLine;
			}

			$lines[] = $this->_indent(4) . "\$this->log('{$methodName} completed with success');";
			$lines[] = $this->_indent(4) . '} catch (Exception $e) {';
			$lines[] = $this->_indent(8) . '$this->log("Selenium test " . __METHOD__ . " failed with exception\n" . $e->getMessage());';
			$lines[] = $this->_indent(8) . '$this->log("Stacktrace\n" . $e->getTraceAsString());';
			$lines[] = $this->_indent(8) . '$this->takeScreenshot("failure");';
			$lines[] = $this->_indent(8) . '$this->fail("Selenium test " . __METHOD__ . " failed with exception\n" . $e->getMessage());';
			$lines[] = $this->_indent(4) . '}';

			$lines[] = "}";
			$lines[] = "";
			$this->_lastTestName = $methodName;
			$this->_testIndex++;
		}
		if (!$functionOnly) {
			$lines[] = "}";
		}
		return $lines;
	}

	/**
	 * The size of the indent (measured in spaces). Will be converted to a string of tabs (4 chars wide).
	 * @param int $size
	 * @return string
	 */
	protected function _indent(int $size) {
		return str_repeat("\t", (int) ($size / 4));
	}

	/**
	 * Construct the class name
	 * @return string
	 */
	protected function _composeClassName() {
		return $this->_tplClassPrefix . $this->_testName . "Test";
	}

	/**
	 * Construct the test method name
	 * @return string
	 */
	protected function _composeTestMethodName() {
		$return = $methodName = "test" . $this->_testName;
		if (isset($this->methodNames[$methodName])) {
			$return = $methodName . sprintf('%03d', (sizeof($this->methodNames[$methodName]) + 1));
		}
		$this->methodNames[$methodName][] = $return;

		return $return;
	}

	/**
	 * Construct the setup of browser, URL and port
	 * @return string[]
	 */
	protected function _composeSetupMethodContent() {
		$mLines = array();
		$mLines[] = '$this->setBrowser("' . $this->_browser . '");';
		if ($this->_testUrl) {
			$mLines[] = '$this->setBrowserUrl("' . $this->_testUrl . '");';
		} else {
			$mLines[] = '$this->setBrowserUrl("' . $this->_defaultTestUrl . '");';
		}
		if ($this->_remoteHost) {
			$mLines[] = '$this->setHost("' . $this->_remoteHost . '");';
		}
		if ($this->_remotePort) {
			$mLines[] = '$this->setPort("' . $this->_remotePort . '");';
		}
		return $mLines;
	}

	/**
	 * Walk through the commands array (for either Selenium RC or Selenium 2) and construct the test method
	 * @return array
	 */
	protected function _composeTestMethodContent() {
		if ($this->_selenium2) {
			require_once 'Commands2.php';
			$commands = new Commands2;
		} else {
			require_once 'Commands.php';
			$commands = new Commands;
		}
		$commands->screenshotsOnEveryStep = $this->screenshotsOnEveryStep;

		// Key value pairs
		$vars = explode('$', $this->overrideSeleniumParams);
		if (!empty($this->overrideSeleniumParams) && is_array($vars) && count($vars) > 0) {
			var_dump($vars);
			foreach ($vars as $var) {
				list($key, $value) = explode(',', $var);
				$commands->overrideSeleniumParams[$key] = $value;
			}
		}

		$mLines = array();

		foreach ($this->_commands as $row) {
			$command = $row['command'];
			$target = $this->_prepareHtml($row['target']);
			$value = $this->_prepareHtml($row['value']);
			$res = $commands->$command($target, $value);
			if (is_string($res)) {
				if ($this->_tplCommandEOL !== '') {
					$res .= $this->_tplCommandEOL;
				}
				$mLines[] = $res;
			} elseif (is_array($res)) {
				$size = count($res);
				$i = 0;
				foreach ($res as $subLine) {
					$i++;
					if ($size === $i && $this->_tplCommandEOL !== '') {
						$subLine .= $this->_tplCommandEOL;
					}

					$mLines[] = $subLine;
				}
			}
		}

		return $mLines;
	}

	/**
	 * Sanitize a HTML string
	 * @param string $html
	 * @return mixed|string
	 */
	protected function _prepareHtml(string $html) {
		$res = $html;
		$res = str_replace('&nbsp;', ' ', $res);
		$res = html_entity_decode($res);
		$res = str_replace('<br />', '\n', $res);
		$res = str_replace('"', '\\"', $res);
		return $res;
	}

	/**
	 * Create a comment for the test cases
	 * @return string
	 */
	protected function _composeComment() {
		$lines = array();
		$lines[] = "/*";
		$lines[] = "* Autogenerated from Selenium html test case by Selenium2php.";
		$lines[] = "* " . date("Y-m-d H:i:s");
		$lines[] = "*/";
		$line = implode($this->_tplEOL, $lines);
		return $line;
	}

	/**
	 * Set the URL to test against
	 * @param string $testUrl
	 */
	public function setTestUrl(string $testUrl) {
		$this->_testUrl = $testUrl;
	}

	/**
	 * The host of the remote Selenium server
	 * @param string $host
	 */
	public function setRemoteHost(string $host) {
		$this->_remoteHost = $host;
	}

	/**
	 * The port of the Selenium server
	 * @param string $port
	 */
	public function setRemotePort(string $port) {
		$this->_remotePort = $port;
	}

	/**
	 * Sets browser where test runs
	 *
	 * @param string $browser example: *firefox
	 */
	public function setBrowser(string $browser) {
		$this->_browser = $browser;
	}

	/**
	 * Sets lines of text before test class defenition
	 * @param string $text
	 */
	public function setTplPreClass(array $linesOfText) {
		$this->_tplPreClass = $linesOfText;
	}

	/**
	 * Sets the EOL symbol for use in generating the test class
	 * @param $tplEOL
	 */
	public function setTplEOL($tplEOL) {
		$this->_tplEOL = $tplEOL;
	}

	/**
	 * Sets lines of text into test class
	 *
	 * @param array $content - array of strings with methods or properties
	 */
	public function setTplAdditionalClassContent(array $linesOfText) {
		$this->_tplAdditionalClassContent = $linesOfText;
	}

	/**
	 * Sets name of class as parent for test class
	 * Default: PHPUnit_Extensions_SeleniumTestCase
	 *
	 * @param string $className
	 */
	public function setTplParentClass(string $className) {
		$this->_tplParentClass = $className;
	}

	/**
	 * Use the given class prefix
	 * @param string $prefix
	 */
	public function setTplClassPrefix(string $prefix) {
		$this->_tplClassPrefix = $prefix;
	}

	/**
	 * Tells the converter to use Selenium 2 templates
	 */
	public function useSelenium2() {
		$this->_selenium2 = true;
		$this->setTplParentClass('PHPUnit_Extensions_Selenium2TestCase');
		$this->_tplCommandEOL = PHP_EOL;
	}

	/**
	 * Passes value to template file
	 *
	 * @param string $value
	 */
	public function setTplCustomParam1(string $value) {
		$this->_tplCustomParam1 = $value;
	}

	/**
	 * Passes value to template file
	 *
	 * @param string $value
	 */
	public function setTplCustomParam2(string $value) {
		$this->_tplCustomParam2 = $value;
	}

}
