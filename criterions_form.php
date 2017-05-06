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

//require_once($CFG->dirroot.'/mod/groupevaluation/criteriontypes/criteriontypes.class.php');

class groupevaluation_criterions_form extends moodleform {

    public function __construct($action, $movecrt=false) {
        $this->movecrt = $movecrt;
        return parent::__construct($action);
    }

    public function definition() {
        global $CFG, $groupevaluation, $SESSION, $OUTPUT, $cm, $context;
        global $DB;

        $mform    =& $this->_form;
        //TODO Actualizar la variable $SESSION en criterions.php con los datos de este formulario

        //$this->add_action_buttons();
        //$mform->addElement('submit', 'addsurvey', get_string('addsurvey', 'groupevaluation'));

        //TODO Borrar
        $stredit = get_string('edit', 'groupevaluation');
        $strremove = get_string('remove', 'groupevaluation');
        $strmove = get_string('move');
        $strmovehere = get_string('movehere');
        $stryes = get_string('yes');
        $strno = get_string('no');
        $strposition = get_string('position', 'groupevaluation');

        $table = 'groupevaluation_criterions';
        $select = "groupevaluationid = $groupevaluation->id"; //is put into the where clause
        $criterions = $DB->get_records_select($table, $select, null, 'position ASC');

        if ($this->movecrt) {
            $movecrtposition = $criterions[$this->movecrt]->position; //TODO Campo position en la base de datos
        }

        $pos = 0;

        // ADD NEW CRITERION //
        $mform->addElement('header', 'criterionhdr', get_string('addcriterions', 'groupevaluation'));
        $mform->addHelpButton('criterionhdr', 'addcriterions', 'groupevaluation');

        $addcrtgroup = array();

        //$number[0] = '----- Num of answers -----';
        for ($i = 1; $i <= 10; $i++) {
            $number[$i] = $i;
        }
        $addcrtgroup[] =& $mform->createElement('select', 'numanswers', '', $number);
        $mform->setType('numanswers', PARAM_INT);

        // The 'sticky' type_id value for further new questions.
        if (isset($SESSION->groupevaluation->numanswers)) {
          $mform->setDefault('numanswers', $SESSION->groupevaluation->numanswers);
        } else {
          $mform->setDefault('numanswers', 5);
        }


        $addcrtgroup[] =& $mform->createElement('submit', 'addcrtbutton', get_string('addnewcriterion', 'groupevaluation'));
        $mform->addGroup($addcrtgroup, 'addcrtgroup', get_string('possibleanswers', 'groupevaluation'), '  ', false);
        //$mform->addGroup($addcrtgroup, 'addcrtgroup', get_string('addnewcriterion', 'groupevaluation'), '  ', false);

        $crtnum = 0;

        // MANAGE CRITERIONS //

        $mform->addElement('header', 'managecrt', get_string('managecriterions', 'groupevaluation'));
        $mform->addHelpButton('managecrt', 'managecriterions', 'groupevaluation');
        $mform->setExpanded('managecrt', true);

        $mform->addElement('html', '<div class="qcontainer">');

        foreach ($criterions as $criterion) {
            $managecrtgroup = array();

            $crtid = $criterion->id;
            $special = $criterion->special;
            $pos = $criterion->position;

            // Does this groupevaluation contain branching criterions already?


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

            $movecrtgroup = array();

            $spacer = $OUTPUT->pix_url('spacer');

            if (!$this->movecrt) {
                $mform->addElement('html', '<div class="qn-container">'); // Begin div qn-container.
                $mextra = array('value' => $crtid,
                                'alt' => $strmove,
                                'title' => $strmove);
                $eextra = array('value' => $crtid,
                                'alt' => $stredit,
                                'title' => $stredit);
                $rextra = array('value' => $crtid,
                                'alt' => $strremove,
                                'title' => $strremove);


                $esrc = $CFG->wwwroot.'/mod/groupevaluation/images/edit.gif';
                $esrc = $OUTPUT->pix_url('t/edit');
                $rsrc = $OUTPUT->pix_url('t/delete');
                        $qreq = '';

                // criterion numbers.
                $managecrtgroup[] =& $mform->createElement('static', 'qnums', '',
                                '<div class="qnums">'.$strposition.' '.$pos.'</div>');

                // Need to index by 'id' since IE doesn't return assigned 'values' for image inputs.
                $managecrtgroup[] =& $mform->createElement('static', 'opentag_'.$crtid, '', '');
                $msrc = $OUTPUT->pix_url('t/move');


                $managecrtgroup[] =& $mform->createElement('image', 'movebutton['.$crtid.']',
                                $msrc, $mextra);
                $managecrtgroup[] =& $mform->createElement('image', 'editbutton['.$crtid.']', $esrc, $eextra);
                $managecrtgroup[] =& $mform->createElement('image', 'removebutton['.$crtid.']', $rsrc, $rextra);


                $managecrtgroup[] =& $mform->createElement('static', 'closetag_'.$crtid, '', '');

            } else {
                $managecrtgroup[] =& $mform->createElement('static', 'qnum', '',
                                '<div class="qnums">'.$strposition.' '.$pos.'</div>');
                $movecrtgroup[] =& $mform->createElement('static', 'qnum', '', '');


                if ($this->movecrt == $crtid) {
                    $movecrtgroup[] =& $mform->createElement('cancel', 'cancelbutton', get_string('cancel'));
                } else {
                    $mextra = array('value' => $crtid,
                                    'alt' => $strmove,
                                    'title' => $strmovehere.' (position '.$pos.')');
                    $msrc = $OUTPUT->pix_url('movehere');
                    $movecrtgroup[] =& $mform->createElement('static', 'opentag_'.$crtid, '', '');
                    $movecrtgroup[] =& $mform->createElement('image', 'moveherebutton['.$pos.']', $msrc, $mextra);
                    $movecrtgroup[] =& $mform->createElement('static', 'closetag_'.$crtid, '', '');
                }

            }
            if ($criterion->name) {
                $qname = '('.$criterion->name.')';
            } else {
                $qname = '';
            }
            $managecrtgroup[] =& $mform->createElement('static', 'qname_'.$crtid, '', $qname);



          //  $mform->addElement('html', '</div>'); // End div qn-container.

            if ($this->movecrt && $pos < $movecrtposition) {
                $mform->addGroup($movecrtgroup, 'movecrtgroup', '', '', false);
            }
            if ($this->movecrt) {
                if ($this->movecrt == $crtid) {
                    $mform->addElement('html', '<div class="moving" title="'.$strmove.'">'); // Begin div qn-container.
                } else {
                    $mform->addElement('html', '<div class="qn-container">'); // Begin div qn-container.
                }
            }
            $mform->addGroup($managecrtgroup, 'manageqgroup', '', '&nbsp;', false);

            $ctrnumber = '<div class="qn-info"><h2 class="qn-number">'.$pos.'</h2></div>';
            $mform->addElement('static', 'qcontent_'.$crtid, '',$ctrnumber.'<div class="qn-question">'.$text.'</div>');

            $mform->addElement('html', '</div>'); // End div qn-container.

            if ($this->movecrt && $pos >= $movecrtposition) {
                $mform->addGroup($movecrtgroup, 'movecrtgroup', '', '', false);
            }
        }

        if ($this->movecrt) {
            $mform->addElement('hidden', 'movecrt', $this->movecrt);
        }


        $mform->addElement('header', 'savedcrt', get_string('savedcriterions', 'groupevaluation'));
        $mform->addHelpButton('savedcrt', 'savedcriterions', 'groupevaluation');
        $mform->addElement('html', '<div class="qcontainer">');

        //$mform->addElement('html', '</div>'); // End div qn-container.

        // Hidden fields.
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'action', 'main');
        $mform->setType('action', PARAM_RAW);
        $mform->setType('movecrt', PARAM_RAW);

