<?php

class ProctorU {
    /**
     * 
     * @global type $DB
     * @param type $params
     * @return \stdClass
     */
    public static function default_profile_field($params) {
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
    
    public static function isUserATeacherSomehwere() {
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
    
    public static function fetchLocalUser($userId){
        
        return $localUser;
    }
    
    public static function verifyProctorUser($userId){
        $registrationStatus = false;
        $proctorURecord = fetchProctorURecord($userId);
        //evaluate return from fetchProctorURecord($userId)
        if(isset($proctorURecord->hasImage)){
            $registrationStatus = true;
        }
        return $registrationStatus;
    }
    
    public static function fetchProctorURecord($localUser){
        $proctorURecord = new stdClass();
        //curl their webservice
        return $proctorURecord;
    }

    public static function updateUserRecord($userId){
        
    }
}
?>
