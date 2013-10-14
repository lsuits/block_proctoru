<?php
global $CFG;
require_once $CFG->dirroot . '/blocks/proctoru/lib.php';
require_once 'Cronlib.php';

class block_proctoru extends block_base {

    public $field;
    
    
    public function has_config() {
        return true;
    }

    public function init() {

        $this->title = get_string('pluginname', 'block_proctoru');
        $fieldParams = array(
            'shortname' => get_string('profilefield_shortname', 'block_proctoru'),
            'categoryid' => 1,
        );
        $this->field = ProctorU::default_profile_field($fieldParams);
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

        $this->content    = new stdClass();
        $acceptableStatus = ProctorU::blnUserHasAcceptableStatus($USER->id);
        $hasExemptRole    = ProctorU::blnUserHasExemptRole($USER->id);

        if ($acceptableStatus or $hasExemptRole) {
            return $this->content;
        } elseif(!$excluded){
            header("Location: /blocks/proctoru/index.php");
        } else {
            //what?
        }
    }


    public function cron() {

        if (get_config('block_proctoru','bool_cron' == 1)) {
            mtrace(sprintf("Running ProctorU cron tasks"));
            $cron = new ProctorUCronProcessor();
            
            //get users without status (new users)
            list($unreg,$exempt) = $cron->objPartitionUsersWithoutStatus();
            
            //set appropriate status for new users
            $intUnreg = $cron->intSetStatusForUser($unreg, ProctorU::UNREGISTERED);
            mtrace(sprintf("Set status %s for %d of %d unregistered users.",ProctorU::UNREGISTERED, $intUnreg, count($unreg)));
            
            $intExempt = $cron->intSetStatusForUser($exempt, ProctorU::EXEMPT);
            mtrace(sprintf("Set status %s for %d of %d unregistered users.",ProctorU::EXEMPT, $intExempt, count($exempt)));

            $needProcessing = $cron->objGetUnverifiedUsers();
            mtrace(sprintf("Begin processing user status for %d users", count($needProcessing)));

            $cron->blnProcessUsers($needProcessing);
        } else {
            mtrace("Skipping ProctorU");
        }
        return true;
    }
}

?>