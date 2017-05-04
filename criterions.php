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
require_once($CFG->dirroot.'/mod/groupevaluation/locallib.php');

/*optional_param($parname, $default, $type)*/
$id             = required_param('id', PARAM_INT);                // Course module ID
$action         = optional_param('action', 'main', PARAM_ALPHA);  // Screen.
$crtid          = optional_param('crtid', 0, PARAM_INT);          // criterion id.
$movecrt        = optional_param('movecrt', 0, PARAM_INT);        // criterion id to move.
$delcrt         = optional_param('delcrt', 0, PARAM_INT);         // criterion id to delete
$currentgroupid = optional_param('group', 0, PARAM_INT);          // Group id.

// Check if the module instance exists
if (! $cm = get_coursemodule_from_id('groupevaluation', $id)) {
    print_error('invalidcoursemodule');
}

if (! $course = $DB->get_record("course", array("id" => $cm->course))) {
    print_error('coursemisconf');
}

if (! $groupevaluation = $DB->get_record("groupevaluation", array("id" => $cm->instance))) {
    print_error('moduleinstancedoesnotexist');
}

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);

$url = new moodle_url($CFG->wwwroot.'/mod/groupevaluation/criterions.php');
$url->param('id', $id);
if ($crtid) {
    $url->param('crtid', $crtid);
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

$groupevaluationid = $groupevaluation->id;
$select = 'groupevaluationid = '.$groupevaluationid;
$criterions = $DB->get_records_select('groupevaluation_criterions', $select, null, 'position ASC');

// ******************** DELETE CRITERION ****************************************
// Delete criterion button has been pressed in criterions_form AND deletion has been confirmed on the confirmation page.
if ($delcrt) {
    $crtid = $delcrt;

    // Need to reload criterions before setting deleted criterion to 'y'.
    $DB->delete_records('groupevaluation_criterions', array('id' => $crtid, 'groupevaluationid' => $groupevaluationid));

    // Just in case the page is refreshed (F5) after a criterion has been deleted.
    if (isset($criterions[$crtid])) {
        $select = 'groupevaluationid = '.$groupevaluationid.' AND position > '.$criterions[$crtid]->position;
    } else {
        redirect($CFG->wwwroot.'/mod/groupevaluation/criterions.php?id='.$cm->id);
    }

    if ($records = $DB->get_records_select('groupevaluation_criterions', $select, null, 'position ASC')) {
        foreach ($records as $record) {
            $DB->set_field('groupevaluation_criterions', 'position', $record->position - 1, array('id' => $record->id));
        }
    }

    //// Delete responses to that deleted criterion.
    //groupevaluation_delete_responses($crtid);


    // If no criterions left in this groupevaluation, remove all attempts and responses.
    if (!$criterions) {
        $DB->delete_records('groupevaluation_tags', array('criterionid' => $crtid));
        $DB->delete_records('groupevaluation_assessments', array('criterionid' => $crtid));
    }


    // Log criterion deleted event.
    $context = context_module::instance($cm->id);
    $crtname = $criterions[$crtid]->name;
    $params = array(
                    'context' => $context,
                    'courseid' => $groupevaluation->courseid,
                    'other' => array('criterionname' => $crtname)
    );
    $event = \mod_groupevaluation\event\criterion_deleted::create($params);
    $event->trigger();

    $reload = true;
}

// ******************** MAIN VIEW ****************************************

if ($action == 'main') {
    $criterionsform = new groupevaluation_criterions_form('criterions.php', $movecrt);
    $sdata = clone($groupevaluation);
    $sdata->id = $cm->id;

    if (!empty($criterions)) {
        $pos = 1;
        foreach ($criterions as $criterionx) {
            $sdata->{'pos_'.$criterionx->id} = $pos;
            $pos++;
        }
    }
    $criterionsform->set_data($sdata);

    if ($criterionsform->is_cancelled()) {
        // Switch to main screen.
        $action = 'main';
        redirect($CFG->wwwroot.'/mod/groupevaluation/criterions.php?id='.$cm->id);
        $reload = true;
    }
    if ($crtformdata = $criterionsform->get_data()) {
        // Quickforms doesn't return values for 'image' input types using 'exportValue', so we need to grab
        // it from the raw submitted data.
        $exformdata = data_submitted();

        if (isset($exformdata->movebutton)) {
            $crtformdata->movebutton = $exformdata->movebutton;
        } else if (isset($exformdata->moveherebutton)) {
            $crtformdata->moveherebutton = $exformdata->moveherebutton;
        } else if (isset($exformdata->editbutton)) {
            $crtformdata->editbutton = $exformdata->editbutton;
        } else if (isset($exformdata->removebutton)) {
            $crtformdata->removebutton = $exformdata->removebutton;
        } else if (isset($exformdata->requiredbutton)) {
            $crtformdata->requiredbutton = $exformdata->requiredbutton;
        }

        // Insert a section break.
        if (isset($crtformdata->removebutton)) {
            // Need to use the key, since IE returns the image position as the value rather than the specified
            // value in the <input> tag.
            $crtid = key($crtformdata->removebutton);

            $action = "confirmdelcriterion";

        } else if (isset($crtformdata->editbutton)) {
            // Switch to edit criterion screen.
            $action = 'criterion';
            // Need to use the key, since IE returns the image position as the value rather than the specified
            // value in the <input> tag.
            $crtid = key($crtformdata->editbutton);
            $reload = true;

        } else if (isset($crtformdata->addcrtbutton)) {

            // Switch to edit criterion screen.
            $action = 'criterion';
            $crtid = 0;
            $reload = true;
            $numanswers = $crtformdata->numanswers;

        } else if (isset($crtformdata->addanswer)) {

            // Switch to edit criterion screen.
            /*$action = 'criterion';
            $crtid = 0;
            $reload = true;*/
            print_error('moduleinstancedoesnotexist');

        } else if (isset($crtformdata->movebutton)) {
            // Nothing I do will seem to reload the form with new data, except for moving away from the page, so...
            redirect($CFG->wwwroot.'/mod/groupevaluation/criterions.php?id='.$cm->id.
                     '&movecrt='.key($crtformdata->movebutton));
            $reload = true;

        } else if (isset($crtformdata->moveherebutton)) {
            // Need to use the key, since IE returns the image position as the value rather than the specified
            // value in the <input> tag.

            // No need to move criterion if new position = old position!
            $crtpos = key($crtformdata->moveherebutton);
            if ($crtformdata->movecrt != $crtpos) {
                move_criterion($criterions, $crtformdata->movecrt, $crtpos); // TODO Revisar funcion en locallib.php
            }
            // Nothing I do will seem to reload the form with new data, except for moving away from the page, so...
            redirect($CFG->wwwroot.'/mod/groupevaluation/criterions.php?id='.$cm->id);
            $reload = true;
        }
    }

// ******************** ACTION CRITERION ****************************************
} else if ($action == 'criterion') {
    if ($crtid != 0) {
        $criterion = clone($criterions[$crtid]);
        $criterion->crtid = $criterion->id;
        $criterion->groupevaluationid = $groupevaluationid;
        $criterion->cmid = $cm->id;
        $draftideditor = file_get_submitted_draft_itemid('criterion');
        $text = file_prepare_draft_area($draftideditor, $context->id, 'mod_groupevaluation', 'criterion',
                                           $criterion->crtid, array('subdirs' => true), $criterion->text);
        $criterion->text = array('text' => $text, 'format' => FORMAT_HTML, 'itemid' => $draftideditor);
    } else {
        $criterion = new stdClass();
        $criterion->groupevaluationid = $groupevaluationid;
        $criterion->cmid = $cm->id;
        $draftideditor = file_get_submitted_draft_itemid('criterion');
        $text = file_prepare_draft_area($draftideditor, $context->id, 'mod_groupevaluation', 'criterion',
                                           null, array('subdirs' => true), '');
        $criterion->text = array('text' => $text, 'format' => FORMAT_HTML, 'itemid' => $draftideditor);
    }
    /*AQUI*/
    $criterion->numanswers = $numanswers;
    $criterionsform = new groupevaluation_edit_criterion_form('criterions.php');
    $criterionsform->set_data($criterion);
    if ($criterionsform->is_cancelled()) {
        // Switch to main screen.
        $action = 'main';
        $reload = true;

    } else if ($crtformdata = $criterionsform->get_data()) {
        // Saving criterion data.
        if (isset($crtformdata->makecopy)) {
            $crtformdata->crtid = 0;
        }

        // THIS SECTION NEEDS TO BE MOVED OUT OF HERE - SHOULD CREATE criterion-SPECIFIC UPDATE FUNCTIONS.
        // Eliminate trailing blank lines.
        $crtformdata->tags = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $crtformdata->tags);
        // Trim to eliminate potential trailing carriage return.
        $crtformdata->tags = trim($crtformdata->tags);
        if (empty($crtformdata->tags)) {
            // Add dummy blank space character for empty value.
            $crtformdata->tags = " ";

        } else {
            // Sanity checks for min and max checked boxes.
            $tags = $crtformdata->tags;
            $tags = explode("\n", $tags);
            $nbvalues = count($tags);
        }

        $crtformdata->timecreated  = time();

        if (!empty($crtformdata->crtid)) {

            // Update existing criterion.
            // Handle any attachments in the text.
            $crtformdata->itemid  = $crtformdata->text['itemid'];
            $crtformdata->textformat  = $crtformdata->text['format'];
            $crtformdata->text = $crtformdata->text['text'];
            $crtformdata->text = file_save_draft_area_files($crtformdata->itemid, $context->id, 'mod_groupevaluation', 'criterion',
                                                             $crtformdata->crtid, array('subdirs' => true), $crtformdata->text);

            /*$fields = array('name', 'text', 'textformat', 'weight', 'default', 'timecreated', 'timemodified',
                            'createdby', 'modifiedby', 'special', 'position');*/

            // Update criterions table
            $fields = array('name', 'text', 'textformat', 'weight', 'timecreated','special', 'position', 'tags');
            $criterionrecord = new stdClass();
            $criterionrecord->id = $crtformdata->crtid;
            foreach ($fields as $f) {
                if (isset($crtformdata->$f)) {
                    $criterionrecord->$f = trim($crtformdata->$f);
                }
            }
            $result = $DB->update_record('groupevaluation_criterions', $criterionrecord);

//*

//*
            // Update tags table
            $tagrecord = new stdClass();
            $tagfields = array('name', 'text', 'textformat', 'weight', 'timecreated','special', 'position', 'tags');
            foreach ($tags as $tag) {
              foreach ($fields as $f) {
                  if (isset($crtformdata->$f)) {
                      $criterionrecord->$f = trim($crtformdata->$f);
                  }
              }
            }
            $resulttags = $DB->update_record('groupevaluation_tags', $criterionrecord);

        } else {
            // Create new criterion:
            // set the position to the end.
            $sql = 'SELECT MAX(position) as maxpos FROM {groupevaluation_criterions} '. // TODO Cambiar consulta -> esta no es
                   'WHERE groupevaluationid = '.$groupevaluation->id;
            if ($record = $DB->get_record_sql($sql)) {
                $crtformdata->position = $record->maxpos + 1;
            } else {
                $crtformdata->position = 1;
            }

            // Handle any attachments in the text.
            $crtformdata->itemid  = $crtformdata->text['itemid'];
            $crtformdata->textformat  = $crtformdata->text['format'];
            $crtformdata->text = $crtformdata->text['text'];
            $text            = file_save_draft_area_files($crtformdata->itemid, $context->id, 'mod_groupevaluation', 'criterion',
                                                             $crtformdata->crtid, array('subdirs' => true), $crtformdata->text);

            // Need to update any image text after the criterion is created, so create then update the text.
            /*$fields = array('groupevaluationid', 'name', 'text', 'textformat', 'weight', 'default', 'timecreated', 'timemodified',
                            'createdby', 'modifiedby', 'special', 'position');*/
            $fields = array('groupevaluationid', 'name', 'text', 'textformat', 'weight', 'timecreated','special', 'position','tags');
            $criterionrecord = new stdClass();
            foreach ($fields as $f) {
                if (isset($crtformdata->$f)) {
                    $criterionrecord->$f = trim($crtformdata->$f);
                }
            }

            $crtformdata->crtid = $DB->insert_record('groupevaluation_criterions', $criterionrecord);


            // TODO $result = $DB->set_field('groupevaluation_criterions', 'text', $text, array('id' => $crtformdata->crtid));
        }

        // UPDATE or INSERT rows for each of the criterion choices for this criterion.
        $cidx = 0;
        if (isset($criterion->choices) && !isset($crtformdata->makecopy)) {
            $oldcount = count($criterion->choices);
            $echoice = reset($criterion->choices);
            $ekey = key($criterion->choices);
        } else {
            $oldcount = 0;
        }

        $newchoices = explode("\n", $crtformdata->tags);
        $nidx = 0;
        $newcount = count($newchoices);

        while (($nidx < $newcount) && ($cidx < $oldcount)) {
            if ($newchoices[$nidx] != $echoice->text) {
                $newchoices[$nidx] = trim ($newchoices[$nidx]);
                // TODO $result = $DB->set_field('groupevaluation_quest_choice', 'text', $newchoices[$nidx], array('id' => $ekey));
                $r = preg_match_all("/^(\d{1,2})(=.*)$/", $newchoices[$nidx], $matches);
                // This choice has been attributed a "score value" OR this is a rate criterion type.
                if ($r) {
                    $newscore = $matches[1][0];
                    // TODO $result = $DB->set_field('groupevaluation_quest_choice', 'value', $newscore, array('id' => $ekey));
                } else {     // No score value for this choice.
                    // TODO $result = $DB->set_field('groupevaluation_quest_choice', 'value', null, array('id' => $ekey));
                }
            }
            $nidx++;
            $echoice = next($criterion->choices);
            $ekey = key($criterion->choices);
            $cidx++;
        }

        while ($nidx < $newcount) {
            // New choices...
            $choicerecord = new stdClass();
            $choicerecord->criterion_id = $crtformdata->crtid;
            $choicerecord->text = trim($newchoices[$nidx]);
            $r = preg_match_all("/^(\d{1,2})(=.*)$/", $choicerecord->text, $matches);
            // This choice has been attributed a "score value" OR this is a rate criterion type.
            if ($r) {
                $choicerecord->value = $matches[1][0];
            }
            // TODO $result = $DB->insert_record('groupevaluation_quest_choice', $choicerecord);
            $nidx++;
        }

        while ($cidx < $oldcount) {
            // TODO $result = $DB->delete_records('groupevaluation_quest_choice', array('id' => $ekey));
            $echoice = next($criterion->choices);
            $ekey = key($criterion->choices);
            $cidx++;
        }

        // Make these field values 'sticky' for further new criterions.
        if (!isset($crtformdata->required)) {
            $crtformdata->required = 'n';
        }
        // Need to reload criterions.
        $criterions = $DB->get_records('groupevaluation_criterions', array('groupevaluationid' => $groupevaluation->id), 'id');

        // Switch to main screen.
        $action = 'main';
        $reload = true;
    }

    // Log criterion created event. // TODO En questionnaire/classes/evet/"question_created"
    /*if (isset($crtformdata)) {
        $context = context_module::instance($cm->id);
        $params = array(
                        'context' => $context,
                        'courseid' => $course->id//,
                        //'other' => array('criterionname' => $criterions[$crtid])
        );
        $event = \mod_groupevaluation\event\criterion_created::create($params);
        $event->trigger();
    }*/

    $criterionsform->set_data($criterion);
}

