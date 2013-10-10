<?php
require_once 'lib.php';
require_once 'Webservicelib.php';
require_once($CFG->libdir . '/gdlib.php');

class ProctorUCronProcessor {
    
    public function __construct(){
        $this->localDataStore = new LocalDataStoreClient();
    }

    /**
     * get a list of users that need to have a status assigned
     */
    public function blnUpdateNewUsersAsExempt(){
        global $DB;
        foreach(ProctorU::objGetAllUsersWithoutProctorStatus() as $unreg){
            $data = new stdClass();
            $data->userid   = $unreg->id;
            $data->fieldid  = ProctorU::intCustomFieldID();
            $data->data     = ProctorU::UNREGISTERED;
            mtrace(sprintf("Setting status unregistered for user %d", $unreg->id));
            $DB->insert_record('user_info_data',$data, false);//consider donig this as a bulk operation
        }
        
    }

    public function blnProcessUsers($users){        
        foreach($users as $u){
            $status = $this->constProcessUser($u);
            ProctorU::intSaveProfileFieldStatus($u->id, $status);
        }
    }
    
    public function constProcessUser($u){
        if(!isset($u->idnumber)){
            return ProctorU::UNREGISTERED;
        }
        if(!$this->localDataStore->blnUserExists($u->idnumber)){
//            mtrace(sprintf("User %d is NOT registered", $u->id));
            return ProctorU::UNREGISTERED;
        }
        $pseudoID = $this->localDataStore->intPseudoId($u->idnumber);
//        mtrace(sprintf("got pseudoID for user %s of %s", $u->id, $pseudoID));
        if($pseudoID !=false){
            $puClient = new ProctorUClient();
            if($puClient->blnUserStatus($pseudoID)){
                $path = $puClient->filGetUserImage($pseudoID);
//                $this->blnInsertPicture($path, $u->id);
                return ProctorU::VERIFIED;
            }else{
                return ProctorU::REGISTERED;
            }
        }else{
            return ProctorU::ERROR;
        }
    }
    
    public function blnInsertPicture($path, $userid){
        global $DB;
        $context = get_context_instance(CONTEXT_USER, $userid);
        process_new_icon($context, 'user', 'icon', 0, $path);
        $DB->set_field('user', 'picture', 1, array('id' => $userid));
    }
}
?>
