<?php
global $CFG;
require_once $CFG->dirroot . '/blocks/proctoru/lib.php';
require_once $CFG->dirroot . '/blocks/proctoru/tests/abstract_testcase.php';
require_once $CFG->dirroot . '/blocks/proctoru/tests/conf/ConfigProctorU.php';

class ProctorU_testcase extends abstract_testcase{
    
    
    public function setup(){
        parent::setup();
        global $DB;
        //map lookup is a little anonymous; refactor the map to make semantic sense
        $this->assertEquals($this->conf->config[1][1], get_config('block_proctoru','roleselection'));
        $this->assertNotEmpty($DB->get_record('user_info_field',array('shortname' => 'user_proctoru')));
        $this->assertNotEmpty(get_config('block_proctoru','localwebservice_url'));
        
        $this->assertNotEmpty($this->pu->localWebservicesUrl);
        $this->assertInternalType('string', get_config('block_proctoru','localwebservice_url'));
        $this->assertInternalType('string', $this->pu->localWebservicesUrl);
    }

    
    public function test_ensureLocalUserExists(){
        $dasInvalidParamsResponse = 
        "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
        <RESULTS>
            <ERROR_MSG>                 
                Problem validating input parameters.
            </ERROR_MSG>  
        </RESULTS>";
    }
    
    public function test_intCustomFieldID(){
        global $DB;
        $conf = get_config('block_proctoru',$this->conf->config[2][0]);
        $id   = $DB->get_field('user_info_field','id',array('shortname' => "user_".$conf));
        $this->assertEquals($id, ProctorU::intCustomFieldID());
    }
    
    public function test_userHasProctoruProfileFieldValue(){
        $u = $this->getDataGenerator()->create_user();
        $this->assertFalse(ProctorU::userHasProctoruProfileFieldValue($u->id));
    }

    
    public function test_objGetExemptRoles(){
        $config = explode(',',get_config('block_proctoru', 'roleselection'));
        $unit   = $this->pu->objGetExemptRoles();

        $this->assertEquals(count($config), count($unit));
        $this->assertEmpty(array_diff(array_keys($unit), $config));
    }
    
    public function test_partial_get_users_listing_by_roleid(){

        $teachers = ProctorU::partial_get_users_listing_by_roleid($this->teacherRoleId);
        $students = ProctorU::partial_get_users_listing_by_roleid($this->studentRoleId);
        $this->assertEquals(1, count($teachers));

        $this->assertEquals(4, count($students));
    }
    
    public function test_objGetExemptUsers() {
        $exempt = ProctorU::objGetExemptUsers();
        assert(count($exempt)>0);
        
        $this->assertEquals(1,count($exempt));
        $admin = array_pop($exempt);
        $this->assertEquals('teacher', $admin->username);
    }
}
?>
