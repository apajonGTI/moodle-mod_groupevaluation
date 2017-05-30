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
$id           = required_param('id', PARAM_INT);
$format       = optional_param('format', FORMAT_MOODLE, PARAM_INT);
$addsubmit    = optional_param('addsubmit', false, PARAM_ALPHA);
$removesubmit = optional_param('removesubmit', false, PARAM_ALPHA);
$addgroup     = optional_param_array('addgroup', false, PARAM_INT);
$removegroup  = optional_param_array('removegroup', false, PARAM_INT);
$perpage      = optional_param('perpage', groupevaluation_DEFAULT_PAGE_COUNT, PARAM_INT);  // How many per page.
$showall      = optional_param('showall', false, PARAM_INT);  // Should we show all users?
if (!isset($SESSION->groupevaluation)) {
    $SESSION->groupevaluation = new stdClass();
}

$SESSION->groupevaluation->current_tab = 'groups';

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

$url = new moodle_url('/mod/groupevaluation/groups.php', array('id' => $cm->id));

$PAGE->set_url($url);

if (!$context = context_module::instance($cm->id)) {
        print_error('badcontext');
}

// We need the coursecontext to allow updateing of mass mails.
if (!$coursecontext = context_course::instance($course->id)) {
        print_error('badcontext');
}

require_login($course, true, $cm);

if (($formdata = data_submitted()) AND !confirm_sesskey()) {
    print_error('invalidsesskey');
}

require_capability('mod/groupevaluation:editsurvey', $context);

$good = false;
if (($addsubmit && is_array($addgroup)) || ($removesubmit && is_array($removegroup))) {
    $good = true;

    if ($addsubmit && is_array($addgroup)) {
        foreach ($addgroup as $groupid) {
            if(!groupevaluation_create_surveys($groupevaluation->id, $groupid)) {
              $good = false;
            }
        }
    }
    if ($removesubmit && is_array($removegroup)) {
        foreach ($removegroup as $groupid) {
            if(!groupevaluation_remove_surveys($groupevaluation->id, $groupid)) {
              $good = false;
            }
        }
    }

    // Updated groups message
    if ($good) {
        echo $OUTPUT->container(get_string('updatedgroups', 'groupevaluation'), 'important', 'notice');
    } else {
        echo $OUTPUT->notification(get_string('updatedgroupsfailed', 'groupevaluation'));
    }

    /*if ($good) {
        //$msg = $OUTPUT->heading(get_string('updatedgroups', 'groupevaluation'));
    } else {
        $msg = $OUTPUT->heading(get_string('updatedgroupsfailed', 'groupevaluation'));
    }
    $url = new moodle_url('/mod/groupevaluation/view.php', array('id' => $cm->id));
    redirect($url, $msg, 4);
    exit;*/
}
// GET GROUPS //
$groups = $DB->get_records("groups", array("courseid" => $cm->course));
$countgroups = count($groups);
$query = 'SELECT DISTINCT groupid FROM mdl_groupevaluation_surveys WHERE groupevaluationid = ?';
$groupsadded = $DB->get_records_sql($query, array($groupevaluation->id));
$countgroupsadded = count($groupsadded);

// Get the responses of given user.
// Print the page header.
$PAGE->navbar->add(get_string('groups', 'groupevaluation'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_title(format_string($groupevaluation->name));

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($groupevaluation->name));

require('tabs.php');

$usedgroupid = false;
$sort = '';
$startpage = false;
$pagecount = false;

// Print the main part of the page.
// Print the users with no responses

// Preparing the table for output.
$baseurl = new moodle_url('/mod/groupevaluation/groups.php');
$baseurl->params(array('id' => $cm->id));


$tablecolumns[] = 'groupname';
$tableheaders[] = get_string('groupname', 'groupevaluation');

$tablecolumns[] = 'members';
$tableheaders[] = get_string('members', 'groupevaluation');

$tablecolumns[] = 'status';
$tableheaders[] = get_string('status');

$tablecolumns[] = 'select';
$tableheaders[] = '<input type="checkbox" id="selectall"/>'.get_string('select');

$table = new flexible_table('groupevaluation-groups-'.$course->id);

$table->define_columns($tablecolumns);
$table->define_headers($tableheaders);
$table->define_baseurl($baseurl);

$table->sortable(true, 'groupname', SORT_DESC);
$table->set_attribute('cellspacing', '0');
$table->set_attribute('id', 'showentrytable');
$table->set_attribute('class', 'flexible generaltable generalbox');
$table->set_control_variables(array(
            TABLE_VAR_SORT    => 'ssort',
            TABLE_VAR_IFIRST  => 'sifirst',
            TABLE_VAR_ILAST   => 'silast',
            TABLE_VAR_PAGE    => 'spage'
            ));

$table->no_sorting('members');
$table->no_sorting('status');
$table->no_sorting('select');

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
    $table->pagesize($perpage, $countgroups);
    $startpage = $table->get_page_start();
    $pagecount = $table->get_page_size();
}

