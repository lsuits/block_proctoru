<?php
    $capabilities = array(
 
    'block/proctoru:myaddinstance' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'user' => CAP_PROHIBIT
        ),
 
        'clonepermissionsfrom' => 'moodle/my:manageblocks'
    ),
 
    'block/proctoru:addinstance' => array(
 
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'user' => CAP_PROHIBIT
        ),
 
        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),
);