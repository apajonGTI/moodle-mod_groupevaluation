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
 * @package    mod_groupevaluation
 * @copyright  Jose Vilas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once($CFG->dirroot.'/mod/groupevaluation/locallib.php');
require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->libdir.'/gradelib.php');

// Get the params.
$id           = required_param('id', PARAM_INT);
$perpage      = optional_param('perpage', groupevaluation_DEFAULT_PAGE_COUNT, PARAM_INT);  // How many per page.
$showall      = optional_param('showall', false, PARAM_INT);  // Should we show all users?
$viewmode     = optional_param('viewmode', 'participants', PARAM_ALPHA); //
$orderby      = optional_param('orderby', 'firstname', PARAM_ALPHA); //
$groupid      = optional_param('groupid', 0, PARAM_INT);  //
$newgrade     = optional_param('newgrade', 0, PARAM_INT);  //

if (!isset($SESSION->groupevaluation)) {
    $SESSION->groupevaluation = new stdClass();
}

$SESSION->groupevaluation->current_tab = 'report';

// Get the objects.

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
}

$url = new moodle_url('/mod/groupevaluation/report.php', array('id' => $cm->id));

$PAGE->set_url($url);

if (!$context = context_module::instance($cm->id)) {
        print_error('badcontext');
}

// We need the coursecontext to allow updateing of mass mails.
if (!$coursecontext = context_course::instance($course->id)) {
        print_error('badcontext');
}

require_login($course, false, $cm);

if (($formdata = data_submitted()) AND !confirm_sesskey()) {
    print_error('invalidsesskey');
}

require_capability('mod/groupevaluation:readresponses', $context);

// UPDATE USER GRADE //
if ($newgrade) {
  $editgrade = $_POST['editgrade'];

  foreach ($editgrade as $userid => $grade) {
    $conditions = array('userid' => $userid, 'groupevaluationid' => $groupevaluation->id);

    if ($usergrade = $DB->get_record('groupevaluation_grades', $conditions)) {
      $usergrade->grade = $grade;
      $usergrade->timemodified = time();
      $DB->update_record('groupevaluation_grades', $usergrade);
    } else {
      $graderecord = new stdClass();
      $graderecord->groupevaluationid = $groupevaluation->id;
      $graderecord->userid = $userid;
      $graderecord->grade = $grade;
      $graderecord->timemodified = time();
      $DB->insert_record('groupevaluation_grades', $graderecord);
    }
    // CALL API FUNCTION //
    groupevaluation_update_grades($groupevaluation, $userid, true);
  }
}

// GET PARTICIPANTS //
$where = 'groupevaluationid = '.$groupevaluation->id;
if ($viewmode == 'crossevaluations') {
  $where .= ' AND groupid = '.$groupid;
}
$select = 'SELECT DISTINCT userid FROM mdl_groupevaluation_surveys WHERE '.$where;
$query = 'SELECT * FROM mdl_user WHERE id IN ('.$select.') ORDER BY '.$orderby;
$participants = $DB->get_records_sql($query);
$countparticipants = count($participants);

// GET GRADES //
$grading_info = grade_get_grades($groupevaluation->course, 'mod', 'groupevaluation', $groupevaluation->id, array_keys($participants));
$grades = $grading_info->items[0]->grades;

// GET GROUPS //
$select = 'SELECT DISTINCT groupid FROM mdl_groupevaluation_surveys WHERE groupevaluationid = '.$groupevaluation->id;
$query = 'SELECT * FROM mdl_groups WHERE id IN ('.$select.') ORDER BY name';
$surveygroups = $DB->get_records_sql($query);

