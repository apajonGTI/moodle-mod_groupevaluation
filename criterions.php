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

/*optional_param($parname, $saved, $type)*/
$id             = required_param('id', PARAM_INT);                // Course module ID
$action         = optional_param('action', 'main', PARAM_ALPHA);  // Screen.
$crtid          = optional_param('crtid', 0, PARAM_INT);          // criterion id.
$delcrt         = optional_param('delcrt', 0, PARAM_INT);         // criterion id to delete
$reweight       = optional_param('reweight', 0, PARAM_INT);         // TODO Borrar
$lang           = optional_param('lang', 'en', PARAM_ALPHA);         //
$popup          = optional_param('popup', 0, PARAM_INT);         //

$showpopup = true;

//$selectweight   = optional_param('selectweight', 0);   //

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


$groupevaluationid = $groupevaluation->id;
$select = 'groupevaluationid = '.$groupevaluationid;
$criterions = $DB->get_records_select('groupevaluation_criterions', $select, null, 'position ASC');

$select = 'groupevaluationid = '.$groupevaluationid.' OR (groupevaluationid IS NULL AND saved = 1)';
$allcriterions = $DB->get_records_select('groupevaluation_criterions', $select, null, 'position ASC');
// ******************** DELETE CRITERION ****************************************
// Delete criterion button has been pressed in criterions_form AND deletion has been confirmed on the confirmation page.
if ($delcrt) {
    $crtid = $delcrt;
    $criterion = $DB->get_record('groupevaluation_criterions', array('id' => $crtid));
    if (!$criterion->defaultcriterion) {

      if (isset($criterion->defaultcriterion)) {
        // Update the weight values
        $DB->set_field('groupevaluation_criterions', 'weight', 0, array('id' => $crtid));
        groupevaluation_recalculate_weights($criterions, 0, $crtid);

        // Move the criterion to the last position
        move_criterion($criterions, $crtid, count($criterions));
      }

      $DB->delete_records('groupevaluation_criterions', array('id' => $crtid));
      $DB->delete_records('groupevaluation_tags', array('criterionid' => $crtid));

      // Log criterion deleted event.
      /*$context = context_module::instance($cm->id);
      $crtname = $criterions[$crtid]->name;
      $params = array(
                      'context' => $context,
                      'courseid' => $groupevaluation->courseid,
                      'other' => array('criterionname' => $crtname)
      );
      $event = \mod_groupevaluation\event\criterion_deleted::create($params);
      $event->trigger();*/
    }
    redirect($CFG->wwwroot.'/mod/groupevaluation/criterions.php?id='.$cm->id);
    $reload = true;
}

if ($reweight) {
    $selectweight = $_POST['selectweight'];
    $sumweight = 0;
    foreach ($selectweight as $crtid => $weightvalue) {
      $sumweight = $sumweight + $weightvalue;
    }
    $sumweight = round($sumweight);

    foreach ($selectweight as $crtid => $weightvalue) {
      $newweight = $weightvalue;
      if ($sumweight > 0) {
        $normalizedweight = $weightvalue / $sumweight;
        $newweight = $normalizedweight * 100.00;
      } else if (count($selectweight) > 0) {
        $newweight = ($weightvalue / count($selectweight));
      }

      $DB->set_field('groupevaluation_criterions', 'weight', $newweight, array('id' => $crtid));
    }
    redirect($CFG->wwwroot.'/mod/groupevaluation/criterions.php?id='.$cm->id);
}


