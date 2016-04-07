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

/**
 * Provides formatting some special commands into
 * PHPUnit_Extensions_Selenium2TestCase analogues.
 * 
 * Tested with PHPUnit 3.7.27.
 * 
 */
class Commands2 {

	/**
	 * Take screenshots after every "open" and "click" step?
	 * @var boolean
	 */
	public $screenshotsOnEveryStep = false;
	protected $_obj = '$this';
	
	public $stepCount = 1;

	/**
	 * 
	 * @param string $name
	 * @param string $arguments
	 * @return string
	 */
	public function __call($name, $arguments) {
		if (isset($arguments[1]) && false !== $arguments[1]) {
			$line = "//{$this->_obj}->$name(\"{$arguments[0]}\", \"{$arguments[1]}\");";
			$this->_addNote('Unknown command', $name, $arguments);
		} else if (false !== $arguments[0]) {
			$line = "{$this->_obj}->$name(\"{$arguments[0]}\")";
		} else {
			$line = "{$this->_obj}->$name()";
		}
		return $line;
	}

	protected function _addNote($noteText, $commandName, $arguments = array()) {
		if (is_string($arguments)) {
			$arguments = array($arguments);
		}
		echo "$noteText - $commandName('" . implode("', '", $arguments) . "')\n";
	}

	public function open($target) {
		return array_merge(["{$this->_obj}->url(\"$target\");"], $this->screenshotOnStep());
	}

	public function type($selector, $value) {
		$lines = array();
		$lines[] = '$input = ' . $this->_byQuery($selector) . ';';
		$lines[] = '$input->clear();';
		$lines[] = '$input->value("' . $value . '");';
		return $lines;
	}

	/**
	 * Sends key by key
	 * 
	 * In PHPUnit 3.7.27 $this->keys() is not implemented,
	 * but $this->value() does it key by key.
	 * 
	 * @param type $selector
	 * @param type $value
	 * @return string
	 */
	public function sendKeys($selector, $value) {
		$lines = array();
		$lines[] = '$input = ' . $this->_byQuery($selector) . ';';
		$lines[] = '$input->value("' . $value . '");';
		return $lines;
	}

	protected function _byQuery($selector) {
		if (preg_match('/^\/\/(.+)/', $selector)) {
			/* "//a[contains(@href, '?logout')]" */
			return $this->byXPath($selector);
		} else if (preg_match('/^([a-z]+)=(.+)/', $selector, $match)) {
			/* "id=login_name" */
			switch ($match[1]) {
				case 'id':
					return $this->byId($match[2]);
					break;
				case 'name':
					return $this->byName($match[2]);
					break;
				case 'link':
					return $this->byLinkText($match[2]);
					break;
				case 'xpath':
					return $this->byXPath($match[2]);
					break;
				case 'css':
					$cssSelector = str_replace('..', '.', $match[2]);
					return $this->byCssSelector($cssSelector);
					break;
			}
		}
		throw new \Exception("Unknown selector '$selector'");
	}

	/**
	 * By ID
	 * @param string $selector
	 */
	public function byId($selector) {
		// By ID (wait for element)
		return "{$this->_obj}->byId(\"{$selector}\", true)";
	}

	/**
	 * By Name
	 * @param string $selector
	 */
	public function byName($selector) {
		// By Name (wait for element)
		return "{$this->_obj}->byName(\"{$selector}\", true)";
	}

	/**
	 * By css
	 * @param string $selector
	 */
	public function byCssSelector($selector) {
		// By css (wait for element)
		return "{$this->_obj}->byCssSelector(\"{$selector}\", true)";
	}

	/**
	 * byXPath
	 * @param string $selector
	 */
	public function byXPath($selector) {
		// By XPath (wait for element)
		return "{$this->_obj}->byXPath(\"{$selector}\", true)";
	}

	/**
	 * byLinkText
	 * @param string $selector
	 */
	public function byLinkText($selector) {
		// By LinkText (wait for element)
		return "{$this->_obj}->byLinkText(\"{$selector}\", true)";
	}

	public function click($selector) {
		$lines = array();
		$lines[] = '$input = ' . $this->_byQuery($selector) . ';';
		$lines[] = '$input->click();';
		return $lines;
	}

