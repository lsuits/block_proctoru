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

    $settings->add(
            new admin_setting_configtext(
                    'block_proctoru_proctoru_key',
                    get_string('block_proctoru_proctoru_token','block_proctoru'),
                    get_string('block_proctoru_proctoru_token_description','block_proctoru'),
                    ''
            )
    );

    $settings->add(
            new admin_setting_configtext(
                    'block_proctoru_credentials_location',
                    get_string('credentials_location','block_proctoru'),
                    get_string('credentials_location_description','block_proctoru'),
                    ''
            )
    );

    $settings->add(
            new admin_setting_configcheckbox(
                    'block_proctoru_bool_cron',
                    get_string('block_proctoru_cron_run','block_proctoru'),
                    get_string('block_proctoru_cron_desc','block_proctoru'),
                    true, true, false)
            );
}
?>
