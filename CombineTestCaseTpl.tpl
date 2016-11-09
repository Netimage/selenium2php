<?php

{$comment}
class {$className} extends NI_Test_PHPUnit_Selenium2BrowserStackTestCase {

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
			$this->store('var_EMAIL', 'dk.combine.qa+sys-03-01-' . uniqid() . '@gmail.com');
		}
		$this->setBrowserUrl("{$testUrl}");
		// $this->currentBrowser = "chrome";
		parent::setUp();
	}
	
	protected function failedTest () {
		// No extra action -> just continue
	}

	public function {$testMethodName}() {
 		try {
			{$testMethodContent}
		} catch (Exception $e) {
			$this->fail("Selenium test " . __METHOD__ . " failed with message `" . $e->getMessage() . "\n" . print_r($e, true));
		}
	}
	
{$testMethods}
}