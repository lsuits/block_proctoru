<?php

class ProctorU {

    public $username, $password, $url;
    /**
     * 
     * @global type $DB
     * @param type $params
     * @return \stdClass
     */
    public function default_profile_field($params) {
        global $DB;

        if (!$field = $DB->get_record('user_info_field', $params)) {
            $field = new stdClass;
            $field->shortname = $params['shortname'];
            $field->name = get_string($field->shortname, 'block_proctoru');
            $field->description = get_string('custom_field_desc', 'block_proctoru');
            $field->descriptionformat = 1;
            $field->datatype = 'text';
            $field->categoryid = $params['categoryid'];
            $field->locked = 1;
            $field->visible = 1;
            $field->param1 = 30;
            $field->param2 = 2048;

            $field->id = $DB->insert_record('user_info_field', $field);
        }

        return $field;
    }
    
    public function isUserATeacherSomehwere() {
        global $CFG, $USER;
        require_login();

        $contexts = $USER->access['ra'];

        foreach ($contexts as $path => $role) {
            foreach (array_values($role) as $roleid) {
                if (in_array($roleid, explode(',', $CFG->block_proctoru_roleselection))) {
                    return true;
                }
            }
        }
        return false;
    }
    
    public function fetchLocalUser($userId){
        
        return $localUser;
    }
    
    public function verifyProctorUser($userId){
        $registrationStatus = false;
        $proctorURecord = fetchProctorURecord($userId);
        //evaluate return from fetchProctorURecord($userId)
        if(isset($proctorURecord->hasImage)){
            $registrationStatus = true;
        }
        return $registrationStatus;
    }
    
    public function fetchProctorURecord($localUser){
        $proctorURecord = new stdClass();
        //curl their webservice
        return $proctorURecord;
    }

    public function updateUserRecord($userId){
        
    }

    public function getLocalWebservicesCredentials(){
        global $CFG;

        if (!preg_match('/^[http|https]/', $this->url)) {
            throw new Exception('bad_url');
        }

        require_once $CFG->libdir . '/filelib.php';

        $curl = new curl(array('cache' => true));
        $resp = $curl->post($this->url, array('credentials' => 'get'));

        list($username, $password) = explode("\n", $resp);

        if (empty($username) or empty($password)) {
            throw new Exception('bad_resp');
        }

        $this->username = trim($username);
        $this->password = trim($password);
    }
}
?>
