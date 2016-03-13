<?php

{$comment}
class {$className} extends NI_Test_PHPUnit_Selenium2BrowserStackTestCase {

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