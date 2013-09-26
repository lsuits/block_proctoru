<?php

class block_proctoru extends block_base {
    
    public function has_config(){
        return true;
    }
    public function init() {
        $this->title = get_string('pluginname', 'block_proctoru');
    }
    
    public function get_content() {

        if ($this->content !== null) {
          return $this->content;
        }
        
        $this->content = new stdClass();

        if($this->isUserATeacherSomehwere()){
            return $this->content;
        }else{
            return $this->content->text = "You must register with Proctor U before Moodle course content will be available!";
        }

    }
    
    public function isUserATeacherSomehwere(){
        global $CFG,$USER;
        require_login();
        
        $contexts = $USER->access['ra'];
        
        foreach($contexts as $path=>$role){
            foreach(array_values($role) as $roleid){
                if(in_array($roleid, explode(',',$CFG->block_proctoru_roleselection))){
                    return true;
                }
            }
        }
        return false;
    }

    public function cron(){
        global $CFG;
        if($CFG->block_proctoru_bool_cron == 1){
            mtrace(sprintf("Running ProctorU cron tasks"));
        }else{
            mtrace("Skipping ProctorU");
        }
        return true;
    }
 
}
?>