// Reload the form data if called for...
if ($reload) {
    unset($criterionsform);
    if ($action == 'main') {
        $criterionsform = new groupevaluation_criterions_form('criterions.php', $movecrt);
        $sdata = clone($groupevaluation);
        $sdata->id = $cm->id;
        if (!empty($criterions)) {
            $pos = 1;
            foreach ($criterions as $crtidx => $criterion) {
                $sdata->{'pos_'.$crtidx} = $pos;
                $pos++;
            }
        }
        $criterionsform->set_data($sdata);
    } else if ($action == 'criterion') {

        $criterion = new stdClass();
        $criterion->cmid = $cm->id;
        $draftideditor = file_get_submitted_draft_itemid('criterion');
        $text = file_prepare_draft_area($draftideditor, $context->id, 'mod_groupevaluation', 'criterion',
                                           null, array('subdirs' => true), '');
        $criterion->text = array('text' => $text, 'format' => FORMAT_HTML, 'itemid' => $draftideditor);
        $criterion->numanswers = $numanswers;
        $criterionsform = new groupevaluation_edit_criterion_form('criterions.php');
        $criterionsform->set_data($criterion);
    }
}

// Print the page header.
if ($action == 'criterion') {
    if (isset($criterion->crtid)) {
        $strtitle = get_string('editcriterion', 'groupevaluation');
    } else {
        $strtitle = get_string('addnewcriterion', 'groupevaluation');
    }
} else {
    $strtitle = get_string('editsurvey', 'groupevaluation');
}


