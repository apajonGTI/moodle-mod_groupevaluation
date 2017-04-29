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
require_once($CFG->dirroot.'/mod/groupevaluation/criterions_form.php');

$id     = required_param('id', PARAM_INT);                 // Course module ID
$action = optional_param('action', 'main', PARAM_ALPHA);   // Screen.
$ctrid    = optional_param('ctrid', 0, PARAM_INT);             // Question id.
$moveq  = optional_param('moveq', 0, PARAM_INT);           // Question id to move.
$delctr   = optional_param('delctr', 0, PARAM_INT);             // Question id to delete
$qtype  = optional_param('type_id', 0, PARAM_INT);         // Question type.
$currentgroupid = optional_param('group', 0, PARAM_INT); // Group id.

if (! $cm = get_coursemodule_from_id('groupevaluation', $id)) {
    print_error('invalidcoursemodule');
}

if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
    print_error('coursemisconf');
}

if (! $groupevaluation = $DB->get_record("groupevaluation", array("id" => $cm->instance))) {
    print_error('invalidcoursemodule');
}

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);

$url = new moodle_url($CFG->wwwroot.'/mod/groupevaluation/criterions.php');
$url->param('id', $id);
if ($ctrid) {
    $url->param('ctrid', $ctrid);
}

$PAGE->set_url($url);
$PAGE->set_context($context);

if (!(has_capability('mod/groupevaluation:editsurvey', $context))) {
    print_error('nopermissions', 'error', 'mod:groupevaluation:edit');
}

if (!isset($SESSION->groupevaluation)) {
    $SESSION->groupevaluation = new stdClass();
}
$SESSION->groupevaluation->current_tab = 'criterions';
$reload = false;
// Process form data.

// Delete criterion button has been pressed in criterions_form AND deletion has been confirmed on the confirmation page.
if ($delctr) {
    $ctrid = $delctr;
    $groupevaluationid = $groupevaluation->id;

    // Need to reload questions before setting deleted question to 'y'.
    $criterions = $DB->get_records('groupevaluation_criterions', array('groupevaluationid' => $groupevaluationid), 'id');
    $DB->delete_records('groupevaluation_criterions', array('id' => $ctrid, 'groupevaluationid' => $groupevaluationid));

    // Just in case the page is refreshed (F5) after a question has been deleted.
    if (isset($criterions[$ctrid])) {
        $select = 'groupevaluationid = '.$groupevaluationid.' AND position > '.$criterions[$ctrid]->position;
    } else {
        redirect($CFG->wwwroot.'/mod/groupevaluation/criterions.php?id='.$cm->id);
    }

    if ($records = $DB->get_records_select('groupevaluation_criterions', $select, null, 'position ASC')) {
        foreach ($records as $record) {
            $DB->set_field('groupevaluation_criterions', 'position', $record->position - 1, array('id' => $record->id));
        }
    }

    //// Delete responses to that deleted question.
    //groupevaluation_delete_responses($ctrid);

    /* AQUI */

    // If no questions left in this questionnaire, remove all attempts and responses.
    if (!$criterions = $DB->get_records('groupevaluation_criterions', array('groupevaluationid' => $groupevaluationid), 'id') ) {
        $DB->delete_records('questionnaire_tags', array('criterionid' => $ctrid));
        $DB->delete_records('questionnaire_assessments', array('criterionid' => $ctrid));
    }


    // Log question deleted event.
    $context = context_module::instance($cm->id);
    $questiontype = $qtypenames[$qtype];
    $params = array(
                    'context' => $context,
                    'courseid' => $questionnaire->course->id,
                    'other' => array('questiontype' => $questiontype)
    );
    $event = \mod_questionnaire\event\question_deleted::create($params);
    $event->trigger();

    if ($questionnairehasdependencies) {
        $SESSION->questionnaire->validateresults = questionnaire_check_page_breaks($questionnaire);
    }
    $reload = true;
}

