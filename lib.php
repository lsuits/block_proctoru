<?php

global $CFG;
require_once $CFG->libdir . '/filelib.php';

class ProctorU {

    public $username, $password, $localWebservicesCredentialsUrl, $localWebserviceUrl;
    
    const ERROR         = -1;
    const UNREGISTERED  = 0;
    const REGISTERED    = 1;
    const VERIFIED      = 2;
    
    public function __construct() {
        $this->localWebservicesCredentialsUrl = get_config('block_proctoru', 'credentials_location');
        $this->localWebservicesUrl = get_config('block_proctoru', 'localwebservice_url');
    }

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
            $field->description = get_string('profilefield_shortname', 'block_proctoru');
            $field->descriptionformat = 1;
            $field->datatype = 'text';
            $field->categoryid = $params['categoryid'];
            $field->locked  = 1;
            $field->visible = 1;
            $field->param1  = 30;
            $field->param2  = 2048;

            $field->id = $DB->insert_record('user_info_field', $field);
        }

        return $field;
    }

    /**
     * Determine whether the user is proctoru-registered or exempt.
     * Admins are exempt and return true
     * Users having any instance of any role specified in the admin settings
     * for this block are exempt and return true
     * Users aready having a value == 'registered' in their custom 
     * proctoru profile field return true
     *
     * @return type
     */
    public function userHasRegistration() {
        global $USER;
        require_login();

        $admin      = is_siteadmin($USER->id);
        $exempt     = $this->userHasExemptRole();
        $registered = $this->userHasProctoruProfileFieldValue();

        return $admin or $exempt or $registered;
    }

    /**
     * see if the proctoru custom field exists in the user profile
     * @global stdClass $USER
     * @return stdClass|false
     */
    public function userHasProctoruProfileFieldValue() {
        global $USER;

        $custField = get_config('block_proctoru', 'infofield_shortname');
        //@TODO handle specific values, not just non-null
        return isset($USER->profile[$custField]) ? $USER->profile[$custField] : false;
    }

    public function userHasExemptRole() {
        $paths = $this->getFlattenedUserAccessContextPaths();
        $userRoles = $paths ? array_values($paths) : false;
        $rolesExempt = explode(',', get_config('block_proctoru', 'roleselection'));

        if (!$userRoles || empty($rolesExempt)) {
            return false;
        }
        $intersection = array_intersect(array_values($userRoles), $rolesExempt);

        return empty($intersection) ? false : true;
    }

    /**
     * helper method to get the 'access' member of the global USER object
     * and flatten it into a new array of the form
     * contextPath => roleid
     * @global type $USER
     * @return boolean|mixed
     */
    public function getFlattenedUserAccessContextPaths() {
        global $USER;
        if (!isset($USER->access['ra'])) {
            return false;
        }
        $mapPathRoles = array();
        //@TODO eliminate the nested loops
        foreach ($USER->access['ra'] as $path => $raMap) {
            foreach (array_keys($raMap) as $role) {
                $mapPathRoles[$path] = $role;
            }
        }
        return $mapPathRoles;
    }
    
    public function usrGetMoodleUser($userId) {
        global $DB;
        $user = $DB->get_record('user', array('id' => $userId));
        return $user;
    }
    
    /**
     * 
     * @global type $DB
     * @param int $userid
     * @param ProctorU $status one of the class constants
     * @return int insert id
     */
    public static function intSaveProfileFieldStatus($userid, $status){
        global $DB;
        $msg = sprintf("Setting ProctorU status for user %s: ", $userid);
        //@TODO put these lookups into some method or class var
        $shortname = get_config('block_proctoru', 'profilefield_shortname'); 
        $fieldId = $DB->get_field('user_info_field', 'id', array('shortname'=>'user_'.$shortname));
        
        $record  = $DB->get_record('user_info_data', array('userid'=>$userid, 'fieldid'=>$fieldId));
        
        if(!$record){
            $record = new stdClass();
            $record->data = $status; //DRY
            $record->userid = $userid;
            $record->fieldid = $fieldId;
            $record->dataformat = 0; //why
            
            mtrace(sprintf("%sInsert new record, status %s", $msg,$status));
            return $DB->insert_record('user_info_data',$record, true, false);
            
        }elseif($record->data != $status){
            
            mtrace(sprintf("%supdate from %s to %s.",$msg,$record->data,$status));
            $record->data = $status;
            return $DB->update_record('user_info_data',$record, false);
        }else{
            mtrace(sprintf("%s Already set - do nothing", $msg));
            return true;
        }
        
    }
    
    
    public static function arrFetchNonExemptUserids() {
        global $DB;
        $rolesExempt = get_config('block_proctoru', 'roleselection');
        $sql = sprintf('SELECT DISTINCT userid FROM {role_assignments} WHERE roleid NOT IN (%s)', $rolesExempt);
        return array_keys($DB->get_records_sql($sql));
    }
    
    /**
     * 
     * @global type $DB
     * @param array $filter IDs to search in
     * @return type
     */
    public static function arrFetchRegisteredStatusByUserid(array $filter = array(), $status = "*"){
        global $DB;
        
        $shortname = get_config('block_proctoru', 'profilefield_shortname');
        $dataFilter = isset($status) ? "AND data    = \"$status\"" : "";
        $sql = "SELECT u.id, u.username, u.idnumber, (
                    SELECT data 
                    FROM   {user_info_data}
                    WHERE  userid = u.id 
                        $dataFilter
                        AND fieldid = (
                                SELECT id 
                                FROM   {user_info_field}
                                WHERE  shortname = '{$shortname}'
                                )
                        ) AS status 
                FROM {user} u ";
        
        $sql .= empty($filter) ? ";" : sprintf("WHERE u.id IN (%s);", implode(',',$filter));

        return $DB->get_records_sql(sprintf($sql,$shortname));
    }
}
?>
