<?php

defined('MOODLE_INTERNAL') || die;
global $DB;

if ($ADMIN->fulltree) {

    $roles = role_get_names(null, null, true);

    $exemptRoles = array('student');

    $settings->add(
            new admin_setting_configmultiselect(
                    'block_proctoru_roleselection',
                    get_string('block_proctoru_roleselectionlabel','block_proctoru'),
                    get_string('block_proctoru_roleselectiondescription','block_proctoru'),
                    $exemptRoles,
                    $roles
            )
    );
}
?>
