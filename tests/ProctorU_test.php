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
    
    public function test_blnUserHasProctoruProfileFieldValue(){
        global $DB;
        $this->resetUserTables();   //get rid of testcase fixture users
        
        //first try a fresh user 
        $u = $this->getDataGenerator()->create_user();
        $this->assertFalse(ProctorU::blnUserHasProctoruProfileFieldValue($u->id), sprintf("user %s has a value set", $u->id));
        
        //now give user a pu status
        $status = new stdClass();
        $status->userid  = $u->id;
        $status->fieldid = ProctorU::intCustomFieldID();
        $status->data    = ProctorU::UNREGISTERED;
        $DB->insert_record('user_info_data', $status);
        $this->assertTrue(ProctorU::blnUserHasProctoruProfileFieldValue($u->id));
    }
    
    public function test_blnUserHasExemptRole(){
        $this->assertTrue(ProctorU::blnUserHasExemptRole($this->users['teacher']->id));
        $this->assertFalse(ProctorU::blnUserHasExemptRole($this->users['userUnregistered']->id));
    }

    public function test_constProctorUStatusForUserId(){
        //user has no record whatsoever in the custom profile field
        $anonUser = $this->getDataGenerator()->create_user();
        $this->assertFalse(ProctorU::constProctorUStatusForUserId($anonUser->id));
        
        //user has status UNREGISTERED
        $this->assertEquals(0,ProctorU::constProctorUStatusForUserId($this->users['userUnregistered']->id));
        
        //user has status REGISTERED
        $this->assertEquals(1,ProctorU::constProctorUStatusForUserId($this->users['userRegistered']->id));
        
        //user has status VERIFIED
        $this->assertEquals(2,ProctorU::constProctorUStatusForUserId($this->users['userVerified']->id));
        
        //user has status EXEMPT
        $this->assertEquals(3,ProctorU::constProctorUStatusForUserId($this->users['teacher']->id));
    }
    
    public function test_blnUserHasAcceptableStatus() {
        global $DB;
        
        //fresh unknown user
        $unknown = $this->getDataGenerator()->create_user();
        $this->assertFalse(ProctorU::blnUserHasAcceptableStatus($unknown->id));
        
        //user with exempt role
        $this->assertTrue(ProctorU::blnUserHasAcceptableStatus($this->users['teacher']->id));
        
        //user with VERIFIED status
        $verifiedUserID = $this->users['userVerified']->id;
        $statusRecordShouldEqualVerified = $DB->get_record('user_info_data',array('userid'=>$verifiedUserID, 'fieldid'=>ProctorU::intCustomFieldID()));
        $this->assertEquals(ProctorU::VERIFIED, $statusRecordShouldEqualVerified->data);
        
        $this->assertTrue(ProctorU::blnUserHasAcceptableStatus($verifiedUserID));
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
    
    public function test_partial_get_users_listing_excludesSuspendedUsers(){
        //this scenario, defined in abstract_testcase, has 1 non-student, admin, apart from the guest user which is already excluded 
        //by the library fn get_users_listing; 
        $this->getDataGenerator()->create_user();   //adds an unregistered user for a total of 2
        
        $i=0;
        while($i<50){
            $this->getDataGenerator()->create_user(array('username'=>'suspended-user'.$i, 'suspended'=>1));
            $i++;
        }
        
        $unregistered = ProctorU::partial_get_users_listing(ProctorU::UNREGISTERED);
        $this->assertEquals(2, count($unregistered));
        
        
        //one registered, one verified
        $registered = ProctorU::partial_get_users_listing(ProctorU::REGISTERED);
        $this->assertEquals(1, count($registered));
        
        $verified = ProctorU::partial_get_users_listing(ProctorU::VERIFIED);
        $this->assertEquals(1, count($verified));
    }
    
    public function test_objGetAllUsersWithoutProctorStatus_excludesPeopleWithPUStatus() {
        global $DB;
        //at the start of each test, we have admin + guest 
        // AND 4 new users corresponding to each of the possible ProctorU
        // status constants, guest is eliminated by all functions, 
        // so should not figure in calculations
        $this->addNUsersToDatabase(100); //random, unregistered users
        
        $this->assertEquals(105, count(ProctorU::objGetAllUsers()));
        
        $usersWithoutStatus = ProctorU::objGetAllUsersWithoutProctorStatus();
        
        $sql = "SELECT count(id) AS count FROM {user_info_data} WHERE fieldid = ".ProctorU::intCustomFieldID();

        $allSql = array_values($DB->get_records_sql($sql));
        
        $this->assertFalse($allSql[0]->count == count($usersWithoutStatus));
        $this->assertEquals(101,count($usersWithoutStatus));
    }
    
    public function test_objGetAllUsers() {
        global $DB;
        //at the start of each test, we have admin + guest 
        // AND 4 new users corresponding to each of the possible ProctorU
        // status constants
        $this->assertEquals(6,count($DB->get_records('user')));
        
        //the fn under test excludes guest, deleted and suspended accounts
        $gen = $this->getDataGenerator();
        $i=0;
        while($i<30){
            $gen->create_user(array('suspended'=>1));
            if($i%3 == 0){
                $gen->create_user(array('deleted'=>1));
            }
            $i++;
        }
        $this->assertEquals(30, count($DB->get_records('user',array('suspended'=>1))));
        $this->assertEquals(10, count($DB->get_records('user',array('deleted'=>1))));
        
        //ensure our fn still returns what we expect (guest gets excluded)
        $this->assertEquals(5,count(ProctorU::objGetAllUsers()));
    }
    
    public function test_objGetUsersWithStatusUnregistered() {
        $this->assertEquals(1, count(ProctorU::objGetUsersWithStatusUnregistered()));
    }
    public function test_objGetUsersWithStatusRegistered() {
        $this->assertEquals(1, count(ProctorU::objGetUsersWithStatusRegistered()));
    }
    public function test_objGetVerifiedUsers() {
        $this->assertEquals(1, count(ProctorU::objGetUsersWithStatusVerified()));
    }
    public function test_objGetUsersWithStatusExempt() {
        $this->assertEquals(1, count(ProctorU::objGetUsersWithStatusExempt()));
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