// ******************** MAIN VIEW ****************************************
if ($action == 'main') {
    $criterionsform = new groupevaluation_criterions_form('criterions.php');
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

        if (isset($exformdata->savedbutton)) {
            $crtformdata->savedbutton = $exformdata->savedbutton;
        } else if (isset($exformdata->moveherebutton)) {
            $crtformdata->moveherebutton = $exformdata->moveherebutton;
        } else if (isset($exformdata->editbutton)) {
            $crtformdata->editbutton = $exformdata->editbutton;
        } else if (isset($exformdata->removebutton)) {
            $crtformdata->removebutton = $exformdata->removebutton;
        } else if (isset($exformdata->addsavedbutton)) {
            $crtformdata->addsavedbutton = $exformdata->addsavedbutton;
        } else if (isset($exformdata->specialbutton)) {
            $crtformdata->specialbutton = $exformdata->specialbutton;
        } else if (isset($exformdata->hiddenweight)) {
            $crtformdata->hiddenweight = $exformdata->hiddenweight;
        }
        if (isset($exformdata->movecrt)) {
            $crtformdata->movecrt = $exformdata->movecrt;
        }

        // Insert a section break.
        if (isset($crtformdata->removebutton)) {
            // Need to use the key, since IE returns the image position as the value rather than the specified
            // value in the <input> tag.
            $crtid = key($crtformdata->removebutton);

            $action = "confirmdelcriterion";
            $showpopup = false;
        } else if (isset($crtformdata->editbutton)) {
            // Switch to edit criterion screen.
            $action = 'criterion';
            // Need to use the key, since IE returns the image position as the value rather than the specified
            // value in the <input> tag.
            $crtid = key($crtformdata->editbutton);
            $reload = true;
            $showpopup = false;

        } else if (isset($crtformdata->specialbutton)) {
            // Need to use the key, since IE returns the image position as the value rather than the specified
            // value in the <input> tag.

            $crtid = key($crtformdata->specialbutton);
            if ($criterions[$crtid]->special == '1') {
                $DB->set_field('groupevaluation_criterions', 'special', 0, array('id' => $crtid, 'groupevaluationid' => $groupevaluationid));

            } else {
                $DB->set_field('groupevaluation_criterions', 'special', 1, array('id' => $crtid, 'groupevaluationid' => $groupevaluationid));
            }

            $reload = true;
            $showpopup = false;

        } else if (isset($crtformdata->moveherebutton)) {
            // Need to use the key, since IE returns the image position as the value rather than the specified
            // value in the <input> tag.

            // No need to move criterion if new position = old position!
            $crtpos = key($crtformdata->moveherebutton);
            if ($crtformdata->movecrt != $crtpos) {
                move_criterion($criterions, $crtformdata->movecrt, $crtpos);
            }
            // Nothing I do will seem to reload the form with new data, except for moving away from the page, so...
            redirect($CFG->wwwroot.'/mod/groupevaluation/criterions.php?id='.$cm->id.'#id_managecrt');
            $reload = true;
            $showpopup = false;
        } else if (isset($crtformdata->addsavedbutton)) {
            $crtid = key($crtformdata->addsavedbutton);

            $criterionrecord = clone($allcriterions[$crtid]);
            $criterionrecord->groupevaluationid = $groupevaluationid;
            $criterionrecord->defaultcriterion = 0;
            $criterionrecord->weight = 0;
            $criterionrecord->timecreated = time();
            $criterionrecord->timemodified = $criterionrecord->timecreated;
            $criterionrecord->createdby = $USER->id;
            $criterionrecord->position = count($criterions) + 1;

            // Update the weights values of the old criterions
            groupevaluation_recalculate_weights($criterions, 0);
            $newcrtid = $DB->insert_record('groupevaluation_criterions', $criterionrecord);
            $tags = $DB->get_records('groupevaluation_tags', array('criterionid' => $crtid));

            // Create new tags for this criterion:
            foreach ($tags as $tag) {
              $tagrecord = clone($tag);
              $tagrecord->criterionid = $newcrtid;
              $tagrecord->timemodified = $criterionrecord->timecreated;

              $resulttag = $DB->insert_record('groupevaluation_tags', $tagrecord);
            }
            redirect($CFG->wwwroot.'/mod/groupevaluation/criterions.php?id='.$cm->id);
            $reload = true;

        } else if (isset($crtformdata->selectweight)) {
            $selectweight = $crtformdata->selectweight;
            $sumweight = 0.00;

            foreach ($crtformdata->selectweight as $crtid => $weightvalue) {
              $sumweight = $sumweight + $weightvalue;
            }
            $warning = false;
            foreach ($crtformdata->selectweight as $crtid => $weightvalue) {
              $newweight = $weightvalue;
              $selectweight = $crtformdata->selectweight;
              if ($sumweight > 0) {
                $normalizedweight = $weightvalue / $sumweight;
                $newweight = $normalizedweight * 100.00;
              } else if (count($selectweight) > 0) {
                $newweight = ($weightvalue / count($selectweight));
              }
              if ($weightvalue != round($newweight)) {
                $warning = true;
              }
            }

            //if (round($sumweight) == 100) {
            if ($warning) {
              $action = "warningweights";
            } else {
              foreach ($crtformdata->selectweight as $crtid => $weightvalue) {
                $DB->set_field('groupevaluation_criterions', 'weight', $weightvalue, array('id' => $crtid));
              }
            }

        } else if (isset($crtformdata->addcrtbutton)) {

            // Switch to edit criterion screen.
            $action = 'criterion';
            $crtid = 0;
            $reload = true;

        } else if (isset($crtformdata->addanswer)) {

            // Switch to edit criterion screen.
            /*$action = 'criterion';
            $crtid = 0;
            $reload = true;*/
            print_error('moduleinstancedoesnotexist');

        }
        if (isset($crtformdata->savedbutton)) {
          $showpopup = false;
        }
    }

