<?php
class PHP_Job
{
	public function perform()
	{
		sleep(1);
		fwrite(STDOUT, "Hello 111!\n");
	}
}
?>
