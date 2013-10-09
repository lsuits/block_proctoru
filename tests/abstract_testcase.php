<?php
global $CFG;
require_once $CFG->dirroot . '/blocks/proctoru/lib.php';
require_once $CFG->dirroot . '/blocks/proctoru/Cronlib.php';
require_once $CFG->dirroot . '/blocks/proctoru/tests/conf/ConfigProctorU.php';

abstract class abstract_testcase extends advanced_testcase{
    public $conf;
    public $pu;
    public $cron;
    
    public function setup(){
        global $DB;
        $this->resetAfterTest();
        
        //init configs
        $this->conf = new ConfigProctorU();
        $this->conf->setConfigs();
        
        $this->pu   = new ProctorU();
        $this->cron = new ProctorUCronProcessor();

        $this->assertNotEmpty($DB->get_record('user_info_field',array('shortname' => 'user_proctoru')));
        $this->assertNotEmpty(get_config('block_proctoru','localwebservice_url'));
        
        $this->assertNotEmpty($this->pu->localWebservicesUrl);
        $this->assertInternalType('string', get_config('block_proctoru','localwebservice_url'));
        $this->assertInternalType('string', $this->pu->localWebservicesUrl);
    }
    
    protected function insertOneUserOfEachFlavor(){
        $data = $this->conf->data;
        $gen  = $this->getDataGenerator();

        $userUnregistered   = $gen->create_user($data['testUser1']);
        $userRegistered     = $gen->create_user($data['testUser2']);
        $userVerified       = $gen->create_user($data['testUser3']);
        
        $this->setProfileField($userUnregistered->id, ProctorU::UNREGISTERED);
        $this->setProfileField($userRegistered->id, ProctorU::REGISTERED);
        $this->setProfileField($userVerified->id, ProctorU::VERIFIED);
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
        mtrace(sprintf("setting fieldid for shortname %s = %d", $shortname,$fieldId));
        $fieldData = new stdClass();
        $fieldData->userid  = $userid;
        $fieldData->fieldid = $fieldId;
        $fieldData->data    = $value;
        $fieldData->dataFormat = 0;
        
        $DB->insert_record('user_info_data',$fieldData, true, false);
    }
}

?>
