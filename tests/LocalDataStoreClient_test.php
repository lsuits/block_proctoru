<?php

global $CFG;
require_once $CFG->dirroot . '/blocks/proctoru/lib.php';
require_once $CFG->dirroot . '/blocks/proctoru/tests/conf/ConfigProctorU.php';

class LocalDataStoreClient_testcase extends advanced_testcase {

    public function setup() {
        $this->resetAfterTest();

        //init local config
        $this->conf = new ConfigProctorU();
        $this->conf->setConfigs();
        $this->class = new LocalDataStoreClient();
    }
    
    public function test_blnUserExists(){
        $idnumber = $this->conf->data['testUser1']['idnumber'];
        $this->assertEquals('N',$this->class->blnUserExists($idnumber));
        
        $idnumber = $this->conf->data['testUser2']['idnumber'];
        $this->assertEquals('Y',$this->class->blnUserExists($idnumber));
    }
    
    public function test_intPseudoId(){
        $idnumber = $this->conf->data['testUser1']['idnumber'];
        $this->assertInternalType('int',$this->class->intPseudoId($idnumber));
        
        $idnumber = $this->conf->data['testUser2']['idnumber'];
        $this->assertInternalType('int',$this->class->intPseudoId($idnumber));
    }
}
?>
