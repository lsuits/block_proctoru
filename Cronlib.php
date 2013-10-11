<?php
require_once 'lib.php';
require_once 'Webservicelib.php';
require_once($CFG->libdir . '/gdlib.php');

class ProctorUCronProcessor {
    
    public function __construct(){
        $this->localDataStore = new LocalDataStoreClient();
    }

    /**
     * Early cron phase:
     * For any user without a status already,set unregistered.
     */
    public function blnSetUnregisteredForUsersWithoutStatus(){
        foreach(ProctorU::objGetAllUsersWithoutProctorStatus() as $unreg){
            mtrace(sprintf("Setting status unregistered for user %d", $unreg->id));
            ProctorU::intSaveProfileFieldStatus($unreg->id, ProctorU::UNREGISTERED);
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
            if($puClient->constUserStatus($pseudoID)){
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
