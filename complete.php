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
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot.'/mod/groupevaluation/locallib.php');

if (!isset($SESSION->groupevaluation)) {
    $SESSION->groupevaluation = new stdClass();
}
$SESSION->groupevaluation->current_tab = 'view';

$id = optional_param('id', null, PARAM_INT);    // Course Module ID.
$a = optional_param('a', null, PARAM_INT);      // groupevaluation ID.

$sid = optional_param('sid', null, PARAM_INT);  // Survey id.
$resume = optional_param('resume', null, PARAM_INT);    // Is this attempt a resume of a saved attempt?

if ($id) {
    if (! $cm = get_coursemodule_from_id('groupevaluation', $id)) {
        print_error('invalidcoursemodule');
    }

    if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
        print_error('coursemisconf');
    }

    if (! $groupevaluation = $DB->get_record("groupevaluation", array("id" => $cm->instance))) {
        print_error('invalidcoursemodule');
    }

} else {
    if (! $groupevaluation = $DB->get_record("groupevaluation", array("id" => $a))) {
        print_error('invalidcoursemodule');
    }
    if (! $course = $DB->get_record("course", array("id" => $groupevaluation->course))) {
        print_error('coursemisconf');
    }
    if (! $cm = get_coursemodule_from_instance("groupevaluation", $groupevaluation->id, $course->id)) {
        print_error('invalidcoursemodule');
    }
}

// Check login and get context.
require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/groupevaluation:view', $context);

$url = new moodle_url($CFG->wwwroot.'/mod/groupevaluation/complete.php');
if (isset($id)) {
    $url->param('id', $id);
} else {
    $url->param('a', $a);
}

$PAGE->set_url($url);
$PAGE->set_context($context);

$groupevaluation->strgroupevaluations = get_string("modulenameplural", "groupevaluation");
$groupevaluation->strgroupevaluation  = get_string("modulename", "groupevaluation");

// Mark as viewed.
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

if ($resume) {
    $context = context_module::instance($groupevaluation->cm->id);
    $anonymous = $groupevaluation->respondenttype == 'anonymous';

    $event = \mod_groupevaluation\event\attempt_resumed::create(array(
                    'objectid' => $groupevaluation->id,
                    'anonymous' => $anonymous,
                    'context' => $context
    ));
    $event->trigger();
}

view();