	public function select($selectSelector, $optionSelector) {
		$lines = array();
		$lines[] = '$element = ' . $this->_byQuery($selectSelector) . ';';
		$lines[] = '$selectElement = ' . $this->_obj . '->select($element);';

		if (preg_match('/label=(.+)/', $optionSelector, $match)) {
			$lines[] = '$selectElement->selectOptionByLabel("' . $match[1] . '");';
		} else if (preg_match('/value=(.+)/', $optionSelector, $match)) {
			$lines[] = '$selectElement->selectOptionByValue("' . $match[1] . '");';
		} else {
			throw new \Exception("Unknown option selector '$optionSelector'");
		}

		return $lines;
	}

	/**
	 * 
	 * @param string $target
	 * @return array
	 */
	public function clickAndWait($target) {
		return array_merge($this->screenshotOnStep(), $this->click($target));
	}

	/**
	 * If the configuration screenshotsOnEveryStep is set to 1, a screenshot will be taken
	 */
	public function screenshotOnStep() {
		$lines = [];
		if ($this->screenshotsOnEveryStep) {
			$lines[] = '$this->takeScreenshot("step-' . $this->stepCount . '");';
			$this->stepCount++;
		}
		return $lines;
	}

	/**
	 * 
	 * @param string $target
	 * @param string $value
	 * @return string
	 */
	public function assertText($target, $value) {
		$lines = array();
		$lines[] = '$input = ' . $this->_byQuery($target) . ';';

		if (strpos($value, '*')) {
			$value = '/' . str_replace('*', '.+', $value) . '/';
			$lines[] = "{$this->_obj}->assertRegExp(\"$value\", \$input->text());";
		} else {
			$lines[] = "{$this->_obj}->assertEquals(\"$value\", \$input->text());";
		}

		return $lines;
	}

	/**
	 * 
	 * @param string $target
	 * @param string $value
	 * @return string
	 */
	public function assertNotText($target, $value) {
		$lines = array();
		$lines[] = '$input = ' . $this->_byQuery($target) . ';';

		if (strpos($value, '*')) {
			$value = '/' . str_replace('*', '.+', $value) . '/';
			$lines[] = "{$this->_obj}->assertNotRegExp(\"$value\", \$input->text());";
		} else {
			$lines[] = "{$this->_obj}->assertNotEquals(\"$value\", \$input->text());";
		}

		return $lines;
	}

	/**
	 * 
	 * @param string $target
	 * @return string
	 */
	public function assertElementPresent($target) {
		$lines = array();
		$lines[] = 'try {';
		$lines[] = "    " . $this->_byQuery($target) . ';';
		$lines[] = "    {$this->_obj}->assertTrue(true);";
		$lines[] = '} catch (PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {';
		$lines[] = "    if (PHPUnit_Extensions_Selenium2TestCase_WebDriverException::NoSuchElement === \$e->getCode()) {";
		$lines[] = "        {$this->_obj}->assertTrue(false, \"Element $target not found\");";
		$lines[] = "    } else { ";
		$lines[] = "        throw \$e;";
		$lines[] = "    }";
		$lines[] = '}';
		return $lines;
	}

	/**
	 * 
	 * @param string $target
	 * @return string
	 */
	public function assertElementNotPresent($target) {
		$lines = array();
		$lines[] = 'try {';
		$lines[] = "    " . $this->_byQuery($target) . ';';
		$lines[] = "    {$this->_obj}->assertTrue(false, \"Element $target was found\");";
		$lines[] = '} catch (PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {';
		$lines[] = "    {$this->_obj}->assertEquals(PHPUnit_Extensions_Selenium2TestCase_WebDriverException::NoSuchElement, \$e->getCode());";
		$lines[] = '}';
		return $lines;
	}

	/**
	 * 
	 * @param string $target
	 * @return array
	 */
	public function waitForElementPresent($target) {
		$localExpression = str_replace($this->_obj, '$testCase', $this->_byQuery($target));

		/*
		 * In Selenium 2 we can not interact with invisible elements.
		 */

		$lines = array();
		$lines[] = $this->_obj . '->waitUntil(function($testCase) {';
		$lines[] = '    try {';
		$lines[] = "        \$element = $localExpression;";
		$lines[] = "        if (\$element && \$element->displayed()) {";
		$lines[] = "            return true;";
		$lines[] = "        }";
		$lines[] = '    } catch (Exception $e) {}';
		$lines[] = '}, 30000);';
		return $lines;
	}

