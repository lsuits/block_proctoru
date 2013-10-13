<?php
require_once 'lib.php';
require_once 'Webservicelib.php';
require_once($CFG->libdir . '/gdlib.php');

class ProctorUCronProcessor {
    
    public function __construct(){
        $this->localDataStore = new LocalDataStoreClient();
        $this->puClient       = new ProctorUClient();
    }

    /**
     * Early cron phase:
     * For any user without a status already,set unregistered.
     */
    public function blnSetUnregisteredForUsersWithoutStatus(){
        $i=0;
        foreach(ProctorU::objGetAllUsersWithoutProctorStatus() as $unreg){
            mtrace(sprintf("Setting status unregistered for user %d", $unreg->id));
            ProctorU::intSaveProfileFieldStatus($unreg->id, ProctorU::UNREGISTERED);
            $i++;
        }
        return $i;
    }

    
    public function blnProcessUsers($users){        
        foreach($users as $u){
            $status = $this->constProcessUser($u);
            ProctorU::intSaveProfileFieldStatus($u->id, $status);
        }
    }
    
    public function constProcessUser($u){
        if(!isset($u->idnumber)){
            mtrace(sprintf("No idnumber for user with id %d", $u->id));
            throw new Exception(sprintf(
                    "Tried fetching data for user with no idnumber.\n
                        Details:\n
                        userid: %d\n
                        username: %s\n",$u->id,$u->username));
        }
        if(!$this->localDataStore->blnUserExists($u->idnumber)){

            mtrace(sprintf("User %d is NOT registered with DAS\n", $u->id));
            return ProctorU::SAM_HAS_PROFILE_ERROR;
        }
        $pseudoID = $this->localDataStore->intPseudoId($u->idnumber);
        mtrace(sprintf("got pseudoID for user %s of %s\n", $u->id, $pseudoID));
        
        if($pseudoID != false){

            $puStatus = $this->puClient->constUserStatus($pseudoID);
            if($puStatus == false){
                return ProctorU::PU_NOT_FOUND;
            }else{
                $path = $this->puClient->filGetUserImage($pseudoID);
//                $this->blnInsertPicture($path, $u->id);
                return $puStatus;
            }
        }else{
            mtrace(sprintf("Pseudo id lookup failed with unknown error for user with id %d", $u->id));
            return ProctorU::NO_PSEUDO;
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
