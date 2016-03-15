<?php

{$comment}
class {$className} extends Selenium2php\PHPUnit\Selenium2AllBrowserTestCase {

	/**
	 * Configuration of browsers to run all tests in for all tests
	 * This has to be defined in the array initializer, as it will be used inside the selenium test case
	 * to iterate the tests.
	 * @var array
	 */
	public static $browsers = array(
		{$browsers}
	);

	public static $first = false;

	public function setUp() {
		$this->usePluginService = false;
		if (!self::$first) {
			self::shareSession(true);
			self::$first = true;
		}
		$this->setBrowserUrl("{$testUrl}");
		// $this->currentBrowser = "chrome";
		parent::setUp();
	}
	
	protected function failedTest () {
		// No extra action -> just continue
	}
	
	public function {$testMethodName}() {
		{$testMethodContent}
	}
	
{$testMethods}
}