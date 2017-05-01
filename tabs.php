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
 * prints the tabbed bar
 *
 * @package    mod_groupevaluation
 * @copyright  Jose Vilas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 // TODO mirar en questionnaire/tabs.php porque borre casi todo

global $DB, $SESSION, $course, $USER;
$tabs = array();
$row  = array();
$inactive = array();
$activated = array();
if (!isset($SESSION->groupevaluation)) {
    $SESSION->groupevaluation = new stdClass();
}
$currenttab = $SESSION->groupevaluation->current_tab;

// If this groupevaluation has a survey, get the survey and owner.
// In a groupevaluation instance created "using" a PUBLIC groupevaluation, prevent anyone from editing settings, editing questions,
// viewing all responses...except in the course where that PUBLIC groupevaluation was originally created.

$courseid = $course->id;

//$cm      = get_coursemodule_from_id('groupevaluation', $id, 0, false, MUST_EXIST);
//$context = context_module::instance($cm->id);

$row[] = new tabobject('criterions', $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/criterions.php?'.
            'id='.$cm->id), get_string('criterions', 'groupevaluation'));

$survey = $DB->get_records('groupevaluation_surveys', array('groupevaluationid' => $groupevaluation->id, 'userid' => $USER->id));

if ($survey) {
  $usernumresp = count($DB->get_records('groupevaluation_assessments', array('surveyid' => $survey->id), 'id'));
} else {
  $usernumresp = 0;
}

if (has_capability('mod/groupevaluation:readresponses', $context) && ($usernumresp > 0)) {
    $argstr = 'instance='.$groupevaluation->id.'&user='.$USER->id.'&group='.$currentgroupid;
    if ($usernumresp == 1) {
        $argstr .= '&byresponse=1&action=vresp';
        $yourrespstring = get_string('yourresponse', 'groupevaluation');
    } else {
        $yourrespstring = get_string('yourresponses', 'groupevaluation');
    }
    $row[] = new tabobject('myreport', $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/myreport.php?'.
                           $argstr), $yourrespstring);

    if ($usernumresp > 1 && in_array($currenttab, array('mysummary', 'mybyresponse', 'myvall', 'mydownloadcsv'))) {
        $inactive[] = 'myreport';
        $activated[] = 'myreport';
        $row2 = array();
        $argstr2 = $argstr.'&action=summary';
        $row2[] = new tabobject('mysummary', $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/myreport.php?'.$argstr2),
                                get_string('summary', 'groupevaluation'));
        $argstr2 = $argstr.'&byresponse=1&action=vresp';
        $row2[] = new tabobject('mybyresponse', $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/myreport.php?'.$argstr2),
                                get_string('viewindividualresponse', 'groupevaluation'));
        $argstr2 = $argstr.'&byresponse=0&action=vall&group='.$currentgroupid;
        $row2[] = new tabobject('myvall', $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/myreport.php?'.$argstr2),
                                get_string('myresponses', 'groupevaluation'));
        if ($groupevaluation->capabilities->downloadresponses) {
            $argstr2 = $argstr.'&action=dwnpg';
            $link  = $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/report.php?'.$argstr2);
            $row2[] = new tabobject('mydownloadcsv', $link, get_string('downloadtext'));
        }
    } else if (in_array($currenttab, array('mybyresponse', 'mysummary'))) {
        $inactive[] = 'myreport';
        $activated[] = 'myreport';
    }
}

//TODO Esta consulta no es -> hacer la buena
$numresp = $DB->get_records('groupevaluation_assessments', array('surveyid' => $groupevaluation->id), 'id');

// Number of responses in currently selected group (or all participants etc.).
if (isset($SESSION->groupevaluation->numselectedresps)) {
    $numselectedresps = $SESSION->groupevaluation->numselectedresps;
} else {
    $numselectedresps = $numresp;
}

