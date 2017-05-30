<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Prints a particular instance of groupevaluation
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_groupevaluation
 * @copyright  Jose Vilas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("../../config.php");
require_once($CFG->dirroot.'/mod/groupevaluation/locallib.php');
require_once($CFG->dirroot.'/mod/groupevaluation/complete_form.php');

if (!isset($SESSION->groupevaluation)) {
    $SESSION->groupevaluation = new stdClass();
}
$SESSION->groupevaluation->current_tab = 'view';

$id = required_param('id', PARAM_INT);                // Course module ID
$a = optional_param('a', null, PARAM_INT);      // groupevaluation ID.

$sid = optional_param('sid', 0, PARAM_INT);  // Survey id.
$resume = optional_param('resume', null, PARAM_INT);    // Is this attempt a resume of a saved attempt?

if (! $cm = get_coursemodule_from_id('groupevaluation', $id)) {
    print_error('invalidcoursemodule');
}

if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
    print_error('coursemisconf');
}

if (! $groupevaluation = $DB->get_record("groupevaluation", array("id" => $cm->instance))) {
    print_error('invalidcoursemodule');
}



// Check login and get context.
require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/groupevaluation:editsurvey', $context);

if (!isset($SESSION->groupevaluation)) {
    $SESSION->groupevaluation = new stdClass();
}
$SESSION->groupevaluation->current_tab = 'preview';

$url = new moodle_url($CFG->wwwroot.'/mod/groupevaluation/preview.php');
if (isset($id)) {
    $url->param('id', $id);
} else {
    $url->param('a', $a);
}
if (isset($sid)) {
    $url->param('sid', $sid);
}

$PAGE->set_url($url);
$PAGE->set_context($context);

$PAGE->set_title(format_string($groupevaluation->name));
$PAGE->set_heading(format_string($course->fullname));



if (!$cm->visible && !has_capability('moodle/course:viewhiddenactivities', $context, null, false)) {
        notice(get_string("activityiscurrentlyhidden"));
}

// Print the main part of the page.

// Criterions
$groupevaluationid = $groupevaluation->id;
$select = 'groupevaluationid = '.$groupevaluationid;
$criterions = $DB->get_records_select('groupevaluation_criterions', $select, null, 'position ASC');

$completeform = new groupevaluation_complete_form('preview.php', true);
$sdata = new stdClass();
$sdata->id = $cm->id;
$sdata->sid = $sid;

// Set hidden values.
$completeform->set_data($sdata);

if ($completeform->is_cancelled()) {
    // Switch to main screen.
    redirect($CFG->wwwroot.'/mod/groupevaluation/preview.php?id='.$cm->id.'&sid='.$sid);
}
if ($crtformdata = $completeform->get_data()) {

}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($groupevaluation->name));
require('tabs.php');

$completeform->display();


// Finish the page.
echo $OUTPUT->footer($course);
