<?php
require_once 'lib.php';
require_once 'Webservicelib.php';

class ProctorUCronProcessor {
    
    public function __construct(){
        $this->localDataStore = new LocalDataStoreClient();
    }


    /**
     * @TODO allow this to be parameterized during corn
     */
    public function blnProcessUsers(array $userids= array(), $status = ProctorU::UNREGISTERED){

        $userids = empty($userids) ? ProctorU::arrFetchNonExemptUserids() :$userids;
        $users   = ProctorU::arrFetchRegisteredStatusByUserid($userids);
        
        foreach($users as $u){
            $status = $this->constProcessUser($u);
            ProctorU::intSaveProfileFieldStatus($u->id, $status);
        }
    }
    
    public function constProcessUser($u){
//        mtrace(sprintf("Processing user with idnumer %s", $u->idnumber));
        if(!$this->localDataStore->blnUserExists($u->idnumber)){
//            mtrace(sprintf("User %d is NOT registered", $u->id));
            return ProctorU::UNREGISTERED;
        }
        $pseudoID = $this->localDataStore->intPseudoId($u->idnumber);
//        mtrace(sprintf("got pseudoID for user %s of %s", $u->id, $pseudoID));
        if($pseudoID !=false){
            $puClient = new ProctorUClient();
            if($puClient->blnUserStatus($pseudoID)){
                return ProctorU::VERIFIED;
            }else{
                return ProctorU::REGISTERED;
            }
        }else{
            return ProctorU::ERROR;
        }
    }
}
?>
