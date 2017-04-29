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

     // Delete all of the response data for a question.
     $DB->delete_records('questionnaire_response_bool', array('question_id' => $qid));
     $DB->delete_records('questionnaire_response_date', array('question_id' => $qid));
     $DB->delete_records('questionnaire_resp_multiple', array('question_id' => $qid));
     $DB->delete_records('questionnaire_response_other', array('question_id' => $qid));
     $DB->delete_records('questionnaire_response_rank', array('question_id' => $qid));
     $DB->delete_records('questionnaire_resp_single', array('question_id' => $qid));
     $DB->delete_records('questionnaire_response_text', array('question_id' => $qid));

     $status = $status && $DB->delete_records('questionnaire_response', array('id' => $qid));
     $status = $status && $DB->delete_records('questionnaire_attempts', array('rid' => $qid));

     return $status;
 }
