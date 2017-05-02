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

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // ... groupevaluation instance ID - it should be named as the first character of the module.

if (!isset($SESSION->groupevaluation)) {
    $SESSION->groupevaluation = new stdClass();
}
$SESSION->groupevaluation->current_tab = 'view';

if ($id) {
    $cm         = get_coursemodule_from_id('groupevaluation', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $groupevaluation  = $DB->get_record('groupevaluation', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $groupevaluation  = $DB->get_record('groupevaluation', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $groupevaluation->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('groupevaluation', $groupevaluation->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

// Check login and get context.
require_login($course, true, $cm);
$context = context_module::instance($cm->id);

$url = new moodle_url($CFG->wwwroot.'/mod/groupevaluation/view.php');
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

// Print the page header.
$PAGE->set_title(format_string($groupevaluation->name));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($groupevaluation->name));

// Print the main part of the page.
if ($groupevaluation->intro) {
    echo $OUTPUT->box(format_module_intro('groupevaluation', $groupevaluation, $cm->id), 'generalbox', 'intro');
}

//echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');

$currentgroupid = groups_get_activity_group($cm);
if (!groups_is_member($currentgroupid, $USER->id)) {
    $currentgroupid = 0;
}

$numusers = 0; //borrar TODO
if (has_capability('mod/groupevaluation:readresponses', $context)) {
    echo "<div class=\"reportlink\"><a href=\"report.php?id=$cm->id\">".
          get_string("viewgroupevaluationresponses", "groupevaluation", $numusers)."</a></div>";
} else if (!$cm->visible) {
    notice(get_string("activityiscurrentlyhidden"));
}
//***************************************************
if (has_capability('mod/groupevaluation:editsurvey', $context)) {

  /*// TODO borrar
  $tableaux = 'groupevaluation';
  $selectaux = "id = $groupevaluation->id"; //is put into the where clause
  $resultaux = $DB->get_records_select($tableaux,$selectaux);
  foreach ($resultaux as $aux) {
    $array = get_object_vars ($aux);
    foreach ($array as $a) {
      echo '<p>'.$a.'</p>';
    }
  }*/

  //$result = $DB->get_records_sql('SELECT COUNT(*) FROM groupevaluation_surveys WHERE groupevaluationid = ?', array($groupevaluation->id));
  $table = 'groupevaluation_surveys';
  $select = "groupevaluationid = $groupevaluation->id"; //is put into the where clause
  $result = $DB->get_records_select($table,$select);
  if (!$result) {
    echo '<p>'.get_string('nosurvey', 'groupevaluation').'</p>';
    echo '<a href="'.$CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/criterions.php?'.'id='.$cm->id).'">'.'<strong>'.get_string("createsurvey", "groupevaluation").'</strong></a>';
  } else {
    echo '<a href="'.$CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/criterions.php?'.'id='.$cm->id).'">'.'<strong>'.get_string("editsurvey", "groupevaluation").'</strong></a>';
  }
} else {// TODO.
  $select = 'groupevaluationid = '.$groupevaluation->id.' AND userid = \''.$USER->id.'\'';
  $resume = $DB->get_record_select('groupevaluation_surveys', $select, null) !== false;
  if (!$resume) {
      $complete = get_string('answerquestions', 'groupevaluation');
  } else {
      $complete = get_string('resumesurvey', 'groupevaluation');
  }
  $criterions = $DB->get_records('groupevaluation_criterions', array('groupevaluationid' => $groupevaluation->id), 'id');

  //if ($criterions) {
      $href = $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/complete.php?id='.$cm->id.'&resume='.$resume);

      echo $OUTPUT->heading(get_string("group",'groupevaluation'), 3, 'helptitle', 'uniqueid');
      echo $OUTPUT->heading('Grupo 0', 5, 'helptitle', 'uniqueid');

      echo $OUTPUT->heading(get_string("evaluations",'groupevaluation'), 3, 'helptitle', 'uniqueid');

      echo $OUTPUT->heading('Alumno Uno', 5, 'helptitle', 'uniqueid');

      echo('<div><a href="'.$href.'" class="btn btn-default btn-lg" role="button">'.
          get_string("evaluate",'groupevaluation').'</a></div>'); //TODO substituir por get_string

      echo $OUTPUT->heading('Alumno Dos', 5, 'helptitle', 'uniqueid');
      echo('<div><a href="'.$href.'" class="btn btn-default btn-lg" role="button">'.
          get_string("evaluate",'groupevaluation').'</a></div>'); //TODO substituir por get_string

  //}
}


//echo $OUTPUT->box_end();


// Finish the page.
echo $OUTPUT->footer();
