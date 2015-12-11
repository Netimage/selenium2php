<?php
{$comment}
class {$className} extends NI_Test_PHPUnit_Selenium2TestCase {

	public function setUp() {
		$this->usePluginService = false;
		$this->shareSession(true);
		
		$this->setHost("{$remoteHost}");
		$this->setPort({$remotePort});

		// Use SOCKS proxy to access restricted pages
		$this->setDesiredCapabilities(array(
			'proxy' => array(
				'proxyType' => 'manual',
				'socksProxy' => 'localhost:8890'
			)
		));
		$this->setBrowser("{$browser}");
		$this->setBrowserUrl("{$testUrl}");
		parent::setUp();
	}
	
	public function {$testMethodName}() {
		{$testMethodContent}
	}
	
{$testMethods}
}