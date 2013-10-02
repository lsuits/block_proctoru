<?php

global $CFG;
require_once $CFG->libdir . '/filelib.php';

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
