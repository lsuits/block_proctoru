<?php
global $CFG;
require_once $CFG->dirroot . '/blocks/proctoru/lib.php';
require_once $CFG->dirroot . '/blocks/proctoru/tests/conf/ConfigProctorU.php';

class ProctorU_testscase extends advanced_testcase{
    public $conf;
    public $pu;
    
    public function setup(){
        $this->resetAfterTest();

        //init local config
        $this->conf = new ConfigProctorU();
        $this->conf->setConfigs();

        $this->pu = new ProctorU();

        $this->assertNotEmpty(get_config('block_proctoru','localwebservice_url'));
        $this->assertNotEmpty($this->pu->localWebservicesUrl);
        $this->assertInternalType('string', get_config('block_proctoru','localwebservice_url'));
        $this->assertInternalType('string', $this->pu->localWebservicesUrl);
    }

    public function test_getMoodleUser(){
        $user = $this->getDataGenerator()->create_user($this->conf->data['testUser1']);
        
        $unit = $this->pu->usrGetMoodleUser($user->id);
        $this->assertObjectHasAttribute('idnumber', $unit);
        $this->assertEquals($user->idnumber, $unit->idnumber);
    }
    
    public function test_userHasExemptRole(){
        global $USER;
        $user = $this->getDataGenerator()->create_user($this->conf->data['testUser1']);
        $this->setUser($user);
        $USER->access = $this->conf->data['teacherAccessArray'];
        
        $this->assertTrue($this->pu->userHasExemptRole());
    }

    public function test_getFlattenedUserAccessContextPathsForStudent(){
        global $USER;
        $user = $this->getDataGenerator()->create_user($this->conf->data['testUser1']);

        $course1 = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course1->id);

        $this->setUser($user);
        $USER->access = $this->conf->data['studentAccessArray'];

        $unit = $this->pu->getFlattenedUserAccessContextPaths();
        $this->assertTrue($unit != false);

        $this->assertNotEmpty($unit);
        $this->assertTrue(is_array($unit));
        $this->assertEquals(3, count($unit));
        $this->assertArrayHasKey('/1', $unit);
        $this->assertArrayHasKey('/1/2', $unit);
        $this->assertArrayHasKey('/1/3/2345', $unit);
        
        $exemptRoles  = explode(',',get_config('block_proctoru', 'roleselection'));
        $userHasRoles = array_values($unit);
        $this->assertEmpty(array_intersect($exemptRoles, $userHasRoles)); //using student sample data
        $this->assertFalse($this->pu->userHasExemptRole());
    }
    
    public function test_ensureLocalUserExists(){
        $dasInvalidParamsResponse = 
        "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
        <RESULTS>
            <ERROR_MSG>                 
                Problem validating input parameters.
            </ERROR_MSG>  
        </RESULTS>";

//        $this->pu->getLocalWebservicesCredentials();
//        $user = $this->getDataGenerator()->create_user($this->conf->data['testUser1']);
//
//        $this->assertNotEmpty($this->pu->localWebservicesUrl);
//        $resp = $this->pu->intGetPseudoId($user->id);
//
//        $xdoc = new DOMDocument();
//        $xdoc->loadXML($resp);
//        $this->assertXmlStringNotEqualsXmlString($dasInvalidParamsResponse, $resp);
    }
}
?>
