<?php

global $CFG;
require_once $CFG->dirroot . '/blocks/proctoru/lib.php';
require_once $CFG->dirroot . '/blocks/proctoru/tests/conf/ConfigProctorU.php';

class ProctorUClient_testcase extends advanced_testcase {
    public function setup() {
        $this->resetAfterTest();

        //init local config
        $this->conf = new ConfigProctorU();
        $this->conf->setConfigs();
        $this->class = new ProctorUClient();
    }
    
    public function test_LookupNotFoundUser() {
        $user       = $this->conf->data['testUser1']['pseudoId'];
        $response   = $this->class->getUserProfile($user);

        $this->assertNotEmpty($response);

        $puUser     = json_decode($response);

        $this->assertStringStartsWith(
                $this->conf->data['testUser1']['puMessage'], 
                $puUser->message
                );
    }
    
    public function test_LookupFoundUser() {
        $user       = $this->conf->data['testUser2']['pseudoId'];
        $response   = $this->class->getUserProfile($user);
        
        $this->assertNotEmpty($response);
        
        $puHasImage = $this->conf->data['testUser2']['puHasImage'];
        $puActive   = $this->conf->data['testUser2']['puActive'];
        $puUserId   = $this->conf->data['testUser2']['puUserId'];
        
        $puUser     = json_decode($response);

        $this->assertEquals($puHasImage, $puUser->data->hasimage);
        $this->assertEquals($puActive,   $puUser->data->active);
        $this->assertEquals($puUserId,   $puUser->data->user_id);
        
    }
}
?>
