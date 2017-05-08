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
$addgroup     = optional_param_array('addgroup', false, PARAM_INT);
$removegroup  = optional_param_array('removegroup', false, PARAM_INT);
$action       = optional_param('action', '', PARAM_ALPHA);
$perpage      = optional_param('perpage', groupevaluation_DEFAULT_PAGE_COUNT, PARAM_INT);  // How many per page.

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

$groups = $DB->get_records("groups", array("courseid" => $cm->course));
$countgroups = count($groups);


if ($action == 'update' && (is_array($addgroup) && is_array($removegroup))) {
    $good = true;

    if (is_array($addgroup)) {
        foreach ($addgroup as $groupid) {
            if(!groupevaluation_create_surveys($groupevaluation->id, $groupid)) {
              $good = false;
            }
        }
    }
    if (is_array($removegroup)) {
        foreach ($removegroup as $groupid) {
            if(!groupevaluation_remove_surveys($groupevaluation->id, $groupid)) {
              $good = false;
            }
        }
    }

    if ($good) {
        $msg = $OUTPUT->heading(get_string('updatedgroups'. 'groupevaluation'));
    } else {
        $msg = $OUTPUT->heading(get_string('updatedgroupsfailed', 'groupevaluation'));
    }

    $url = new moodle_url('/mod/groupevaluation/view.php', array('id' => $cm->id));
    redirect($url, $msg, 4);
    exit;
}

// Get the responses of given user.
// Print the page header.
$PAGE->navbar->add(get_string('groups', 'groupevaluation'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_title(format_string($groupevaluation->name));

echo $OUTPUT->header();

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

$tablecolumns[] = 'addgroupcolm';
$tableheaders[] = get_string('addgroup', 'groupevaluation').'  <input type="checkbox" id="addall"/><br/>';

$tablecolumns[] = 'removegroupcolm';
$tableheaders[] = get_string('removegroup', 'groupevaluation').'  <input type="checkbox" id="removeall"/><br/>';

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
$table->no_sorting('addgroupcolm');
$table->no_sorting('removegroupcolm');

$table->setup();

if ($table->get_sql_sort()) {
    $sort = $table->get_sql_sort();
} else {
    $sort = '';
}

$table->initialbars(false);
$table->pagesize($perpage, $countgroups);
$startpage = $table->get_page_start();
$pagecount = $table->get_page_size();


// Print the list of groups.

echo '<div class="clearer"></div>';
echo $OUTPUT->box_start('left-align');

if (!$groups) {
    echo $OUTPUT->notification(get_string('noexistinggroups', 'groupevaluation'));
} else {
    echo print_string('groupscreated', 'groupevaluation');
    echo ' ('.$countgroups.')'; //SEGUIR AQUI
    echo '<form class="mform" action="groups.php" method="post" id="groupevaluation_updateform">';

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

        if ($DB->get_record('groupevaluation_surveys', array('groupevaluationid' => $groupevaluation->id,'groupid' => $group->id))) {
          // Add group column
          $data[] = get_string('added', 'groupevaluation');
          // Remove group column
          $data[] = '<input type="checkbox" class="removecheckbox" name="removegroup[]" value="'.$group->id.'" alt="0"/>';
        } else {
          // Add group column
          $data[] = '<input type="checkbox" class="addcheckbox" name="addgroup[]" value="'.$group->id.'" alt="0"/>';
          // Remove group column
          $data[] = get_string('notadded', 'groupevaluation');
        }

        $table->add_data($data);
    }

    $table->print_html();

    if ($action == 'update' && (!is_array($addgroup) && !is_array($removegroup))) {
      echo $OUTPUT->notification(get_string('nogroupselected', 'groupevaluation'));
    }

    // Update button.
    echo $OUTPUT->box_start('mdl-align');
    echo '<div class="buttons">';
    echo '<input type="submit" name="update" value="'.get_string('update', 'groupevaluation').'" />';
    echo '</div>';
    echo $OUTPUT->box_end();

    echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
    echo '<input type="hidden" name="action" value="update" />';
    echo '<input type="hidden" name="id" value="'.$cm->id.'" />';

    echo '</form>';
}

// Include the needed js.
$module = array('name' => 'mod_groupevaluation', 'fullpath' => '/mod/groupevaluation/module.js');
$PAGE->requires->js_init_call('M.mod_groupevaluation.init_sendmessage', null, false, $module);
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
