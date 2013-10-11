<?php

global $CFG;
require_once $CFG->libdir . '/filelib.php';
require_once($CFG->dirroot.'/user/filters/profilefield.php');
require_once($CFG->dirroot.'/user/filters/yesno.php');

class ProctorU {

    public $username, $password, $localWebservicesCredentialsUrl, $localWebserviceUrl;
    
    const ERROR         = -1;
    const UNREGISTERED  = 0;
    const REGISTERED    = 1;
    const VERIFIED      = 2;
    const EXEMPT        = 3;
    
    public function __construct() {
        $this->localWebservicesCredentialsUrl = get_config('block_proctoru', 'credentials_location');
        $this->localWebservicesUrl            = get_config('block_proctoru', 'localwebservice_url');
    }

    /**
     * insert new record into {user_info_field}
     * @global type $DB
     * @param type $params
     * @return \stdClass
     */
    public static function default_profile_field($params) {
        global $DB;

        if (!$field = $DB->get_record('user_info_field', $params)) {
            $field              = new stdClass;
            $field->shortname   = $params['shortname'];
            $field->name        = get_string($field->shortname, 'block_proctoru');
            $field->description = get_string('profilefield_shortname', 'block_proctoru');
            $field->descriptionformat = 1;
            $field->datatype    = 'text';
            $field->categoryid  = $params['categoryid'];
            $field->locked      = 1;
            $field->visible     = 1;
            $field->param1      = 30;
            $field->param2      = 2048;

            $field->id = $DB->insert_record('user_info_field', $field);
        }

        return $field;
    }
    
    /**
     * helper fn
     * @return string shortname of the custom field in the DB
     */
    public static function strFieldname() {
        return "user_".get_config('block_proctoru','profilefield_shortname');
    }
    
    /**
     * helper fn returning the record ID of the custom field
     * @global type $DB
     * @return int ID of the custom field
     */
    public static function intCustomFieldID(){
        global $DB;
        return $DB->get_field('user_info_field', 'id', array('shortname'=>self::strFieldname()));
    }

    /**
     * Simple DB lookup, directly in the {user_info_data} table,
     * for an occurence of the userid WHERE fieldid = ??
     * @global stdClass $USER
     * @return stdClass|false
     */
    public static function blnUserHasProctoruProfileFieldValue($userid) {
        global $DB;
        $result = $DB->record_exists('user_info_data',
                array('id'=>$userid, 'fieldid'=>self::intCustomFieldID()));
        
        return $result;
    }
    
    public static function blnUserHasAcceptableStatus($userid) {
        $status = self::blnUserHasProctoruProfileFieldValue($userid);
        
        if($status == ProctorU::VERIFIED || $status == ProctorU::EXEMPT){
            return true;
        }elseif(self::blnUserHasExemptRole()){
            return true;
        }else{
            return false;
        }
    }
    
    public static function blnUserHasExemptRole($userid){
        global $DB;
        $exemptRoleIds = get_config('block_proctoru', 'roleselection');
        $sql = "SELECT id
                FROM {role_assignments} 
                WHERE roleid IN ({$exemptRoleIds}) AND userid = {$userid}";
                
        $intRoles = count($DB->get_records_sql($sql));
        return  $intRoles > 0 ? true : false;
    }
    
