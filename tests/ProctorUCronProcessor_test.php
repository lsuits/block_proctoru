<?php
global $CFG;
require_once $CFG->dirroot . '/blocks/proctoru/lib.php';
require_once $CFG->dirroot . '/blocks/proctoru/Cronlib.php';
require_once $CFG->dirroot . '/blocks/proctoru/tests/conf/ConfigProctorU.php';
require_once $CFG->dirroot . '/blocks/proctoru/tests/abstract_testcase.php';

class ProctorUCronProcessor_testcase extends abstract_testcase{

    public function test_blnUpdateNewUsers(){

        $this->insertOneUserOfEachFlavor();

        $this->assertEquals(5,count($this->cron->objGetAllUsers()));
        $this->assertEquals(1,count($this->cron->objGetUnregisteredUsers()));
        $this->assertEquals(1,count($this->cron->objGetRegisteredUsers()));
        $this->assertEquals(1,count($this->cron->objGetVerifiedUsers()));
        
        $this->assertEquals(3,count($this->cron->objGetAllProctorUsers()));
        $this->assertEquals(2,count($this->cron->objGetAllUsersWithoutProctorStatsus()));
        
        $unit = $this->cron->blnUpdateNewUsers();
        $this->assertEquals(2, count($unit));
    }
}
?>
