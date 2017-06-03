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

$sid = optional_param('sid', null, PARAM_INT);  // Survey id.
$resume = optional_param('resume', null, PARAM_INT);    // Is this attempt a resume of a saved attempt? TODO Utilizarlo para estudiante que quiera ver sus respuetas.
$incomplete = optional_param('incomplete', 0, PARAM_INT);    //

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
require_capability('mod/groupevaluation:view', $context);

$url = new moodle_url($CFG->wwwroot.'/mod/groupevaluation/complete.php');
if (isset($id)) {
    $url->param('id', $id);
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

if (!has_capability('mod/groupevaluation:view', $context)) {
    echo('<br/>');
    groupevaluation_notify(get_string("noteligible", "groupevaluation", $groupevaluation->name));
    echo('<div><a href="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'">'.
        get_string("continue").'</a></div>');
    exit;
}

// Print the main part of the page.


if (!$survey = $DB->get_record('groupevaluation_surveys', array('id' => $sid))) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(format_string($groupevaluation->name));
    echo '<div class="notifyproblem">'.get_string('notavail', 'groupevaluation').'</div>';
} else if (!groupevaluation_is_open($groupevaluation->timeopen)) {
  echo $OUTPUT->header();
  echo $OUTPUT->heading(format_string($groupevaluation->name));
  if (!$groupevaluation->timeopen) {
    echo '<div class="notifyproblem">'.get_string('notopeningdate', 'groupevaluation').'</div>';
  } else {
    echo '<div class="notifyproblem">'.get_string('notopen', 'groupevaluation', userdate($groupevaluation->timeopen)).'</div>';
  }
} else if (groupevaluation_is_closed($groupevaluation->timeclose)) {
  echo $OUTPUT->header();
  echo $OUTPUT->heading(format_string($groupevaluation->name));
  echo '<div class="notifyproblem">'.get_string('closed', 'groupevaluation', userdate($groupevaluation->timeclose)).'</div>';
} else if (!user_is_eligible()) {
  echo $OUTPUT->header();
  echo $OUTPUT->heading(format_string($groupevaluation->name));
    echo '<div class="notifyproblem">'.get_string('noteligible', 'groupevaluation').'</div>';
} else if (user_can_take($USER->id, $sid)) {

  // Criterions
  $groupevaluationid = $groupevaluation->id;
  $select = 'groupevaluationid = '.$groupevaluationid;
  $criterions = $DB->get_records_select('groupevaluation_criterions', $select, null, 'position ASC');

  // Answers saved
  $savedanswers = new stdClass();
  foreach ($criterions as $criterion) {
    $select = 'surveyid = '.$sid.' AND criterionid = '.$criterion->id;
    if ($entry = $DB->get_record_select('groupevaluation_assessments', $select)) {
      $name = 'answer_'.$criterion->id;
      $savedanswers->$name = $entry->assessment;
    }
  }

  $completeform = new groupevaluation_complete_form('complete.php');
  $sdata = new stdClass();
  $sdata->id = $cm->id;
  $sdata->sid = $sid;

  // Set hidden values.
  $completeform->set_data($sdata);

  if ($completeform->is_cancelled()) {
      // Switch to main screen.
      redirect($CFG->wwwroot.'/mod/groupevaluation/complete.php?id='.$cm->id.'&sid='.$sid);
  }
  if ($crtformdata = $completeform->get_data()) {
      if(isset($crtformdata->numcriterions)) {
        $numcriterions = $crtformdata->numcriterions;
      } else {
        $numcriterions = 0;
      }

      // For the radio inputs
      $exformdata = data_submitted();

      $complete = true;
      $answers = array ();

      foreach ($criterions as $criterion) {
        $answer = new stdClass();
        $answer->criterionid = $criterion->id;
        $answer->surveyid = $sid;
        $answername = 'answer_'.$criterion->id;
        if (isset($exformdata->$answername)) {
          $answer->assessment = $exformdata->$answername;

          $answers[] = $answer;
        } else {
          if ($criterion->required) {
            $complete = false;
          }
        }
      }

      $done = false;
      if (isset($exformdata->sendbutton) && $complete) {
        $done = true;
      }
      groupevaluation_save_answers($sid, $answers, $done);

      if (isset($exformdata->sendbutton) && !$complete) {
        redirect($CFG->wwwroot.'/mod/groupevaluation/complete.php?id='.$cm->id.'&sid='.$sid.'&incomplete=1');
      } else {
        redirect($CFG->wwwroot.'/mod/groupevaluation/view.php?id='.$cm->id);
      }
  }
  echo $OUTPUT->header();
  echo $OUTPUT->heading(format_string($groupevaluation->name));
  $completeform->display();

} else {
  echo $OUTPUT->header();
  echo $OUTPUT->heading(format_string($groupevaluation->name));
    echo '<div class="notifyproblem">'.get_string("alreadydone", "groupevaluation").'</div>';
}

// Finish the page.
echo $OUTPUT->footer($course);