if ($action == 'main') {
    $criterionsform = new groupevaluation_criterions_form('questions.php', $moveq);
    $sdata = clone($questionnaire->survey);
    $sdata->sid = $questionnaire->survey->id;
    $sdata->id = $cm->id;
    if (!empty($questionnaire->questions)) {
        $pos = 1;
        foreach ($questionnaire->questions as $ctridx => $question) {
            $sdata->{'pos_'.$ctridx} = $pos;
            $pos++;
        }
    }
    $criterionsform->set_data($sdata);
    if ($criterionsform->is_cancelled()) {
        // Switch to main screen.
        $action = 'main';
        redirect($CFG->wwwroot.'/mod/questionnaire/questions.php?id='.$questionnaire->cm->id);
        $reload = true;
    }
    if ($qformdata = $criterionsform->get_data()) {
        // Quickforms doesn't return values for 'image' input types using 'exportValue', so we need to grab
        // it from the raw submitted data.
        $exformdata = data_submitted();

        if (isset($exformdata->movebutton)) {
            $qformdata->movebutton = $exformdata->movebutton;
        } else if (isset($exformdata->moveherebutton)) {
            $qformdata->moveherebutton = $exformdata->moveherebutton;
        } else if (isset($exformdata->editbutton)) {
            $qformdata->editbutton = $exformdata->editbutton;
        } else if (isset($exformdata->removebutton)) {
            $qformdata->removebutton = $exformdata->removebutton;
        } else if (isset($exformdata->requiredbutton)) {
            $qformdata->requiredbutton = $exformdata->requiredbutton;
        }

        // Insert a section break.
        if (isset($qformdata->removebutton)) {
            // Need to use the key, since IE returns the image position as the value rather than the specified
            // value in the <input> tag.
            $ctrid = key($qformdata->removebutton);
            $qtype = $questionnaire->questions[$ctrid]->type_id;

            // Delete section breaks without asking for confirmation.
            if ($qtype == QUESPAGEBREAK) {
                redirect($CFG->wwwroot.'/mod/questionnaire/questions.php?id='.$questionnaire->cm->id.'&amp;delctr='.$ctrid);
            }
            if ($questionnairehasdependencies) {
                $haschildren  = questionnaire_get_descendants ($questionnaire->questions, $ctrid);
            }
            if (count($haschildren) != 0) {
                $action = "confirmdelctruestionparent";
            } else {
                $action = "confirmdelctruestion";
            }

        } else if (isset($qformdata->editbutton)) {
            // Switch to edit question screen.
            $action = 'question';
            // Need to use the key, since IE returns the image position as the value rather than the specified
            // value in the <input> tag.
            $ctrid = key($qformdata->editbutton);
            $reload = true;

        } else if (isset($qformdata->requiredbutton)) {
            // Need to use the key, since IE returns the image position as the value rather than the specified
            // value in the <input> tag.

            $ctrid = key($qformdata->requiredbutton);
            if ($questionnaire->questions[$ctrid]->required == 'y') {
                $DB->set_field('questionnaire_question', 'required', 'n', array('id' => $ctrid, 'survey_id' => $sid));

            } else {
                $DB->set_field('questionnaire_question', 'required', 'y', array('id' => $ctrid, 'survey_id' => $sid));
            }

            $reload = true;

        } else if (isset($qformdata->addqbutton)) {
            if ($qformdata->type_id == QUESPAGEBREAK) { // Adding section break is handled right away....
                $sql = 'SELECT MAX(position) as maxpos FROM {questionnaire_question} '.
                       'WHERE survey_id = '.$qformdata->sid.' AND deleted = \'n\'';
                if ($record = $DB->get_record_sql($sql)) {
                    $pos = $record->maxpos + 1;
                } else {
                    $pos = 1;
                }
                $question = new stdClass();
                $question->survey_id = $qformdata->sid;
                $question->type_id = QUESPAGEBREAK;
                $question->position = $pos;
                $question->content = 'break';
                $DB->insert_record('questionnaire_question', $question);
                $reload = true;
            } else {
                // Switch to edit question screen.
                $action = 'question';
                $qtype = $qformdata->type_id;
                $ctrid = 0;
                $reload = true;
            }

        } else if (isset($qformdata->movebutton)) {
            // Nothing I do will seem to reload the form with new data, except for moving away from the page, so...
            redirect($CFG->wwwroot.'/mod/questionnaire/questions.php?id='.$questionnaire->cm->id.
                     '&moveq='.key($qformdata->movebutton));
            $reload = true;



        } else if (isset($qformdata->moveherebutton)) {
            // Need to use the key, since IE returns the image position as the value rather than the specified
            // value in the <input> tag.

            // No need to move question if new position = old position!
            $qpos = key($qformdata->moveherebutton);
            if ($qformdata->moveq != $qpos) {
                $questionnaire->move_question($qformdata->moveq, $qpos);
            }
            if ($questionnairehasdependencies) {
                $SESSION->questionnaire->validateresults = questionnaire_check_page_breaks($questionnaire);
            }
            // Nothing I do will seem to reload the form with new data, except for moving away from the page, so...
            redirect($CFG->wwwroot.'/mod/questionnaire/questions.php?id='.$questionnaire->cm->id);
            $reload = true;

        } else if (isset($qformdata->validate)) {
            // Validates page breaks for depend questions.
            $SESSION->questionnaire->validateresults = questionnaire_check_page_breaks($questionnaire);
            $reload = true;
        }
    }


} else if ($action == 'question') {
    if ($ctrid != 0) {
        $question = clone($questionnaire->questions[$ctrid]);
        $question->ctrid = $question->id;
        $question->sid = $questionnaire->survey->id;
        $question->id = $cm->id;
        $draftideditor = file_get_submitted_draft_itemid('question');
        $content = file_prepare_draft_area($draftideditor, $context->id, 'mod_questionnaire', 'question',
                                           $ctrid, array('subdirs' => true), $question->content);
        $question->content = array('text' => $content, 'format' => FORMAT_HTML, 'itemid' => $draftideditor);
    } else {
        $question = new stdClass();
        $question->sid = $questionnaire->survey->id;
        $question->id = $cm->id;
        $question->type_id = $qtype;
        $question->type = '';
        $draftideditor = file_get_submitted_draft_itemid('question');
        $content = file_prepare_draft_area($draftideditor, $context->id, 'mod_questionnaire', 'question',
                                           null, array('subdirs' => true), '');
        $question->content = array('text' => $content, 'format' => FORMAT_HTML, 'itemid' => $draftideditor);
    }
    $criterionsform = new questionnaire_edit_question_form('questions.php');
    $criterionsform->set_data($question);
    if ($criterionsform->is_cancelled()) {
        // Switch to main screen.
        $action = 'main';
        $reload = true;

    } else if ($qformdata = $criterionsform->get_data()) {
        // Saving question data.
        if (isset($qformdata->makecopy)) {
            $qformdata->ctrid = 0;
        }

        $haschoices = $questionnaire->type_has_choices();
        // THIS SECTION NEEDS TO BE MOVED OUT OF HERE - SHOULD CREATE QUESTION-SPECIFIC UPDATE FUNCTIONS.
        if ($haschoices[$qformdata->type_id]) {
            // Eliminate trailing blank lines.
            $qformdata->allchoices = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $qformdata->allchoices);
            // Trim to eliminate potential trailing carriage return.
            $qformdata->allchoices = trim($qformdata->allchoices);
            if (empty($qformdata->allchoices)) {
                if ($qformdata->type_id != QUESRATE) {
                    error (get_string('enterpossibleanswers', 'questionnaire'));
                } else {
                    // Add dummy blank space character for empty value.
                    $qformdata->allchoices = " ";
                }
            } else if ($qformdata->type_id == QUESRATE) {    // Rate.
                $allchoices = $qformdata->allchoices;
                $allchoices = explode("\n", $allchoices);
                $ispossibleanswer = false;
                $nbnameddegrees = 0;
                $nbvalues = 0;
                foreach ($allchoices as $choice) {
                    if ($choice) {
                        // Check for number from 1 to 3 digits, followed by the equal sign =.
                        if (preg_match("/^[0-9]{1,3}=/", $choice)) {
                            $nbnameddegrees++;
                        } else {
                            $nbvalues++;
                            $ispossibleanswer = true;
                        }
                    }
                }
                // Add carriage return and dummy blank space character for empty value.
                if (!$ispossibleanswer) {
                    $qformdata->allchoices .= "\n ";
                }

                // Sanity checks for correct number of values in $qformdata->length.

                // Sanity check for named degrees.
                if ($nbnameddegrees && $nbnameddegrees != $qformdata->length) {
                    $qformdata->length = $nbnameddegrees;
                }
                // Sanity check for "no duplicate choices"".
                if ($qformdata->precise == 2 && ($qformdata->length > $nbvalues || !$qformdata->length)) {
                    $qformdata->length = $nbvalues;
                }
            } else if ($qformdata->type_id == QUESCHECK) {
                // Sanity checks for min and max checked boxes.
                $allchoices = $qformdata->allchoices;
                $allchoices = explode("\n", $allchoices);
                $nbvalues = count($allchoices);

                if ($qformdata->length > $nbvalues) {
                    $qformdata->length = $nbvalues;
                }
                if ($qformdata->precise > $nbvalues) {
                    $qformdata->precise = $nbvalues;
                }
                $qformdata->precise = max($qformdata->length, $qformdata->precise);
            }
        }

        $dependency = array();
        if (isset($qformdata->dependquestion) && $qformdata->dependquestion != 0) {
            $dependency = explode(",", $qformdata->dependquestion);
            $qformdata->dependquestion = $dependency[0];
            $qformdata->dependchoice = $dependency[1];
        }

        if (!empty($qformdata->ctrid)) {

            // Update existing question.
            // Handle any attachments in the content.
            $qformdata->itemid  = $qformdata->content['itemid'];
            $qformdata->format  = $qformdata->content['format'];
            $qformdata->content = $qformdata->content['text'];
            $qformdata->content = file_save_draft_area_files($qformdata->itemid, $context->id, 'mod_questionnaire', 'question',
                                                             $qformdata->ctrid, array('subdirs' => true), $qformdata->content);

            $fields = array('name', 'type_id', 'length', 'precise', 'required', 'content', 'dependquestion', 'dependchoice');
            $questionrecord = new stdClass();
            $questionrecord->id = $qformdata->ctrid;
            foreach ($fields as $f) {
                if (isset($qformdata->$f)) {
                    $questionrecord->$f = trim($qformdata->$f);
                }
            }
            $result = $DB->update_record('questionnaire_question', $questionrecord);
            if ($questionnairehasdependencies) {
                questionnaire_check_page_breaks($questionnaire);
            }
        } else {
            // Create new question:
            // set the position to the end.
            $sql = 'SELECT MAX(position) as maxpos FROM {questionnaire_question} '.
                   'WHERE survey_id = '.$qformdata->sid.' AND deleted = \'n\'';
            if ($record = $DB->get_record_sql($sql)) {
                $qformdata->position = $record->maxpos + 1;
            } else {
                $qformdata->position = 1;
            }

            // Need to update any image content after the question is created, so create then update the content.
            $qformdata->survey_id = $qformdata->sid;
            $fields = array('survey_id', 'name', 'type_id', 'length', 'precise', 'required', 'position',
                            'dependquestion', 'dependchoice');
            $questionrecord = new stdClass();
            foreach ($fields as $f) {
                if (isset($qformdata->$f)) {
                    $questionrecord->$f = trim($qformdata->$f);
                }
            }
            $questionrecord->content = '';

            $qformdata->ctrid = $DB->insert_record('questionnaire_question', $questionrecord);

            // Handle any attachments in the content.
            $qformdata->itemid  = $qformdata->content['itemid'];
            $qformdata->format  = $qformdata->content['format'];
            $qformdata->content = $qformdata->content['text'];
            $content            = file_save_draft_area_files($qformdata->itemid, $context->id, 'mod_questionnaire', 'question',
                                                             $qformdata->ctrid, array('subdirs' => true), $qformdata->content);
            $result = $DB->set_field('questionnaire_question', 'content', $content, array('id' => $qformdata->ctrid));
        }

        // UPDATE or INSERT rows for each of the question choices for this question.
        if ($haschoices[$qformdata->type_id]) {
            $cidx = 0;
            if (isset($question->choices) && !isset($qformdata->makecopy)) {
                $oldcount = count($question->choices);
                $echoice = reset($question->choices);
                $ekey = key($question->choices);
            } else {
                $oldcount = 0;
            }

            $newchoices = explode("\n", $qformdata->allchoices);
            $nidx = 0;
            $newcount = count($newchoices);

            while (($nidx < $newcount) && ($cidx < $oldcount)) {
                if ($newchoices[$nidx] != $echoice->content) {
                    $newchoices[$nidx] = trim ($newchoices[$nidx]);
                    $result = $DB->set_field('questionnaire_quest_choice', 'content', $newchoices[$nidx], array('id' => $ekey));
                    $r = preg_match_all("/^(\d{1,2})(=.*)$/", $newchoices[$nidx], $matches);
                    // This choice has been attributed a "score value" OR this is a rate question type.
                    if ($r) {
                        $newscore = $matches[1][0];
                        $result = $DB->set_field('questionnaire_quest_choice', 'value', $newscore, array('id' => $ekey));
                    } else {     // No score value for this choice.
                        $result = $DB->set_field('questionnaire_quest_choice', 'value', null, array('id' => $ekey));
                    }
                }
                $nidx++;
                $echoice = next($question->choices);
                $ekey = key($question->choices);
                $cidx++;
            }

            while ($nidx < $newcount) {
                // New choices...
                $choicerecord = new stdClass();
                $choicerecord->question_id = $qformdata->ctrid;
                $choicerecord->content = trim($newchoices[$nidx]);
                $r = preg_match_all("/^(\d{1,2})(=.*)$/", $choicerecord->content, $matches);
                // This choice has been attributed a "score value" OR this is a rate question type.
                if ($r) {
                    $choicerecord->value = $matches[1][0];
                }
                $result = $DB->insert_record('questionnaire_quest_choice', $choicerecord);
                $nidx++;
            }

            while ($cidx < $oldcount) {
                $result = $DB->delete_records('questionnaire_quest_choice', array('id' => $ekey));
                $echoice = next($question->choices);
                $ekey = key($question->choices);
                $cidx++;
            }
        }
        // Make these field values 'sticky' for further new questions.
        if (!isset($qformdata->required)) {
            $qformdata->required = 'n';
        }
        // Need to reload questions.
        $criterions = $DB->get_records('questionnaire_question', array('survey_id' => $sid, 'deleted' => 'n'), 'id');
        $questionnairehasdependencies = questionnaire_has_dependencies($criterions);
        if (questionnaire_has_dependencies($criterions)) {
            questionnaire_check_page_breaks($questionnaire);
        }
        $SESSION->questionnaire->required = $qformdata->required;
        $SESSION->questionnaire->type_id = $qformdata->type_id;
        // Switch to main screen.
        $action = 'main';
        $reload = true;
    }

    // Log question created event.
    if (isset($qformdata)) {
        $context = context_module::instance($questionnaire->cm->id);
        $questiontype = $qtypenames[$qformdata->type_id];
        $params = array(
                        'context' => $context,
                        'courseid' => $questionnaire->course->id,
                        'other' => array('questiontype' => $questiontype)
        );
        $event = \mod_questionnaire\event\question_created::create($params);
        $event->trigger();
    }

    $criterionsform->set_data($question);
}