// Get the responses of given user.
// Print the page header.
$PAGE->navbar->add(get_string('report', 'groupevaluation'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_title(format_string($groupevaluation->name));

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($groupevaluation->name));

require('tabs.php');

$usedgroupid = false;
$sort = '';
$startpage = false;
$pagecount = false;
$straverageweighted = get_string('averageweighted', 'groupevaluation');

// Print the main part of the page.
// Print the users with no responses

// Preparing the table for output.
$baseurl = new moodle_url('/mod/groupevaluation/report.php');
$baseurl->params(array('id' => $cm->id));

// TABLE COLUMNS //
if ($viewmode == 'participants') {

  $tablecolumns = array('userpic', 'fullname');
  $tableheaders = array(get_string('userpic'), get_string('fullnameuser'));

  $tablecolumns[] = 'average';
  $tableheaders[] = get_string('assessmentofgroupmates', 'groupevaluation').'<br/><span style="font-size: smaller;">('.$straverageweighted.')</span>';

  $tablecolumns[] = 'selfevaluation';
  $tableheaders[] = get_string('selfevaluation', 'groupevaluation').'<br/><span style="font-size: smaller;">('.$straverageweighted.')</span>';

  $tablecolumns[] = 'deviation';
  $tableheaders[] = get_string('deviation', 'groupevaluation').'<br/><span style="font-size: smaller;">('.$straverageweighted.')</span>';

  $tablecolumns[] = 'grade';
  $tableheaders[] = get_string('grade', 'groupevaluation');

  $tablecolumns[] = 'evaluatedby';
  $tableheaders[] = get_string('evaluatedby', 'groupevaluation');

} elseif ($viewmode == 'crossevaluations') {
  $showall = true;
  $tablecolumns[] = 'participant';
  $tableheaders[] = get_string('reporttableheading', 'groupevaluation');;

  $userscolumns = 0;
  foreach ($participants as $parcitipant) {
    // Username and link to the profilepage.
    $profileurl = $CFG->wwwroot.'/user/view.php?id='.$parcitipant->id.'&amp;course='.$course->id;
    $profilelink = '<strong><a href="'.$profileurl.'">'.fullname($parcitipant).'</a></strong>';
    $userscolumns++;
    $tablecolumns[] = 'user_'.$userscolumns;
    $tableheaders[] = $profilelink;
  }
}

$tablecolumns[] = 'view';
$tableheaders[] = '';

$tableid = 'groupevaluation-groups-'.$course->id;
$table = new flexible_table($tableid);

$table->define_columns($tablecolumns);
$table->define_headers($tableheaders);
$table->define_baseurl($baseurl);

$table->sortable(true, 'lastname', SORT_DESC);
$table->set_attribute('cellspacing', '0');
$table->set_attribute('id', 'showentrytable');
$table->set_attribute('class', 'flexible generaltable generalbox');
$table->set_control_variables(array(
            TABLE_VAR_SORT    => 'ssort',
            TABLE_VAR_IFIRST  => 'sifirst',
            TABLE_VAR_ILAST   => 'silast',
            TABLE_VAR_PAGE    => 'spage'
            ));

// No sorting
$table->no_sorting('view');
if ($viewmode == 'participants') {
  $table->no_sorting('evaluatedby');
} elseif ($viewmode == 'crossevaluations') {
  $table->no_sorting('participant');
  for ($i=1; $i <= $userscolumns; $i++) {
    $table->no_sorting('user_'.$i);
  }
}

$table->setup();

if ($table->get_sql_sort()) {
    $sort = $table->get_sql_sort();
} else {
    $sort = '';
}

$table->initialbars(false);

if ($showall) {
    $startpage = false;
    $pagecount = false;
} else {
    $table->pagesize($perpage, $countparticipants);
    $startpage = $table->get_page_start();
    $pagecount = $table->get_page_size();
}

// Print the list of groups.
echo '<div class="clearer"></div>';
echo $OUTPUT->box_start('left-align');

echo '<form class="mform" action="report.php" method="post" id="groupevaluation_reportform">';

echo $OUTPUT->container_start('view_options', 'view_options');
$options = array( 'participants' => get_string('participants','groupevaluation'),
                  'crossevaluations' => get_string('crossevaluations','groupevaluation'));
echo html_writer::select($options, 'viewmode', $viewmode, false, array('id' => 'viewmode', 'onchange' => 'viewSubmit("groupevaluation_reportform", false)'));
echo $OUTPUT->container_end();

if ($viewmode == 'crossevaluations') {
  echo $OUTPUT->container_start('select_groups', 'select_groups');

  $options = array(0 => '--- '.get_string('group','groupevaluation').' ---');
  foreach ($surveygroups as $group) {
    $options[$group->id] = $group->name;
  }
  echo html_writer::select($options, 'groupid', $groupid, false, array('id' => 'groupid', 'onchange' => 'viewSubmit("groupevaluation_reportform", false)'));
  echo $OUTPUT->container_end();
}

$colorscale = array ();

if (!$participants) {
  if (($viewmode == 'crossevaluations') && (!$groupid)) {
    echo '<div class="notifyproblem">'.get_string('selectgroup', 'groupevaluation').'</div>';
  } else {
    echo $OUTPUT->notification(get_string('noexistingparticipants', 'enrol'));
  }
} else {
    echo print_string('groupevaluationparticipants', 'groupevaluation').' ('.$countparticipants.')';

    // For paging I use array_slice().
    if ($startpage !== false AND $pagecount !== false) {
        $participants = array_slice($participants, $startpage, $pagecount);
    }

    $countrow = 0;
    foreach ($participants as $user) {
      $userid = $user->id;
      $countcolumn = 0;
      // Userpicture and link to the profilepage.
      $profileurl = $CFG->wwwroot.'/user/view.php?id='.$userid.'&amp;course='.$course->id;
      $profilelink = '<strong><a href="'.$profileurl.'">'.fullname($user).'</a></strong>';
      if ($viewmode == 'participants') {
        $data = array ($OUTPUT->user_picture($user, array('courseid' => $course->id)), $profilelink);
        $countcolumn++;
      } elseif ($viewmode == 'crossevaluations') {
        $data = array ($profilelink);
      }
      $countcolumn++;

      $sumaverage = 0;
      $countaverage = 0;
      $selfevaluation = '-';
      foreach ($participants as $author) {
        $where = 'userid = '.$userid.' AND authorid = '.$author->id.' AND groupevaluationid = '.$groupevaluation->id;
        $query = 'SELECT * FROM mdl_groupevaluation_assessments WHERE surveyid IN ('.
                  'SELECT DISTINCT id FROM mdl_groupevaluation_surveys WHERE '.$where.')';
        $answers = $DB->get_records_sql($query);

        // Calculate the weight taking into account the criterions answered, maintaining the proportions.
        // Normalize weight
        $baseweigh = 0;
        $query2 = 'SELECT * FROM mdl_groupevaluation_criterions WHERE id IN ('.
                  'SELECT DISTINCT criterionid FROM mdl_groupevaluation_assessments WHERE surveyid IN ('.
                  'SELECT DISTINCT id FROM mdl_groupevaluation_surveys WHERE '.$where.'))';
        $criterionsweights = $DB->get_records_sql($query2);
        foreach ($criterionsweights as $criterionsweight) {
          $baseweigh = $baseweigh + $criterionsweight->weight;
        }

        $weightedsumauthor = 0;
        foreach ($answers as $answer) { // answer per criterion
          // Normalize weight
          $criterionweight = $DB->get_record('groupevaluation_criterions', array('id' => $answer->criterionid));
          if (!$baseweigh) {
            $weightedsumauthor = '-';
          } else {
            $normalizedweight = ($criterionweight->weight) / $baseweigh;
            // Weighted sum
            $weightedsumauthor = $weightedsumauthor + (($answer->assessment) * $normalizedweight);
          }
        }
        if (!$answers) {
          $weightedsumauthor = '-';
        }

        if ($viewmode == 'crossevaluations') {
          // Color escale array.
          $cell = new stdClass();
          $cell->id = $tableid.'_r'.$countrow.'_c'.$countcolumn;
          $cell->value = $weightedsumauthor;
          $colorscale[] = $cell;

          if ($weightedsumauthor != '-') {
            $data[] = round($weightedsumauthor, 2).'%';
          } else {
            $data[] = $weightedsumauthor;
          }
          $countcolumn++;
        }

        if ($userid != $author->id) {
          if ($weightedsumauthor != '-') {
            $sumaverage = $sumaverage + $weightedsumauthor;
            $countaverage++;
          }
        } else {
          $selfevaluation = $weightedsumauthor;
        }
      }

      if ($viewmode == 'participants') {
        if ($countaverage > 0) {
          $average = round(($sumaverage / $countaverage), 2).'%';
        } else {
          $average = '-';
        }

        if ($selfevaluation != '-') {
          $selfevaluation = round($selfevaluation, 2).'%';
        }

        if ($countaverage > 0 && ($selfevaluation != '-')) {
          $deviation = round(($average - $selfevaluation), 2).'%';
        } else {
          $deviation = '-';
        }

        // GET GRADE //
        $grade = '-';
        if ($grades[$userid]->grade) {
          $grade = round($grades[$userid]->grade, 2).'%';
        }

        // Average of groupmates //
        $data[] = $average;
        // Average selfevaluation //
        $data[] = $selfevaluation;
        // Average deviation //
        $data[] = $deviation;

        // Grade //
        $streditgrade = get_string('edit');
        $strprompt = get_string('enternewgrade', 'groupevaluation');
        $attributes = 'value="'.$userid.'" name="editgradebutton['.$userid.']"  onclick=\'setNewGrade('.$userid.', "'.$strprompt.'");\'';
        $extra = 'title="'.$streditgrade.'" src="'.$OUTPUT->pix_url('t/editstring').'" type="image"';
        $imgeditgrade = '<input id="id_editgradebutton_'.$userid.'" '.$attributes.' '.$extra.'/>';

        $data[] = '<strong>'.$grade.'</strong> '.$imgeditgrade;
        $countcolumn = $countcolumn + 4;

        // evaluatedby
        $evaluatedby = '';
        $separator = '';
        $groupmembers = array();

        // In case a user belongs to several groups
        $query = 'SELECT * FROM mdl_groups WHERE id IN (SELECT DISTINCT groupid FROM mdl_groups_members WHERE userid=?) '.
                'AND id IN (SELECT DISTINCT groupid FROM mdl_groupevaluation_surveys WHERE groupevaluationid=?)';
        $groups = $DB->get_records_sql($query, array($userid, $groupevaluation->id));

        foreach ($groups as $group) {
          if (count($groups)>1) {
            $evaluatedby = $evaluatedby.$separator.$group->name.': ';
            $separator = '';
          }

          $groupmembers = $DB->get_records("groups_members", array('groupid' => $group->id));
          foreach ($groupmembers as $groupmember) {
            $user = $DB->get_record('user', array('id' => $groupmember->userid));

            $conditions = 'userid = '.$userid.' AND groupevaluationid = '.$groupevaluation->id.' AND authorid = '.$user->id.' AND groupid = '.$group->id;
            $survey = $DB->get_record_select('groupevaluation_surveys', $conditions);
            $style = 'style="color: red;"';
            if ($survey) {
              if ($survey->status == groupevaluation_DONE) {
                $style = '';
              }
            }
            $profileurl = $CFG->wwwroot.'/user/view.php?id='.$user->id.'&amp;course='.$course->id;
            $profilelink = '<a href="'.$profileurl.'" '.$style.'>'.fullname($user).'</a>';

            $evaluatedby = $evaluatedby.$separator.$profilelink;
            $separator = ', ';
          }
          $separator = '<br/>';
        }
        $data[] = $evaluatedby;
        $countcolumn++;
      }

      // Column view //
      $strview = get_string('view', 'groupevaluation');
      $href = $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/results.php?id='.$cm->id.'&userid='.$user->id.'&allfields=1');
      $data[] = '<a href="'.$href.'" class="btn btn-default btn-lg"'.'role="button" title="'.$strview.'">'.$strview.'</a>';
      $countcolumn++;
      $table->add_data($data);
      $countrow++;
    }

    $table->print_html();
    $allurl = new moodle_url($baseurl);

    if ($showall && ($viewmode == 'participants')) {
        $allurl->param('showall', 0);
        echo $OUTPUT->container(html_writer::link($allurl, get_string('showperpage', '', groupevaluation_DEFAULT_PAGE_COUNT)),
                                    array(), 'showall');

    } else if ($countparticipants > 0 && $perpage < $countparticipants) {
        $allurl->param('showall', 1);
        echo $OUTPUT->container(html_writer::link($allurl,
                        get_string('showall', '', $countparticipants)), array(), 'showall');
    }
}
echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
echo '<input type="hidden" name="id" value="'.$cm->id.'" />';
echo '<input type="hidden" name="orderby" value="'.$orderby.'" />';

echo '</form>';

// Include the needed js.
$module = array('name' => 'mod_groupevaluation', 'fullpath' => '/mod/groupevaluation/module.js');
$PAGE->requires->js_init_call('M.mod_groupevaluation.init_check', null, false, $module);
//$PAGE->requires->js(new moodle_url($CFG->wwwroot . '/mod/groupevaluation/module.js'));

echo $OUTPUT->box_end();

echo html_writer::start_tag('style');
foreach ($colorscale as $cell) {
  $code = groupevaluation_color_code($cell->value);
  echo '#'.$cell->id.' { background:'.$code.'; }';
}
echo html_writer::end_tag('style');

// Finish the page.
//echo '<script type="text/javascript" src="'.$CFG->wwwroot.'/mod/groupevaluation/module.js" type="text/javascript"></script>';

echo $OUTPUT->footer();

// Log this groupevaluation show non-respondents action.
/*$context = context_module::instance($groupevaluation->cm->id);
$anonymous = $groupevaluation->respondenttype == 'anonymous';

$event = \mod_groupevaluation\event\non_respondents_viewed::create(array(
                'objectid' => $groupevaluation->id,
                'anonymous' => $anonymous,
                'context' => $context
));
$event->trigger();*/
