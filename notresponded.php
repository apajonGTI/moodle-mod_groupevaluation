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
$id = required_param('id', PARAM_INT);
$subject = optional_param('subject', '', PARAM_CLEANHTML);
$message = optional_param('message', '', PARAM_CLEANHTML);
$format = optional_param('format', FORMAT_MOODLE, PARAM_INT);
$messageuser = optional_param_array('messageuser', false, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$perpage = optional_param('perpage', groupevaluation_DEFAULT_PAGE_COUNT, PARAM_INT);  // How many per page.
$showall = optional_param('showall', false, PARAM_INT);  // Should we show all users?
$sid    = optional_param('sid', 0, PARAM_INT);
$qid    = optional_param('qid', 0, PARAM_INT);
$currentgroupid = optional_param('group', 0, PARAM_INT); // Groupid.

if (!isset($SESSION->groupevaluation)) {
    $SESSION->groupevaluation = new stdClass();
}

$SESSION->groupevaluation->current_tab = 'notresponded';

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

$url = new moodle_url('/mod/groupevaluation/notresponded.php', array('id' => $cm->id));

$PAGE->set_url($url);

if (!$context = context_module::instance($cm->id)) {
        print_error('badcontext');
}

// We need the coursecontext to allow sending of mass mails.
if (!$coursecontext = context_course::instance($course->id)) {
        print_error('badcontext');
}

require_login($course, true, $cm);

if (($formdata = data_submitted()) AND !confirm_sesskey()) {
    print_error('invalidsesskey');
}

require_capability('mod/groupevaluation:readresponses', $context);


$nonrespondents = groupevaluation_get_users_notresponded($groupevaluation->id);
$countnonrespondents = count($nonrespondents);

if ($action == 'sendmessage' && !empty($subject) && !empty($message)) {
    $shortname = format_string($course->shortname,
                            true,
                            array('context' => context_course::instance($course->id)));
    $strgroupevaluations = get_string("modulenameplural", "groupevaluation");

    $htmlmessage = "<body id=\"email\">";

    $link1 = $CFG->wwwroot.'/mod/groupevaluation/view.php?id='.$cm->id;

    $htmlmessage .= '<div class="navbar">'.
    '<a target="_blank" href="'.$link1.'">'.format_string($groupevaluation->name, true).'</a>'.
    '</div>';

    $htmlmessage .= $message;
    $htmlmessage .= '</body>';

    $good = 1;

    if (is_array($messageuser)) {
        foreach ($messageuser as $userid) {
            $senduser = $DB->get_record('user', array('id' => $userid));
            $eventdata = new stdClass();
            $eventdata->name             = 'message';
            $eventdata->component        = 'mod_groupevaluation';
            $eventdata->userfrom         = $USER;
            $eventdata->userto           = $senduser;
            $eventdata->subject          = $subject;
            $eventdata->fullmessage      = html_to_text($htmlmessage);
            $eventdata->fullmessageformat = FORMAT_PLAIN;
            $eventdata->fullmessagehtml  = $htmlmessage;
            $eventdata->smallmessage     = '';
            $good = $good && message_send($eventdata);
        }
        if (!empty($good)) {
            $msg = $OUTPUT->heading(get_string('messagedselectedusers'));
        } else {
            $msg = $OUTPUT->heading(get_string('messagedselectedusersfailed'));
        }

        $url = new moodle_url('/mod/groupevaluation/view.php', array('id' => $cm->id));
        redirect($url, $msg, 4);
        exit;
    }
}

// Get the responses of given user.
// Print the page header.
$PAGE->navbar->add(get_string('notresponded', 'groupevaluation'));
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
$baseurl = new moodle_url('/mod/groupevaluation/notresponded.php');
$baseurl->params(array('id' => $cm->id, 'showall' => $showall));

$tablecolumns = array('userpic', 'fullname');

// Extra columns copied from participants view.
$extrafields = get_extra_user_fields($context);
$tableheaders = array(get_string('userpic'), get_string('fullnameuser'));

if (in_array('email', $extrafields) || has_capability('moodle/course:viewhiddenuserfields', $context)) {
    $tablecolumns[] = 'email';
    $tableheaders[] = get_string('email');
}

if (!isset($hiddenfields['city'])) {
    $tablecolumns[] = 'city';
    $tableheaders[] = get_string('city');
}
if (!isset($hiddenfields['country'])) {
    $tablecolumns[] = 'country';
    $tableheaders[] = get_string('country');
}
if (!isset($hiddenfields['lastaccess'])) {
    $tablecolumns[] = 'lastaccess';
    $tableheaders[] = get_string('lastaccess');
}

//esta es para ver cuentas surveys le quedan pendientes
$tablecolumns[] = 'status';
$tableheaders[] = get_string('status');

if (has_capability('mod/groupevaluation:message', $context)) {
    $tablecolumns[] = 'select';
    $tableheaders[] = get_string('select');
}

$table = new flexible_table('groupevaluation-shownonrespondents-'.$course->id);

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
    $table->pagesize($perpage, $countnonrespondents);
    $startpage = $table->get_page_start();
    $pagecount = $table->get_page_size();
}

