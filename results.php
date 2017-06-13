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

// Get the params.
$id             = required_param('id', PARAM_INT);                   //
$perpage        = optional_param('perpage', groupevaluation_DEFAULT_PAGE_COUNT, PARAM_INT);  // How many per page.
$userid         = optional_param('userid', 0, PARAM_INT);            //
$groupid        = optional_param('groupid', 0, PARAM_INT);           // TODO Borrar (tambien en report.php)
$orderby        = optional_param('orderby', 'firstname', PARAM_ALPHA); //
$viewmode       = optional_param('viewmode', 'all', PARAM_ALPHA); //
$allfields      = optional_param('allfields', '0', PARAM_INT);       //
$groupmates      = optional_param('groupmates', false, PARAM_INT);     //
$viewaverage        = optional_param('viewaverage', false, PARAM_INT);        // average column visible
$viewselfevaluation = optional_param('viewselfevaluation', false, PARAM_INT); // selfevaluation column visible
$viewdeviation      = optional_param('viewdeviation', false, PARAM_INT);      // deviation column visible
$viewmaximum        = optional_param('viewmaximum', false, PARAM_INT);        // maximum column visible
$viewminimum        = optional_param('viewminimum', false, PARAM_INT);        // minimum column visible

$viewweight        = optional_param('viewweight', false, PARAM_INT);        // minimum column visible


if (!isset($SESSION->groupevaluation)) {
    $SESSION->groupevaluation = new stdClass();
}

$SESSION->groupevaluation->current_tab = 'results';

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

$url = new moodle_url('/mod/groupevaluation/results.php', array('id' => $cm->id, 'userid' => $userid, 'allfields' => 1));
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

// Strings
$strgroupmates = get_string('groupmates', 'groupevaluation');
$straverage = get_string('average', 'groupevaluation');
$strselfevaluation = get_string('selfevaluation', 'groupevaluation');
$strdeviation = get_string('deviation', 'groupevaluation');
$strdeviationtitle = get_string('deviationtitle', 'groupevaluation');
$strmaximum = get_string('maximum', 'groupevaluation');
$strminimum = get_string('minimum', 'groupevaluation');
$strweight = get_string('weight', 'groupevaluation');

// USERID //
if (has_capability('mod/groupevaluation:readresponses', $context)) {
  if (!$userid) {
    print_error('missingparam', null, null, 'userid');
  }
} else {
  $userid = $USER->id;
}
$user = $DB->get_record('user',array('id' => $userid));