// Reload the form data if called for...
if ($reload) {
    unset($criterionsform);
    $questionnaire = new questionnaire($questionnaire->id, null, $course, $cm);
    if ($action == 'main') {
        $criterionsform = new questionnaire_questions_form('questions.php', $moveq);
        $sdata = clone($questionnaire->survey);
        $sdata->sid = $questionnaire->survey->id;
        $sdata->id = $cm->id;
        if (!empty($questionnaire->questions)) {
            $pos = 1;
            foreach ($questionnaire->questions as $ctridx => $question) {
                $sdata->{'pos_'.$ctridx} = $pos;
                $pos++;
            }
        }
        $criterionsform->set_data($sdata);
    } else if ($action == 'question') {
        if ($ctrid != 0) {
            $question = clone($questionnaire->questions[$ctrid]);
            $question->ctrid = $question->id;
            $question->sid = $questionnaire->survey->id;
            $question->id = $cm->id;
            $draftideditor = file_get_submitted_draft_itemid('question');
            $content = file_prepare_draft_area($draftideditor, $context->id, 'mod_questionnaire', 'question',
                                               $ctrid, array('subdirs' => true), $question->content);
            $question->content = array('text' => $content, 'format' => FORMAT_HTML, 'itemid' => $draftideditor);
        } else {
            $question = new stdClass();
            $question->sid = $questionnaire->survey->id;
            $question->id = $cm->id;
            $question->type_id = $qtype;
            $question->type = $DB->get_field('questionnaire_question_type', 'type', array('id' => $qtype));
            $draftideditor = file_get_submitted_draft_itemid('question');
            $content = file_prepare_draft_area($draftideditor, $context->id, 'mod_questionnaire', 'question',
                                               null, array('subdirs' => true), '');
            $question->content = array('text' => $content, 'format' => FORMAT_HTML, 'itemid' => $draftideditor);
        }
        $criterionsform = new questionnaire_edit_question_form('questions.php');
        $criterionsform->set_data($question);
    }
}