// If groupevaluation is set to separate groups, prevent user who is not member of any group
// to view All responses.
$canviewgroups = true;
$groupmode = groups_get_activity_groupmode($cm, $course);
if ($groupmode == 1) {
    $canviewgroups = groups_has_membership($cm, $USER->id);
}
$canviewallgroups = has_capability('moodle/site:accessallgroups', $context);

if (($canviewallgroups || ($canviewgroups))
                && $numresp > 0 && $numselectedresps > 0) {
    $argstr = 'instance='.$groupevaluation->id;
    $row[] = new tabobject('allreport', $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/report.php?'.
                           $argstr.'&action=vall'), get_string('viewallresponses', 'groupevaluation'));
    if (in_array($currenttab, array('vall', 'vresp', 'valldefault', 'vallasort', 'vallarsort', 'deleteall', 'downloadcsv',
                                     'vrespsummary', 'individualresp', 'printresp', 'deleteresp'))) {
        $inactive[] = 'allreport';
        $activated[] = 'allreport';
        if ($currenttab == 'vrespsummary' || $currenttab == 'valldefault') {
            $inactive[] = 'vresp';
        }
        $row2 = array();
        $argstr2 = $argstr.'&action=vall&group='.$currentgroupid;
        $row2[] = new tabobject('vall', $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/report.php?'.$argstr2),
                                get_string('summary', 'groupevaluation'));
        if (has_capability('mod/groupevaluation:viewsingleresponse', $context)) {
            $argstr2 = $argstr.'&byresponse=1&action=vresp&group='.$currentgroupid;
            $row2[] = new tabobject('vrespsummary', $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/report.php?'.$argstr2),
                                get_string('viewbyresponse', 'groupevaluation'));
            if ($currenttab == 'individualresp' || $currenttab == 'deleteresp') {
                $argstr2 = $argstr.'&byresponse=1&action=vresp';
                $row2[] = new tabobject('vresp', $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/report.php?'.$argstr2),
                        get_string('viewindividualresponse', 'groupevaluation'));
            }
        }
    }
    if (in_array($currenttab, array('valldefault',  'vallasort', 'vallarsort', 'deleteall', 'downloadcsv'))) {
        $activated[] = 'vall';
        $row3 = array();

        $argstr2 = $argstr.'&action=vall&group='.$currentgroupid;
        $row3[] = new tabobject('valldefault', $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/report.php?'.$argstr2),
                                get_string('order_default', 'groupevaluation'));
        if ($currenttab != 'downloadcsv' && $currenttab != 'deleteall') {
            $argstr2 = $argstr.'&action=vallasort&group='.$currentgroupid;
            $row3[] = new tabobject('vallasort', $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/report.php?'.$argstr2),
                                    get_string('order_ascending', 'groupevaluation'));
            $argstr2 = $argstr.'&action=vallarsort&group='.$currentgroupid;
            $row3[] = new tabobject('vallarsort', $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/report.php?'.$argstr2),
                                    get_string('order_descending', 'groupevaluation'));
        }
        if (has_capability('mod/groupevaluation:deleteresponses', $context)) {
            $argstr2 = $argstr.'&action=delallresp&group='.$currentgroupid;
            $row3[] = new tabobject('deleteall', $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/report.php?'.$argstr2),
                                    get_string('deleteallresponses', 'groupevaluation'));
        }

        if (has_capability('mod/groupevaluation:downloadresponses', $context)) {
            $argstr2 = $argstr.'&action=dwnpg&group='.$currentgroupid;
            $link  = $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/report.php?'.$argstr2);
            $row3[] = new tabobject('downloadcsv', $link, get_string('downloadtext'));
        }
    }

    if (in_array($currenttab, array('individualresp', 'deleteresp'))) {
        $inactive[] = 'vresp';
        if ($currenttab != 'deleteresp') {
            $activated[] = 'vresp';
        }
        if (has_capability('mod/groupevaluation:deleteresponses', $context)) {
            $argstr2 = $argstr.'&action=dresp&rid='.$rid.'&individualresponse=1';
            $row2[] = new tabobject('deleteresp', $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/report.php?'.$argstr2),
                            get_string('deleteresp', 'groupevaluation'));
        }

    }
} else if ($canviewgroups && has_capability('mod/groupevaluation:readresponses', $context) && ($numresp > 0) && $canviewgroups &&
           ($groupevaluation->resp_view == groupevaluation_STUDENTVIEWRESPONSES_ALWAYS ||
            ($groupevaluation->resp_view == groupevaluation_STUDENTVIEWRESPONSES_WHENCLOSED
                && $groupevaluation->is_closed()) ||
            ($groupevaluation->resp_view == groupevaluation_STUDENTVIEWRESPONSES_WHENANSWERED
                && $usernumresp > 0 )) &&
           $groupevaluation->is_survey_owner()) {
    $argstr = 'instance='.$groupevaluation->id.'&sid='.$groupevaluation->sid;
    $row[] = new tabobject('allreport', $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/report.php?'.
                           $argstr.'&action=vall&group='.$currentgroupid), get_string('viewallresponses', 'groupevaluation'));
    if (in_array($currenttab, array('valldefault',  'vallasort', 'vallarsort', 'deleteall', 'downloadcsv'))) {
        $inactive[] = 'vall';
        $activated[] = 'vall';
        $row2 = array();
        $argstr2 = $argstr.'&action=vall&group='.$currentgroupid;
        $row2[] = new tabobject('valldefault', $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/report.php?'.$argstr2),
                                get_string('summary', 'groupevaluation'));
        $inactive[] = $currenttab;
        $activated[] = $currenttab;
        $row3 = array();
        $argstr2 = $argstr.'&action=vall&group='.$currentgroupid;
        $row3[] = new tabobject('valldefault', $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/report.php?'.$argstr2),
                                get_string('order_default', 'groupevaluation'));
        $argstr2 = $argstr.'&action=vallasort&group='.$currentgroupid;
        $row3[] = new tabobject('vallasort', $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/report.php?'.$argstr2),
                                get_string('order_ascending', 'groupevaluation'));
        $argstr2 = $argstr.'&action=vallarsort&group='.$currentgroupid;
        $row3[] = new tabobject('vallarsort', $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/report.php?'.$argstr2),
                                get_string('order_descending', 'groupevaluation'));
        if ($groupevaluation->capabilities->deleteresponses) {
            $argstr2 = $argstr.'&action=delallresp';
            $row2[] = new tabobject('deleteall', $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/report.php?'.$argstr2),
                                    get_string('deleteallresponses', 'groupevaluation'));
        }

        if ($groupevaluation->capabilities->downloadresponses) {
            $argstr2 = $argstr.'&action=dwnpg';
            $link  = htmlspecialchars('/mod/groupevaluation/report.php?'.$argstr2);
            $row2[] = new tabobject('downloadcsv', $link, get_string('downloadtext'));
        }
        if (count($row2) <= 1) {
            $currenttab = 'allreport';
        }
    }
}

if (has_capability('mod/groupevaluation:viewsingleresponse', $context) && ($canviewallgroups || $canviewgroups)) {
    $nonrespondenturl = new moodle_url('/mod/groupevaluation/show_nonrespondents.php', array('id' => $cm->id));
    $row[] = new tabobject('nonrespondents',
                    $nonrespondenturl->out(),
                    get_string('show_nonrespondents', 'groupevaluation'));
}

if ((count($row) > 1) || (!empty($row2) && (count($row2) > 1))) {
    $tabs[] = $row;

    if (!empty($row2) && (count($row2) > 1)) {
        $tabs[] = $row2;
    }

    if (!empty($row3) && (count($row3) > 1)) {
        $tabs[] = $row3;
    }

    print_tabs($tabs, $currenttab, $inactive, $activated);

}