	public function waitForElementNotPresent($target) {
		$localExpression = str_replace($this->_obj, '$testCase', $this->_byQuery($target));
		$lines = array();
		$lines[] = $this->_obj . '->waitUntil(function($testCase) {';
		$lines[] = "    try {";
		$lines[] = "        $localExpression;";
		$lines[] = '    } catch (PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {';
		$lines[] = "        if (PHPUnit_Extensions_Selenium2TestCase_WebDriverException::NoSuchElement == \$e->getCode()) {";
		$lines[] = "            return true;";
		$lines[] = "        }";
		$lines[] = '    }';
		$lines[] = '}, 30000);';
		return $lines;
	}

	/**
	 * SELENIUM DEPRECATED
	 * 
	 * @param string $target
	 * @return array
	 */
	public function waitForTextPresent($text) {
		$this->_addNote('Deprecated command', 'waitForTextPresent', $text);
		$lines = array();
		$lines[] = $this->_obj . '->waitUntil(function($testCase) {';
		$lines[] = "    if (strpos(\$testCase->byTag('body')->text(), \"$text\") !== false) {";
		$lines[] = "         return true;";
		$lines[] = '    }';
		$lines[] = '}, 8000);';
		return $lines;
	}

	/**
	 * 
	 * @param string $expression
	 * @return array
	 */
	protected function _waitWrapper($expression) {
		$localExpression = str_replace($this->_obj, '$testCase', $expression);
		$lines = array();
		$lines[] = $this->_obj . '->waitUntil(function($testCase) {';
		$lines[] = '    try {';
		$lines[] = "        $localExpression";
		$lines[] = '    } catch (PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {}';
		$lines[] = '}, 8000);';
		return $lines;
	}

	/**
	 * 
	 * @param string $expression
	 * @return string
	 */
	protected function _assertFalse($expression) {
		return "{$this->_obj}->assertFalse($expression);";
	}

	/**
	 * 
	 * @param string $expression
	 * @return string
	 */
	public function storeValue($target, $value) {
		$lines = array();
		$lines[] = "{$this->_obj}->storeValue(\"$target\", \"$value\");";
		return $lines;
	}

	/**
	 * 
	 * @param string $expression
	 * @return string
	 */
	public function storeXpathCount($target, $value) {
		$lines = array();
		$lines[] = "{$this->_obj}->storeXpathCount(\"$target\", \"$value\");";
		return $lines;
	}

	public function store($target, $value) {
		$lines = array();
		$lines[] = "{$this->_obj}->store(\"$value\", \"$target\");";
		return $lines;
	}
	
	/**
	 * Store evaluated expression (javascript)
	 * @param string $target
	 * @param string $value
	 * @return array
	 */
	public function storeEval($target, $value) {
		$lines = array();
		$lines[] = "{$this->_obj}->store(\"$value\", \"javascript:$target\");";
		return $lines;
	}
	
	/**
	 * Fire javascript event on page 
	 * @param string $target xpath, css, id expression
	 * @param string $value Event type
	 * @return array
	 */
	public function fireEvent($target, $value) {
		$lines = array();
		$lines[] = "{$this->_obj}->fireEvent(\"$target\", \"$value\");";
		return $lines;
	}

	/**
	 * 
	 * @param string $expression
	 * @return string
	 */
	protected function _assertTrue($expression) {
		return "{$this->_obj}->assertTrue($expression);";
	}

	protected function _assertPattern($target, $string) {
		$target = str_replace("?", "[\s\S]", $target);
		$expression = "(bool)preg_match('/^$target$/', " . $string . ")";
		return $expression;
	}

	protected function _isTextPresent($text) {
		return "(bool)(strpos({$this->_obj}->byTag('body')->text(), \"$text\") !== false)";
	}

	/**
	 * SELENIUM DEPRECATED
	 * 
	 * @param type $target
	 * @return type
	 */
	public function assertTextPresent($target) {
		$this->_addNote('Deprecated command', 'assertTextPresent', $target);
		return $this->_assertTrue($this->_isTextPresent($target));
	}

	/**
	 * SELENIUM DEPRECATED
	 * 
	 * @param type $target
	 * @return type
	 */
	public function assertTextNotPresent($target) {
		$this->_addNote('Deprecated command', 'assertTextNotPresent', $target);
		return $this->_assertFalse($this->_isTextPresent($target));
	}

	public function waitForText($target, $value) {
		$localExpression = '$input = ' . str_replace($this->_obj, '$testCase', $this->_byQuery($target));
		$lines = array();
		$lines[] = $this->_obj . '->waitUntil(function($testCase) {';
		$lines[] = "    $localExpression;";
		$lines[] = "    if (('$value' === '' && \$input->text() === '') || strpos(\$input->text(), \"$value\") !== false) {";
		$lines[] = "         return true;";
		$lines[] = '    }';
		$lines[] = '}, 8000);';
		return $lines;
	}

