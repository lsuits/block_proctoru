<?php

global $CFG;
require_once $CFG->dirroot . '/blocks/proctoru/lib.php';
require_once $CFG->dirroot . '/blocks/proctoru/tests/conf/ConfigProctorU.php';

class ProctorUClient_testcase extends advanced_testcase {
    
    /**
     * load our local config and instantaite a new 
     * webservice client
     */
    public function setup() {
        $this->resetAfterTest();

        //init local config
        $this->conf = new ConfigProctorU();
        $this->conf->setConfigs();
        $this->class = new ProctorUClient();
    }
    
    public function test_LookupNotFoundUser() {
        $user       = $this->conf->data['testUser1']['pseudoId'];
        $response   = $this->class->strRequestUserProfile($user);
        $userConf   = $this->conf->data['testUser1'];
        $this->assertNotEmpty($response);
        
        $puUser     = json_decode($response);
        $this->assertNotEmpty($puUser);

        $this->assertStringStartsWith(
                $userConf['puMessage'], 
                $puUser->message
                );
    }

    /**
     * Fetch a user known to exist in the system
     * and verify that this user's fields agree with 
     * the values we expect for 'hasimage, etc...
     */
    public function test_LookupFoundUser() {
        $user       = $this->conf->data['testUser2']['pseudoId'];
        $response   = $this->class->strRequestUserProfile($user);
        
        $this->assertNotEmpty($response);
        
        $puHasImage = $this->conf->data['testUser2']['puHasImage'];
        $puActive   = $this->conf->data['testUser2']['puActive'];
        $puUserId   = $this->conf->data['testUser2']['puUserId'];
        
        $puUser     = json_decode($response);

        $this->assertEquals($puHasImage, $puUser->data->hasimage);
        $this->assertEquals($puActive,   $puUser->data->active);
        $this->assertEquals($puUserId,   $puUser->data->user_id);
    }
    
    public function test_LookupFoundUser_ProductionApi() {
        $this->class->baseUrl = get_config('block_proctoru', 'proctoru_api_prod');
        $user       = $this->conf->data['testUser3']['pseudoId'];
        $response   = $this->class->strRequestUserProfile($user);
        
        $this->assertNotEmpty($response);
        
        $puHasImage = $this->conf->data['testUser3']['puHasImage'];
        $puActive   = $this->conf->data['testUser3']['puActive'];
        $puUserId   = $this->conf->data['testUser3']['puUserId'];
        
        $puUser     = json_decode($response);

        $this->assertNotEmpty($puUser);
        $this->assertNotEmpty($puUser->data);
        $this->assertEquals($puHasImage, $puUser->data->hasimage);
        $this->assertEquals($puActive,   $puUser->data->active);
        $this->assertEquals($puUserId,   $puUser->data->user_id);
    }
    
    
}
?>
