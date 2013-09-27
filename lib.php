<?php

require_once $CFG->libdir . '/filelib.php';

class ProctorU {

    public $username, $password, $localWebservicesCredentialsUrl, $localWebserviceUrl;

    public function __construct() {
        $this->localWebservicesCredentialsUrl = get_config('block_proctoru','credentials_location');
        $this->localWebservicesUrl = get_config('block_proctoru','localwebservice_url');
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
        //@TODO - see what else we can use require_login for to restrict/redirect
        require_login();

        if (is_siteadmin($USER->id)) {
            return true;
        }

        $mapPathRoles = $this->getUserAccessContextPaths();

        foreach (array_values($mapPathRoles) as $role) {
            if (in_array($role, explode(',', $CFG->block_proctoru_roleselection))) {
                return true;
            }
        }
        return false;
    }

    public function getFlattenedUserAccessContextPaths() {
        global $USER;
        if (!isset($USER->access['ra'])) {
            return false;
        }
        $mapPathRoles = array();
        foreach ($USER->access['ra'] as $path => $raMap) {
            foreach (array_keys($raMap) as $role) {
                $mapPathRoles[] = array($path => $role);
            }
        }
        return $mapPathRoles;
    }

    public function ensureLocalUserExists($userId) {
        $user = $this->getMoodleUser($userId);
        $params = array(
            'serviceId' => get_config('block_proctoru','localwebservice_fetchuser_servicename'),
            'widget1'   => $this->username,
            'widget2'   => $this->password,
            '1'         => $user->idnumber,
        );

        $curl = new curl();
        $resp = $curl->post($this->localWebservicesUrl, $params);

        return $resp;
    }

    public function getMoodleUser($userId) {
        global $DB;
        $user = $DB->get_record('user', array('id' => $userId));
        return $user;
    }

//    public function fetchLocalUser($userId) {
//        $params = array(
//        'serviceId' =>
//        );
//        $curl = new curl();
//        $resp = $curl->post($this->localWebserviceUrl, $params);
//
//        return $localUser;
//    }

    public function verifyProctorUser($userId) {
        $registrationStatus = false;
        $proctorURecord = fetchProctorURecord($userId);
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

    public function getLocalWebservicesCredentials() {
        global $CFG;

        if (!preg_match('/^[http|https]/', $this->localWebservicesCredentialsUrl)) {
            throw new Exception('bad_url');
        }

        require_once $CFG->libdir . '/filelib.php';

        $curl = new curl(array('cache' => true));
        $resp = $curl->post($this->localWebservicesCredentialsUrl, array('credentials' => 'get'));

        list($username, $password) = explode("\n", $resp);

        if (empty($username) or empty($password)) {
            throw new Exception('bad_resp');
        }

        $this->username = trim($username);
        $this->password = trim($password);
    }

}

?>
