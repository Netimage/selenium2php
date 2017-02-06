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

	/**
	* The current test index
	* @var integer
	*/
	protected $testIndex = 0;

	public function setUp() {
		// Because the blacklist is using the autoload (on class_exists) this will crash with the NIClass autoloader
		// Therefore; we need to remove all blacklisted classes not on a proper include path
		PHPUnit_Util_Blacklist::$blacklistedClassNames = array(
        	'File_Iterator'                              => 1
		);
		$this->usePluginService = false;
		if (!self::$first) {
			self::shareSession(false);
			self::$first = true;
			$this->store('var_EMAIL', 'dk.combine.qa+sys-03-01-' . uniqid() . '@gmail.com');
		}
		$this->setBrowserUrl("{$testUrl}");
		$this->prependScreenshotNumber = true;
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