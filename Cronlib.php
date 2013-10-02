<?php
require 'lib.php';

class ProctorUCron {

    public function arrFetchNonExemptUserids() {
        global $DB;
        $exemptRoles    = get_config('block_proctoru', 'roleselection');
        $sql = 'SELECT DISTINCT userid FROM {role_assignments} WHERE roleid NOT IN ?';
        return array_values($DB->get_records_sql($sql,$exemptRoles));
    }
    
    public function arrFetchRegisteredUserids(){
        global $DB;
        $sql = 'SELECT DISTINCT userid FROM {role_assignments} WHERE roleid NOT IN ?';
        return array_values($DB->get_records_sql($sql));
    }

    public function verifyProctorUser($userId) {
        $registrationStatus = false;
        $proctorURecord     = fetchProctorURecord($userId);
        //evaluate return from fetchProctorURecord($userId)
        if (isset($proctorURecord->hasImage)) {
            $registrationStatus = true;
        }
        return $registrationStatus;
    }

    public function fetchProctorURecord($localUser) {
        $proctorURecord = new stdClass();
        //curl their webservice
        return $proctorURecord;
    }

    public function updateUserRecord($userId) {
        
    }
    
}
?>
