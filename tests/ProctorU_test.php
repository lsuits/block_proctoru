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
    
    private function setProfileField($userid, $value){
        global $DB;
        $shortname = get_config('block_proctoru', 'profilefield_shortname');
        
        //create profile field
        $fieldParams = array(
            'shortname' => get_string('profilefield_shortname', 'block_proctoru'),
            'categoryid' => 1,
        );
        $this->pu->default_profile_field($fieldParams);
        
        $fieldId = $DB->get_field('user_info_field', 'id', array('shortname'=>$shortname));
        
        $fieldData = new stdClass();
        $fieldData->userid  = $userid;
        $fieldData->fieldid = $fieldId;
        $fieldData->data    = $value;
        $fieldData->dataFormat = 0;
        
        $DB->insert_record('user_info_data',$fieldData, true, false);
    }
    
    public function test_arrFetchRegisteredStatusByUserid(){

        $gen = $this->getDataGenerator();
        
        //check for verified users
        $verifiedUser = $gen->create_user();
        $this->setProfileField($verifiedUser->id, ProctorU::VERIFIED);
        $vUsers = ProctorU::arrFetchRegisteredStatusByUserid(array(), ProctorU::VERIFIED);
        $this->assertEquals(ProctorU::VERIFIED,$vUsers[$verifiedUser->id]->status);
        
        
        //registered users
        $registeredUser = $gen->create_user();
        $this->setProfileField($registeredUser->id, ProctorU::REGISTERED);
        $rUsers = ProctorU::arrFetchRegisteredStatusByUserid(array(), ProctorU::REGISTERED);
        $this->assertEquals(ProctorU::REGISTERED, $rUsers[$registeredUser->id]->status);
        
        //registered users
        $unregisteredUser = $gen->create_user();
        $this->setProfileField($unregisteredUser->id, ProctorU::UNREGISTERED);
        $uUsers = ProctorU::arrFetchRegisteredStatusByUserid(array(), ProctorU::UNREGISTERED);
        $this->assertEquals(ProctorU::UNREGISTERED, $uUsers[$unregisteredUser->id]->status);
        
        
        //check that filtering with userids works as expected
        $includeFilter = array($unregisteredUser->id, $registeredUser->id);
        $onlyTwo = ProctorU::arrFetchRegisteredStatusByUserid($includeFilter);
        $this->assertEquals(count($includeFilter),count($onlyTwo));
        $this->assertArrayNotHasKey($verifiedUser->id, $onlyTwo);
        $this->assertArrayHasKey($unregisteredUser->id, $onlyTwo);
        $this->assertArrayHasKey($registeredUser->id, $onlyTwo);
    }
}
?>
