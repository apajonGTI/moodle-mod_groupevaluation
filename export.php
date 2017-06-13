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
 * Defines the export criterions form.
 *
 * @package   mod_groupevaluation
 * @category  grade
 * @copyright Jose Vilas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once("../../config.php");
require_once($CFG->dirroot . '/mod/groupevaluation/export_form.php');
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

$url = new moodle_url($CFG->wwwroot.'/mod/groupevaluation/export.php');
$url->param('id', $id);
$PAGE->set_url($url);

// get display strings
$strexportcriterions = get_string('exportcriterions', 'groupevaluation');

$PAGE->set_context($context);

if (!(has_capability('mod/groupevaluation:editsurvey', $context))) {
    print_error('nopermissions', 'error', 'mod:groupevaluation:edit');
}

$export_form = new criterion_export_form($url);

/// Header
$PAGE->set_title($strexportcriterions);
$PAGE->set_heading($course->fullname);
//$PAGE->navbar->add($strexportcriterions);

echo $OUTPUT->header();

if ($from_form = $export_form->get_data()) {

    $type = $from_form->type;
    if (isset($from_form->language)) {
      $language = $from_form->language;
    } else {
      $language = current_language();
    }

    $base = clean_filename(get_string('criterions', 'groupevaluation'));
    $dateformat = str_replace(' ', '_', get_string('exportnameformat', 'groupevaluation'));
    $timestamp = clean_filename(userdate(time(), $dateformat, 99, false));
    $shortname = clean_filename($course->shortname);
    if ($shortname == '' || $shortname == '_' ) {
        $shortname = $course->id;
    }

    $extension = '.xml';
    $filename = "{$base}-{$type}-{$shortname}-{$timestamp}".$extension;

    /*$exportfile = "{$CFG->tempdir}/criterionexport/";
    make_temp_directory('criterionexport');*/

    $fs = get_file_storage();

    // Prepare file record object
    $fileinfo = array(
        'contextid' => $context->id, // ID of context
        'component' => 'mod_groupevaluation',     // usually = table name
        'filearea' => 'export',     // usually = table name
        'itemid' => 0,               // usually = ID of row in table
        'filepath' => '/'.'criterions/',           // any path beginning and ending in /
        'filename' => $filename); // any filename

    // Create export file
    $fs->create_file_from_string($fileinfo, generate_xml($type, $language, $groupevaluation));

    $export_url = moodle_url::make_pluginfile_url($fileinfo['contextid'], $fileinfo['component'],
      $fileinfo['filearea'], $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename'], true);

    echo $OUTPUT->box_start();
    echo get_string('yourfileshoulddownload', 'groupevaluation', $export_url->out());
    echo $OUTPUT->box_end();

    // Don't allow force download for behat site, as pop-up can't be handled by selenium.
    if (!defined('BEHAT_SITE_RUNNING')) {
        $PAGE->requires->js_function_call('document.location.replace', array($export_url->out(false)), false, 1);
    }

    $urlview = new moodle_url("/mod/groupevaluation/view.php?id={$cm->id}");
    $continue = get_string('continue', 'groupevaluation');

    echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
    echo '<div><br/><a href="'.$urlview.'" class="btn btn-default btn-lg" role="button">'.$continue.'</a></div>';
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
    exit;
}

/// Display export form
echo $OUTPUT->heading_with_help($strexportcriterions, 'exportcriterions', 'groupevaluation');
$export_form->display();

echo '<script type="text/javascript">';
echo 'function selectTypeChanged() {
        if (document.getElementById("id_type").value == "default" ||
            document.getElementById("id_type").value == "all") {
          document.getElementById("id_select_language").setAttribute("style", "visibility:visible");
        } else {
          document.getElementById("id_select_language").setAttribute("style", "visibility:hidden");
        }
      }';
echo '</script>';


echo $OUTPUT->footer();

/*
*
*
*/

function generate_xml($type, $language, $groupevaluation) { // groupevaluation - saved - default - all
  global $DB;

  if ($type == 'groupevaluation') {
    $select = "groupevaluationid = $groupevaluation->id";
  } else if ($type == 'saved') {
    $select = "groupevaluationid IS NULL AND saved = 1 AND defaultcriterion = 0";
  } else if ($type == 'default') {
    $select = 'groupevaluationid IS NULL AND saved = 1 AND defaultcriterion = 1 AND languagecode = "'.$language.'"';
  } else if ($type == 'all') {
    $select = "(groupevaluationid = $groupevaluation->id) OR ";
    $select .= '(groupevaluationid IS NULL AND saved = 1 AND defaultcriterion = 0) OR ';
    $select .= '(groupevaluationid IS NULL AND saved = 1 AND defaultcriterion = 1 AND languagecode = "'.$language.'")';

  }
  $criterions = $DB->get_records_select('groupevaluation_criterions', $select, null);

  $xml = '<?xml version="1.0" ?>' . "\n";
  $xml .= '<quiz>' . "\n";

  foreach ($criterions as $criterion) {
    $xml .= '  <question>' . "\n";
    $xml .= '    <name>' . "\n";
    $xml .= '      <text>' . $criterion->name . '</text>' . "\n";
    $xml .= '    </name>' . "\n";

    $xml .= '    <questiontext>' . "\n";
    $xml .= '      <text><![CDATA[' . $criterion->text . ']]></text>' . "\n";
    $xml .= '    </questiontext>' . "\n";

    $tags = $DB->get_records_select('groupevaluation_tags', 'criterionid = '.$criterion->id, null, 'position');
    foreach ($tags as $tag) {
      $xml .= '      <answer fraction="' . $tag->value . '"' . ">\n";
      $xml .= '        <text><![CDATA[' . $tag->text . ']]></text>' . "\n";
      $xml .= '      </answer>' . "\n";
    }

    $xml .= '  </question>' . "\n";
  }

  $xml .= '</quiz>' . "\n";

  return $xml;
}
