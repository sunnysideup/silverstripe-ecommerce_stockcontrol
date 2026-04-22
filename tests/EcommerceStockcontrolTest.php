<?php

use SilverStripe\Dev\SapphireTest;

class EcommerceStockcontrolTest extends SapphireTest
{
    protected $usesDatabase = false;

    protected $requiredExtensions = [];

    public function TestDevBuild()
    {
        $exitStatus = shell_exec('php vendor/bin/sake dev/build flush=all  > dev/null; echo $?');

/**
  * ### @@@@ START REPLACEMENT @@@@ ###
  * WHY: automated upgrade
  * OLD: trim(
  * EXP: SS5 change
  * ### @@@@ STOP REPLACEMENT @@@@ ###
  */
        $exitStatus = intval(trim((string) $exitStatus));
        $this->assertEquals(0, $exitStatus);
    }
}
