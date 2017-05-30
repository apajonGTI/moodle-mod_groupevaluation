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

global $DB, $SESSION, $course, $USER, $OUTPUT;
$tabs = array();
$row  = array();
$inactive = array();
$activated = array();
if (!isset($SESSION->groupevaluation)) {
    $SESSION->groupevaluation = new stdClass();
}
$currenttab = $SESSION->groupevaluation->current_tab;

//$cm      = get_coursemodule_from_id('groupevaluation', $id, 0, false, MUST_EXIST);
//$context = context_module::instance($cm->id);

$srccriterions = $OUTPUT->pix_url('t/edit');
$srcpreview = $OUTPUT->pix_url('t/preview');
$srcgroups = $OUTPUT->pix_url('t/groups');
$srcreport = $OUTPUT->pix_url('t/scales');
$srcnotresponded = $OUTPUT->pix_url('i/risk_personal');

$imgcriterions  = '<img src="'.$srccriterions.'"/> '.get_string('criterions', 'groupevaluation');
$imgpreview     = '<img src="'.$srcpreview.'"/> '.get_string('preview', 'groupevaluation');
$imggroups      = '<img src="'.$srcgroups.'"/> '.get_string('groups', 'groupevaluation');
$imgreport      = '<img src="'.$srcreport.'"/> '.get_string('report', 'groupevaluation');
$imgnotresponded = '<img src="'.$srcnotresponded.'"/> '.get_string('notresponded', 'groupevaluation');

if (has_capability('mod/groupevaluation:editsurvey', $context)) {
    // CRITERIONS //
    $row[] = new tabobject('criterions', $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/criterions.php?'.
              'id='.$cm->id), $imgcriterions, get_string('criterions', 'groupevaluation'));

    // PREVIEW //
    $row[] = new tabobject('preview', $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/preview.php?'.
              'id='.$cm->id), $imgpreview, get_string('preview', 'groupevaluation'));

    // GROUPS //
    $row[] = new tabobject('groups', $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/groups.php?'.
              'id='.$cm->id), $imggroups, get_string('groups', 'groupevaluation'));
}

if (has_capability('mod/groupevaluation:readresponses', $context)) {
    // REPORT //
    $row[] = new tabobject('report', $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/report.php?'.
              'id='.$cm->id), $imgreport, get_string('report', 'groupevaluation'));

    // NO RESPONDED //
    $row[] = new tabobject('notresponded', $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/notresponded.php?'.
              'id='.$cm->id), $imgnotresponded, get_string('notresponded', 'groupevaluation'));
}

if (count($row) > 1) {
    $tabs[] = $row;
    print_tabs($tabs, $currenttab, $inactive, $activated);
}
