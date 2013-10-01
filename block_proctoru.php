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

    public function applicable_formats() {
        return array('site' => true, 'my' => false, 'course' => true);
    }

    public function get_content() {
        global $COURSE, $USER;
        if ($this->content !== null) {
            return $this->content;
        }
        
        $public   = explode(',',get_config('block_proctoru','excluded_courses'));
        $excluded = in_array($COURSE->id,$public);

        $this->content = new stdClass();

        if ($this->pu->userHasRegistration()) {
            return $this->content;
        } elseif(!$excluded){
            header("Location: /blocks/proctoru/index.php");
        } else {

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