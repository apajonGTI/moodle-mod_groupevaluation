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
require_once($CFG->dirroot.'/mod/groupevaluation/locallib.php');
$lang = get_language();
require($CFG->dirroot.'/mod/groupevaluation/lang/'.$lang.'/groupevaluation.php');


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

//echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');

$currentgroupid = groups_get_activity_group($cm);
if (!groups_is_member($currentgroupid, $USER->id)) {
    $currentgroupid = 0;
}

//TODO Igual es mejor poner los alumno ealuados, no las encuestas entregadas. No sé.
$select = 'groupevaluationid = '.$groupevaluation->id.' AND status = '.groupevaluation_DONE;
$donesurveys = $DB->get_records_select('groupevaluation_surveys', $select);


//***************************************************
if (has_capability('mod/groupevaluation:editsurvey', $context)) {

  if (has_capability('mod/groupevaluation:readresponses', $context)) {
    echo "<div class=\"reportlink\"><a href=\"report.php?id=$cm->id\">".get_string("viewreport", "groupevaluation", count($donesurveys))."</a></div>";
  }

  echo "<div class=\"infobox\">";
  if ($groupevaluation->intro) {
    echo "<div class=\"descriptionbox\">".format_module_intro('groupevaluation', $groupevaluation, $cm->id).'</div>';
  }

  // STATE OF THE ACTIVITY //
  $href = $CFG->wwwroot.htmlspecialchars('/course/modedit.php?update='.$cm->id.'&return=1#id_timing');
  $edithtml = '<a href="'.$href.'">'.get_string('edit').'</a>';
  if (groupevaluation_is_open($groupevaluation->timeopen)) {
    echo '<div>'.get_string('isopen', 'groupevaluation', userdate($groupevaluation->timeopen)).' '.$edithtml.'</div>';
  } else if (!$groupevaluation->timeopen) {
    echo '<div class="notifyproblem">'.get_string('notopeningdate', 'groupevaluation').' '.$edithtml.'</div>';
  } else {
    echo '<div>'.get_string('notopen', 'groupevaluation', userdate($groupevaluation->timeopen)).' '.$edithtml.'</div>';
  }

  if (groupevaluation_is_closed($groupevaluation->timeclose)) {
    echo '<div>'.get_string('closed', 'groupevaluation', userdate($groupevaluation->timeclose)).' '.$edithtml.'</div>';
  } else if (!$groupevaluation->timeclose) {
    echo '<div class="notifyproblem">'.get_string('notclosingdate', 'groupevaluation').' '.$edithtml.'</div>';
  } else {
    echo '<div>'.get_string('notcloseduntil', 'groupevaluation', userdate($groupevaluation->timeclose)).' '.$edithtml.'</div>';
  }
  echo "</div>"; // end infobox
  echo '<div style="height: 20px"></div>';

  // BUTTONS //
  $table = 'groupevaluation_criterions';
  $select = "groupevaluationid = $groupevaluation->id"; //is put into the where clause
  $result = $DB->get_records_select($table,$select);
  if (!$result) {
    echo '<p>'.get_string('nosurvey', 'groupevaluation').'</p>';
    $href = $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/criterions.php?'.'id='.$cm->id);
    echo '<a href="'.$href.'" class="btn btn-default btn-lg" style = "border: 2px solid #d0d0d0;" role="button">'.get_string("createsurvey", "groupevaluation").'</a>';
  } else {
    $href = $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/criterions.php?'.'id='.$cm->id);
    echo '<a href="'.$href.'" class="btn btn-default btn-lg"  style = "border: 2px solid #d0d0d0;" role="button">'.get_string("editsurvey", "groupevaluation").'</a><br/>';
    echo '<div style="height: 10px"></div>';
    $href = $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/groups.php?'.'id='.$cm->id);
    echo '<a href="'.$href.'" class="btn btn-default btn-lg"  style = "border: 2px solid #d0d0d0;" role="button">'.get_string("setgroups", "groupevaluation").'</a>';
  }
} else { // Unprivileged user

  if (groupevaluation_is_closed($groupevaluation->timeclose)) {
    echo "<div class=\"reportlink\"><a href=\"results.php?id=$cm->id\">".get_string("viewresults", "groupevaluation")."</a></div>";
  }
  if ($groupevaluation->intro) {
      echo $OUTPUT->box(format_module_intro('groupevaluation', $groupevaluation, $cm->id), 'generalbox', 'intro');
  }

  // EALUATIONS OF THE STUDENT //
  $select = 'groupevaluationid = "'.$groupevaluation->id.'" AND authorid = "'.$USER->id.'"';
  $surveys = $DB->get_records_select('groupevaluation_surveys', $select);
  $groupids = array ();

  if (!groupevaluation_is_open($groupevaluation->timeopen)) {
    if (!$groupevaluation->timeopen) {
      echo '<div class="notifyproblem">'.get_string('notopeningdate', 'groupevaluation').'</div>';
    } else {
      echo '<div class="notifyproblem">'.get_string('notopen', 'groupevaluation', userdate($groupevaluation->timeopen)).'</div>';
    }
  } else if (groupevaluation_is_closed($groupevaluation->timeclose)) {
    echo '<div class="notifyproblem">'.get_string('closed', 'groupevaluation', userdate($groupevaluation->timeclose)).'</div>';
  } else {

    if (!$surveys) {
      echo $OUTPUT->notification(get_string('nonparticipatinguser', 'groupevaluation'));
    }

    foreach ($surveys as $survey) {
      $surveyid = $survey->id;
      $status = $survey->status;
      $href = $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/complete.php?id='.$cm->id.'&sid='.$surveyid);

      $group = $DB->get_record('groups',array('id'=>$survey->groupid));
      $user = $DB->get_record('user',array('id'=>$survey->userid));

      if (!in_array($group->id, $groupids)) {
        $groupids[] = $group->id;
        echo $OUTPUT->heading(get_string("group",'groupevaluation'), 3, 'helptitle', 'uniqueid');
        echo $OUTPUT->heading($group->name, 5, 'helptitle', 'uniqueid');

        echo $OUTPUT->heading(get_string("evaluations",'groupevaluation'), 3, 'helptitle', 'uniqueid');
      }

      if ($survey->userid == $survey->authorid) {
        $name = get_string('selfevaluation', 'groupevaluation');
      } else {
        $name = fullname($user);
      }
      $profileurl = $CFG->wwwroot.'/user/view.php?id='.$user->id.'&amp;course='.$course->id;
      $profilelink = '<a href="'.$profileurl.'">'.$name.'</a>';
      echo $OUTPUT->heading($profilelink, 5, 'helptitle', 'uniqueid');

      if ($status == groupevaluation_INCOMPLETE) {
        echo '<div><a href="'.$href.'" class="btn btn-default btn-lg"  style = "border: 2px solid #d0d0d0;" role="button">'.get_string('evaluate', 'groupevaluation').'</a></div>';
      } elseif ($status == groupevaluation_COMPLETE) {
        echo '<div>';
        echo '<a href="'.$href.'" class="btn btn-default btn-lg"  style = "border: 2px solid #d0d0d0;" role="button">'.get_string('evaluate', 'groupevaluation').'</a>';
        echo '<span style="color:red;"> '.get_string('inprogress', 'groupevaluation').'</span>';
        echo '<span> ('.get_string('notsubmitted', 'groupevaluation').')</span>';
        echo '</div>';
      } elseif ($status == groupevaluation_DONE) {
        echo '<div><p>'.get_string('evaluated', 'groupevaluation').'</p></div>';
      }
    }
  }
}

echo $OUTPUT->footer();
