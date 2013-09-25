<?php

class proctor_u extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_proctor_u');
    }
    
    public function get_content() {
        global $USER;
        if ($this->content !== null) {
          return $this->content;
        }
        
        
    }
    
    public function isUserATeacherSomehwere(){
        global $USER;
        require_login();
        
        $contexts = $USER->access['ra'];
        
        foreach($contexts as $path=>$role){
            if(($ctx = strstr('/1/3/',$path)!=false)){
                if($role == 5){
                    return true;
                }
            }
        }
        return false;
    }
 
}
?>