// ******************** ACTION CRITERION ****************************************
} else if ($action == 'criterion') {
    if ($crtid != 0) {
        //$criterion = clone($criterions[$crtid]);
        $criterion = clone($allcriterions[$crtid]);
        $criterion->crtid = $criterion->id;
        $criterion->id = $cm->id;
        $draftideditor = file_get_submitted_draft_itemid('criterion');
        $text = file_prepare_draft_area($draftideditor, $context->id, 'mod_groupevaluation', 'criterion',
                                           $criterion->crtid, array('subdirs' => true), $criterion->text);
        $criterion->text = array('text' => $text, 'format' => FORMAT_HTML, 'itemid' => $draftideditor);

        $tags = $DB->get_records_select('groupevaluation_tags', 'criterionid = '.$criterion->crtid, null, 'position');
        $options = array('wrap' => 'virtual', 'class' => 'qopts');
        //for ($t = 1; $t <= count($tags); $t++) {
        $t = 1;
        foreach ($tags as $tag) {
          $tagname = 'tag_'.$t;
          $tagvaluename = 'tagvalue_'.$t;
          $tagpositioname = 'tagposition_'.$t;
          $criterion->$tagname = $tag->text;
          $criterion->$tagvaluename = $tag->value;
          $criterion->$tagpositioname = $tag->position;
          $t++;
        }

        $criterion->numanswers = count($tags);

    } else {
        $criterion = new stdClass();
        $criterion->groupevaluationid = $groupevaluationid;
        $criterion->id = $cm->id;
        $draftideditor = file_get_submitted_draft_itemid('criterion');
        $text = file_prepare_draft_area($draftideditor, $context->id, 'mod_groupevaluation', 'criterion',
                                           null, array('subdirs' => true), '');
        $criterion->text = array('text' => $text, 'format' => FORMAT_HTML, 'itemid' => $draftideditor);
        $criterion->numanswers = 1;
    }
    /*AQUI*/

    $criterionsform = new groupevaluation_edit_criterion_form('criterions.php');
    $criterionsform->set_data($criterion);
    if ($criterionsform->is_cancelled()) {
        // Switch to main screen.
        $action = 'main';
        $reload = true;

    } else if ($crtformdata = $criterionsform->get_data()) {

        // For the texarea inputs
        $exformdata = data_submitted();

        // Saving criterion data.
        if (isset($exformdata->makecopy)) {
            $crtformdata->crtid = 0;
            $crtformdata->saved = 0;
            $redirect = true; // TODO Borrar
        }

        if (!isset($crtformdata->weight)) {
          $crtformdata->weight = trim($exformdata->weight);
        }
        $numanswers = $crtformdata->numanswers;
        for ($i = 1; $i <= $numanswers; $i++) {
          // Trim to eliminate potential trailing carriage return.
          $tagname = 'tag_'.$i;
          $tagvaluename = 'tagvalue_'.$i;
          $tagpositioname = 'tagposition_'.$i;
          $crtformdata->$tagname = trim($exformdata->$tagname);
          $crtformdata->$tagvaluename = trim($exformdata->$tagvaluename);
          $crtformdata->$tagpositioname = trim($exformdata->$tagpositioname);
          //$crtformdata->$tagname = trim($_POST[$tagname]);
        }

        $crtformdata->timecreated  = time();
        $crtformdata->timemodified = time();
        $crtformdata->createdby  = $USER->id;
        $crtformdata->modifiedby = $USER->id;

        if (!empty($crtformdata->crtid)) {

            // Update existing criterion.
            // Handle any attachments in the text.
            $crtformdata->itemid  = $crtformdata->text['itemid'];
            $crtformdata->textformat  = $crtformdata->text['format'];
            $crtformdata->text = $crtformdata->text['text'];
            $crtformdata->text = file_save_draft_area_files($crtformdata->itemid, $context->id, 'mod_groupevaluation', 'criterion',
                                                             $crtformdata->crtid, array('subdirs' => true), $crtformdata->text);

            // Update criterions table
            $fields = array('name', 'text', 'textformat', 'weight', 'saved', 'timemodified',
                            'modifiedby','special', 'position', 'required');
            $criterionrecord = new stdClass();
            $criterionrecord->id = $crtformdata->crtid;
            foreach ($fields as $f) {
                if (isset($crtformdata->$f)) {
                    $criterionrecord->$f = trim($crtformdata->$f);
                }
            }

            if (isset($criterionrecord->weight)) {
              // Update the weights values of the old criterions
              $sumcriterionsweights = groupevaluation_recalculate_weights($criterions, $criterionrecord->weight, $criterionrecord->id);
              if ($sumcriterionsweights == 0 && $criterionrecord->weight > 0) {
                $criterionrecord->weight = 100;
              }
            }

            $result = $DB->update_record('groupevaluation_criterions', $criterionrecord);

            // Update tags already exists
            $tags = $DB->get_records('groupevaluation_tags', array('criterionid' => $crtformdata->crtid));
            $oldtag = 1;

            if ($numanswers >= count($tags)) {
              foreach ($tags as $tag) {
                $tagname = 'tag_'.$oldtag;
                $tagvaluename = 'tagvalue_'.$oldtag;
                $tagpositionname = 'tagposition_'.$oldtag;
                $tag->text = $crtformdata->$tagname;
                $tag->value = $crtformdata->$tagvaluename;
                $tag->position = $crtformdata->$tagpositionname;
                $tag->timemodified = trim($crtformdata->timemodified);

                $resulttag = $DB->update_record('groupevaluation_tags', $tag);
                $oldtag++;
              }
            } else {
              /*$limit = count($tags) - $numanswers;
              $oldidtags = $DB->get_records('groupevaluation_tags', array('criterionid' => $crtformdata->crtid), 'position', id, null, $limit);
              echo $oldtags;*/
              $DB->delete_records('groupevaluation_tags', array('criterionid' => $crtformdata->crtid));
            }

            // Create the entries for the new tags
            for ($i = $oldtag; $i <= $numanswers; $i++) {
              $tagrecord = new stdClass();
              $tagrecord->criterionid = $crtformdata->crtid;
              $tagname = 'tag_'.$i;
              $tagvaluename = 'tagvalue_'.$i;
              $tagpositionname = 'tagposition_'.$i;
              $tagrecord->text = $crtformdata->$tagname;
              $tagrecord->value = $crtformdata->$tagvaluename;
              $tagrecord->position = $crtformdata->$tagpositionname;
              $tagrecord->timemodified = trim($crtformdata->timemodified);

              $resulttag = $DB->insert_record('groupevaluation_tags', $tagrecord);
            }

        } else {
            // Create new criterion:
            // set the position to the end.
            $sql = 'SELECT MAX(position) as maxpos FROM {groupevaluation_criterions} '.
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
            $fields = array('groupevaluationid', 'name', 'text', 'textformat', 'weight', 'saved',
                      'timecreated','timemodified','createdby', 'modifiedby','special', 'position', 'required');
            $criterionrecord = new stdClass();
            foreach ($fields as $f) {
             if (isset($crtformdata->$f)) {
                 $criterionrecord->$f = trim($crtformdata->$f);
             }
            }

            // Update the weights values of the old criterions
            $sumcriterionsweights = groupevaluation_recalculate_weights($criterions, $criterionrecord->weight);
            if ($sumcriterionsweights == 0 && $criterionrecord->weight > 0) {
              $criterionrecord->weight = 100;
            }
            $crtformdata->crtid = $DB->insert_record('groupevaluation_criterions', $criterionrecord);



            // Create new tags for this criterion:
            for ($i = 1; $i <= $numanswers; $i++) {
              $tagrecord = new stdClass();
              $tagrecord->criterionid = $crtformdata->crtid;
              $tagname = 'tag_'.$i;
              $tagvaluename = 'tagvalue_'.$i;
              $tagpositionname = 'tagposition_'.$i;
              $tagrecord->text = $crtformdata->$tagname;
              $tagrecord->value = $crtformdata->$tagvaluename;
              $tagrecord->position = $crtformdata->$tagpositionname;
              $tagrecord->timemodified = trim($crtformdata->timemodified);

              $resulttag = $DB->insert_record('groupevaluation_tags', $tagrecord);
            }

            // Save criterion in the system if it is the case
            if ($crtformdata->saved) {
              $fields = array('name', 'text', 'textformat', 'saved','timecreated','timemodified','createdby', 'modifiedby','special');
              $criterionrecord = new stdClass();
              foreach ($fields as $f) {
               if (isset($crtformdata->$f)) {
                   $criterionrecord->$f = trim($crtformdata->$f);
               }
              }
              $savedcrtid = $DB->insert_record('groupevaluation_criterions', $criterionrecord);

              // Create new tags for this criterion:
              for ($i = 1; $i <= $numanswers; $i++) {
                $tagrecord = new stdClass();
                $tagrecord->criterionid = $savedcrtid;
                $tagname = 'tag_'.$i;
                $tagvaluename = 'tagvalue_'.$i;
                $tagpositionname = 'tagposition_'.$i;
                $tagrecord->text = $crtformdata->$tagname;
                $tagrecord->value = $crtformdata->$tagvaluename;
                $tagrecord->position = $crtformdata->$tagpositionname;
                $tagrecord->timemodified = trim($crtformdata->timemodified);

                $resulttag = $DB->insert_record('groupevaluation_tags', $tagrecord);
              }
            }

        }

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
        $criterionsform = new groupevaluation_criterions_form('criterions.php');
        $sdata = clone($groupevaluation);
        $sdata->id = $cm->id;
        if (!empty($criterions)) {// TODO Borrar
            $pos = 1;
            foreach ($criterions as $crtidx => $criterion) {
                $sdata->{'pos_'.$crtidx} = $pos;
                $pos++;
            }
        }
        $criterionsform->set_data($sdata);
    } else if ($action == 'criterion') {

        if ($crtid != 0) {
            //$criterion = clone($criterions[$crtid]);
            $criterion = clone($allcriterions[$crtid]);
            $criterion->crtid = $criterion->id;
            $criterion->id = $cm->id;
            $draftideditor = file_get_submitted_draft_itemid('criterion');
            $text = file_prepare_draft_area($draftideditor, $context->id, 'mod_groupevaluation', 'criterion',
                                               $criterion->crtid, array('subdirs' => true), $criterion->text);
            $criterion->text = array('text' => $text, 'format' => FORMAT_HTML, 'itemid' => $draftideditor);

            $tags = $DB->get_records_select('groupevaluation_tags', 'criterionid = '. $criterion->crtid, null, 'position');

            //for ($t = 1; $t <= count($tags); $t++) {
            $t = 1;
            foreach ($tags as $tag) {
              $tagname = 'tag_'.$t;
              $tagvaluename = 'tagvalue_'.$t;
              $tagpositionname = 'tagposition_'.$t;
              $criterion->$tagname = $tag->text;
              $criterion->$tagvaluename = $tag->value;
              $criterion->$tagpositionname = $tag->position;
              $t++;
            }
            $criterion->numanswers = count($tags);

        } else {
            $criterion = new stdClass();
            $criterion->groupevaluationid = $groupevaluationid;
            $criterion->id = $cm->id;
            $draftideditor = file_get_submitted_draft_itemid('criterion');
            $text = file_prepare_draft_area($draftideditor, $context->id, 'mod_groupevaluation', 'criterion',
                                               null, array('subdirs' => true), '');
            $criterion->text = array('text' => $text, 'format' => FORMAT_HTML, 'itemid' => $draftideditor);
            $criterion->numanswers = 1;
        }
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

if ($action == 'criterion') {
  $editurl = new moodle_url('/mod/groupevaluation/criterions.php');
  $editurl->params(array('id' => $cm->id));
  $PAGE->navbar->add(get_string('criterions', 'groupevaluation'), $editurl);
}

$PAGE->navbar->add($strtitle);

echo $OUTPUT->header();

echo $OUTPUT->heading(format_string($groupevaluation->name));

require('tabs.php');

if ($action == "confirmdelcriterion") {
    $savedcriterion = false;
    $defaultcriterion = false;

    $crtid = key($crtformdata->removebutton);
    if (isset($criterions[$crtid])) {
      $criterion = $criterions[$crtid];
    } else {
      $criterion = $allcriterions[$crtid];
      $savedcriterion = true;
      if ($criterion->defaultcriterion) {
        $defaultcriterion = true;
      }
    }

    // Needed to print potential media in criterion text.

    // If criterion text is "empty", i.e. 2 non-breaking spaces were inserted, do not display any criterion text.

    if ($criterion->text == '<p>  </p>') {
        $criterion->text = '';
    }

    if ($defaultcriterion) {
      $pos = '-';
      $msg = '<div class="warning centerpara"><p>'.get_string('confirmdeldefaultcriterion', 'groupevaluation').'</p></div>';
    } else if ($savedcriterion) {
      $pos = '-';
      $msg = '<div class="warning centerpara"><p>'.get_string('confirmdelcriterionsaved', 'groupevaluation').'</p></div>';
    } else {
      $pos = $criterion->position;
      $msg = '<div class="warning centerpara"><p>'.get_string('confirmdelcriterion', 'groupevaluation', $pos).'</p></div>';
    }

    $msg .= '<div class = "qn-container">';
    $msg .= '<div class="crtname">'.$criterion->name.'</div>';
    $msg .= '<div class="qn-info"><h2 class="qn-number">'.$pos.'</h2></div>';
    $msg .= '<div class="qn-question"><p>'.$criterion->text.'</p></div>';
    $msg .= '</div>';

    $args = "id={$cm->id}";
    $urlno = new moodle_url("/mod/groupevaluation/criterions.php?{$args}");
    $args .= "&delcrt={$crtid}";
    $urlyes = new moodle_url("/mod/groupevaluation/criterions.php?{$args}");

    if ($defaultcriterion) {
      echo $OUTPUT->container_start('important', 'notice');
      echo $msg;
      echo '<div><a href="'.$urlno.'" class="btn btn-default btn-lg" role="button">'.get_string('continue', 'groupevaluation').'</a></div>';
      echo $OUTPUT->container_end();
    } else {
      $buttonyes = new single_button($urlyes, get_string('yes'));
      $buttonno = new single_button($urlno, get_string('no'));
      echo $OUTPUT->confirm($msg, $buttonyes, $buttonno);
    }

} else if ($action == "warningweights") {
    unset($criterionsform);
    $criterionsform = new groupevaluation_confirm_reweight_form('criterions.php');
    $sdata = clone($groupevaluation);
    $sdata->id = $cm->id;

    $criterionsform->set_data($sdata);

    if ($criterionsform->is_cancelled()) {
        // Switch to main screen.
        redirect($CFG->wwwroot.'/mod/groupevaluation/criterions.php?id='.$cm->id);
    }

    /*if ($crtformdata = $criterionsform->get_data()) {
        if ($crtformdata->reweight) {

            foreach ($selectweight as $crtid => $weightvalue) {
              $newweight = $weightvalue;
              if ($sumweight > 0) {
                $normalizedweight = $weightvalue / $sumweight;
                $newweight = $normalizedweight * 100.00;
              } else if (count($selectweight) > 0) {
                $newweight = ($weightvalue / count($selectweight));
              }

              $DB->set_field('groupevaluation_criterions', 'weight', $newweight, array('id' => $crtid));
            }
        }
        redirect($CFG->wwwroot.'/mod/groupevaluation/criterions.php?id='.$cm->id);
    }*/

    $criterionsform->display();
} else {
    // Check if some student has already responded
    $surveys = $DB->get_records('groupevaluation_surveys', array('groupevaluationid' => $groupevaluationid));
    $surveycomplete = false;

    foreach ($surveys as $survey) {
      if ($survey->status != groupevaluation_INCOMPLETE) {
        $surveycomplete = true;
      }
    }
    $strnoteditable = get_string('noteditable', 'groupevaluation');
    if (groupevaluation_is_open($groupevaluation->timeopen)) {
      echo $OUTPUT->notification($strnoteditable.' '.get_string('isopen', 'groupevaluation', userdate($groupevaluation->timeopen)));
    } else if (groupevaluation_is_closed($groupevaluation->timeclose)) {
      echo $OUTPUT->notification($strnoteditable.' '.get_string('isclosed', 'groupevaluation', userdate($groupevaluation->timeclose)));
    } else if ($surveycomplete) {
      echo $OUTPUT->notification($strnoteditable.' '.get_string('alreadyanswered', 'groupevaluation'));
    } else {
      $criterionsform->display();
    }
}
echo '<script type="text/javascript" src="'.$CFG->wwwroot.'/mod/groupevaluation/javascript/criterions_form.js"></script>';

if ($popup && $showpopup) {
  echo '<script type="text/javascript">popupSavedCriterions();</script>';
}

//TODO (sortable)
echo '<script type="text/javascript" src="'.$CFG->wwwroot.'/mod/groupevaluation/javascript/jquery-1.js"></script>';
echo '<script type="text/javascript" src="'.$CFG->wwwroot.'/mod/groupevaluation/javascript/jquery-ui.js"></script>';
echo '<script type="text/javascript">
        $(document).ready(function(){
            $(".list").sortable({
                connectWith: ".list",
                //handle: ".ui-sortable .qoptcontainer",
                opacity: 0.5,
                tolerance: "pointer",
                //cancel: ".qopts",
                placeholder: "place_holder_qoptcontainer",
                helper: function(event, el) {
                    var myclone = el.clone();
                    $("body").append(myclone);
                    return myclone;
                },

                stop:	function( event, ui ) {
                  var tagpositions = document.getElementsByClassName("tagposition");
                  for (i = 0; i < tagpositions.length; i++) {
                      tagpositions[i].setAttribute("value", i+1);
                  }
                  var spanpositions = document.getElementsByClassName("spanposition");
                  for (i = 0; i < spanpositions.length; i++) {
                      spanpositions[i].innerHTML = i+1;
                  }
                }
            });
            $(".qcontainer").sortable({
                connectWith: ".qcontainer",
                opacity: 0.5,
                tolerance: "pointer",
                placeholder: "place_holder_element",
                helper: function(event, el) {
                    var myclone = el.clone();
                    $("body").append(myclone);
                    return myclone;
                },

                stop:	function( event, ui ) {
                  id = ui.item.attr("id").split("element_")[1];
                  var newposition = ui.item.index()+1;

                  var input = document.createElement("input");
                  input.setAttribute("type", "hidden");
                  input.setAttribute("name", "movecrt");
                  input.setAttribute("value", id);

                  var movehere = document.createElement("input");
                  movehere.setAttribute("id", "moveherebutton_"+newposition);
                  movehere.setAttribute("type", "image");
                  movehere.setAttribute("name", "moveherebutton["+newposition+"]");
                  movehere.setAttribute("value", newposition);

                  //append to form
                  document.getElementById("criterionsform").appendChild(input);
                  document.getElementById("criterionsform").appendChild(movehere);

                  document.getElementById("moveherebutton_" + newposition).click();
                }
            });
        });
    </script>';
//sorteable
echo $OUTPUT->footer();