    /**
     * @global type $DB
     * @param int $userid
     * @param ProctorU $status one of the class constants
     * @return int insert id
     */
    public static function intSaveProfileFieldStatus($userid, $status){
        global $DB;
        $msg = sprintf("Setting ProctorU status for user %s: ", $userid);

        $fieldId = self::intCustomFieldID();

        $record  = $DB->get_record('user_info_data', array('userid'=>$userid, 'fieldid'=>$fieldId));
        
        if(!$record){
            $record = new stdClass();
            $record->data = $status;
            $record->userid = $userid;
            $record->fieldid = $fieldId;
            
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
    
/**
 * Partial application of the datalib.php function get_users_listing tailored to 
 * the task at hand
 * 
 * Return filtered (if provided) list of users in site, except guest and deleted users.
 *
 * @param string $sort          PASSTHROUGH An SQL field to sort by
 * @param string $dir           PASSTHROUGH The sort direction ASC|DESC
 * @param int $page             PASSTHROUGH The page or records to return
 * @param int                   PASSTHROUGH $recordsperpage The number of records to return per page
 * @param string                PASSTHROUGH(|IGNORE) $search A simple string to search for
 * @param string $firstinitial  PASSTHROUGH Users whose first name starts with $firstinitial
 * @param string $lastinitial   PASSTHROUGH Users whose last name starts with $lastinitial
 * @param string $extraselect   An additional SQL select statement to append to the query
 * @param array  $extraparams   Additional parameters to use for the above $extraselect
 * @param stdClass $extracontext If specified, will include user 'extra fields'
 *   as appropriate for current user and given context
 * @return array Array of {@link $USER} records
 */
public static function partial_get_users_listing($status= null,$sort='lastaccess', $dir='ASC', $page=0, $recordsperpage=0,
                           $search='', $firstinitial='', $lastinitial='') {

    global $DB;
    // $status = PROCTORU::VERIFIED; 
    // echo $status;
    // the extraselect needs to vary to allow the user to specify 'is not empty', etc
    $proFilter  = new user_filter_profilefield('profile','Profile',1);

    if(!isset($status)){
        $extraselect = '';
        $extraparams = array();
    }else{
        //figure out which field key the filter function uses for our field
        $fieldKey = null;
        $fieldShortname = "user_".get_config('block_proctoru', 'profilefield_shortname');
        foreach($proFilter->get_profile_fields() as $k=>$sn){
            if($sn == $fieldShortname){
                $fieldKey = $k;
            }
        }

        $data['profile'] = $fieldKey;
        switch($status){
            case ProctorU::UNREGISTERED:
                $data['operator']   = 6;
                $data['value']      = '';
                break;
            case ProctorU::REGISTERED:
                $data['operator']   = 2;
                $data['value']      = ProctorU::REGISTERED;
                break;
            case ProctorU::VERIFIED:
                $data['operator']   = 2;
                $data['value']      = ProctorU::VERIFIED;
                break;
        }
        list($extraselect, $extraparams) = $proFilter->get_sql_filter($data);
    }

    $suspFilter = new user_filter_yesno('suspended', 'Suspended',1,'suspended');
    $suspData = array(
        'value' => "0",
    );
    list($suspXSelect, $suspXParams) = $suspFilter->get_sql_filter($suspData);
    
    $extraselect .= " AND ".$suspXSelect . "AND deleted = 0";
    $extraparams += $suspXParams;
    
    $extracontext= context_system::instance();
    
    return get_users_listing($sort,$dir,$page,$recordsperpage,$search,
            $firstinitial,$lastinitial, $extraselect, $extraparams, $extracontext);
    }
    
    public static function partial_get_users_listing_by_roleid($roleid){
        $roFilter     = new user_filter_courserole('role', 'Role', 1);
        $data         = array('value'=>false, 'roleid'=>$roleid, 'categoryid'=>0);
        $extracontext = context_system::instance();
        
        list($extraselect, $extraparams) = $roFilter->get_sql_filter($data);
        
        //exclude suspended users
        {
            $suspFilter = new user_filter_yesno('suspended', 'Suspended',1,'suspended');
            $suspData = array(
                'value' => "0",
            );
            list($suspXSelect, $suspXParams) = $suspFilter->get_sql_filter($suspData);

            $extraselect .= " AND ".$suspXSelect . "AND deleted = 0";
            $extraparams += $suspXParams;
        }
        
        return get_users_listing('','',null,null,'',
            '','', $extraselect, $extraparams, $extracontext);
    }
    
    /**
     * 
     * @global type $DB
     * @return object[] role records of type stdClass, keyed by id
     */
    public static function objGetExemptRoles(){
        global $DB;
        $rolesConfig = get_config('block_proctoru', 'roleselection');
        return $DB->get_records_list('role', 'id', explode(',', $rolesConfig));
    }
    
    public static function objGetExemptUsers() {
        $exemptRoles = self::objGetExemptRoles();
        $exempt = array();
        foreach (array_keys($exemptRoles) as $roleid) {
            $exempt += ProctorU::partial_get_users_listing_by_roleid($roleid);
        }
        return $exempt;
    }
    
        
    public static function objGetAllUsers(){
        global $DB;
        $guestUser = $DB->get_field('user', 'id', array('username'=>'guest'));
        $all = $DB->get_records('user', array('suspended'=>0, 'deleted'=>0));
        unset($all[$guestUser]);
        return $all;
    }
    
    public static function objGetAllUsersWithProctorStatus(){
        return self::objGetUnregisteredUsers() +
                self::objGetRegisteredUsers()  +
                self::objGetVerifiedUsers();
    }
    
    public static function objGetAllUsersWithoutProctorStatus(){

        $all = self::objGetAllUsers();
        mtrace(sprintf("found %d users without PU status", count($all)));

        $ids = array_diff(
                array_keys($all),
                array_keys(self::objGetAllUsersWithProctorStatus())
                );
        return array_intersect_key($all, array_flip($ids));
    }
    
    public static function objGetUnregisteredUsers(){
        return ProctorU::partial_get_users_listing(ProctorU::UNREGISTERED);
    }
    
    public static function objGetRegisteredUsers(){
        return ProctorU::partial_get_users_listing(ProctorU::REGISTERED);
    }
    
    public static function objGetVerifiedUsers(){
        return ProctorU::partial_get_users_listing(ProctorU::VERIFIED);
    }
            
}
?>
