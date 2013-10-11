<?php
global $CFG;
require_once $CFG->dirroot . '/blocks/proctoru/lib.php';
require_once $CFG->dirroot . '/blocks/proctoru/Cronlib.php';
require_once $CFG->dirroot . '/blocks/proctoru/tests/conf/ConfigProctorU.php';
require_once $CFG->dirroot . '/enrol/externallib.php';

abstract class abstract_testcase extends advanced_testcase{
    public $conf;
    public $pu;
    public $cron;
    public $users;
    public $teacherRoleId;
    public $studentRoleId;
    
    public function setup(){
        global $DB;
        $this->resetAfterTest();
        
        //init configs
        $this->conf = new ConfigProctorU();
        $this->conf->setConfigs();
        
        $this->pu   = new ProctorU();
        $this->cron = new ProctorUCronProcessor();
        $this->courses[] = $this->getDataGenerator()->create_course();
        $this->assertEquals(2,$this->courses[0]->id);
        
        //these come in handy
        $this->studentRoleId = $DB->get_field('role', 'id', array('shortname'=>'student'));
        $this->teacherRoleId = $DB->get_field('role', 'id', array('shortname'=>'teacher'));
        
        //enroll some users
        $this->insertOneUserOfEachFlavor();
        $this->enrolUsers();

        $this->assertNotEmpty($DB->get_record('user_info_field',array('shortname' => 'user_proctoru')));
        $this->assertNotEmpty(get_config('block_proctoru','localwebservice_url'));
        
        $this->assertNotEmpty($this->pu->localWebservicesUrl);
        $this->assertInternalType('string', get_config('block_proctoru','localwebservice_url'));
        $this->assertInternalType('string', $this->pu->localWebservicesUrl);
        
    }
    
    /**
     * enrols the test users as students in an anonymous course
     * enrols admin as the teach simulating a population where there are 
     * EXEMPT, UNREGISTERED, REGISTERED and VERIFIED users
     * @global type $DB
     */
    protected function enrolUsers(){
        global $DB;
        
        $this->getDataGenerator()->enrol_user($this->users['teacher']->id, $this->courses[0]->id, $this->teacherRoleId);

        foreach($this->users as $u){
            assert($this->getDataGenerator()->enrol_user($u->id, $this->courses[0]->id, $this->studentRoleId));
        }
    }
    
    protected function insertOneUserOfEachFlavor(){
        global $DB;
        $before = count($DB->get_records('user'));
        
        $data = $this->conf->data;
        $gen  = $this->getDataGenerator();

        $this->users['userUnregistered']   = $gen->create_user($data['testUser1']);
        $this->users['userRegistered']     = $gen->create_user($data['testUser2']);
        $this->users['userVerified']       = $gen->create_user($data['testUser3']);
        $this->users['teacher']            = $gen->create_user(array('username'=>'teacher'));
        $this->assertEquals($before + 4, count($DB->get_records('user')));
        
        $this->setProfileField($this->users['userUnregistered']->id, ProctorU::UNREGISTERED);
        $this->setProfileField($this->users['userRegistered']->id,   ProctorU::REGISTERED);
        $this->setProfileField($this->users['userVerified']->id,     ProctorU::VERIFIED);
    }
    
    protected function setProfileField($userid, $value){
        global $DB;
        $shortname = "user_".get_config('block_proctoru', 'profilefield_shortname');
        
        //create profile field
        $fieldParams = array(
            'shortname' => $shortname,
            'categoryid' => 1,
        );
        $this->pu->default_profile_field($fieldParams);
        
        $fieldId = $DB->get_field('user_info_field', 'id', array('shortname'=>$shortname));
//        mtrace(sprintf("setting fieldid for shortname %s = %d", $shortname,$fieldId));
        $fieldData = new stdClass();
        $fieldData->userid  = $userid;
        $fieldData->fieldid = $fieldId;
        $fieldData->data    = $value;
        $fieldData->dataFormat = 0;
        
        $DB->insert_record('user_info_data',$fieldData, true, false);
    }
}

?>
