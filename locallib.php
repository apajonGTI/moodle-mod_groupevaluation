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
 * Internal library of functions for module groupevaluation
 *
 * All the groupevaluation specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_groupevaluation
 * @copyright  Jose Vilas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 require_once(dirname(__FILE__).'/lib.php');

defined('MOODLE_INTERNAL') || die();

/*
 * TODO Probablemente no utilice esta funcion -> BORRAR
 * Does something really useful with the passed things
 *
 * @param array $things
 * @return object
 *function groupevaluation_do_something_useful(array $things) {
 *    return new stdClass();
 *}
 */
 function groupevaluation_delete_responses($qid) {
     global $DB;

     $status = true;

     // Delete all of the response data for a criterion.
     $DB->delete_records('criterionnaire_response_bool', array('criterion_id' => $qid));
     $DB->delete_records('criterionnaire_response_date', array('criterion_id' => $qid));
     $DB->delete_records('criterionnaire_resp_multiple', array('criterion_id' => $qid));
     $DB->delete_records('criterionnaire_response_other', array('criterion_id' => $qid));
     $DB->delete_records('criterionnaire_response_rank', array('criterion_id' => $qid));
     $DB->delete_records('criterionnaire_resp_single', array('criterion_id' => $qid));
     $DB->delete_records('criterionnaire_response_text', array('criterion_id' => $qid));

     $status = $status && $DB->delete_records('criterionnaire_response', array('id' => $qid));
     $status = $status && $DB->delete_records('criterionnaire_attempts', array('rid' => $qid));

     return $status;
 }

 /**
  * Function to move a criterion to a new position.
  * Adapted from feedback plugin.
  *
  * @param int $movecrtid The id of the criterion to be moved.
  * @param int $movetopos The position to move criterion to.
  *
  */

 function move_criterion($criterions, $movecrtid, $movetopos) {
     global $DB;

     $movecriterion = $criterions[$movecrtid];

     if (is_array($criterions)) {
         $index = 1;
         foreach ($criterions as $criterion) {
             if ($index == $movetopos) {
                 $index++;
             }
             if ($criterion->id == $movecriterion->id) {
                 $movecriterion->position = $movetopos;
                 $DB->update_record("groupevaluation_criterions", $movecriterion);
                 continue;
             }
             $criterion->position = $index;
             $DB->update_record("groupevaluation_criterions", $criterion);
             $index++;
         }
         return true;
     }
     return false;
 }

 function view() {
     global $CFG, $USER, $PAGE, $OUTPUT, $groupevaluation, $context, $course;

     $PAGE->set_title(format_string($groupevaluation->name));
     $PAGE->set_heading(format_string($course->fullname));

     // Initialise the JavaScript.

     echo $OUTPUT->header();

     if (!has_capability('mod/groupevaluation:view', $context)) {
         echo('<br/>');
         groupevaluation_notify(get_string("noteligible", "groupevaluation", $groupevaluation->name));
         echo('<div><a href="'.$CFG->wwwroot.'/course/view.php?id='.$groupevaluation->course->id.'" class="btn btn-default btn-lg active" role="button">'.
             get_string("continue").'</a></div>');
         exit;
     }


     echo $OUTPUT->heading(format_string($groupevaluation->name));

    // Finish the page.
    echo $OUTPUT->footer($groupevaluation->course);
 }
