<?php
{$comment}
class {$className} extends NI_Test_PHPUnit_Selenium2TestCase {

	public static $first = false;
	
    public function setUp(){
        
		$this->shareSession(true);
		
		if (self::$first === false) {
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
			self::$first = true;
		}
		parent::setUp();
    }

    public function {$testMethodName}() {
        {$testMethodContent}
    }
}