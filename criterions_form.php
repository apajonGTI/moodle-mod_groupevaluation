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

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/groupevaluation/defaultcriterions.php');

//require_once($CFG->dirroot.'/mod/groupevaluation/criteriontypes/criteriontypes.class.php');

class groupevaluation_criterions_form extends moodleform {

    public function __construct($action) {
        //moodleform($action=null, $customdata=null, $method='post', $target='', $attributes=null, $editable=true)
        $attributes = array('id' => 'criterionsform');
        return parent::__construct($action, null, 'post', null, $attributes);
        //return parent::__construct($action);
    }

    public function definition() {
        global $CFG, $groupevaluation, $SESSION, $OUTPUT, $cm, $context, $popup, $lang;
        global $DB;
        global $languages; // From defaultcriterions.php

        $mform    =& $this->_form;
        //TODO Actualizar la variable $SESSION en criterions.php con los datos de este formulario

        //$this->add_action_buttons();
        //$mform->addElement('submit', 'addsurvey', get_string('addsurvey', 'groupevaluation'));

        $stredit = get_string('edit', 'groupevaluation');
        $strremove = get_string('remove', 'groupevaluation');
        $straddsaved = get_string('addcriterion', 'groupevaluation');
        $strmove = get_string('move');
        $strmovehere = get_string('movehere');
        $stryes = get_string('yes');
        $strno = get_string('no');
        $strweight = get_string('weight', 'groupevaluation');
        $strposition = get_string('position', 'groupevaluation');

        $table = 'groupevaluation_criterions';
        $select = "groupevaluationid = $groupevaluation->id"; //is put into the where clause
        $criterions = $DB->get_records_select($table, $select, null, 'position ASC');



        $pos = 0;

        // ADD NEW CRITERION //
        $mform->addElement('header', 'criterionhdr', get_string('addcriterions', 'groupevaluation'));
        $mform->addHelpButton('criterionhdr', 'addcriterions', 'groupevaluation');

        /*$straddsavedlink = get_string('savedcriterions', 'groupevaluation');
        $addsavedhtml = '<a href="#saved" onclick="expandSavedCriterions()">'.$straddsavedlink.'</a>';

        $mform->addElement('submit', 'addcrtbutton', get_string('addnewcriterion', 'groupevaluation'));
        $mform->addElement('html', $addsavedhtml);*/

        $straddcrt = get_string('addcriterion', 'groupevaluation');
        $srcadd = $OUTPUT->pix_url('t/add');
        $addcrturl = new moodle_url($CFG->wwwroot.'/mod/groupevaluation/criterions.php');
        $addcrturl->param('id', $cm->id);
        $addcrturl->param('action', 'criterion');
        $mform->addElement('html', '<div class="popup">');
        $mform->addElement('html', ' <button type="button" style="visibility:visible;" onclick="popupAddCriterion()">'.$straddcrt.'</button> ');
        //$mform->addElement('html', '<div class="popup">');
        $mform->addElement('html', '<div class="addpopup" id="addpopup">
        <li class="add-menu">
          <a href="'.$addcrturl.'">
            <img class="iconsmall" alt="" src="'.$srcadd.'">
            <span class="menu-action-text"> '.get_string('addnewcriterion', 'groupevaluation').'</span>
          </a></li>
        <li class="add-menu">
          <a onclick="popupSavedCriterions()">
            <img class="iconsmall" alt="" src="'.$srcadd.'">
            <span class="menu-action-text"> '.get_string('addfromcrtbank', 'groupevaluation').'</span>
          </a>
        </li>
        </div>');



        $mform->addElement('html', '</div>');

        /*$mform->addElement('html', ' <button type="button" id="create-modal">Create modal!</button> ');*/

        //$mform->addElement('html', '<input id="hiddenbutton" style="visibility: hidden;" name="hiddenbutton" value="" type="submit">');

        $crtnum = 0;

        // MANAGE CRITERIONS //
        $mform->addElement('header', 'managecrt', get_string('managecriterions', 'groupevaluation'));
        $mform->addHelpButton('managecrt', 'managecriterions', 'groupevaluation');
        $mform->setExpanded('managecrt', true);

        // Save weights
        //$mform->addElement('submit', 'savedbutton', get_string('saveweights', 'groupevaluation'));
        $mform->addElement('html', '<button id="id_savedbutton" name="savedbutton" type="submit" style="margin-left: 20px;">'.
                            get_string('saveweights', 'groupevaluation').'</button>'.'<span></span>');
        $mform->addElement('html', '<span id="weightschanged" style="visibility:hidden;">('
                            .get_string('weightschanged', 'groupevaluation').')</span>');
        $srcexpand = $OUTPUT->pix_url('t/collapsed');
        $srccollapse = $OUTPUT->pix_url('t/expanded');
        $strexpandall = get_string('expandallanswers', 'groupevaluation');
        $strcollapseall = get_string('collapseallanswers', 'groupevaluation');
        $onclick = 'onclick=\'expandAllAnswers("'.$strexpandall.'","'.$strcollapseall.'", "'.$srcexpand.'", "'.$srccollapse.'", false)\'';
        $mform->addElement('html', '<a style="float: right; cursor: pointer;" id="expandallbutton" '.$onclick.'>'.
        '<img src="'.$srcexpand.'">'.$strexpandall.'</a>');

        $mform->addElement('html', '<div class="qcontainer">');

        foreach ($criterions as $criterion) {
            $managecrtgroup = array();

            $crtid = $criterion->id;
            $special = $criterion->special;
            $pos = $criterion->position;
            $weight = round($criterion->weight);

            $tags = $DB->get_records('groupevaluation_tags', array('criterionid' => $crtid), 'position');

            $crtnum++;

            // Needed for non-English languages JR.
            $text = '';
            // If criterion text is "empty", i.e. 2 non-breaking spaces were inserted, do not display any criterion text.
            if ($criterion->text == '<p>  </p>') {
                $criterion->text = '';
            }
            // Needed to print potential media in criterion text.
            $text = format_text(file_rewrite_pluginfile_urls($criterion->text, 'pluginfile.php',
                    $context->id, 'mod_groupevaluation', 'criterion', $crtid), FORMAT_HTML);

            $spacer = $OUTPUT->pix_url('spacer');

            $mform->addElement('html', '<div class="element" id="element_'.$crtid.'">');

            $srcdragdrop = $OUTPUT->pix_url('i/dragdrop');
            $mform->addElement('html', '<div style="float: left;"><img class="dragdrop" src="'.$srcdragdrop.'"/></div>');

            $mform->addElement('html', '<div class="qn-container">'); // Begin div qn-container.

            if ($special == 1) {
              $spesrc = $OUTPUT->pix_url('i/marked');
              $strspecial = get_string('special', 'groupevaluation');

            } else {
              $spesrc = $OUTPUT->pix_url('i/marker');
              $strspecial = get_string('nospecial', 'groupevaluation');
            }
            $strspecial .= ' '.get_string('clicktoswitch', 'groupevaluation');

            //$mextra = array('value' => $crtid, 'alt' => $strmove, 'title' => $strmove);
            $eextra = array('value' => $crtid, 'alt' => $stredit, 'title' => $stredit);
            $rextra = array('value' => $crtid, 'alt' => $strremove, 'title' => $strremove);
            $speextra = array('value' => $crtid, 'alt' => $strspecial, 'title' => $strspecial);

            $esrc = $OUTPUT->pix_url('t/edit');
            $rsrc = $OUTPUT->pix_url('t/delete');
            //$msrc = $OUTPUT->pix_url('t/move');

            $arrayOfOptions = array ();
            for ($i=0;$i<=100;$i++) {
              //$arrayOfOptions[] = $i;
              $arrayOfOptions[$i] = $i.'%';
            }
            $auxurl = new moodle_url($CFG->wwwroot.'/mod/groupevaluation/criterion_weight.php');
            $auxurl->param('id', $cm->id);

            $style = 'visibility:visible; color:red; font-size: smaller; margin-left: 5px';
            $arrayOfAttributes = array('id'=>'selectweight['.$crtid.']', 'class'=>'crtweight',
            'onchange' => 'document.getElementById("weightschanged").setAttribute("style", "'.$style.'");');

            //Criterion name.
            $managecrtgroup[] =& $mform->createElement('static', 'crtname', '',
                            '<div class="crtname">'.$criterion->name.'</div>');

            // Need to index by 'id' since IE doesn't return assigned 'values' for image inputs.
            $managecrtgroup[] =& $mform->createElement('static', 'opentag_'.$crtid, '', '');

            $managecrtgroup[] =& $mform->createElement('select', 'selectweight['.$crtid.']', '', $arrayOfOptions, $arrayOfAttributes);
            $mform->setDefault('selectweight['.$crtid.']', round($weight));

            //$managecrtgroup[] =& $mform->createElement('image', 'movebutton['.$crtid.']',$msrc, $mextra);
            $managecrtgroup[] =& $mform->createElement('image', 'specialbutton['.$crtid.']', $spesrc, $speextra);
            $managecrtgroup[] =& $mform->createElement('image', 'editbutton['.$crtid.']', $esrc, $eextra);
            $managecrtgroup[] =& $mform->createElement('image', 'removebutton['.$crtid.']', $rsrc, $rextra);

            $managecrtgroup[] =& $mform->createElement('static', 'closetag_'.$crtid, '', '');
            $mform->addGroup($managecrtgroup, 'manageqgroup', '', '&nbsp;', false);

            $ctrnumber = '<div class="qn-info"><h2 class="qn-number">'.$pos.'</h2></div>';

            $strshowanswers = get_string('showanswers', 'groupevaluation');
            $strhideanswers = get_string('hideanswers', 'groupevaluation');
            $expandbutton = '<button class="expandbutton" type="button" id="expandbutton_'.$crtid.'"'.
                            'onclick=\'expandAnswers('.$crtid.',"'.$strshowanswers.'","'.$strhideanswers.'", false)\'>'.$strshowanswers.'</button>';

            $mform->addElement('static', 'qcontent_'.$crtid, '',$ctrnumber.'<div class="qn-question">'.$text.'</div>'.$expandbutton);

            $ctranswersid = 'crtanswers_'.$crtid;
            $t = 1;

            $mform->addElement('html', '<div id="'.$ctranswersid.'" class="crt-edit-answers">'); // Begin "div crt-edit-answers"

            foreach ($tags as $tag) {
              //$ansnumber = '<div class="qn-ansinfo"><h2 class="qn-ansnumber">'.$t.'</h2></div>';
              $mform->addElement('html', '<p><strong>['.round($tag->value).'%] </strong>'.$tag->text.'</p>');
              $t++;
            }

            $mform->addElement('html', '</div>'); // End div "div crt-edit-answers".
            $mform->addElement('html', '</div>'); // End div "qn-container".

            // TODO Borrar (sortable)
            $mform->addElement('html', '</div>'); // End div "element".
        }

        $mform->addElement('html', '</div>'); // qcontainer

// ******************************************************************************

// SAVED CRITERIONS //

        $mform->addElement('html', '<div class="popup2">');
        $mform->addElement('html', '<div id="lightbox" style="position: fixed; width: 100%; height: 100%; top: 0px; left: 0px; z-index: 3000;" class="yui3-widget-mask moodle-dialogue-lightbox"></div>');

        $mform->addElement('html', '<div class="crtbank-dialogue" id="crtbank-dialogue">');

        $mform->addElement('html', '<div class="crtbank-dialogue-wrap">');


        $mform->addElement('html', '<div class="crtbank-dialogue-hd">'.get_string('bankofcriterions', 'groupevaluation'));
        $mform->addElement('html', '<span><button title="'.get_string('close', 'groupevaluation').'" class="close-button" '.
        'type="button" onclick="popupSavedCriterions()">&times;</button></span>');
        $mform->addElement('html', '</div>'); // end crtbank-dialogue-hd

        $mform->addElement('html', '<div class="crtbank-dialogue-ft">');

        // SELECT CRITERIONS TO SHOW //
        $popupaux = 1;
        if ($popup) {
          $popupaux = $popup;
        }

        // Default language
        $currentlanguaje = current_language();
        if (in_array($currentlanguaje, $languages)) { // $languages from defaultcriterions.php
          $langaux = $currentlanguaje;
        } else {
          $langaux = 'en';
        }
        if ($lang) {
          $langaux = $lang;
        }


        // Select defaultcriterions || savedcriterions
        $options =  array(1 => get_string('defaultcriterions','groupevaluation'),
                          2 => get_string('savedcriterions','groupevaluation'));
        $mform->addElement('html', '<select name="popup" id="id_popup" onchange=\'document.getElementById("hiddenbutton").click();\'>');
        foreach ($options as $value => $text) {
          if ($value == $popupaux) {
            $selected = 'selected="selected"';
          } else {
            $selected = '';
          }
          $mform->addElement('html', '<option value="'.$value.'" '.$selected.'>'.$text.'</option>');
        }
        $mform->addElement('html', '</select>');

        // Select languages
        if ($popupaux == 1) {
          $options =  array();
          foreach ($languages as $language => $value) {
            $options[$language] = get_string($value,'groupevaluation');
          }
          $mform->addElement('html', '<select name="lang" id="id_lang" onchange=\'document.getElementById("hiddenbutton").click();\'>');
          foreach ($options as $value => $text) {
            if ($value == $langaux) {
              $selected = 'selected="selected"';
            } else {
              $selected = '';
            }
            $mform->addElement('html', '<option value="'.$value.'" '.$selected.'>'.$text.'</option>');
          }
          $mform->addElement('html', '</select>');
        }

        // Update buton
        //$mform->addElement('html', '<button type="submit" name="update">'.get_string('update','groupevaluation').'</button>');

        $srcexpand = $OUTPUT->pix_url('t/collapsed');
        $srccollapse = $OUTPUT->pix_url('t/expanded');
        $strexpandall = get_string('expandallanswers', 'groupevaluation');
        $strcollapseall = get_string('collapseallanswers', 'groupevaluation');
        $onclick = 'onclick=\'expandAllAnswers("'.$strexpandall.'","'.$strcollapseall.'", "'.$srcexpand.'", "'.$srccollapse.'", true)\'';
        $mform->addElement('html', '<a style="float: right; cursor: pointer;" id="expandallsavedbutton" '.$onclick.'>'.
        '<img src="'.$srcexpand.'">'.$strexpandall.'</a>');

        $mform->addElement('html', '</div>'); // end crtbank-dialogue-ft

        $mform->addElement('html', '<div class="crtbank-dialogue-bd">');

        /*$strsaved = '<a name="saved">'.get_string('savedcriterions', 'groupevaluation').'</a>';
        $mform->addElement('header', 'savedcrt', $strsaved, array('id' => 'savedcrtarea'));
        $mform->addHelpButton('savedcrt', 'savedcriterions', 'groupevaluation');*/
        $mform->addElement('html', '<div class="qcontainer">');


        // GET DEFAULT/SAVED CRITERIONS
        if ($popupaux != 1) {
          $select = "saved = 1 AND defaultcriterion = 0 AND groupevaluationid IS NULL";
        } else {
          $select = "saved = 1 AND defaultcriterion = 1 AND groupevaluationid IS NULL AND languagecode = '$langaux'";
        }

        $savedcriterions = $DB->get_records_select($table, $select);

        if (!$savedcriterions) {
          $mform->addElement('html', '<div class="alert alert-error">'.get_string('nocriterionsstored', 'groupevaluation').'</div>');
        }

        $pos = 1;
        foreach ($savedcriterions as $criterion) {
            $savedcrtgroup = array();
            $crtid = $criterion->id;

            $tags = $DB->get_records('groupevaluation_tags', array('criterionid' => $crtid), 'position');
            $crtnum++;

            // Needed for non-English languages JR.
            $text = '';
            // If criterion text is "empty", i.e. 2 non-breaking spaces were inserted, do not display any criterion text.
            if ($criterion->text == '<p>  </p>') {
                $criterion->text = '';
            }

            // Needed to print potential media in criterion text.
            $text = format_text(file_rewrite_pluginfile_urls($criterion->text, 'pluginfile.php',
                    $context->id, 'mod_groupevaluation', 'criterion', $crtid), FORMAT_HTML);

            $spacer = $OUTPUT->pix_url('spacer');
            $mform->addElement('html', '<div class="qn-container">'); // Begin div qn-container.

            $eextra = array('value' => $crtid,'alt' => $stredit,'title' => $stredit);
            $rextra = array('value' => $crtid,'alt' => $strremove,  'title' => $strremove);
            $aextra = array('value' => $crtid,'alt' => $straddsaved,  'title' => $straddsaved);
            $esrc = $OUTPUT->pix_url('t/edit');
            $rsrc = $OUTPUT->pix_url('t/delete');
            $asrc = $OUTPUT->pix_url('t/add');

            // criterion name
            $crtname = $criterion->name;

            // Need to index by 'id' since IE doesn't return assigned 'values' for image inputs.
            $savedcrtgroup[] =& $mform->createElement('static', 'opentag_'.$crtid, '', '');
            $savedcrtgroup[] =& $mform->createElement('static', 'crtname', '','<div class="crtname">'.$crtname.'</div>');

            if (!$criterion->defaultcriterion) {
              $savedcrtgroup[] =& $mform->createElement('image', 'editbutton['.$crtid.']', $esrc, $eextra);
              $savedcrtgroup[] =& $mform->createElement('image', 'removebutton['.$crtid.']', $rsrc, $rextra);
            }
            $savedcrtgroup[] =& $mform->createElement('image', 'addsavedbutton['.$crtid.']', $asrc, $aextra);
            $savedcrtgroup[] =& $mform->createElement('static', 'closetag_'.$crtid, '', '');

            $mform->addGroup($savedcrtgroup, 'savedcrtgroup', '', '&nbsp;', false);

            $strshowanswers = get_string('showanswers', 'groupevaluation');
            $strhideanswers = get_string('hideanswers', 'groupevaluation');
            $expandbutton = '<button class="expandbutton" type="button" id="expandbutton_'.$crtid.'"'.
                            'onclick=\'expandAnswers('.$crtid.',"'.$strshowanswers.'","'.$strhideanswers.'", true)\'>'.$strshowanswers.'</button>';
            $ctrnumber = '<div class="qn-info"><h2 class="qn-number">'.$pos.'</h2></div>';
            $mform->addElement('static', 'qcontent_'.$crtid, '',$ctrnumber.'<div class="qn-question">'.$text.'</div>'.$expandbutton);

            $t = 1;

            $ctrsavedansid = 'crtsavedans_'.$crtid;

            $mform->addElement('html', '<div id="'.$ctrsavedansid.'" class="crt-saved-answers">');

            foreach ($tags as $tag) {
              $tagtext = $tag->text;
              //$ansnumber = '<div class="qn-ansinfo"><h2 class="qn-ansnumber">'.$t.'</h2></div>';
              $mform->addElement('html', '<p><strong>['.round($tag->value).'%] </strong>'.$tagtext.'</p>');
              $t++;
            }
            $mform->addElement('html', '</div>'); // End div "div crt-edit-answers".

            $mform->addElement('html', '</div>'); // End div "qn-container".

            $pos++;
        }
// ******************************************************************************
        $mform->addElement('html', '</div>'); //end qcontainer;

        $mform->addElement('html', '</div>'); // end crtbank-dialogue-bd
        $mform->addElement('html', '</div>'); // end crtbank-dialogue-wrap
        $mform->addElement('html', '</div>'); // end crtbank-dialogue
        $mform->addElement('html', '</div>'); // end popup2*/


        //$mform->addElement('html', '</div>'); // End div qn-container.

        // Hidden button to update
        $mform->addElement('html', '<input id="hiddenbutton" style="visibility: hidden;" name="hiddenbutton" value="" type="submit">');

        // Hidden fields.
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'action', 'main');
        $mform->setType('action', PARAM_RAW);
        $mform->setType('movecrt', PARAM_RAW);
        /*$mform->addElement('hidden', 'hiddenweight',null,array('id'=>'hiddenweight'));
        $mform->setType('hiddenweight', PARAM_INT);*/
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return true;
    }

}

