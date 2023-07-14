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
define('groupevaluation_DEFAULT_PAGE_COUNT', 5);
$languages =  array('en' => 'english', 'es' => 'spanish', 'gl' => 'galician');

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
     $column = "position";

     $movecriterion = $criterions[$movecrtid];

     if (is_array($criterions)) {
         $index = 1;
         foreach ($criterions as $criterion) {
             if ($index == $movetopos) {
                 $index++;
             }
             if ($criterion->id == $movecriterion->id) {
                 $movecriterion->position = $movetopos;
                 //$DB->update_record("groupevaluation_criterions", new stdClass($movecriterion));
                 $DB->execute("UPDATE {groupevaluation_criterions} SET $column=? WHERE id=?", array($movetopos, $movecriterion->id));

                 continue;
             }
             $criterion->position = $index;
             //$DB->update_record("groupevaluation_criterions", $criterion);
             $DB->execute("UPDATE {groupevaluation_criterions} SET $column=? WHERE id=?", array($index, $criterion->id));

             $index++;
         }
         return true;
     }
     return false;
 }


 // Access functions.
 function groupevaluation_is_open($timeopen) {
   return ($timeopen > 0) ? ($timeopen < time()) : false;
 }

 function groupevaluation_is_closed($closedate) {
   return ($closedate > 0) ? ($closedate < time()) : false;
 }

function user_is_eligible() {
  global $context;
   return (has_capability('mod/groupevaluation:view', $context) &&
          has_capability('mod/groupevaluation:submit', $context));
}

function user_can_take($userid, $sid) {

   if (!user_is_eligible()) {
       return false;
   } else if ($userid > 0) {
       return user_new_assessment($userid, $sid);
   } else {
       return false;
   }
}

function user_new_assessment($userid, $sid) {
  global $DB;
  $select = 'id = '.$sid.' AND authorid = '.$userid;
  $survey = $DB->get_record_select('groupevaluation_surveys', $select);
  $assessment = false;

  if ($survey->status == groupevaluation_INCOMPLETE ||
      $survey->status == groupevaluation_COMPLETE) {
        $assessment = true;
  } elseif ($survey->status == groupevaluation_DONE) {
    $assessment = false;
  }
  return $assessment;
}


function groupevaluation_recalculate_weights($criterions, $newweight, $crtid=false) {
  global $DB;
  $sumweight = 0;
  foreach ($criterions as $criterion) {
    if (($criterion->id != $crtid) || !$crtid) {
      $sumweight = $sumweight + $criterion->weight;
    }
  }

  foreach ($criterions as $criterion) {
    if ($sumweight > 0) {
      $normalizedweight = $criterion->weight / $sumweight; // Normalized to 1
      //$weight = floor($normalizedweight * (100 - $newweight));
      $weight = round($normalizedweight * (100 - $newweight));
      $DB->set_field('groupevaluation_criterions', 'weight', $weight, array('id' => $criterion->id));
    }
  }
  return $sumweight;
}

function groupevaluation_save_answers($sid, $answers, $done=false) {
  global $DB;
  $survey = $DB->get_record('groupevaluation_surveys', array('id' => $sid));
  $groupevaluationid = $survey->groupevaluationid;
  $table = 'groupevaluation_assessments';

  foreach ($answers as $answer) {
    $criterionid = $answer->criterionid;

    // Update assessments table
    $conditions = array('surveyid' => $answer->surveyid, 'criterionid' => $criterionid);
    if($entry = $DB->get_record($table, $conditions)) {
      $entry->assessment = $answer->assessment;
      $result = $DB->update_record($table, $entry);
    } else {
      $result = $DB->insert_record($table, $answer);
    }
  }

  // Update status of survey
  $survey->status = groupevaluation_COMPLETE;
  if ($done) {
    $survey->status = groupevaluation_DONE;
  }

  $result = $DB->update_record('groupevaluation_surveys', $survey);
}

