<?php

define('BROWSERSTACK_USER', 'combine3');
define('BROWSERSTACK_KEY', 'Ts4zUMxjiMMpYJzgs7bi');

{$comment}
class {$className} extends NI_Test_PHPUnit_Selenium2TestCase {

	public static $first = false;
	
	public static $browsers = array(
		array(
			'browserName' => 'chrome',
			'host' => 'hub.browserstack.com',
			'port' => 80,
			'desiredCapabilities' => array(
			  'version' => '30',
			  'browserstack.user' => BROWSERSTACK_USER,
			  'browserstack.key' => BROWSERSTACK_KEY,
			  'os' => 'OS X',
			  'os_version' => 'Mountain Lion'   
			)
		)
	);

	public function setUp() {
		$this->usePluginService = false;
		if (!self::$first) {
			self::shareSession(true);
			$this->setBrowserUrl("{$testUrl}");
			
			self::$first = true;
		}
		$this->currentBrowser = "chrome";
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