class groupevaluation_edit_criterion_form extends moodleform {

    public function definition() {
        global $CFG, $cm, $COURSE, $groupevaluation, $criterion, $SESSION, $OUTPUT;
        global $DB;

        $cmid = $criterion->id;
        $mform    =& $this->_form;

        $stryes = get_string('yes');
        $strno  = get_string('no');
        $stranswer  = get_string('answer', 'groupevaluation');
        $strvalue  = get_string('value', 'groupevaluation');
        $strposition  = get_string('position', 'groupevaluation');
        $srcdragdrop = $OUTPUT->pix_url('i/dragdrop');
        $strmoveanswer = get_string('moveanswer', 'groupevaluation');

        $defaultcriterion = 0;
        if (isset($criterion->defaultcriterion)) {
          $defaultcriterion = $criterion->defaultcriterion;
        }

        if ($defaultcriterion) {
          $mform->addElement('html', '<div class="alert alert-error">'.get_string('alerteditdefaultcrt', 'groupevaluation').'</div>');
          $href = $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/criterions.php?id='.$cm->id);
          $cancelhtml = '<a href="'.$href.'" class="btn btn-default btn-lg" style="margin: 0px 0px 10px 5px;" '.
                  'role="button">'.get_string('continue').'</a>';
          $mform->addElement('html', $cancelhtml);
        } else {
          $mform->addElement('header', 'general', get_string('general', 'form'));

          // Name field
          $mform->addElement('text', 'name', get_string('criterionname', 'groupevaluation'),
                          array('size' => '30', 'maxlength' => '255'));
          $mform->setType('name', PARAM_TEXT);
          $mform->addRule('name', null, 'required', null, 'client');
          $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
          $mform->addHelpButton('name', 'criterionname', 'groupevaluation');

          // Special field
          if (!empty($criterion->groupevaluationid)) {
            $specialgroup = array();
            $specialgroup[] =& $mform->createElement('radio', 'special', '', $stryes, 1);
            $specialgroup[] =& $mform->createElement('radio', 'special', '', $strno, 0);
            $mform->addGroup($specialgroup, 'specialgroup', get_string('special', 'groupevaluation'), ' ', false);
            $mform->addHelpButton('specialgroup', 'special', 'groupevaluation');
            $mform->setDefault('special', 0);
          }

          // saved field
          if (empty($criterion->crtid)) {
            $savedgroup = array();
            $savedgroup[] =& $mform->createElement('radio', 'saved', '', $stryes, 1);
            $savedgroup[] =& $mform->createElement('radio', 'saved', '', $strno, 0);
            $mform->addGroup($savedgroup, 'savedgroup', get_string('savecriterion', 'groupevaluation'), ' ', false);
            $mform->addHelpButton('savedgroup', 'savecriterion', 'groupevaluation');
            $mform->setDefault('saved', 1);
          } else {
            $mform->addElement('hidden', 'saved', $criterion->saved);
            $mform->setType('saved', PARAM_INT);
          }

          // Required field
          if (!empty($criterion->groupevaluationid)) {
            $requiredgroup = array();
            $requiredgroup[] =& $mform->createElement('radio', 'required', '', $stryes, 1);
            $requiredgroup[] =& $mform->createElement('radio', 'required', '', $strno, 0);
            $mform->addGroup($requiredgroup, 'requiredgroup', get_string('required', 'groupevaluation'), ' ', false);
            $mform->addHelpButton('requiredgroup', 'required', 'groupevaluation');
            $mform->setDefault('required', 1);
          }

          // Weight field

          if (!empty($criterion->groupevaluationid)) {
            $select = "groupevaluationid = $groupevaluation->id";
            $criterions = $DB->get_records_select('groupevaluation_criterions', $select, null, 'position ASC');
            $defaultweight = round(100 / (count($criterions) + 1));

            for ($i=1;$i<=100;$i++) {
              $arrayOfOptions[$i] = $i.'%';
            }

            $checked = '';
            $disabled = 'disabled="disabled"';
            $weightattributes = array('id' => 'weight');
            if (isset($criterion->weight)) {
              if ($criterion->weight == 0) {
                $weightattributes['disabled'] = 'disabled';
                $checked = 'checked="checked"';
                $disabled = '';
              }
            }
            $onchange = 'onchange="notIncludeInGrade();"';
            $includecheckbox = '<input name="includecheckbox" value="0" '.$checked.' id="includecheckbox" type="checkbox" '.$onchange.'/>';
            $weighttext = $includecheckbox.get_string('notincludeingrade', 'groupevaluation');
            $mform->addElement('select', 'weight', get_string('weight', 'groupevaluation'), $arrayOfOptions, $weightattributes);
            $mform->addHelpButton('weight', 'weight', 'groupevaluation');
            $mform->setType('weight', PARAM_INT);
            $mform->setDefault('weight', $defaultweight);
            $mform->addElement('static', 'notinclude', '', $weighttext);
            $mform->addElement('html', '<input name="auxweight" id="auxweight" type="hidden" value="'.$defaultweight.'" />');
            $mform->addElement('html', '<input name="weight" '.$disabled.' id="id_weight" type="hidden" value="0"/>');
          }

          // text field.
          $modcontext    = $this->_customdata['modcontext'];
          $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'trusttext' => true, 'context' => $modcontext);
          $mform->addElement('editor', 'text', get_string('criteriontext', 'groupevaluation'), null, $editoroptions);
          $mform->setType('text', PARAM_RAW);
          $mform->addRule('text', null, 'required', null, 'client');

          // Options section:
          // has answer options ... so show that part of the form.

          $mform->addElement('header', 'answers', get_string('possibleanswers', 'groupevaluation'));
          $mform->addHelpButton('answers', 'possibleanswers', 'groupevaluation');

          // sortable //
          $mform->addElement('html', '<div id="answerscontainer" class="list ui-sortable">');

          $numanswers = $criterion->numanswers;
          for ($i = 1; $i <= $numanswers; $i++) {
            $tagvalue = 0;
            if (isset($criterion->{'tagvalue_'.$i})) {
              $tagvalue = round($criterion->{'tagvalue_'.$i});
            }
            $tagposition = 1;
            if (isset($criterion->{'tagposition_'.$i})) {
              $tagposition = $criterion->{'tagposition_'.$i};
            }

            //$mform->addElement('html', '<div class="element" id="element_'.$i.'">'); // sortable //
            $mform->addElement('html', '<div id="qoptcontainer_'.$i.'" class="qoptcontainer">');

            $onchange = 'checkboxChanged("tagcheckbox_'.$i.'")';
            $label  = '<div><img title="'.$strmoveanswer.'" class="dragdrop" src="'.$srcdragdrop.'"/></div><br/><br/>';
            $label .= '<input name="tagcheckbox_'.$i.'" value="0" id="tagcheckbox_'.$i.'" type="checkbox" onchange=\''.$onchange.'\'></input>';
            $label .= $stranswer.' '.$i.'<br/>';
            $label .= $strvalue.': <select name="tagvalue_'.$i.'" id="tagvalue_'.$i.'" class="answeight">';
            for ($j=0;$j<=100;$j++) {
              if ($j == $tagvalue) {
                $label .= '<option selected="selected" value="'.$j.'">'.$j.'%</option>';
              } else {
                $label .= '<option value="'.$j.'">'.$j.'%</option>';
              }
            }
            $label .= '</select><br/>';

            $label .= '<input name="tagposition_'.$i.'" id="tagposition_'.$i.'" value="'.$i.'" class="tagposition" type="hidden"/>';

            $options = array('wrap' => 'virtual', 'class' => 'qopts', 'required' => 'required');
            $mform->addElement('textarea', 'tag_'.$i, $label, $options);
            $mform->setType('tag_'.$i, PARAM_RAW);

            $mform->addElement('html', '</div>'); // end qoptcontainer
            //$mform->addElement('html', '</div>'); // sortable //
          }
          $mform->addElement('html', '</div>'); // end answerscontainer

          // Add & Remove answer buttons
          $answergroup = array();
          $addattributes = array('onclick'=>'addAnswer("'.$stranswer.'","'.$strvalue.'","'.$strposition.'","'.$srcdragdrop.'","'.$strmoveanswer.'")');
          $remattributes = array('onclick'=>'removeAnswers("'.$stranswer.'","'.$strvalue.'","'.$strposition.'","'.$srcdragdrop.'","'.$strmoveanswer.'")');
          $answergroup[] =& $mform->createElement('button', 'addanswer', get_string("addanswer", 'groupevaluation'), $addattributes);
          $answergroup[] =& $mform->createElement('button', 'removeanswers', get_string("removeselanswers", 'groupevaluation'), $remattributes);
          $mform->addGroup($answergroup, 'answergroup', null, ' ', false);

          // Hidden fields.
          $mform->addElement('hidden', 'id', $cmid);
          $mform->setType('id', PARAM_INT);
          $mform->addElement('hidden', 'crtid', 0); //Criterionid a 0
          $mform->setType('crtid', PARAM_INT);
          $mform->addElement('hidden', 'action', 'criterion');
          $mform->setType('action', PARAM_RAW);
          $mform->addElement('hidden', 'groupevaluationid', $groupevaluation->id);
          $mform->setType('groupevaluationid', PARAM_INT);
          $mform->addElement('hidden', 'numanswers', $numanswers, array('id'=>'numanswers'));
          $mform->setType('numanswers', PARAM_INT);

          // Buttons.
          /*$attributes = array('onclick' => 'return checkHasAnswers("'.get_string('anansweratleast','groupevaluation').'")');
          $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'), $attributes);
          if (isset($criterion->crtid) && !empty($criterion->groupevaluationid)) {
              $buttonarray[] = &$mform->createElement('submit', 'makecopy', get_string('saveasnew', 'groupevaluation'), $attributes);
          }
          $buttonarray[] = &$mform->createElement('cancel');
          $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);*/

          // Buttons.
          $onclick = 'onclick=\'return checkHasAnswers("'.get_string('anansweratleast','groupevaluation').'")\'';
          $href = $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/criterions.php?id='.$cm->id);
          $cancelhtml = '<a href="'.$href.'" class="btn btn-default btn-lg" style="margin: 0px 0px 10px 5px;" '.
                  'role="button">'.get_string('cancel').'</a>';

          $mform->addElement('html', '<div id="fgroup_id_buttonar" class="fitem fitem_actionbuttons fitem_fgroup">');
          $mform->addElement('html', '<div id="yui_3_17_2_1_1494530448840_1068" class="felement fgroup">');

          $mform->addElement('html', '<input type="submit" id="id_submitbutton" name="submitbutton" value="'.
                            get_string('savechanges').'" onclick="'.$onclick.'"/>');

          if (isset($criterion->crtid) && !empty($criterion->groupevaluationid)) {
              $mform->addElement('html', '<input type="submit" name="makecopy" id="id_makecopy" name="sendbutton" value="'.
                                get_string('saveasnew', 'groupevaluation').'" '.$onclick.'/>');
          }
          $mform->addElement('html', $cancelhtml);

          $mform->addElement('html', '</div>');
          $mform->addElement('html', '</div>');
        }
    }

    public function validation($data, $files) {
      $errors = parent::validation($data, $files);
      return $errors;
    }
}

