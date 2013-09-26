<?php

class block_proctoru extends block_base {

    public $field;
    
    public function has_config() {
        return true;
    }

    public function init() {
        $this->title = get_string('pluginname', 'block_proctoru');
        $fieldParams = array(
            'shortname' => get_string('infofield_shortname', 'block_proctoru'),
            'categoryid' => 1,
        );
        $this->field = self::default_profile_field($fieldParams);
    }

    public function get_content() {

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();

        if ($this->isUserATeacherSomehwere()) {
            return $this->content;
        } else {
            return $this->content->text = "You must register with Proctor U before Moodle course content will be available!";
        }
    }

    public function isUserATeacherSomehwere() {
        global $CFG, $USER;
        require_login();

        $contexts = $USER->access['ra'];

        foreach ($contexts as $path => $role) {
            foreach (array_values($role) as $roleid) {
                if (in_array($roleid, explode(',', $CFG->block_proctoru_roleselection))) {
                    return true;
                }
            }
        }
        return false;
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

    /**
     * 
     * @global type $DB
     * @param type $params
     * @return \stdClass
     */
    public static function default_profile_field($params) {
        global $DB;

        if (!$field = $DB->get_record('user_info_field', $params)) {
            $field = new stdClass;
            $field->shortname = $params['shortname'];
            $field->name = get_string($field->shortname, 'block_proctoru');
            $field->description = get_string('custom_field_desc', 'block_proctoru');
            $field->descriptionformat = 1;
            $field->datatype = 'text';
            $field->categoryid = $params['categoryid'];
            $field->locked = 1;
            $field->visible = 1;
            $field->param1 = 30;
            $field->param2 = 2048;

            $field->id = $DB->insert_record('user_info_field', $field);
        }

        return $field;
    }

}

?>
