<?php
require_once("inc/basejob.php");
require_once("inc/database.php");
require_once("inc/wechat.php");
class Job extends BaseJob
{
    public function perform()
    {
	$this->logger->addError('test');

        //fwrite(STDOUT, 'Hello!');
        //fwrite(STDOUT, 'args:' . json_encode($this->args));
    }

    public  function setUp()
    {
        parent::initConfig();
    }

    public function tearDown() {

    }
}
?>
