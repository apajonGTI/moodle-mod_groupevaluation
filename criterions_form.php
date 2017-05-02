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
        global $CFG, $groupevaluation, $SESSION, $OUTPUT;
        global $DB;

        $mform    =& $this->_form;

        // ADD NEW CRITERION //

        $mform->addElement('header', 'criterionhdr', get_string('addcriterions', 'groupevaluation'));
        $mform->addHelpButton('criterionhdr', 'addcriterions', 'groupevaluation');

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
        $criterions = $DB->get_records_select($table,$select);

        if ($this->movecrt) {
            $movecrtposition = $criterions[$this->movecrt]->position;//TODO Campo position en la base de datos
        }

        $pos = 0;

        $mform->addElement('submit', 'addcrtbutton', get_string('addnewcriterion', 'groupevaluation'));

        $crtnum = 0;

        // MANAGE CRITERIONS //

        $mform->addElement('header', 'managecrt', get_string('managecriterions', 'groupevaluation'));
        //$mform->addHelpButton('managecrt', 'managecriterions ', 'groupevaluation');

        $mform->addElement('html', '<div class="qcontainer">');

        foreach ($criterions as $criterion) {
            $managecrtgroup = array();

            $crtid = $criterion->id;
            $special = $criterion->special;

            // Does this groupevaluation contain branching criterions already?


            $pos = $criterion->position;

            // No page break in first position!
            if ($pos == 1) {
                if ($records = $DB->get_records_select('groupevaluation_criterions', $select, null, 'position ASC')) {
                    foreach ($records as $record) {
                        $DB->set_field('groupevaluation_criterions', 'position', $record->position - 1, array('id' => $record->id));
                    }
                }
                redirect($CFG->wwwroot.'/mod/groupevaluation/criterions.php?id='.$groupevaluation->cm->id);
            }

            $crtnum++;


            // Needed for non-English languages JR.
            $text = '';
            // If criterion text is "empty", i.e. 2 non-breaking spaces were inserted, do not display any criterion text.
            if ($criterion->text == '<p>  </p>') {
                $criterion->text = '';
            }
            if ($tid != QUESPAGEBREAK) {
                // Needed to print potential media in criterion text.
                $text = format_text(file_rewrite_pluginfile_urls($criterion->text, 'pluginfile.php',
                                $criterion->context->id, 'mod_groupevaluation', 'criterion', $criterion->id), FORMAT_HTML);
            }
            $movecrtgroup = array();

            $spacer = $OUTPUT->pix_url('spacer');

            if (!$this->movecrt) {
                $mform->addElement('html', '<div class="qn-container">'); // Begin div qn-container.
                $mextra = array('value' => $criterion->id,
                                'alt' => $strmove,
                                'title' => $strmove);
                $eextra = array('value' => $criterion->id,
                                'alt' => $stredit,
                                'title' => $stredit);
                $rextra = array('value' => $criterion->id,
                                'alt' => $strremove,
                                'title' => $strremove);

                /*if ($tid == QUESPAGEBREAK) {
                    $esrc = $CFG->wwwroot.'/mod/groupevaluation/images/editd.gif';
                    $eextra = array('disabled' => 'disabled');
                } else {
                    $esrc = $CFG->wwwroot.'/mod/groupevaluation/images/edit.gif';
                }

                if ($tid == QUESPAGEBREAK) {
                    $esrc = $spacer;
                    $eextra = array('disabled' => 'disabled');
                } else {
                    $esrc = $OUTPUT->pix_url('t/edit');
                }
                $rsrc = $OUTPUT->pix_url('t/delete');
                        $qreq = '';*/

                // criterion numbers.
                $manageqgroup[] =& $mform->createElement('static', 'qnums', '',
                                '<div class="qnums">'.$strposition.' '.$pos.'</div>');

                // Need to index by 'id' since IE doesn't return assigned 'values' for image inputs.
                $manageqgroup[] =& $mform->createElement('static', 'opentag_'.$criterion->id, '', '');
                $msrc = $OUTPUT->pix_url('t/move');


                $manageqgroup[] =& $mform->createElement('image', 'movebutton['.$criterion->id.']',
                                $msrc, $mextra);
                $manageqgroup[] =& $mform->createElement('image', 'editbutton['.$criterion->id.']', $esrc, $eextra);
                $manageqgroup[] =& $mform->createElement('image', 'removebutton['.$criterion->id.']', $rsrc, $rextra);


                $manageqgroup[] =& $mform->createElement('static', 'closetag_'.$criterion->id, '', '');

            } else {
                $manageqgroup[] =& $mform->createElement('static', 'qnum', '',
                                '<div class="qnums">'.$strposition.' '.$pos.'</div>');
                $movecrtgroup[] =& $mform->createElement('static', 'qnum', '', '');

                $display = true;

                if ($display) {
                    // Do not move a page break to first position.
                    if ($pos == 1) {
                        $manageqgroup[] =& $mform->createElement('static', 'qnums', '', '');
                    } else {
                        if ($this->movecrt == $criterion->id) {
                            $movecrtgroup[] =& $mform->createElement('cancel', 'cancelbutton', get_string('cancel'));
                        } else {
                            $mextra = array('value' => $criterion->id,
                                            'alt' => $strmove,
                                            'title' => $strmovehere.' (position '.$pos.')');
                            $msrc = $OUTPUT->pix_url('movehere');
                            $movecrtgroup[] =& $mform->createElement('static', 'opentag_'.$criterion->id, '', '');
                            $movecrtgroup[] =& $mform->createElement('image', 'moveherebutton['.$pos.']', $msrc, $mextra);
                            $movecrtgroup[] =& $mform->createElement('static', 'closetag_'.$criterion->id, '', '');
                        }
                    }
                } else {
                    $manageqgroup[] =& $mform->createElement('static', 'qnums', '', '');
                    $movecrtgroup[] =& $mform->createElement('static', 'qnums', '', '');
                }
            }
            if ($criterion->name) {
                $qname = '('.$criterion->name.')';
            } else {
                $qname = '';
            }
            $manageqgroup[] =& $mform->createElement('static', 'qname_'.$criterion->id, '', $qname);


            if ($this->movecrt && $pos < $movecrtposition) {
                $mform->addGroup($movecrtgroup, 'movecrtgroup', '', '', false);
            }
            if ($this->movecrt) {
                if ($this->movecrt == $criterion->id && $display) {
                    $mform->addElement('html', '<div class="moving" title="'.$strmove.'">'); // Begin div qn-container.
                } else {
                    $mform->addElement('html', '<div class="qn-container">'); // Begin div qn-container.
                }
            }
            $mform->addGroup($manageqgroup, 'manageqgroup', '', '&nbsp;', false);

            $mform->addElement('html', '</div>'); // End div qn-container.

            if ($this->movecrt && $pos >= $movecrtposition) {
                $mform->addGroup($movecrtgroup, 'movecrtgroup', '', '', false);
            }
        }

        if ($this->movecrt) {
            $mform->addElement('hidden', 'movecrt', $this->movecrt);
        }

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
        global $DB;

        $mform    =& $this->_form;

        // Display different messages for new criterion creation and existing criterion modification.
        if (isset($criterion->crtid)) {
            $streditcriterion = get_string('editcriterion', 'groupevaluation');
        } else {
            $streditcriterion = get_string('addnewcriterion', 'groupevaluation');
        }

        $mform->addElement('header', 'criterionhdredit', $streditcriterion);

        // Name and spcial fields.
        $stryes = get_string('yes');
        $strno  = get_string('no');

        $mform->addElement('text', 'name', get_string('criterionname', 'groupevaluation'),
                        array('size' => '30', 'maxlength' => '30'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'criterionname', 'groupevaluation');

        $specialgroup = array();
        $specialgroup[] =& $mform->createElement('radio', 'special', '', $stryes, 1);
        $specialgroup[] =& $mform->createElement('radio', 'special', '', $strno, 0);
        $mform->addGroup($specialgroup, 'specialgroup', get_string('special', 'groupevaluation'), ' ', false);
        $mform->addHelpButton('specialgroup', 'special', 'groupevaluation');
        $mform->setDefault($stryes.$strno, 0);
        // text field.
        $modcontext    = $this->_customdata['modcontext'];
        $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'trusttext' => true, 'context' => $modcontext);
        $mform->addElement('editor', 'text', get_string('criteriontext', 'groupevaluation'), null, $editoroptions);
        $mform->setType('text', PARAM_RAW);
        $mform->addRule('text', null, 'required', null, 'client');

        // Options section:
        // has answer options ... so show that part of the form.


        $mform->addElement('html', '<div class="qoptcontainer">');

        $options = array('wrap' => 'virtual', 'class' => 'qopts');
        $mform->addElement('textarea', 'allchoices', get_string('possibleanswers', 'groupevaluation'), $options);
        $mform->setType('allchoices', PARAM_RAW);
        $mform->addRule('allchoices', null, 'required', null, 'client');
        $mform->addHelpButton('allchoices', 'possibleanswers', 'groupevaluation');

        $mform->addElement('html', '</div>');


        // Hidden fields.
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'crtid', 0); //Criterionid a 0
        $mform->setType('crtid', PARAM_INT);
        $mform->addElement('hidden', 'action', 'criterion');
        $mform->setType('action', PARAM_RAW);
        $mform->addElement('hidden', 'groupevaluationid', $groupevaluation->id);
        $mform->setType('groupevaluationid', PARAM_INT);

        // Buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));
        if (isset($criterion->crtid)) {
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