        $mform->addElement('html', '</div>');
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }

}
//aqui
class groupevaluation_edit_criterion_form extends moodleform {

    public function definition() {
        global $CFG, $COURSE, $groupevaluation, $criterion, $groupevaluationrealms, $SESSION;
        global $DB, $cm;

        $mform    =& $this->_form;

        $stryes = get_string('yes');
        $strno  = get_string('no');

        // Display different messages for new criterion creation and existing criterion modification.
        /*if (isset($crtid)) {
            $streditcriterion = get_string('editcriterion', 'groupevaluation');
        } else {
            $streditcriterion = get_string('addnewcriterion', 'groupevaluation');
        }
        $mform->addElement('header', 'criterionhdredit', $streditcriterion);*/

        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Name field
        $mform->addElement('text', 'name', get_string('criterionname', 'groupevaluation'),
                        array('size' => '30', 'maxlength' => '30'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'criterionname', 'groupevaluation');

        // Special field
        $specialgroup = array();
        $specialgroup[] =& $mform->createElement('radio', 'special', '', $stryes, 1);
        $specialgroup[] =& $mform->createElement('radio', 'special', '', $strno, 0);
        $mform->addGroup($specialgroup, 'specialgroup', get_string('special', 'groupevaluation'), ' ', false);
        $mform->addHelpButton('specialgroup', 'special', 'groupevaluation');
        $mform->setDefault($stryes.$strno, 0);

        // saved field
        $savedgroup = array();
        $savedgroup[] =& $mform->createElement('radio', 'saved', '', $stryes, 1);
        $savedgroup[] =& $mform->createElement('radio', 'saved', '', $strno, 0);
        $mform->addGroup($savedgroup, 'savedgroup', get_string('savecriterion', 'groupevaluation'), ' ', false);
        $mform->addHelpButton('savedgroup', 'savecriterion', 'groupevaluation');
        $mform->setDefault($stryes.$strno, 0);

        // Weight field
        $mform->addElement('text', 'weight', get_string('weight', 'groupevaluation'));
        $mform->setType('weight', PARAM_INT);
        $mform->addHelpButton('weight', 'weight', 'groupevaluation');
        $mform->setDefault('weight', 20);

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

        $numanswers = $criterion->numanswers;;
        for ($i = 1; $i <= $numanswers; $i++) {
          $mform->addElement('html', '<div class="qoptcontainer">');
          $options = array('wrap' => 'virtual', 'class' => 'qopts');
          $mform->addElement('textarea', 'tag_'.$i, get_string('possibleanswer', 'groupevaluation',$i), $options);
          $mform->setType('tag_'.$i, PARAM_RAW);
          $mform->addRule('tag_'.$i, null, 'required', null, 'client');
          //$mform->disabledIf('tags', 'someselect', 'eq', 42);
          $mform->addElement('html', '</div>');
        }

        // Hidden fields.
        $mform->addElement('hidden', 'id', $cm->id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'crtid', 0); //Criterionid a 0
        $mform->setType('crtid', PARAM_INT);
        $mform->addElement('hidden', 'action', 'criterion');
        $mform->setType('action', PARAM_RAW);
        $mform->addElement('hidden', 'groupevaluationid', $groupevaluation->id);
        $mform->setType('groupevaluationid', PARAM_INT);
        $mform->addElement('hidden', 'numanswers', $numanswers);
        $mform->setType('numanswers', PARAM_INT);


        /*$element = new MoodleQuickForm_submitlink('elementName', 'attributes');
         $element->_js = 'write your javascript here, but do not write the <script...> and </script> tags';
         $element->_onclick = 'write your onclick call here, followed by "return false;"';
         $mform->addElement($element);*/

        /*$mform->addElement('submit', 'addanswer', get_string('addanswer', 'groupevaluation'), true);
        $mform->addElement('submitlink', 'addanswer', 'Add answer', array('_js' => '', '_onclick' => 'skipClientValidation = true;'));
        $mform->addElement('submitlink', 'addanswer', 'Add answer', array('href' => 'www.google.com', 'onclick' => 'skipClientValidation = true;'));
        */
        /*
        //$mform->addElement('html', '<input name="addanswer" value="'.get_string('addanswer','groupevaluation').
        //                  '" onclick="skipClientValidation = true; return=true;" id="id_addanswer" type="submit">');

        $mform->addElement('html', '<input name="addanswers" value="Blanks for 3 more choices" onclick="skipClientValidation = true;" id="id_addanswers" type="submit">');
        */

        // Buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));
        if (isset($crtid)) {
            $buttonarray[] = &$mform->createElement('submit', 'makecopy', get_string('saveasnew', 'groupevaluation'));
        }
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

    }

    public function validation($data, $files) {
      $errors = parent::validation($data, $files);
      return $errors;
    }
}
