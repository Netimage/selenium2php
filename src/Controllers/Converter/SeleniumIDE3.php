<?php

namespace Combine\Controllers\Converter;

use Exception;
use SebastianBergmann\RecursionContext\Exception as Exception2;
use JsonSerializable;
use Zend_Config_Ini;

class SeleniumIDE3Converter extends Base
{

    /**
     *
     * @var string Name or test suite reference by ID in JSON file
     */
    public $_suiteReference = null;


    /**
     * Parses JSON array from test case object of the belonging commands,
     * determines test method name.
     *
     * @param string $jsonStr
     * @throws Exception
     */
    protected function _parseTestCase(array $jsonTestCase, &$testMethodName = '')
    {
        if ($jsonTestCase) {
            if (empty($testMethodName)) {
                $title = $jsonTestCase['name'];
                $testMethodName = $this->_makeTestName($title);
            }

            $numScreenCapture = 1;
            $commands = array();
            foreach ($jsonTestCase['commands'] as $commandContent) {
                if ($commandContent['command']) {
                    $commands[] = array(
                        'command' => trim($commandContent['command']),
                        'target' => trim($commandContent['target']),
                        'value' => trim($commandContent['value'])
                    );
                }
                /**
                 * Work-around to be able for scripts to handle screenshot,
                 * even though Selenium IDE 3 does not include this.
                 */
                if ($commandContent['comment'] == 'captureEntirePageScreenshot') {
                    $commands[] = array(
                        'command' => trim('captureEntirePageScreenshot'),
                        'target' => trim(($commandContent['target'] ? $commandContent['target'] : "${VAR_FILEPATH}/{$this->_testName}_{$numScreenCapture}.uat.tc.png")),
                        'value' => ''
                    );
                    $numScreenCapture++;
                }
            }
        } else {
            throw new Exception("JSON test case parse error");
        }
        return $commands;
    }

    /**
     * Create a comment for the test cases
     * @return string
     */
    protected function _composeComment()
    {
        $lines = array();
        $lines[] = "/*";
        $lines[] = "* Autogenerated from Selenium IDE 3 JSON test case by Selenium2php.";
        $lines[] = "* " . date("Y-m-d H:i:s");
        $lines[] = "*/";
        $line = implode($this->_tplEOL, $lines);
        return $line;
    }


	/**
	 * Parses HTML from a suite file
	 * @param string $jsonStr
	 * @param string $suitePath
	 */
	protected function _parse(string $jsonStr, string $suitePath) {
		$json = json_decode($jsonStr, true);

		// Adding keys to the test cases
		$tests = array();
		foreach ($json['tests'] as $value) {
			$key = $value['id'];
			$tests[$key] = $value;
		}
		if (sizeof($tests) > 0) {
			$json['tests'] = $tests;
		}

		$results = [];
		foreach ($json['suites'] as $testSuite) {
			if ($testSuite['name'] == $this->_suiteReference || $testSuite['id'] == $this->_suiteReference) {
				$this->_testName = $this->_makeTestName($testSuite['name']);
				foreach ($testSuite['tests'] as $testCaseReference) {
					/* @var $testCaseReference string */
					$jsonTestCase = $json['tests'][$testCaseReference];

					$testMethodName = '';
					$results[$testCaseReference]['commands'] = $this->_parseTestCase($jsonTestCase, $testMethodName);
					$results[$testCaseReference]['name'] = $testMethodName;
				}

				break;
			}
		}

		return $results;
	}

	/**
	 * Converts the JSON string to a set of test cases
	 * @param string $jsonStr
	 * @param string $testName
	 * @param string $tplFile
	 * @param string $suitePath
	 * @return string
	 */
	public function convertJSON(string $jsonStr, string $testName = '', string $tplFile = '', string $suitePath = '') {
		$commandLines = $this->_parse($jsonStr, $suitePath);

		// $testStringContent = implode("\n\n", $testContent);
		if ($tplFile) {
			if (is_file($tplFile)) {
				$this->_testName = $testName;
				$content = $this->_convertToTpl($tplFile, $commandLines);
			} else {
				echo "Template file {$tplFile} is not accessible.";
				exit;
			}
		} else {
			$content = $this->_composeStr($this->_composeLines($commandLines, false));
		}

		return $content;
	}

}
