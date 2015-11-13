<?php
{$comment}
class {$className} extends NI_Test_PHPUnit_Selenium2TestCase {

	private static $first = false;
	
    public function setUp(){
        
        $this->setHost("{$remoteHost}");
        $this->setPort({$remotePort});
		
		if (self::$first === false) {
			$this->setBrowser("{$browser}");
			$this->shareSession(true);
			$this->setBrowserUrl("{$testUrl}");
			self::$first = true;
		}
    }

    public function {$testMethodName}() {
        {$testMethodContent}
    }
}