class groupevaluation_confirm_reweight_form extends moodleform {

    public function definition() {
        global $CFG, $COURSE, $cm, $groupevaluation, $selectweight, $sumweight, $criterions;
        global $DB;

        $mform    =& $this->_form;

        $msg = '<div class="alert alert-error">'.get_string('incorrectweightssum', 'groupevaluation').'</div>';
        $msg .= '<div id="notice" class="box generalbox">';
        $msg .= '<div class="warning centerpara"><p>'.get_string('confirmnewweights', 'groupevaluation').'</p>';
        $msg .= '</div>';
        //$msg .= '<div class = "qn-container">'.$num.' '.$pos.'<div class="qn-question">'.$criterion->text.'</div></div>';

        $msg .= '<div class = "weight-container">';

        $strposition  = get_string('position', 'groupevaluation');
        $hiddeninputs = '';
        foreach ($selectweight as $crtid => $weightvalue) {
          //$criterion = $DB->get_records('groupevaluation_tags', array('criterionid' => $crtid));
          $criterion = $criterions[$crtid];
          $newweight = $weightvalue;

          if ($sumweight > 0) {
            $normalizedweight = $weightvalue / $sumweight;
            $newweight = round($normalizedweight * 100.00, 2);
          } else if (count($selectweight) > 0) {
            $newweight = round(($weightvalue / count($selectweight)), 2);
          }

          $msg .= '<div class="qn-question">';

          $msg .= '<p>'.$criterion->name.' ('.$strposition.': '.$criterion->position.')<br/><strong>'.$weightvalue.'% &rarr; '.round($newweight).'%</strong></p>';
          $msg .= '</div>';

          // PHP reads this into an array
          $hiddeninputs .= '<input name="selectweight['.$crtid.']" type="hidden" value="'.$newweight.'" />';
        }
        $hiddeninputs .= '<input name="reweight" type="hidden" value="1" />';

        $msg .= '</div>';
        $msg .= '</div>';

        $msg .= $hiddeninputs;

        $mform->addElement('html', $msg);

        // Buttons.
        $href = $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/criterions.php?id='.$cm->id);
        $cancelhtml = '<a href="'.$href.'" class="btn btn-default btn-lg" style="margin-right: 10px;" '.
                      'role="button" >'.get_string('cancel', 'groupevaluation').'</a>';
        $accepthtml = '<input name="reweightbutton" value="'.get_string('accept', 'groupevaluation').
                    '" id="id_reweightbutton" style="margin: 0 0 0 10px;" type="submit">';

        $mform->addElement('html', '<div style="text-align: center;">');
        if ($newweight != round($newweight)) {
          $mform->addElement('html', '<p>*'.get_string('weightsdisplayedrounded', 'groupevaluation').'</p>');
        }
        $mform->addElement('html', $cancelhtml);
        $mform->addElement('html', $accepthtml);
        $mform->addElement('html', '</div>');


        // Hidden fields.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'action', 'main');
        $mform->setType('action', PARAM_RAW);
        $mform->addElement('hidden', 'groupevaluationid', $groupevaluation->id);
        $mform->setType('groupevaluationid', PARAM_INT);
    }

    public function validation($data, $files) {
      $errors = parent::validation($data, $files);
      return $errors;
    }
}