// Print the page header.
if ($action == 'question') {
    if (isset($question->ctrid)) {
        $streditquestion = get_string('editquestion', 'questionnaire', questionnaire_get_type($question->type_id));
    } else {
        $streditquestion = get_string('addnewquestion', 'questionnaire', questionnaire_get_type($question->type_id));
    }
} else {
    $streditquestion = get_string('managequestions', 'questionnaire');
}

$PAGE->set_title($streditquestion);
$PAGE->set_heading(format_string($course->fullname));
$PAGE->navbar->add($streditquestion);
echo $OUTPUT->header();
require('tabs.php');

if ($action == "confirmdelctruestion" || $action == "confirmdelctruestionparent") {

    $ctrid = key($qformdata->removebutton);
    $question = $questionnaire->questions[$ctrid];
    $qtype = $question->type_id;

    // Count responses already saved for that question.
    $countresps = 0;
    if ($qtype != QUESSECTIONTEXT) {
        $responsetable = $DB->get_field('questionnaire_question_type', 'response_table', array('typeid' => $qtype));
        if (!empty($responsetable)) {
            $countresps = $DB->count_records('questionnaire_'.$responsetable, array('question_id' => $ctrid));
        }
    }

    // Needed to print potential media in question text.

    // If question text is "empty", i.e. 2 non-breaking spaces were inserted, do not display any question text.

    if ($question->content == '<p>  </p>') {
        $question->content = '';
    }

    $qname = '';
    if ($question->name) {
        $qname = ' ('.$question->name.')';
    }

    $num = get_string('position', 'questionnaire');
    $pos = $question->position.$qname;

    $msg = '<div class="warning centerpara"><p>'.get_string('confirmdelctruestion', 'questionnaire', $pos).'</p>';
    if ($countresps !== 0) {
        $msg .= '<p>'.get_string('confirmdelctruestionresps', 'questionnaire', $countresps).'</p>';
    }
    $msg .= '</div>';
    $msg .= '<div class = "qn-container">'.$num.' '.$pos.'<div class="qn-question">'.$question->content.'</div></div>';
    $args = "id={$questionnaire->cm->id}";
    $urlno = new moodle_url("/mod/questionnaire/questions.php?{$args}");
    $args .= "&delctr={$ctrid}";
    $urlyes = new moodle_url("/mod/questionnaire/questions.php?{$args}");
    $buttonyes = new single_button($urlyes, get_string('yes'));
    $buttonno = new single_button($urlno, get_string('no'));
    if ($action == "confirmdelctruestionparent") {
        $strnum = get_string('position', 'questionnaire');
        $ctrid = key($qformdata->removebutton);
        $msg .= '<div class="warning">'.get_string('confirmdelchildren', 'questionnaire').'</div><br />';
        foreach ($haschildren as $child) {
            $childname = '';
            if ($child['name']) {
                $childname = ' ('.$child['name'].')';
            }
            $msg .= '<div class = "qn-container">'.$strnum.' '.$child['position'].$childname.'<span class="qdepend"><strong>'.
                            get_string('dependquestion', 'questionnaire').'</strong>'.
                            ' ('.$strnum.' '.$child['parentposition'].') '.
                            '&nbsp;:&nbsp;'.$child['parent'].'</span>'.
                            '<div class="qn-question">'.
                            $child['content'].
                            '</div></div>';
        }
    }
    echo $OUTPUT->confirm($msg, $buttonyes, $buttonno);

} else {
    $criterionsform->display();
}
echo $OUTPUT->footer();
