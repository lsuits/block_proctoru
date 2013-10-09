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
    public function blnUpdateNewUsers(){
        global $DB;
        $reg    = array_keys(ProctorU::partial_get_users_listing(ProctorU::REGISTERED));
        
        $unreg  = array_keys($this->objGetUnregisteredUsers());
        
        $verif  = array_keys($this->objGetVerifiedUsers());
        
        $havePUStatus = array_merge($reg, $unreg, $verif);
        $all    = array_keys($DB->get_records('user'));
        $diff = array_diff($reg, $unreg);
        
        return $diff;
    }
    
    public function objGetAllUsers(){
        global $DB;
        return $DB->get_records('user');
    }
    
    public function objGetAllProctorUsers(){
        return $this->objGetUnregisteredUsers() +
                $this->objGetRegisteredUsers()  +
                $this->objGetVerifiedUsers();
    }
    
    public function objGetAllUsersWithoutProctorStatsus(){
        assert(is_array($this->objGetAllProctorUsers()));
        $all = $this->objGetAllUsers();

        $ids = array_diff(
                array_keys($all),
                array_keys($this->objGetAllProctorUsers())
                );
        return array_intersect_key($all, array_flip($ids));
    }
    
    public function objGetAllExemptUsers(){
        
    }
    
    public function objGetUnregisteredUsers(){
        return ProctorU::partial_get_users_listing(ProctorU::UNREGISTERED);
    }
    
    public function objGetRegisteredUsers(){
        return ProctorU::partial_get_users_listing(ProctorU::REGISTERED);
    }
    
    public function objGetVerifiedUsers(){
        return ProctorU::partial_get_users_listing(ProctorU::VERIFIED);
    }
    

    /**
     * @TODO allow this to be parameterized during corn
     */
    public function blnProcessUsers(){
        $filter = ProctorU::arrFetchNonExemptUserids();
        $status = "*"; //TODO: this needs to make better semantic sense
        $userfields = array();
        $sort="";
        $limit=10;
        
//        $userids = empty($userids) ? ProctorU::arrFetchNonExemptUserids() :$userids;
        $users   = ProctorU::partial_get_users_listing(ProctorU::UNREGISTERED);
        
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
                $path = $puClient->filGetUserImage($pseudoID);
                $this->blnInsertPicture($path, $u->id);
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