// For paging I use array_slice().
if ($startpage !== false AND $pagecount !== false) {
    $nonrespondents = array_slice($nonrespondents, $startpage, $pagecount);
}

// Viewreports-start.
// Print the list of students.
echo '<div class="clearer"></div>';
echo $OUTPUT->box_start('left-align');

$countries = get_string_manager()->get_list_of_countries();

$strnever = get_string('never');

$datestring = new stdClass();
$datestring->year  = get_string('year');
$datestring->years = get_string('years');
$datestring->day   = get_string('day');
$datestring->days  = get_string('days');
$datestring->hour  = get_string('hour');
$datestring->hours = get_string('hours');
$datestring->min   = get_string('min');
$datestring->mins  = get_string('mins');
$datestring->sec   = get_string('sec');
$datestring->secs  = get_string('secs');

if (!$nonrespondents) {
    echo '<div class="notifyproblem">'.get_string('allhavefulfilled', 'groupevaluation').'</div>';
} else {
    echo print_string('non_respondents', 'groupevaluation');
    echo ' ('.$countnonrespondents.')';
    echo '<form class="mform" action="notresponded.php" method="post" id="groupevaluation_sendmessageform">';

    foreach ($nonrespondents as $user) {
        // Userpicture and link to the profilepage.
        $profileurl = $CFG->wwwroot.'/user/view.php?id='.$user->id.'&amp;course='.$course->id;
        $profilelink = '<strong><a href="'.$profileurl.'">'.fullname($user).'</a></strong>';
        $data = array ($OUTPUT->user_picture($user, array('courseid' => $course->id)), $profilelink);
        if (in_array('email', $tablecolumns)) {
            $data[] = $user->email;
        }
        if (!isset($hiddenfields['city'])) {
            $data[] = $user->city;
        }
        if (!isset($hiddenfields['country'])) {
            $data[] = (!empty($user->country)) ? $countries[$user->country] : '';
        }
        if ($user->lastaccess) {
            $lastaccess = format_time(time() - $user->lastaccess, $datestring);
        } else {
            $lastaccess = get_string('never');
        }
        $data[] = $lastaccess;

        //este es para ver cuantas surveys le quedan TODO (cambiar nombre a la columna tambien (status))
        $data[] = get_string('not_started', 'groupevaluation');
        $checkboxaltvalue = 0;

        if (has_capability('mod/groupevaluation:message', $context)) {
            // If groupevaluation is set to "resume", look for saved (not completed) responses
            // we use the alt attribute of the checkboxes to store the started/not started value!
            $checkboxaltvalue = '';
            $data[] = '<input type="checkbox" class="usercheckbox" name="messageuser[]" value="'.
                $user->id.'" alt="'.$checkboxaltvalue.'" />';
        }
        $table->add_data($data);

    }

    $table->print_html();
    $allurl = new moodle_url($baseurl);
    if ($showall) {
        $allurl->param('showall', 0);
        echo $OUTPUT->container(html_writer::link($allurl, get_string('showperpage', '', groupevaluation_DEFAULT_PAGE_COUNT)),
                                    array(), 'showall');

    } else if ($countnonrespondents > 0 && $perpage < $countnonrespondents) {
        $allurl->param('showall', 1);
        echo $OUTPUT->container(html_writer::link($allurl,
                        get_string('showall', '', $countnonrespondents)), array(), 'showall');
    }
    if (has_capability('mod/groupevaluation:message', $context)) {
        echo $OUTPUT->box_start('mdl-align'); // Selection buttons container.
        echo '<div class="buttons">';
        echo '<input type="button" id="checkall" value="'.get_string('selectall').'" /> ';
        echo '<input type="button" id="checknone" value="'.get_string('deselectall').'" /> ';

        if ($perpage >= $countnonrespondents) {
            echo '<input type="button" id="checkstarted" value="'.get_string('checkstarted', 'groupevaluation').'" />'."\n";
            echo '<input type="button" id="checknotstarted" value="'.
                get_string('checknotstarted', 'groupevaluation').'" />'."\n";
        }

        echo '</div>';
        echo $OUTPUT->box_end();
        if ($action == 'sendmessage' && !is_array($messageuser)) {
            echo $OUTPUT->notification(get_string('nousersselected', 'groupevaluation'));
        }

        // Message editor.
        // Prepare data.
        echo '<fieldset class="clearfix">';
        if ($action == 'sendmessage' && (empty($subject) || empty($message))) {
            echo $OUTPUT->notification(get_string('allfieldsrequired'));
        }
        echo '<legend class="ftoggler">'.get_string('send_message', 'groupevaluation').'</legend>';
        $id = 'message' . '_id';
        $subjecteditor = '&nbsp;&nbsp;&nbsp;<input type="text" id="groupevaluation_subject" size="65"
            maxlength="255" name="subject" value="'.$subject.'" />';
        $format = '';
            $editor = editors_get_preferred_editor();
            $editor->use_editor($id, groupevaluation_get_editor_options($context));
            $texteditor = html_writer::tag('div', html_writer::tag('textarea', $message,
                    array('id' => $id, 'name' => "message", 'rows' => '10', 'cols' => '60')));
            echo '<input type="hidden" name="format" value="'.FORMAT_HTML.'" />';


        // Print editor.
        $table = new html_table();
        $table->align = array('left', 'left');
        $table->data[] = array( '<strong>'.get_string('subject', 'groupevaluation').'</strong>', $subjecteditor);
        $table->data[] = array('<strong>'.get_string('messagebody').'</strong>', $texteditor);

        echo html_writer::table($table);

        // Send button.
        echo $OUTPUT->box_start('mdl-left');
        echo '<div class="buttons">';
        echo '<input type="submit" name="send_message" value="'.get_string('send', 'groupevaluation').'" />';
        echo '</div>';
        echo $OUTPUT->box_end();

        echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
        echo '<input type="hidden" name="action" value="sendmessage" />';
        echo '<input type="hidden" name="id" value="'.$cm->id.'" />';

        echo '</fieldset>';
        echo '</form>';

        // Include the needed js.
        $module = array('name' => 'mod_groupevaluation', 'fullpath' => '/mod/groupevaluation/module.js');
        $PAGE->requires->js_init_call('M.mod_groupevaluation.init_sendmessage', null, false, $module);
    }
}
echo $OUTPUT->box_end();

// Finish the page.

echo $OUTPUT->footer();

/*/ Log this groupevaluation show non-respondents action.
$context = context_module::instance($groupevaluation->cm->id);
$event = \mod_groupevaluation\event\non_respondents_viewed::create(array(
                'objectid' => $groupevaluation->id,
                'context' => $context
));
$event->trigger();*/
