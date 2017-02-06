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

	/**
	 * Array of parameters to always override
	 * @var array
	 */
	public $overrideSeleniumParams = array();
	protected $_obj = '$this';
	protected $_confirm = true;
	public $stepCount = 1;
	public $waitInMilliseconds = 30000;

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
		$lines[] = "\$this->log(\"Typing $selector : $value\");";
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
		$lines[] = "\$this->log(\"Sending keys $selector : $value\");";
		$lines[] = '$input = ' . $this->_byQuery($selector) . ';';
		$lines[] = '$input->value("' . $value . '");';
		return $lines;
	}

	/**
	 * 
	 * @param type $selector
	 * @param boolean $wait Wait for element to be visible (currently only implemented on ID)
	 * @return type
	 * @throws \Exception
	 */
	protected function _byQuery($selector, $wait = true) {
		if (preg_match('/^\/\/(.+)/', $selector)) {
			/* "//a[contains(@href, '?logout')]" */
			return $this->byXPath($selector);
		} else if (preg_match('/^([a-z]+)=(.+)/', $selector, $match)) {
			/* "id=login_name" */
			switch ($match[1]) {
				case 'id':
					return $this->byId($match[2], $wait);
					break;
				case 'name':
					return $this->byName($match[2], $wait);
					break;
				case 'link':
					return $this->byLinkText($match[2], $wait);
					break;
				case 'xpath':
					// Remove trailing slash
					$match[2] = rtrim($match[2], '/');
					return $this->byXPath($match[2], $wait);
					break;
				case 'css':
					$cssSelector = str_replace('..', '.', $match[2]);
					return $this->byCssSelector($cssSelector, $wait);
					break;
			}
		}
		throw new \Exception("Unknown selector '$selector'");
	}

	/**
	 * Wait for condition
	 * @param string $condition
	 * @param int $timeout
	 * @return string
	 */
	public function waitForCondition($condition, $timeout) {
		// Replace selenium.browserbot.getUserWindow(). with nothing. This is the default scope when ran through browserstack
		$condition = str_replace('selenium.browserbot.getUserWindow().', '', $condition);
		return "{$this->_obj}->waitForCondition(\"{$condition}\", \"{$timeout}\");";
	}

	/**
	 * By ID
	 * @param string $selector
	 * @param boolean $wait Wait for element
	 */
	public function byId($selector, $wait = true) {
		// By ID (wait for element)
		return "{$this->_obj}->byId(\"{$selector}\", " . ($wait ? 'true' : 'false') . ")";
	}

	/**
	 * By Name
	 * @param string $selector
	 */
	public function byName($selector, $wait = true) {
		// By Name (wait for element)
		return "{$this->_obj}->byName(\"{$selector}\", " . ($wait ? 'true' : 'false') . ")";
	}

	/**
	 * By css
	 * @param string $selector
	 */
	public function byCssSelector($selector, $wait = true) {
		// By css (wait for element)
		return "{$this->_obj}->byCssSelector(\"{$selector}\", " . ($wait ? 'true' : 'false') . ")";
	}

	/**
	 * byXPath
	 * @param string $selector
	 */
	public function byXPath($selector, $wait = true) {
		// By XPath (wait for element)
		return "{$this->_obj}->byXPath(\"{$selector}\", " . ($wait ? 'true' : 'false') . ")";
	}

	/**
	 * byLinkText
	 * @param string $selector
	 */
	public function byLinkText($selector, $wait = true) {
		// By LinkText (wait for element)
		return "{$this->_obj}->byLinkText(\"{$selector}\", " . ($wait ? 'true' : 'false') . ")";
	}

	public function clickWithWait($selector) {
		$lines = array();
		$localExpression = str_replace($this->_obj, '$testCase', "{$this->_obj}->url()");
		$lines[] = "\$this->log(\"Click on  $selector\");";
		$lines[] = '$input = ' . $this->_byQuery($selector) . ';';
		$lines[] = "\$gotoUrl = " . $this->_byQuery($selector) . ";";
		$lines[] = "\$gotoUrlHref = false;";
		$lines[] = "if (\$gotoUrl && !empty(\$gotoUrl->attribute('href'))) {";
		$lines[] = "	\$gotoUrlHref = \$gotoUrl->attribute('href');";
		$lines[] = "}";
		$lines[] = '$input->click();';

		$lines[] = "if (\$gotoUrlHref) {";
		$lines[] = "    \$this->log(\"Wait for location \" . \$gotoUrlHref);";
		$lines[] = "    " . $this->_obj . '->waitUntil(function($testCase) use ($gotoUrlHref) {';
		$lines[] = '        try {';
		$lines[] = "            \$url = {$localExpression};";
		$lines[] = "            if (\$url == \$gotoUrlHref) {";
		$lines[] = "                return true;";
		$lines[] = "            }";
		$lines[] = '        } catch (Exception $e) {}';
		$lines[] = "    }, {$this->waitInMilliseconds});";
		$lines[] = "}";
		return $lines;
	}

	public function click($selector) {
		$lines = array();
		$lines[] = "\$this->log(\"Click on  $selector\");";
		$lines[] = '$input = ' . $this->_byQuery($selector) . ';';
		$lines[] = '$input->click();';
//		
//		// If we got a dialog; simply confirm it
//		$lines[] = 'try {';
//		$lines[] = '    ' . $this->_obj . '->acceptAlert();';
//		$lines[] = '} catch (Exception $e) { // Do not care; the link may not produce a dialog';
//		$lines[] = '}';
		return $lines;
	}

	public function select($selectSelector, $optionSelector) {
		$lines = array();
		$lines[] = "\$this->log(\"Select element $selectSelector : $optionSelector\");";
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
	 * Passthrough
	 * 
	 * @param string $selectSelector
	 * @param string $optionSelector
	 * @return array
	 */
	public function selectAndWait($selectSelector, $optionSelector) {
		return $this->select($selectSelector, $optionSelector);
	}

	/**
	 * 
	 * @param string $target
	 * @return array
	 */
	public function clickAndWait($target) {
		return array_merge($this->screenshotOnStep(), $this->clickWithWait($target));
	}
	
	/**
	 * 
	 * @param string $target
	 * @return array
	 */
	public function verifyLocation($target) {
		$lines = [];
		$lines[] = "\$this->log(\"Waiting for $target\");";
		$lines[] = $this->_obj . '->waitUntil(function($testCase) {';
		$lines[] = '    try {';
		$lines[] = "        // Wait for location";
		$lines[] = "        if ({$this->_obj}->url() == \"$target\") {";
		$lines[] = "            return true;";
		$lines[] = "        }";
		$lines[] = '    } catch (Exception $e) {}';
		$lines[] = '}, 30000);';
		return $lines;
	}

	public function captureEntirePageScreenshot($target) {
		$lines = [];
		$lines[] = "\$this->log(\"Taking screenshot\");";
		if (mb_strlen(trim($target)) > 0) {
			$filename = $target;
			if ($pos = mb_strripos($filename, '/')) {
				$filename = mb_substr($filename, $pos + 1);
			}
			if ($pos = mb_strripos($filename, '\\')) {
				$filename = mb_substr($filename, $pos + 1);
			}
		} else {
			$filename = "step-'{$this->stepCount}";
			$this->stepCount++;
		}
		$lines[] = '$this->takeScreenshot("' . $filename . '");';
		return $lines;
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
		$lines[] = "\$this->log(\"Assert text $target = $value\");";
		$lines[] = '$input = ' . $this->_byQuery($target, false) . ';';

		if (strpos($value, '*')) {
			$value = '/' . str_replace('*', '.+', $value) . '/';
			$lines[] = "{$this->_obj}->assertRegExp(\"$value\", \$input->text());";
		} else {
			$lines[] = "{$this->_obj}->assertEquals(\"$value\", \$input->text(), \"Failed to assert equal '{$value}' to '{\$input->text()}'\");";
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
		$lines[] = "\$this->log(\"Assert not text $target = $value\");";
		$lines[] = '$input = ' . $this->_byQuery($target, false) . ';';

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
		$lines[] = "\$this->log(\"Assert element present $target\");";
		$lines[] = 'try {';
		$lines[] = "    " . $this->_byQuery($target, false) . ';';
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
		$lines[] = "\$this->log(\"Assert element not present $target\");";
		$lines[] = 'try {';
		$lines[] = "    " . $this->_byQuery($target, false) . ';';
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

		$localExpression = str_replace($this->_obj, '$testCase', $this->_byQuery($target, false));

		/*
		 * In Selenium 2 we can not interact with invisible elements.
		 */

		$lines = array();
		$lines[] = "\$this->log(\"Wait for element $target\");";
		$lines[] = $this->_obj . '->waitUntil(function($testCase) {';
		$lines[] = '    try {';
		$lines[] = "        \$element = $localExpression;";
		$lines[] = "        if (\$element && \$element->displayed()) {";
		$lines[] = "            return true;";
		$lines[] = "        }";
		$lines[] = '    } catch (Exception $e) {}';
		$lines[] = "}, {$this->waitInMilliseconds});";
		return $lines;
	}

	public function waitForElementNotPresent($target) {
		$localExpression = str_replace($this->_obj, '$testCase', $this->_byQuery($target, false));
		$lines = array();
		$lines[] = "\$this->log(\"Wait for element not present $target\");";
		$lines[] = $this->_obj . '->waitUntil(function($testCase) {';
		$lines[] = "    try {";
		$lines[] = "        \$element = $localExpression;";
		$lines[] = "        if ( ! (\$element && \$element->displayed())) {";
		$lines[] = "            return true;";
		$lines[] = "        }";
		$lines[] = '    } catch (PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {';
		$lines[] = "        if (PHPUnit_Extensions_Selenium2TestCase_WebDriverException::NoSuchElement == \$e->getCode()) {";
		$lines[] = "            return true;";
		$lines[] = "        } else {";
		$lines[] = "            \$this->fail(\"Unexpected exception: \" . print_r(\$e, true));";
		$lines[] = "        }";
		$lines[] = "    } catch (Exception \$e) {";
		$lines[] = "        \$this->fail(\"Unexpected exception: \" . print_r(\$e, true));";
		$lines[] = "    }";
		$lines[] = "}, {$this->waitInMilliseconds});";
		return $lines;
	}

	/**
	 * 
	 * @param string $target
	 * @return array
	 */
	public function waitForVisible($target) {
		$localExpression = str_replace($this->_obj, '$testCase', $this->_byQuery($target, false));

		/*
		 * In Selenium 2 we can not interact with invisible elements.
		 */

		$lines = array();
		$lines[] = "\$this->log(\"Wait for visible $target\");";
		$lines[] = $this->_obj . '->waitUntil(function($testCase) {';
		$lines[] = '    try {';
		$lines[] = "        \$element = $localExpression;";
		$lines[] = "        if (\$element && \$element->displayed()) {";
		$lines[] = "            return true;";
		$lines[] = "        }";
		$lines[] = '    } catch (Exception $e) {}';
		$lines[] = "}, {$this->waitInMilliseconds});";
		return $lines;
	}

	public function waitForNotVisible($target) {
		$localExpression = str_replace($this->_obj, '$testCase', $this->_byQuery($target, false));
		$lines = array();
		$lines[] = "\$this->log(\"Wait for not visible $target\");";
		$lines[] = $this->_obj . '->waitUntil(function($testCase) {';
		$lines[] = "    try {";
		$lines[] = "        \$element = $localExpression;";
		$lines[] = "        if ( ! (\$element && \$element->displayed())) {";
		$lines[] = "            return true;";
		$lines[] = "        }";
		$lines[] = '    } catch (PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {';
		$lines[] = "        if (PHPUnit_Extensions_Selenium2TestCase_WebDriverException::NoSuchElement == \$e->getCode()) {";
		$lines[] = "            return true;";
		$lines[] = "        }";
		$lines[] = '    }';
		$lines[] = "}, {$this->waitInMilliseconds});";
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
		$lines[] = "\$this->log(\"Wait for text $text\");";
		$lines[] = $this->_obj . '->waitUntil(function($testCase) {';
		$lines[] = "    if (strpos(\$testCase->byTag('body')->text(), \"$text\") !== false) {";
		$lines[] = "         return true;";
		$lines[] = '    }';
		$lines[] = "}, {$this->waitInMilliseconds});";
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
		$parsedValue = $this->assignKeyValue($target, $value);
		$lines[] = "{$this->_obj}->storeValue(\"$target\", \"$parsedValue\");";
		return $lines;
	}

	/**
	 * 
	 * @param string $expression
	 * @return string
	 */
	public function storeXpathCount($target, $value) {
		$lines = array();
		$parsedValue = $this->assignKeyValue($target, $value);
		$lines[] = "{$this->_obj}->storeXpathCount(\"$target\", \"$parsedValue\");";
		return $lines;
	}

	public function store($target, $value) {
		$lines = array();
		$parsedTarget = $this->assignKeyValue($value, $target);
		$lines[] = "{$this->_obj}->store(\"$value\", \"$parsedTarget\");";
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
		$parsedValue = $this->assignKeyValue($target, $value);
		$lines[] = "{$this->_obj}->store(\"$parsedValue\", \"javascript:$target\");";
		return $lines;
	}

	/**
	 * 
	 * @param string $key
	 * @param string $value
	 * @return string Correct value
	 */
	private function assignKeyValue($key, $value) {
		// Override?
		if (isset($this->overrideSeleniumParams[$key])) {
			$value = $this->overrideSeleniumParams[$key];
			echo "overriding value: $key = $value";
		}
		return $value;
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
		$localExpression = '$input = ' . str_replace($this->_obj, '$testCase', $this->_byQuery($target, false));
		$lines = array();
		$lines[] = "\$this->log(\"Wait for text $target = $value\");";
		$lines[] = $this->_obj . '->waitUntil(function($testCase) {';
		$lines[] = "    $localExpression;";
		$lines[] = "    if (('$value' === '' && \$input->text() === '') || strpos(\$input->text(), \"$value\") !== false) {";
		$lines[] = "         return true;";
		$lines[] = '    }';
		$lines[] = "}, {$this->waitInMilliseconds});";
		return $lines;
	}

	public function waitForNotText($target, $value) {
		$localExpression =  str_replace($this->_obj, '$testCase', $this->_byQuery($target, false));
		$lines = array();
		$lines[] = "\$this->log(\"Wait for not text $target = $value\");";
		$lines[] = $this->_obj . '->waitUntil(function($testCase) {';
		$lines[] = "    try {";
		$lines[] = "        \$input = {$localExpression};";
		$lines[] = "        if (('$value' === '' && \$input->text() !== '') || strpos(\$input->text(), \"$value\") === false) {";
		$lines[] = "            return true;";
		$lines[] = '        }';
		$lines[] = '    } catch (PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {';
		$lines[] = "        if (PHPUnit_Extensions_Selenium2TestCase_WebDriverException::NoSuchElement == \$e->getCode()) {";
		$lines[] = "            return true;";
		$lines[] = "        }";
		$lines[] = '    }';
		$lines[] = "}, {$this->waitInMilliseconds});";
		return $lines;
	}

	public function assertConfirmation($text) {
		$lines = array();
		$lines[] = "if ( !is_null(\$alertText = {$this->_obj}->alertText()) ) {";
		$lines[] = "    {$this->_obj}->assertEquals(\"$text\", \$alertText);";
		$lines[] = "}";
		$lines[] = $this->_obj . '->' . (($this->_confirm) ? 'accept' : 'dismiss') . 'Alert();';
		return $lines;
	}

	public function assertNotConfirmation($text) {
		$lines = array();
		$lines[] = "if ( !is_null(\$alertText = {$this->_obj}->alertText()) ) {";
		$lines[] = "    {$this->_obj}->assertNotEquals(\"$text\", \$alertText);";
		$lines[] = "}";
		$lines[] = $this->_obj . '->' . (($this->_confirm) ? 'accept' : 'dismiss') . 'Alert();';
		return $lines;
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
		$script = str_replace(array('$', '\${'), array('\$', '${'), $script);

		$re = '/(\\${[a-zA-Z0-9_]*\\})/';
		$replaceTemplate = '" . $this->getStoredValue("[value]") . "';
		preg_match_all($re, $script, $matches);
		if (count($matches) > 0) {
			$search = $replace = [];
			$matchList = reset($matches);
			foreach ($matchList as $match) {
				$search[] = $match;
				$replace[] = str_replace(['[value]', '${', '}'], [$match, '', ''], $replaceTemplate);
			}
		}
		$script = str_replace($search, $replace, $script);
		
		$lines = array();
		$lines[] = "\$this->log(\"Running script $script\");";
		$lines[] = "\$script = \"{$script}\";";
		$lines[] = "\$result = {$this->_obj}->runJavascript([\$script]);";
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
		$line = "{$this->_obj}->assertEquals(\"$value\", " . $this->_getAttributeByLocator($target, false) . ');';
		return $line;
	}

	/**
	 * Returns value of dom attribute
	 * 
	 * @param string $locator - locator ending with @attr. For example css=.some-link@href
	 * @return string expression
	 */
	protected function _getAttributeByLocator($locator, $wait = true) {
		/*
		 * We dont have a $this->getAttribute($locator)
		 */
		/*
		 * /(.+)\/@([\S])+$/ ~ //div/a/@href -> //div/a
		 */
		$elementTarget = preg_replace('/(.+@?)\/?@([\S]+)$/', '$1', $locator);
		$attribute = preg_replace('/(.+@?)\/?@([\S]+)$/', '$2', $locator);
		$attribute = str_replace("'", "\'", $attribute);
		$line = $this->_byQuery($elementTarget, $wait) . "->attribute('$attribute')";
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
		$lines[] = "{$this->_obj}->store(\"$varName\", \$element->text());";
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

	/**
	 * 
	 * @param string $target
	 * @return array
	 */
	public function assertVisible($target) {
		$lines = array();
		$lines[] = "\$this->log(\"Assert visible $target\");";
		$lines[] = '$element = ' . $this->_byQuery($target, false) . ';';
		$lines[] = "{$this->_obj}->assertTrue(\$element && \$element->displayed());";
		return $lines;
	}

	/**
	 * 
	 * @param string $target
	 * @return array
	 */
	public function assertNotVisible($target) {
		$lines = array();
		// By query; do not wait for element to be visible
		$lines[] = "\$this->log(\"Assert not visible $target\");";
		$lines[] = "try {";
		$lines[] = '    $element = ' . $this->_byQuery($target, false) . ';';
		$lines[] = "    {$this->_obj}->assertTrue(!\$element || !\$element->displayed());";
		$lines[] = "} catch (Exception \$e) {";
		$lines[] = "    echo \"Could not find element `$target`: {\$e->getMessage()}\";";
		$lines[] = "}";
		return $lines;
	}

	/**
	 * 
	 * @param string $target
	 * @return string Expression
	 */
	public function assertLocation($target) {
		$lines = array();
		$lines[] = "\$this->log(\"Assert location $target\");";
		if (mb_strpos($target, '*') > 0) {
			$lines[] = "\$target = str_replace(array('/', '.', '*'), array('\/', '\.', '.*'), \"{$target}\");";
			$lines[] = "{$this->_obj}->assertRegExp(\"/{\$target}/\", {$this->_obj}->url(), \"Failed to assert equal '{$target}' to '{{$this->_obj}->url()}'\");";
		} else {
			$lines[] = "{$this->_obj}->assertEquals(\"{$target}\", {$this->_obj}->url(), \"Failed to assert equal '{$target}' to '{{$this->_obj}->url()}'\");";
		}
		return $lines;
	}

	/**
	 * 
	 * @param string $target
	 * @return string Expression
	 */
	public function assertNotLocation($target) {
		$lines = array();
		$lines[] = "\$this->log(\"Assert not location $target\");";
		if (mb_strpos($target, '*') > 0) {
			$lines[] = "\$target = str_replace(array('/', '.', '*'), array('\/', '\.', '.*'), \"{$target}\");";
			$lines[] = "{$this->_obj}->assertNotRegExp(\"/{\$target}/\", {$this->_obj}->url(), \"Failed to assert equal '{$target}' to '{{$this->_obj}->url()}'\");";
		} else {
			$lines[] = "{$this->_obj}->assertNotEquals(\"{$target}\", {$this->_obj}->url(), \"Failed to assert equal '{$target}' to '{{$this->_obj}->url()}'\");";
		}
		return $lines;
	}

	/**
	 * 
	 * @param string $varName
	 * @return string Expression
	 */
	public function storeLocation($varName) {
		$this->_checkVarName($varName);
		$line = "{$this->_obj}->store(\"$varName\", {$this->_obj}->url());";
		return $line;
	}

	public function assertEval($script, $value) {
		$lines = $this->runScript($script);
		if ($value == 'true') {
			$lines[] = "{$this->_obj}->assertTrue(\$result);";
		} else {
			$lines[] = "{$this->_obj}->assertEquals(\"{$value}\", \$result);";
		}
		return $lines;
	}

	public function assertXpathCount($target, $count) {
		// For non-numeric values: Escape
		if (!is_numeric($count)) {
			$count = '"' . $count . '"';
		}
		$line = "{$this->_obj}->assertEquals($count, {$this->_obj}->xPathCount(\"{$target}\"));";
		return $line;
	}

	public function assertNotXpathCount($target, $count) {
		// For non-numeric values: Escape
		if (!is_numeric($count)) {
			$count = '"' . $count . '"';
		}
		$line = "{$this->_obj}->assertNotEquals($count, {$this->_obj}->xPathCount(\"{$target}\"));";
		return $line;
	}

	/**
	 * 
	 * @param string $target
	 * @return array
	 */
	public function waitForLocation($target) {
		$localExpression = str_replace($this->_obj, '$testCase', "{$this->_obj}->url()");

		$re = '/(\\${[a-zA-Z0-9_]*\\})/';
		$replaceTemplate = '" . $testCase->getStoredValue("[value]") . "';
		preg_match_all($re, $target, $matches);
		if (count($matches) > 0) {
			$search = $replace = [];
			$matchList = reset($matches);
			foreach ($matchList as $match) {
				$search[] = $match;
				$replace[] = str_replace(['[value]', '${', '}'], [$match, '', ''], $replaceTemplate);
			}
		}
		$localValue = str_replace($search, $replace, $target);

		// Any basic auth we need to filter out?
		$re = '/(?<protocol>http[s]{0,1}:\/\/)(?<username>.*):(?<password>.*)@(?<url>.*)/';
		$nbMatches = preg_match_all($re, $localValue, $matches);

		if ($nbMatches > 0 && !empty($matches['username'])) {
			$localValue = $matches['protocol'] . $matches['url'];
		}

		// Wildcard support
		if (stripos($target, '*') !== false) {

			$compareLine = <<<COMP
		\$localValueEscaped = preg_quote("{$localValue}", "/");
        \$localValueReplaced = '/' . str_replace('\*', '.*', \$localValueEscaped) . '\$/';
		\$matches = [];
		if (preg_match_all(\$localValueReplaced, \$url, \$matches) > 0) {
COMP;
"        if (\$url === \"{$localValue}\") {";
		} else {
			$compareLine = "if (\$url === \"{$localValue}\") {";
		}

		$lines = array();
		$lines[] = "\$this->log(\"Wait for location $target\");";
		$lines[] = $this->_obj . '->waitUntil(function($testCase) {';
		$lines[] = '    try {';
		$lines[] = "        \$url = {$localExpression};";
		$lines[] = "        $compareLine";
		$lines[] = "            return true;";
		$lines[] = "        }";
		$lines[] = '    } catch (Exception $e) {}';
		$lines[] = "}, {$this->waitInMilliseconds});";
		return $lines;
	}
	
	public function chooseOkOnNextConfirmation() {
		$this->_confirm = true;
	}
	
	public function chooseOkOnNextConfirmationAndWait() {
		$this->chooseOkOnNextConfirmation();
	}
	
	public function  chooseCancelOnNextConfirmation() {
		$this->_confirm = false;
	}
	
	public function storeConfirmation($varName) {
		$this->_checkVarName($varName);
		$lines[] = 'try {';
		$lines[] = "    {$this->_obj}->store(\"$varName\", {$this->_obj}->alertText());";		
		$lines[] = '    ' . $this->_obj . '->' . (($this->_confirm) ? 'accept' : 'dismiss') . 'Alert();';
		$lines[] = '} catch (Exception $e) {';
		$lines[] = "    echo \"Could not get expected confirmation: {\$e->getMessage()}\";";
		$lines[] = '}';
		return $lines;
	}

	public function storeAlert($varName) {
		$this->_checkVarName($varName);
		$lines[] = 'try {';
		$lines[] = "    {$this->_obj}->store(\"$varName\", {$this->_obj}->alertText());";		
		$lines[] = '    ' . $this->_obj . '->acceptAlert();';
		$lines[] = '} catch (Exception $e) {';
		$lines[] = "    echo \"Could not get expected alert: {\$e->getMessage()}\";";
		$lines[] = '}';
		return $lines;
	}
	
	public function assertTitle($value) {
		if (strpos($value, '*')) {
			$value = '/' . str_replace('*', '.+', $value) . '/';
			$lines[] = "{$this->_obj}->assertRegExp(\"$value\", {$this->_obj}->title());";
		} else {
			$lines[] = "{$this->_obj}->assertEquals(\"$value\", {$this->_obj}->title());";
		}

		return $lines;		
	}
	
	public function assertNotTitle($value) {
		if (strpos($value, '*')) {
			$value = '/' . str_replace('*', '.+', $value) . '/';
			$lines[] = "{$this->_obj}->assertNotRegExp(\"$value\", {$this->_obj}->title());";
		} else {
			$lines[] = "{$this->_obj}->assertNotEquals(\"$value\", {$this->_obj}->title());";
		}

		return $lines;		
	}
	
	public function assertValue($target, $value) {
		$lines = array();
		$lines[] = "\$this->log(\"Assert value $target = $value\");";
		$lines[] = 'try {';
		$lines[] = '    $input = ' . $this->_byQuery($target, false) . ';';
		if (strpos($value, '*')) {
			$value = '/' . str_replace('*', '.+', $value) . '/';
			$lines[] = "    {$this->_obj}->assertRegExp(\"$value\", \$input->value());";
		} else {
			$lines[] = "    {$this->_obj}->assertEquals(\"$value\", \$input->value());";
		}
		$lines[] = '} catch (PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {';
		$lines[] = "    if (PHPUnit_Extensions_Selenium2TestCase_WebDriverException::NoSuchElement === \$e->getCode()) {";
		$lines[] = "        {$this->_obj}->assertTrue(false, \"Element $target not found\");";
		$lines[] = "    } else { ";
		$lines[] = "        throw \$e;";
		$lines[] = "    }";
		$lines[] = '}';
		return $lines;
	}
	
	public function verifyValue($target, $value) {
		return $this->assertValue($target, $value);
	}
	
	public function waitForXpathCount($target, $count) {
		/*
		 * In Selenium 2 we can not interact with invisible elements.
		 */
		// For non-numeric values: Escape
		if (!is_numeric($count)) {
			$count = '"' . $count . '"';
		}
		
		$re = '/(\\${[a-zA-Z0-9_]*\\})/';
		$replaceTemplate = '" . $testCase->getStoredValue("[value]") . "';
		preg_match_all($re, $count, $matches);
		if (count($matches) > 0) {
			$search = $replace = [];
			$matchList = reset($matches);
			foreach ($matchList as $match) {
				$search[] = $match;
				$replace[] = str_replace(['[value]', '${', '}'], [$match, '', ''], $replaceTemplate);
			}
		}
		$localValue = str_replace($search, $replace, $count);

		$lines = array();
		$lines[] = "\$this->log(\"Wait for xpath count " . addslashes($target) . " = " . addslashes($count) . "\");";
		$lines[] = $this->_obj . '->waitUntil(function($testCase) {';
		$lines[] = '    try {';
		$lines[] = "        if ({$localValue} == \$testCase->xPathCount(\"{$target}\")) {";
		$lines[] = "            return true;";
		$lines[] = "        }";
		$lines[] = '    } catch (Exception $e) {}';
		$lines[] = "}, {$this->waitInMilliseconds});";
		return $lines;
	}
	
	public function waitForValue($target, $value) {
		/*
		 * In Selenium 2 we can not interact with invisible elements.
		 */
		// For non-numeric values: Escape
		if (!is_numeric($value)) {
			$value = '"' . $value . '"';
		}
		
		$re = '/(\\${[a-zA-Z0-9_]*\\})/';
		$replaceTemplate = '" . $testCase->getStoredValue("[value]") . "';
		preg_match_all($re, $value, $matches);
		if (count($matches) > 0) {
			$search = $replace = [];
			$matchList = reset($matches);
			foreach ($matchList as $match) {
				$search[] = $match;
				$replace[] = str_replace(['[value]', '${', '}'], [$match, '', ''], $replaceTemplate);
			}
		}
		$localValue = str_replace($search, $replace, $value);

		$localExpression = str_replace($this->_obj, '$testCase', $this->_byQuery($target, false));
		$lines = array();
		$lines[] = "\$this->log(\"Wait for value $target = $value\");";
		$lines[] = $this->_obj . '->waitUntil(function($testCase) {';
		$lines[] = '    try {';
		$lines[] = "        \$input = {$localExpression};";
		$lines[] = "        if ({$localValue} == \$input->value()) {";
		$lines[] = "            return true;";
		$lines[] = "        }";
		$lines[] = '    } catch (Exception $e) {}';
		$lines[] = "}, {$this->waitInMilliseconds});";
		return $lines;
	}
}