// Print the list of groups.


echo '<div class="clearer"></div>';
echo $OUTPUT->box_start('left-align');

if (!$groups) {
    echo $OUTPUT->notification(get_string('noexistinggroups', 'groupevaluation'));
} else {
    echo print_string('groupscreated', 'groupevaluation').' ('.$countgroups.')<br/>';
    echo print_string('groupsadded', 'groupevaluation').' ('.$countgroupsadded.')';
    echo '<form class="mform" action="groups.php" method="post" id="groupevaluation_updateform">';


    // For paging I use array_slice().
    if ($startpage !== false AND $pagecount !== false) {
        $groups = array_slice($groups, $startpage, $pagecount);
    }

    foreach ($groups as $group) {

        $data = array ();
        $data[] = $group->name;

        $coma = '';
        $members = '';
        $groupmembers = array();
        $groupmembers = $DB->get_records("groups_members", array('groupid' => $group->id));

        foreach ($groupmembers as $user) {
          $user = $DB->get_record('user', array('id' => $user->userid));

          $profileurl = $CFG->wwwroot.'/user/view.php?id='.$user->id.'&amp;course='.$course->id;
          $profilelink = '<strong><a href="'.$profileurl.'">'.fullname($user).'</a></strong>';

          $members = $members.$coma.$profilelink;
          $coma = ', ';
        }

        $data[] = $members;

        // If groupevaluation is set to "resume", look for saved (not completed) responses
        // we use the alt attribute of the checkboxes to store the started/not started value!
        $checkboxaltvalue = '';

        if ($DB->get_records('groupevaluation_surveys', array('groupevaluationid' => $groupevaluation->id,'groupid' => $group->id))) {
          // Status column
          $data[] = get_string('added', 'groupevaluation');
          // Remove group column
          $data[] = '<input type="checkbox" class="removecheckbox" name="removegroup[]" value="'.$group->id.'"/>';
        } else {
          // Status column
          $data[] = '<span style="color: grey;">'.get_string('notadded', 'groupevaluation').'</span>';
          // Add group column
          $data[] = '<input type="checkbox" class="addcheckbox" name="addgroup[]" value="'.$group->id.'"/>';
        }

        $table->add_data($data);
    }

    // Select buttons
    echo $OUTPUT->box_start('mdl-align'); // Selection buttons container.
    echo '<div style="float:right; text-align:left; margin-right: 21px;">';
    /*echo '<input type="button" id="checkadded" value="'.get_string('checkadded', 'groupevaluation').'" />';
    echo '<input type="button" id="checknotadded" value="'.get_string('checknotadded', 'groupevaluation').'" />';*/
    echo '<input type="checkbox" id="checkadded"/>'.get_string('checkadded', 'groupevaluation').'<br/>';
    echo '<input type="checkbox" id="checknotadded"/>'.get_string('checknotadded', 'groupevaluation');

    echo '</div>';
    echo $OUTPUT->box_end();

    $table->print_html();

    if (($addsubmit || $removesubmit) && (!is_array($addgroup) && !is_array($removegroup))) {
      echo $OUTPUT->notification(get_string('nogroupselected', 'groupevaluation'));
    }

    // Submit buttons
    echo $OUTPUT->box_start('mdl-align');
    echo '<div class="buttons">';
    echo '<input type="submit" name="addsubmit" value="'.get_string('addgroups', 'groupevaluation').'" />';
    echo '<input type="submit" name="removesubmit" value="'.get_string('removegroups', 'groupevaluation').'" />';
    echo '</div>';
    echo $OUTPUT->box_end();

    echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
    echo '<input type="hidden" name="id" value="'.$cm->id.'" />';

    echo '</form>';
}

// Include the needed js.
$module = array('name' => 'mod_groupevaluation', 'fullpath' => '/mod/groupevaluation/module.js');
$PAGE->requires->js_init_call('M.mod_groupevaluation.init_check', null, false, $module);
//$PAGE->requires->js(new moodle_url($CFG->wwwroot . '/mod/groupevaluation/module.js'));

echo $OUTPUT->box_end();

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