	public function waitForNotText($target, $value) {
		$localExpression = '$input = ' . str_replace($this->_obj, '$testCase', $this->_byQuery($target));
		$lines = array();
		$lines[] = $this->_obj . '->waitUntil(function($testCase) {';
		$lines[] = "    try {";
		$lines[] = "        $localExpression;";
		$lines[] = "        if (('$value' === '' && \$input->text() !== '') || strpos(\$input->text(), \"$value\") === false) {";
		$lines[] = "            return true;";
		$lines[] = '        }';
		$lines[] = '    } catch (PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {';
		$lines[] = "        if (PHPUnit_Extensions_Selenium2TestCase_WebDriverException::NoSuchElement == \$e->getCode()) {";
		$lines[] = "            return true;";
		$lines[] = "        }";
		$lines[] = '    }';
		$lines[] = '}, 8000);';
		return $lines;
	}

	public function assertConfirmation($text) {
		return $this->assertAlert($text);
	}

	public function assertAlert($text) {
		$lines = array();
		$lines[] = "if ( !is_null({$this->_obj}->alertText()) ) {";
		$lines[] = "    {$this->_obj}->assertEquals(\"$text\", {$this->_obj}->alertText());";
		$lines[] = "}";
		$lines[] = "{$this->_obj}->acceptAlert();";
		return $lines;
	}

	public function runScript($script) {
		$lines = array();
		$lines[] = "\$script = \"$script\";";
		$lines[] = "\$result = {$this->_obj}->execute(array(";
		$lines[] = "    'script' => \$script,";
		$lines[] = "    'args' => array()";
		$lines[] = "));";
		return $lines;
	}

	/**
	 * 
	 * @param string $target
	 * @param string $varName
	 * @return string
	 */
	public function storeAttribute($target, $varName) {
		$this->_checkVarName($varName);
		$line = "{$this->_obj}->store(\"$varName\", " . $this->_getAttributeByLocator($target) . ");";
		return $line;
	}

	/**
	 * 
	 * @param string $target
	 * @param string $value
	 * @return string
	 */
	public function assertAttribute($target, $value) {
		$line = "{$this->_obj}->assertEquals(\"$value\", " . $this->_getAttributeByLocator($target) . ');';
		return $line;
	}

	/**
	 * Returns value of dom attribute
	 * 
	 * @param string $locator - locator ending with @attr. For example css=.some-link@href
	 * @return string expression
	 */
	protected function _getAttributeByLocator($locator) {
		/*
		 * We dont have a $this->getAttribute($locator)
		 */
		/*
		 * /(.+)\/@([\S])+$/ ~ //div/a/@href -> //div/a
		 */
		$elementTarget = preg_replace('/(.+?)\/?@([\S]+)$/', '$1', $locator);
		$attribute = preg_replace('/(.+?)\/?@([\S]+)$/', '$2', $locator);
		$line = $this->_byQuery($elementTarget) . "->attribute('$attribute')";
		return $line;
	}

	protected function _checkVarName($varName) {
		$reservedWords = array(
			'element',
			'input',
			'script',
			'result',
			'selectElement',
		);
		if (in_array($varName, $reservedWords)) {
			$this->_addNote("'$varName' is bad name for variable, converter uses it for other commands", 'store*', $varName);
		}
	}

	public function storeText($target, $varName) {
		$this->_checkVarName($varName);
		$lines = array();
		$lines[] = '$element = ' . $this->_byQuery($target) . ';';
		$lines[] = "\$$varName = \$element->text();";
		return $lines;
	}

	/**
	 * 
	 * @param type $target
	 * @return type
	 */
	public function mouseOver($target) {
		$lines = array();
		$lines[] = '$element = ' . $this->_byQuery($target) . ';';
		$lines[] = "{$this->_obj}->moveto(\$element);";
		return $lines;
	}

	/**
	 * Waits for any response
	 * 
	 * @param int $timeout ms
	 * @return type
	 */
	public function waitForPageToLoad($timeout) {
		$timeout = intval($timeout);
		$lines = array();
		$lines[] = $this->_obj . '->waitUntil(function($testCase) {';
		$lines[] = '    if (strlen($testCase->source()) > 0) {';
		$lines[] = '        return true;';
		$lines[] = '    }';
		$lines[] = " }, $timeout);";
		return $lines;
	}

}
