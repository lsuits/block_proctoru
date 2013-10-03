<?php
require_once '../../config.php';
global $CFG, $PAGE;
//$courseid = required_param('id', PARAM_INT);
$blockname = get_string('pluginname', 'block_proctoru');
$PAGE->set_url('/blocks/proctoru/index.php');
$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
//$PAGE->set_course($course);
$PAGE->set_heading($blockname);
$PAGE->set_title($blockname);
$PAGE->navbar->add($blockname);
$PAGE->set_pagetype('block_ues_logs');

echo $OUTPUT->header();
echo $OUTPUT->heading($blockname);

$puOutput       = $PAGE->theme->get_renderer($PAGE,'block_proctoru');
$message        = $puOutput->getUnregisteredMessage();
$landingCourse  = get_config('block_proctoru', 'landing_course');
$redirect       = new moodle_url('/course/view.php',array('id'=>$landingCourse));

notice($message, $redirect);
?>
