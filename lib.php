<?php

global $CFG;
require_once $CFG->libdir . '/filelib.php';

class ProctorU {

    public $username, $password, $localWebservicesCredentialsUrl, $localWebserviceUrl;

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

    public function xmlFetchLocalWebserviceResponse(array $params) {
        $curl = new curl();
        $resp = $curl->post($this->localWebservicesUrl, $params);
        return new SimpleXMLElement($resp);
    }

    /**
     * 
     * @param int $userId userid to find in remote service
     * @return String raw XML response
     */
    public function intGetPseudoId($userId) {
        $user = $this->usrGetMoodleUser($userId);

        $params = array(
            "serviceId" => get_config('block_proctoru', 'localwebservice_fetchuser_servicename'),
            "widget1" => $this->username,
            "widget2" => $this->password,
            "1" => $user->idnumber,
        );

        $curl = new curl();
        $resp = $curl->post($this->localWebservicesUrl, $params);
        //@TODO return int
        return $resp;
    }

    public function blnLocalWebserviceUserHasProfile($userId) {
        $user = $this->usrGetMoodleUser($userId);

        $params = array(
            "serviceId" => get_config('block_proctoru', 'localwebservice_userexists_servicename'),
            "widget1" => $this->username,
            "widget2" => $this->password,
            "1" => $user->idnumber,
            "2" => get_config('block_proctoru', 'stu_profile'),
        );

        $curl = new curl();
        $resp = $curl->post($this->localWebservicesUrl, $params);

        return $resp;
    }

    public function usrGetMoodleUser($userId) {
        global $DB;
        $user = $DB->get_record('user', array('id' => $userId));
        return $user;
    }

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

}

class CurlXmlClient {

    public $response;
    public $baseUrl;
    public $method;
    public $options;
    public $stdParams;
    public $params;

    public function __construct($baseUrl, $method, $options) {
        if (!preg_match('/^[http|https]/', $baseUrl)) {
            throw new Exception('bad_url');
        }
        $this->baseUrl      = $baseUrl;
        $this->method       = $method;
        $this->options      = $options;
        $this->stdParams    = array();
    }

    public function addParams(array $params= array()) {
        $this->params = array_merge($this->stdParams, $params);
        return $this;
    }

    public function strGetRawResponse() {
        $curl = new curl($this->options);
        $meth = $this->method;
        return $this->resp = $curl->$meth($this->baseUrl, $this->params);
    }

    public function xmlFetchResponse() {
        return new SimpleXMLElement($this->strGetRawResponse());
    }

}

class CredentialsClient extends CurlXmlClient {

    public function __construct() {
        $baseUrl   = get_config('block_proctoru', 'credentials_location');
        $method    = 'post';
        $options   = array('cache' => true);

        parent::__construct($baseUrl, $method, $options);

        $this->stdParams = array('credentials' => 'get');
        $this->addParams();
    }

}

class LocalDataStoreClient extends CurlXmlClient {

    public function __construct() {

        $baseUrl = get_config('block_proctoru', 'localwebservice_url');
        $method = 'get';
        $options = array();

        parent::__construct($baseUrl, $method, $options);
        list($w1, $w2) = $this->getWidgets();

        $this->stdParams = array(
            "widget1" => $w1,
            "widget2" => $w2,
        );
    }

    public function getWidgets() {

        $client = new CredentialsClient();
        $resp   = $client->strGetRawResponse();

        list($widget1, $widget2) = explode("\n", $resp);

        if (empty($widget1) or empty($widget2)) {
            throw new Exception('bad_resp');
        }

        return array(strtolower(trim($widget1)), trim($widget2));
    }

    public function blnUserExists($idnumber) {
        $this->addParams();
        $this->params['serviceId'] = get_config('block_proctoru', 'localwebservice_userexists_servicename');
        $this->params['1'] = $idnumber;
        $this->params['2'] = get_config('block_proctoru', 'stu_profile');

        $xml = $this->xmlFetchResponse();

        return (string)$xml->ROW->HAS_PROFILE;
    }

    public function intPseudoId($idnumber){
        $this->addParams();
        $this->params['serviceId'] = get_config('block_proctoru', 'localwebservice_userexists_servicename');
        $this->params['1'] = $idnumber;

        $xml = $this->xmlFetchResponse();
        return (int)(string)$xml->ROW->PSEUDO_ID;
    }
}

class ProctorUClient extends CurlXmlClient {
    public function __construct(){
        $baseUrl   = get_config('block_proctoru', 'proctoru_api');
        $method    = 'get';
        $options   = array('cache' => true);
        parent::__construct($baseUrl, $method, $options);
    }

    /**
     * @Override
     * @return type
     */
    public function strRequestUserProfile($pseudoId) {
        $now   = new DateTime();
        $meth  = $this->method;
        $curl  = new curl($this->options);
        $token = get_config('block_proctoru', 'proctoru_token');

        $curl->setHeader(sprintf('Authorization-Token: %s', $token));
        $this->params = array(
            'time_sent'     => $now->format(DateTime::ISO8601),
            'student_Id'    => $pseudoId
        );
        return $this->resp = $curl->$meth($this->baseUrl, $this->params);
    }
}

?>
