<?php

defined('MOODLE_INTERNAL') || die;
global $DB;

if ($ADMIN->fulltree) {

    $roles = role_get_names(null, null, true);

    $exemptRoles = array('student');

    $settings->add(
            new admin_setting_configmultiselect(
                    'block_proctoru/roleselection',
                    get_string('roleselection_label', 'block_proctoru'),
                    get_string('roleselection_description', 'block_proctoru'),
                    $exemptRoles,
                    $roles
            )
    );

    $settings->add(
            new admin_setting_configtext(
                    'block_proctoru/proctoru_token',
                    get_string('proctoru_token', 'block_proctoru'),
                    get_string('proctoru_token_description', 'block_proctoru'),
                    ''
            )
    );
    $settings->add(
            new admin_setting_configtext(
                    'block_proctoru/proctoru_api',
                    get_string('proctoru_api', 'block_proctoru'),
                    get_string('proctoru_api_description', 'block_proctoru'),
                    ''
            )
    );

    $settings->add(
            new admin_setting_configtext(
                    'block_proctoru/credentials_location',
                    get_string('credentials_location', 'block_proctoru'),
                    get_string('credentials_location_description', 'block_proctoru'),
                    ''
            )
    );

    $settings->add(
            new admin_setting_configtext(
                    'block_proctoru/localwebservice_url',
                    get_string('localwebservice_url', 'block_proctoru'),
                    get_string('localwebservice_url_description', 'block_proctoru'),
                    ''
            )
    );

    $settings->add(
            new admin_setting_configtext(
                    'block_proctoru/localwebservice_userexists_servicename',
                    get_string('localwebservice_userexists_servicename', 'block_proctoru'),
                    get_string('localwebservice_userexists_servicename_description', 'block_proctoru'),
                    ''
            )
    );
    $settings->add(
            new admin_setting_configtext(
                    'block_proctoru/localwebservice_fetchuser_servicename',
                    get_string('localwebservice_fetchuser_servicename', 'block_proctoru'),
                    get_string('localwebservice_fetchuser_servicename_description', 'block_proctoru'),
                    ''
            )
    );

    $settings->add(
            new admin_setting_configtext(
                    'block_proctoru/stu_profile',
                    get_string('stu_profile', 'block_proctoru'),
                    get_string('stu_profile_description', 'block_proctoru'),
                    '')
    );

    $settings->add(
            new admin_setting_configcheckbox(
                    'block_proctoru/bool_cron',
                    get_string('cron_run', 'block_proctoru'),
                    get_string('cron_desc', 'block_proctoru'),
                    true, true, false)
    );
}
?>