// Print the page header.
$reporturl = new moodle_url('/mod/groupevaluation/report.php');
$reporturl->params(array('id' => $cm->id));
$reporturl->params(array('userid' => $userid));
if (has_capability('mod/groupevaluation:readresponses', $context)) {
  $PAGE->navbar->add(get_string('report', 'groupevaluation'), $reporturl);
}
$PAGE->navbar->add(get_string('results', 'groupevaluation'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_title(format_string($groupevaluation->name));

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($groupevaluation->name));
require('tabs.php');

$groups = array();
$groupmembers = array();
$countgroupmates = 0;

// GROUPS //
$wheregroupid = '';
if ($groupid != 0) { // TODO Probablemente quite el param groupid
  $wheregroupid = 'AND id = '.$groupid;
}
// In case a user belongs to several groups
$query = 'SELECT * FROM mdl_groups WHERE id IN (SELECT DISTINCT groupid FROM mdl_groups_members WHERE userid=?) '.
        'AND id IN (SELECT DISTINCT groupid FROM mdl_groupevaluation_surveys WHERE groupevaluationid=?) '.$wheregroupid;
$groups = $DB->get_records_sql($query, array($userid, $groupevaluation->id));

// GET CRITERIONS //
$criterions = $DB->get_records('groupevaluation_criterions', array('groupevaluationid' => $groupevaluation->id), 'position');
$countcriterions = count($criterions);

// VIEW OPTIONS //
if (has_capability('mod/groupevaluation:readresponses', $context) && ($viewmode != 'student')) {
  $viewgrade = true;

  if ($allfields && ($viewmode == 'all')) {
    $groupmates = true;
    $viewaverage = true;
    $viewselfevaluation = true;
    $viewdeviation = true;
    $viewmaximum = true;
    $viewminimum = true;
    $viewweight = true;
    $viewgrade = false;
  }
  if ($viewmode == 'groupmates') {
    $groupmates = true;
    $viewaverage = false;
    $viewselfevaluation = false;
    $viewdeviation = false;
    $viewmaximum = false;
    $viewminimum = false;
    $viewweight = false;
  }
} else { //Unprivileged user

  $groupmates = false;
  $viewaverage = $groupevaluation->viewaverage;
  $viewselfevaluation = $groupevaluation->viewselfevaluation;
  $viewdeviation = $groupevaluation->viewdeviation;
  $viewmaximum = $groupevaluation->viewmaximum;
  $viewminimum = $groupevaluation->viewminimum;
  $viewweight = $groupevaluation->viewweight;
  $viewgrade = $groupevaluation->viewgrade;
}

$profileurl = $CFG->wwwroot.'/user/view.php?id='.$userid.'&amp;course='.$course->id;
$profilelink = '<a href="'.$profileurl.'">'.fullname($user).'</a>';
echo $OUTPUT->user_picture($user, array('courseid' => $course->id));
echo $OUTPUT->heading(get_string('evaluationof', 'groupevaluation').' '.$profilelink, 4);

$colorscale = array ();

// Print the list of groups.
echo '<div class="clearer"></div>';
echo $OUTPUT->box_start('left-align');

$isclosed = groupevaluation_is_closed($groupevaluation->timeclose);

if (!$isclosed && !has_capability('mod/groupevaluation:readresponses', $context)) {
  echo '<div class="notifyproblem">'.get_string('notclosed', 'groupevaluation').'</div>';
} else if (!$criterions) {
  echo $OUTPUT->notification(get_string('noexistingcriterions', 'groupevaluation'));
} else {
  echo '<form class="mform" action="results.php" method="post" id="groupevaluation_resultsform">';

  if (has_capability('mod/groupevaluation:readresponses', $context)) {
    $href = $CFG->wwwroot.htmlspecialchars('/course/modedit.php?update='.$cm->id.'&return=1#id_timing');

    if ($groupevaluation->timeclose > 0) {
      $dateclose = userdate($groupevaluation->timeclose);
      $edithtml = '<a href="'.$href.'">'.get_string('edit').'</a>';
    } else {
      $edithtml = '';
      $dateclose = '<a href="'.$href.'">'.get_string('nosetted', 'groupevaluation').'</a>';
    }

    if ($isclosed) {
      echo $OUTPUT->container(html_writer::span(get_string('visibleresults', 'groupevaluation', $dateclose).' '.$edithtml));
    } else {
      echo '<div class="notifyproblem">*'.get_string('notseeuntilclosed', 'groupevaluation').' ('.$dateclose.') '.$edithtml.'</div>';
    }
    echo '<div style="height: 20px"></div>';

    if ($viewmode == 'all') {
      echo $OUTPUT->container_start('viewfields', 'viewfields');
      $cbattributes = array('onchange' => 'viewSubmit("groupevaluation_resultsform", false)');
      echo $OUTPUT->container(html_writer::checkbox('viewminimum', '1', $viewweight, $strweight,$cbattributes), 'viewoptions');
      echo $OUTPUT->container(html_writer::checkbox('groupmates', '1', $groupmates, $strgroupmates,$cbattributes), 'viewoptions');
      echo $OUTPUT->container(html_writer::checkbox('viewaverage', '1', $viewaverage, $straverage,$cbattributes), 'viewoptions');
      echo $OUTPUT->container(html_writer::checkbox('viewselfevaluation', '1', $viewselfevaluation, $strselfevaluation,$cbattributes), 'viewoptions');
      echo $OUTPUT->container(html_writer::checkbox('viewdeviation', '1', $viewdeviation, $strdeviation,$cbattributes), 'viewoptions');
      echo $OUTPUT->container(html_writer::checkbox('viewmaximum', '1', $viewmaximum, $strmaximum,$cbattributes), 'viewoptions');
      echo $OUTPUT->container(html_writer::checkbox('viewminimum', '1', $viewminimum, $strminimum,$cbattributes), 'viewoptions last');
      /*$attributes = array('type'=>'submit', 'name'=>'reloadbutton', 'value'=>get_string('reload','groupevaluation'));
      echo $OUTPUT->container(html_writer::tag('input', '', $attributes));*/
      echo $OUTPUT->container_end();
    }
    echo $OUTPUT->container_start('view_options', 'view_options');
    $options = array( 'all' => get_string('all','groupevaluation'),
                      'groupmates' => get_string('groupmates','groupevaluation'),
                      'student' => get_string('studentview','groupevaluation'));
    echo html_writer::select($options, 'viewmode', $viewmode, false, array('id' => 'viewmode', 'onchange' => 'viewSubmit("groupevaluation_resultsform", true)'));
    echo $OUTPUT->container_end();
  }

  foreach ($groups as $group) {
    $groupid = $group->id;

    // GET GROUP MEMBERS //
    $query = 'SELECT * FROM mdl_user WHERE id IN (SELECT DISTINCT userid FROM mdl_groups_members WHERE groupid=?)'.
              'ORDER BY '.$orderby;
    $groupmembers = $DB->get_records_sql($query, array($groupid));

    echo $OUTPUT->heading(get_string('group', 'groupevaluation').': '.$group->name, 5);

    // GRADE //
    if ($viewgrade) {
      $conditions = array('userid' => $userid, 'groupevaluationid' => $groupevaluation->id);
      $usergrade = '- %';
      if ($grade = $DB->get_record('groupevaluation_grades', $conditions)) {
        $usergrade = round($grade->grade, 2).'%';
      }
      echo '<div style="font-weight: bold;">'.get_string('grade', 'groupevaluation').': '.'<span class="grade">'.$usergrade.'</span></div>';
    }

    // --------------------- TABLE --------------------- //
    $sort = '';

    // Preparing the table for output.
    $baseurl = new moodle_url('/mod/groupevaluation/results.php');
    $baseurl->params(array('id' => $cm->id));
    $baseurl->params(array('userid' => $userid));

    $tablecolumns = array ();
    $tableheaders = array ();

    // CRITERION NAME & WEIGHT COLUMNS //
    $tablecolumns[] = 'criterion';
    $tableheaders[] = get_string('criterion', 'groupevaluation');
    if ($viewweight) {
      $tablecolumns[] = 'weight';
      $tableheaders[] = $strweight;
    }

    // groupmateS COLUMNS //
    if (has_capability('mod/groupevaluation:readresponses', $context) && $groupmates) {
      foreach ($groupmembers as $groupmember) {
        $profileurl = $CFG->wwwroot.'/user/view.php?id='.$groupmember->id.'&amp;course='.$course->id;
        $profilelink = '<a href="'.$profileurl.'">'.fullname($groupmember).'</a>';

        if ($userid != $groupmember->id) {
          $countgroupmates++;
          $tablecolumns[] = 'groupmate_'.$countgroupmates;
          $tableheaders[] = $profilelink;
        }
      }
    }

    // COLUMNS //
    if ($viewaverage) {
      $tablecolumns[] = 'average';
      $tableheaders[] = $straverage;
    }
    if ($viewselfevaluation) {
      $tablecolumns[] = 'selfevaluation';
      $tableheaders[] = $strselfevaluation;
    }
    if ($viewdeviation) {
      $tablecolumns[] = 'deviation';
      $tableheaders[] = '<div title="'.$strdeviationtitle.'">'.$strdeviation.'</div>';
    }
    if ($viewmaximum) {
      $tablecolumns[] = 'maximum';
      $tableheaders[] = $strmaximum;
    }
    if ($viewminimum) {
      $tablecolumns[] = 'minimum';
      $tableheaders[] = $strminimum;
    }

    $tableid = 'groupevaluation_results-'.$course->id;
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
    for ($i=1; $i <= $countgroupmates; $i++) {
      $table->no_sorting('groupmate_'.$i);
    }

    $table->setup();

    if ($table->get_sql_sort()) {
        $sort = $table->get_sql_sort();
    } else {
        $sort = '';
    }

    $table->initialbars(false);

    // Normalize weight
    $baseweigh = 0;
    foreach ($criterions as $criterion) {
      $baseweigh = $baseweigh + $criterion->weight;
    }

    $countrow = 0;
    foreach ($criterions as $criterion) {
      $criterionid = $criterion->id;
      $countcolumn = 0;

      // Normalize weight
      if (!$baseweigh) {
        $normalizedweight = 0;
      } else {
        $normalizedweight = ($criterion->weight) / $baseweigh;
      }

      // CRITERION NAME & WEIGHT //
      $data = array ($criterion->name);
      $countcolumn++;
      if ($viewweight) {
        $data[] = round($normalizedweight*100.00, 2).'%';
        $countcolumn++;
      }

      // GROUPMATES COLUMNS //
      $sumaverage = 0;
      $countaverage = 0;
      $countanswers = 0;
      $maximum = 0;
      $minimum = 100;
      foreach ($groupmembers as $groupmember) {
        $where = 'userid = '.$userid.' AND authorid = '.$groupmember->id.' AND groupevaluationid = '.$groupevaluation->id;
        $query = 'SELECT * FROM mdl_groupevaluation_assessments WHERE surveyid IN ('.
                  'SELECT DISTINCT id FROM mdl_groupevaluation_surveys WHERE '.$where.
                  ') AND criterionid = '.$criterionid;
        $answer = $DB->get_record_sql($query);

        if ($answer) {
          $assessment = round($answer->assessment, 2);
          if ($userid != $groupmember->id) {
            $sumaverage = $sumaverage + $assessment;
            $countaverage++;
            if (has_capability('mod/groupevaluation:readresponses', $context) && $groupmates) {
              // Color escale array.
              $cell = new stdClass();
              $cell->id = $tableid.'_r'.$countrow.'_c'.$countcolumn;
              $cell->value = $assessment;
              $colorscale[] = $cell;

              $data[] = $assessment.'%';
              $countcolumn++;
            }
          } else {
            $selfevaluation = $assessment;
          }

          if ($assessment > $maximum) {
            $maximum = $assessment;
          }
          if ($assessment < $minimum) {
            $minimum = $assessment;
          }
          $countanswers++;
        } else {
          if (($userid != $groupmember->id) && has_capability('mod/groupevaluation:readresponses', $context) && $groupmates) {
            $data[] = '-';
            $countcolumn++;
          }
        }
      }

      // COLUMNS //
      if ($viewaverage) {
        if ($countaverage > 0) {
          $data[] = ($sumaverage / $countaverage).'%';
        } else {
          $data[] = '-';
        }
        $countcolumn++;
      }
      if ($viewselfevaluation) {
        if (isset($selfevaluation)) {
          $data[] = $selfevaluation.'%';
        } else {
          $data[] = '-';
        }
        $countcolumn++;
      }
      if ($viewdeviation) {
        if (($countaverage > 0) && isset($selfevaluation)) {
          $deviation = ($sumaverage / $countaverage) - $selfevaluation;
          $data[] = groupevaluation_get_arrow($groupevaluation, $deviation).' '.$deviation.'%';

        } else {
          $data[] = '-';
        }
        $countcolumn++;
      }
      if ($viewmaximum) {
        if ($countanswers > 0) {
          $data[] = $maximum.'%';
        } else {
          $data[] = '-';
        }
        $countcolumn++;
      }
      if ($viewminimum) {
        if ($countanswers > 0) {
          $data[] = $minimum.'%';
        } else {
          $data[] = '-';
        }
        $countcolumn++;
      }

      $table->add_data($data);
      $countrow++;
      //$table->add_separator();
    }

    $table->print_html();
    // --------------------- fin: TABLE --------------------- //

  } // end foreach(groups as group)

  // HIDDEN INPUTS //
  echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
  echo '<input type="hidden" name="id" value="'.$cm->id.'" />';
  echo '<input type="hidden" name="userid" value="'.$userid.'" />';
  echo '<input type="hidden" name="groupid" value="'.$groupid.'" />';
  echo '<input type="hidden" name="orderby" value="'.$orderby.'" />';

  echo '</form>';

} // end else "if(!criterions)"


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
