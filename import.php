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
 * Defines the inport criterions form.
 *
 * @package   mod_groupevaluation
 * @category  grade
 * @copyright Jose Vilas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


//require_once(dirname(__FILE__) . '/../config.php');
require_once("../../config.php");
require_once($CFG->dirroot . '/mod/groupevaluation/import_form.php');
require_once($CFG->dirroot.'/mod/groupevaluation/locallib.php');


$id             = required_param('id', PARAM_INT);                // Course module ID


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

$url = new moodle_url($CFG->wwwroot.'/mod/groupevaluation/import.php');
$url->param('id', $id);
$PAGE->set_url($url);

$PAGE->set_context($context);

if (!(has_capability('mod/groupevaluation:editsurvey', $context))) {
    print_error('nopermissions', 'error', 'mod:groupevaluation:edit');
}

$import_form = new criterion_import_form($url);

if ($import_form->is_cancelled()){
    redirect($thispageurl);
}
//==========
// PAGE HEADER
//==========
$PAGE->set_title(get_string('importcriterions', 'groupevaluation'));
$PAGE->set_heading($course->fullname);

$strimportcriterions = get_string('importcriterions', 'groupevaluation');
//$PAGE->navbar->add($strimportcriterions);

echo $OUTPUT->header();

// file upload form sumitted
if ($form = $import_form->get_data()) {

    // file checks out ok
    $fileisgood = false;

    // work out if this is an uploaded file
    // or one from the filesarea.
    $realfilename = $import_form->get_new_filename('newfile');

    $importfile = "{$CFG->tempdir}/criterionimport/{$realfilename}";
    make_temp_directory('criterionimport');
    if (!$result = $import_form->save_file('newfile', $importfile, true)) {
        throw new moodle_exception('uploadproblem');
    }

    if (file_exists($importfile)) {
      $xml = simplexml_load_file($importfile);

      if (!$xml) {
        print_error('cannotreadfile', '', $importfile);
      } else {
        //echo print_r($xml);
        if (save_criterions($xml)) {
          echo $OUTPUT->container(get_string('importcriteriossuccess', 'groupevaluation'), 'important', 'notice');
        } else {
          echo $OUTPUT->notification(get_string('importcriteriosfailed', 'groupevaluation'));
        }
      }
    } else {
      print_error('cannotopenfile', '', $importfile);
    }

    $urlview = new moodle_url("/mod/groupevaluation/view.php?id={$cm->id}");
    $continue = get_string('continue', 'groupevaluation');
    echo '<div><a href="'.$urlview.'" class="btn btn-default btn-lg" role="button">'.$continue.'</a></div>';
    echo $OUTPUT->footer();
    exit;
}

echo $OUTPUT->heading_with_help($strimportcriterions, 'importcriterions', 'groupevaluation');

/// Print upload form
$import_form->display();
echo $OUTPUT->footer();

function save_criterions($criterions){
  global $DB, $USER;
  //$criterions = new SimpleXMLElement($xml);
  $timemodified = time();
  $good = true;

  foreach ($criterions->criterion as $criterion) {
    echo $criterion->name.' (Text: '.$criterion->text.')';

    // Save criterion
    $criterionrecord = new stdClass();
    $criterionrecord->name = $criterion->name->__toString();
    $criterionrecord->text = $criterion->text->__toString();
    $criterionrecord->saved = 1;
    $criterionrecord->timecreated = $timemodified;
    $criterionrecord->createdby = $USER->id;

    if (!$savedcrtid = $DB->insert_record('groupevaluation_criterions', $criterionrecord)) {
      $good = false;
    }

    foreach ($criterion->answers->answer as $answer) {
      echo '<br/>'.$answer->position.' - '.$answer->text.' ('.$answer->value.'%)';

      // Save possible answers for this criterion
      $tagrecord = new stdClass();
      $tagrecord->criterionid = $savedcrtid;
      //SimpleXMLElement->__toString
      $tagrecord->text = $answer->text->__toString();
      $tagrecord->value = $answer->value->__toString();
      $tagrecord->position = $answer->position->__toString();
      $tagrecord->timemodified = $timemodified;

      if (!$resulttag = $DB->insert_record('groupevaluation_tags', $tagrecord)) {
        $good = false;
      }
    }
    echo '<br/><br/>';
  }
  return $good;
}