function groupevaluation_get_arrow($groupevaluation, $deviation) {
  global $CFG;
  $hardlowerdeviation = $groupevaluation->hardlowerdeviation;
  $hardupperdeviation = $groupevaluation->hardupperdeviation;
  $softlowerdeviation = $groupevaluation->softlowerdeviation;
  $softupperdeviation = $groupevaluation->softupperdeviation;

  if ($deviation <= $hardlowerdeviation) {
    $arrow = 'arrow_red_down.gif';
    $title = get_string('hardlowerdeviation', 'groupevaluation');
  } elseif ($deviation <= $softlowerdeviation) {
    $arrow = 'arrow_yellow_down.gif';
    $title = get_string('hardlowerdeviation', 'groupevaluation');
  } elseif ($deviation < $softupperdeviation) {
    $arrow = 'arrow_yellow_right.gif';
    $title = get_string('hardlowerdeviation', 'groupevaluation');
  } elseif ($deviation < $hardupperdeviation) {
    $arrow = 'arrow_yellow_up.gif';
    $title = get_string('hardlowerdeviation', 'groupevaluation');
  } elseif ($deviation >= $hardupperdeviation) {
    $arrow = 'arrow_green_up.gif';
    $title = get_string('hardlowerdeviation', 'groupevaluation');
  }

  $srcarrow = new moodle_url($CFG->wwwroot.'/mod/groupevaluation/pix/'.$arrow);
  $imgarrow = '<img title="'.$title.'" src="'.$srcarrow.'">';

  return $imgarrow;
}

function groupevaluation_color_code($assessment) {
  if ($assessment != '-') {
    $value = floor($assessment / 10);
    $code = '#';

    if ($value == 0) {        // Red 		      #FF0000 	255,0,0
      $code .= 'FF0000';
    } elseif ($value == 1) {  // FireBrick    #B22222 	178,34,34
      $code .= 'B22222';
    } elseif ($value == 2) {  // OrangeRed 	  #FF4500 	255,69,0
      $code .= 'FF4500';
    } elseif ($value == 3) {  // DarkOrange   #FF8C00 	255,140,0
      $code .= 'FF8C00';
    } elseif ($value == 4) {  // Orange 		  #FFA500 	255,165,0
      $code .= 'FFA500';
    } elseif ($value == 5) {  // Yellow 		  #FFFF00   255,255,0
      $code .= 'FFFF00';
    } elseif ($value == 6) {  // GreenYellow  #ADFF2F 	173,255,47
      $code .= 'ADFF2F';
    } elseif ($value == 7) {  // Chartreuse   #7FFF00 	127,255,0
      $code .= '7FFF00';
    } elseif ($value == 8) {  // LimeGreen 	  #32CD32 	50,205,50
      $code .= '32CD32';
    } elseif ($value == 9) {  // ForestGreen 	#228B22 	34,139,34
      $code .= '228B22';
    } elseif ($value == 10) { // Green 		    #008000 	0,128,0
      $code .= '008000';
    } else {
      $code .= 'FFFFFF';
    }
  } else {
    $code = '#FFFFFF';
  }
  return $code;
}

function groupevaluation_get_users_notresponded($groupevaluationid) {
  global $DB;
  $surveys = $DB->get_records('groupevaluation_surveys', array('groupevaluationid' => $groupevaluationid));
  $idusers = array();

  foreach ($surveys as $survey) {
    if ($survey->status != groupevaluation_DONE) {
      $idusers[] = $survey->authorid;
    }
  }

  $users = $DB->get_records_list('user', 'id', $idusers);

  return $users;
}

/**
 * Called by HTML editor in showrespondents and Essay question. Based on question/essay/renderer.
 * Pending general solution to using the HTML editor outside of moodleforms in Moodle pages.
 */
function groupevaluation_get_editor_options($context) {
    return array(
                    'subdirs' => 0,
                    'maxbytes' => 0,
                    'maxfiles' => -1,
                    'context' => $context,
                    'noclean' => 0,
                    'trusttext' => 0
    );
}

function get_language(){
  require_once("../../config.php");
  $languages =  array('en', 'es', 'gl');
  $currentlanguage  = substr(current_language(), 0, 2);
  if (in_array($currentlanguage, $languages)) { 
    $lang = $currentlanguage;
  } else {
    $lang = 'en';
  }
  return $lang;
}

