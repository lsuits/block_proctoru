<?php
global $CFG;
require_once $CFG->dirroot . '/blocks/proctoru/lib.php';

class block_proctoru extends block_base {

    public $field;
    public $pu;
    
    public function has_config() {
        return true;
    }

    public function init() {
        global $CFG;
        $this->pu = new ProctorU();
        $this->title = get_string('pluginname', 'block_proctoru');
        $fieldParams = array(
            'shortname' => get_string('infofield_shortname', 'block_proctoru'),
            'categoryid' => 1,
        );
        $this->field = $this->pu->default_profile_field($fieldParams);
    }

    public function get_content() {
        
        if ($this->content !== null) {
            return $this->content;
        }
        
        $this->content = new stdClass();

        if ($this->pu->isUserATeacherSomehwere()) {
            return $this->content;
        } else {
            return $this->content->text = "You must register with Proctor U before Moodle course content will be available!";
        }
    }


    public function cron() {
        global $CFG;
        if ($CFG->block_proctoru_bool_cron == 1) {
            mtrace(sprintf("Running ProctorU cron tasks"));
        } else {
            mtrace("Skipping ProctorU");
        }
        return true;
    }
}

?>