$PAGE->set_title($strtitle);
$PAGE->set_heading(format_string($course->fullname));
$PAGE->navbar->add($strtitle);

echo $OUTPUT->header();

echo $OUTPUT->heading(format_string($groupevaluation->name));

require('tabs.php');

if ($action == "confirmdelcriterion" || $action == "confirmdelcriterionparent") {

    $crtid = key($crtformdata->removebutton);
    $criterion = $groupevaluation->criterions[$crtid];
    $qtype = $criterion->type_id;

    // Count responses already saved for that criterion.
    $countresps = 0;
    if ($qtype != QUESSECTIONTEXT) {
        $responsetable = $DB->get_field('groupevaluation_criterion_type', 'response_table', array('typeid' => $qtype));
        if (!empty($responsetable)) {
            $countresps = $DB->count_records('groupevaluation_'.$responsetable, array('criterion_id' => $crtid));
        }
    }

    // Needed to print potential media in criterion text.

    // If criterion text is "empty", i.e. 2 non-breaking spaces were inserted, do not display any criterion text.

    if ($criterion->text == '<p>  </p>') {
        $criterion->text = '';
    }

    $qname = '';
    if ($criterion->name) {
        $qname = ' ('.$criterion->name.')';
    }

    $num = get_string('position', 'groupevaluation');
    $pos = $criterion->position.$qname;

    $msg = '<div class="warning centerpara"><p>'.get_string('confirmdelcriterion', 'groupevaluation', $pos).'</p>';
    if ($countresps !== 0) {
        $msg .= '<p>'.get_string('confirmdelcriterionresps', 'groupevaluation', $countresps).'</p>';
    }
    $msg .= '</div>';
    $msg .= '<div class = "qn-container">'.$num.' '.$pos.'<div class="qn-criterion">'.$criterion->text.'</div></div>';
    $args = "id={$groupevaluation->cm->id}";
    $urlno = new moodle_url("/mod/groupevaluation/criterions.php?{$args}");
    $args .= "&delcrt={$crtid}";
    $urlyes = new moodle_url("/mod/groupevaluation/criterions.php?{$args}");
    $buttonyes = new single_button($urlyes, get_string('yes'));
    $buttonno = new single_button($urlno, get_string('no'));
    if ($action == "confirmdelcriterionparent") {
        $strnum = get_string('position', 'groupevaluation');
        $crtid = key($crtformdata->removebutton);
        $msg .= '<div class="warning">'.get_string('confirmdelchildren', 'groupevaluation').'</div><br />';
        foreach ($haschildren as $child) {
            $childname = '';
            if ($child['name']) {
                $childname = ' ('.$child['name'].')';
            }
            $msg .= '<div class = "qn-container">'.$strnum.' '.$child['position'].$childname.'<span class="qdepend"><strong>'.
                            get_string('dependcriterion', 'groupevaluation').'</strong>'.
                            ' ('.$strnum.' '.$child['parentposition'].') '.
                            '&nbsp;:&nbsp;'.$child['parent'].'</span>'.
                            '<div class="qn-criterion">'.
                            $child['text'].
                            '</div></div>';
        }
    }
    echo $OUTPUT->confirm($msg, $buttonyes, $buttonno);

} else {
    $criterionsform->display();
}
echo '<script type="text/javascript" src="'.$CFG->wwwroot.'/mod/groupevaluation/javascript/criterions_form.js" type="text/javascript"></script>';
//TODO Borrar -> no hace falta javascript

echo $OUTPUT->footer();
