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
//require_once($CFG->dirroot.'/mod/questionnaire/questiontypes/questiontypes.class.php');

class groupevaluation_criterions_form extends moodleform {

    public function __construct($action, $moveq=false) {
        $this->moveq = $moveq;
        return parent::__construct($action);
    }

    public function definition() {
        global $CFG, $groupevaluation, $SESSION, $OUTPUT;
        global $DB;

        $mform    =& $this->_form;

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

        if ($this->moveq) {
            $moveqposition = $criterions[$this->moveq]->position;//TODO Campo position en la base de datos
        }

        $pos = 0;

        // foreach ($qtypes as $key => $qtype) {} //

        $addcrtgroup = array();
        $addcrtgroup[] =& $mform->createElement('select', 'crtselect', '', $criterions);

        // The 'sticky' type_id value for further new questions.
        /*if (isset($SESSION->questionnaire->type_id)) {
                $mform->setDefault('type_id', $SESSION->questionnaire->type_id);
        }*/

        $addcrtgroup[] =& $mform->createElement('submit', 'addcrtbutton', get_string('addselcriterion', 'groupevaluation'));

        //$questionnairehasdependencies = questionnaire_has_dependencies($questionnaire->questions);

        $mform->addGroup($addcrtgroup, 'addcrtgroup', '', ' ', false);

        if (isset($SESSION->groupevaluation->validateresults) &&  $SESSION->groupevaluation->validateresults != '') {
            $mform->addElement('static', 'validateresult', '', '<div class="qdepend warning">'.
                $SESSION->groupevaluation->validateresults.'</div>');
            $SESSION->groupevaluation->validateresults = '';
        }

        $ctrnum = 0;

        // JR skip logic :: to prevent moving child higher than parent OR parent lower than child
        // we must get now the parent and child positions.

        /*if ($questionnairehasdependencies) {
            $parentpositions = questionnaire_get_parent_positions ($questionnaire->questions);
            $childpositions = questionnaire_get_child_positions ($questionnaire->questions);
        }*/

        $mform->addElement('header', 'managecrt', get_string('managecriterions', 'groupevaluation'));
        $mform->addHelpButton('managecrt', 'managecriterions ', 'groupevaluation');

        $mform->addElement('html', '<div class="qcontainer">');

        foreach ($criterions as $criterion) {
            $managecrtgroup = array();

            $ctrid = $criterion->id;
            $required = $criterion->required;

            // Does this questionnaire contain branching questions already?


            $pos = $question->position;

            // No page break in first position!
            if ($pos == 1) {
                if ($records = $DB->get_records_select('groupevaluation_criterions', $select, null, 'position ASC')) {
                    foreach ($records as $record) {
                        $DB->set_field('groupevaluation_criterions', 'position', $record->position - 1, array('id' => $record->id));
                    }
                }
                redirect($CFG->wwwroot.'/mod/groupevaluation/criterions.php?id='.$groupevaluation->cm->id);
            }

            $ctrnum++;


            // Needed for non-English languages JR.
            $text = '';
            // If criterion text is "empty", i.e. 2 non-breaking spaces were inserted, do not display any criterion text.
            if ($criterion->text == '<p>  </p>') {
                $criterion->text = '';
            }
            if ($tid != QUESPAGEBREAK) {
                // Needed to print potential media in question text.
                $text = format_text(file_rewrite_pluginfile_urls($criterion->text, 'pluginfile.php',
                                $criterion->context->id, 'mod_groupevaluation', 'criterion', $criterion->id), FORMAT_HTML);
            }
            $moveqgroup = array();

            $spacer = $OUTPUT->pix_url('spacer');

            if (!$this->moveq) {
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
                    $esrc = $CFG->wwwroot.'/mod/questionnaire/images/editd.gif';
                    $eextra = array('disabled' => 'disabled');
                } else {
                    $esrc = $CFG->wwwroot.'/mod/questionnaire/images/edit.gif';
                }

                if ($tid == QUESPAGEBREAK) {
                    $esrc = $spacer;
                    $eextra = array('disabled' => 'disabled');
                } else {
                    $esrc = $OUTPUT->pix_url('t/edit');
                }
                $rsrc = $OUTPUT->pix_url('t/delete');
                        $qreq = '';*/

                // Question numbers.
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
                $moveqgroup[] =& $mform->createElement('static', 'qnum', '', '');

                $display = true;

                if ($display) {
                    // Do not move a page break to first position.
                    if ($pos == 1) {
                        $manageqgroup[] =& $mform->createElement('static', 'qnums', '', '');
                    } else {
                        if ($this->moveq == $criterion->id) {
                            $moveqgroup[] =& $mform->createElement('cancel', 'cancelbutton', get_string('cancel'));
                        } else {
                            $mextra = array('value' => $criterion->id,
                                            'alt' => $strmove,
                                            'title' => $strmovehere.' (position '.$pos.')');
                            $msrc = $OUTPUT->pix_url('movehere');
                            $moveqgroup[] =& $mform->createElement('static', 'opentag_'.$criterion->id, '', '');
                            $moveqgroup[] =& $mform->createElement('image', 'moveherebutton['.$pos.']', $msrc, $mextra);
                            $moveqgroup[] =& $mform->createElement('static', 'closetag_'.$criterion->id, '', '');
                        }
                    }
                } else {
                    $manageqgroup[] =& $mform->createElement('static', 'qnums', '', '');
                    $moveqgroup[] =& $mform->createElement('static', 'qnums', '', '');
                }
            }
            if ($criterion->name) {
                $qname = '('.$criterion->name.')';
            } else {
                $qname = '';
            }
            $manageqgroup[] =& $mform->createElement('static', 'qname_'.$criterion->id, '', $qname);


            if ($this->moveq && $pos < $moveqposition) {
                $mform->addGroup($moveqgroup, 'moveqgroup', '', '', false);
            }
            if ($this->moveq) {
                if ($this->moveq == $criterion->id && $display) {
                    $mform->addElement('html', '<div class="moving" title="'.$strmove.'">'); // Begin div qn-container.
                } else {
                    $mform->addElement('html', '<div class="qn-container">'); // Begin div qn-container.
                }
            }
            $mform->addGroup($manageqgroup, 'manageqgroup', '', '&nbsp;', false);

            $mform->addElement('html', '</div>'); // End div qn-container.

            if ($this->moveq && $pos >= $moveqposition) {
                $mform->addGroup($moveqgroup, 'moveqgroup', '', '', false);
            }
        }

        if ($this->moveq) {
            $mform->addElement('hidden', 'moveq', $this->moveq);
        }

        // Hidden fields.
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'sid', 0);
        $mform->setType('sid', PARAM_INT);
        $mform->addElement('hidden', 'action', 'main');
        $mform->setType('action', PARAM_RAW);
        $mform->setType('moveq', PARAM_RAW);

        $mform->addElement('html', '</div>');
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }

}
//aqui
class questionnaire_edit_question_form extends moodleform {

    public function definition() {
        global $CFG, $COURSE, $questionnaire, $question, $questionnairerealms, $SESSION;
        global $DB;

        // The 'sticky' required response value for further new questions.
        if (isset($SESSION->questionnaire->required) && !isset($question->ctrid)) {
            $question->required = $SESSION->questionnaire->required;
        }
        if (!isset($question->type_id)) {
            print_error('undefinedquestiontype', 'questionnaire');
        }

        // Initialize question type defaults.
        switch ($question->type_id) {
            case QUESTEXT:
                $deflength = 20;
                $defprecise = 25;
                $lhelpname = 'fieldlength';
                $phelpname = 'maxtextlength';
                break;
            case QUESESSAY:
                $deflength = '';
                $defprecise = '';
                $lhelpname = 'textareacolumns';
                $phelpname = 'textarearows';
                break;
            case QUESCHECK:
                $deflength = 0;
                $defprecise = 0;
                $lhelpname = 'minforcedresponses';
                $phelpname = 'maxforcedresponses';
                $olabelname = 'possibleanswers';
                $ohelpname = 'checkboxes';
                break;
            case QUESRADIO:
                $deflength = 0;
                $defprecise = 0;
                $lhelpname = 'alignment';
                $olabelname = 'possibleanswers';
                $ohelpname = 'radiobuttons';
                break;
            case QUESRATE:
                $deflength = 5;
                $defprecise = 0;
                $lhelpname = 'numberscaleitems';
                $phelpname = 'kindofratescale';
                $olabelname = 'possibleanswers';
                $ohelpname = 'ratescale';
                break;
            case QUESNUMERIC:
                $deflength = 10;
                $defprecise = 0;
                $lhelpname = 'maxdigitsallowed';
                $phelpname = 'numberofdecimaldigits';
                break;
            case QUESDROP:
                $deflength = 0;
                $defprecise = 0;
                $olabelname = 'possibleanswers';
                $ohelpname = 'dropdown';
                break;
            default:
                $deflength = 0;
                $defprecise = 0;
        }

        $defdependquestion = 0;
        $defdependchoice = 0;
        $dlabelname = 'dependquestion';

        $mform    =& $this->_form;

        // Display different messages for new question creation and existing question modification.
        if (isset($question->ctrid)) {
            $streditquestion = get_string('editquestion', 'questionnaire', questionnaire_get_type($question->type_id));
        } else {
            $streditquestion = get_string('addnewquestion', 'questionnaire', questionnaire_get_type($question->type_id));
        }
        switch ($question->type_id) {
            case QUESYESNO:
                $qtype = 'yesno';
                break;
            case QUESTEXT:
                $qtype = 'textbox';
                break;
            case QUESESSAY:
                $qtype = 'essaybox';
                break;
            case QUESRADIO:
                $qtype = 'radiobuttons';
                break;
            case QUESCHECK:
                $qtype = 'checkboxes';
                break;
            case QUESDROP:
                $qtype = 'dropdown';
                break;
            case QUESRATE:
                $qtype = 'ratescale';
                break;
            case QUESDATE:
                $qtype = 'date';
                break;
            case QUESNUMERIC:
                $qtype = 'numeric';
                break;
            case QUESSECTIONTEXT:
                $qtype = 'sectiontext';
                break;
            case QUESPAGEBREAK:
                $qtype = 'sectionbreak';
        }

        $mform->addElement('header', 'questionhdredit', $streditquestion);
        $mform->addHelpButton('questionhdredit', $qtype, 'questionnaire');

        // Name and required fields.
        if ($question->type_id != QUESSECTIONTEXT && $question->type_id != '') {
            $stryes = get_string('yes');
            $strno  = get_string('no');

            $mform->addElement('text', 'name', get_string('optionalname', 'questionnaire'),
                            array('size' => '30', 'maxlength' => '30'));
            $mform->setType('name', PARAM_TEXT);
            $mform->addHelpButton('name', 'optionalname', 'questionnaire');

            $reqgroup = array();
            $reqgroup[] =& $mform->createElement('radio', 'required', '', $stryes, 'y');
            $reqgroup[] =& $mform->createElement('radio', 'required', '', $strno, 'n');
            $mform->addGroup($reqgroup, 'reqgroup', get_string('required', 'questionnaire'), ' ', false);
            $mform->addHelpButton('reqgroup', 'required', 'questionnaire');
        }

        // Length field.
        if ($question->type_id == QUESYESNO || $question->type_id == QUESDROP || $question->type_id == QUESDATE ||
            $question->type_id == QUESSECTIONTEXT) {
            $mform->addElement('hidden', 'length', $deflength);
        } else if ($question->type_id == QUESRADIO) {
            $lengroup = array();
            $lengroup[] =& $mform->createElement('radio', 'length', '', get_string('vertical', 'questionnaire'), '0');
            $lengroup[] =& $mform->createElement('radio', 'length', '', get_string('horizontal', 'questionnaire'), '1');
            $mform->addGroup($lengroup, 'lengroup', get_string($lhelpname, 'questionnaire'), ' ', false);
            $mform->addHelpButton('lengroup', $lhelpname, 'questionnaire');
        } else if ($question->type_id == QUESTEXT || $question->type_id == QUESRATE) {
            $question->length = isset($question->length) ? $question->length : $deflength;
            $mform->addElement('text', 'length', get_string($lhelpname, 'questionnaire'), array('size' => '1'));
            $mform->setType('length', PARAM_TEXT);
            $mform->addHelpButton('length', $lhelpname, 'questionnaire');
        } else if ($question->type_id == QUESESSAY) {
            $responseformats = array(
                            "0" => get_string('formateditor', 'questionnaire'),
                            "1" => get_string('formatplain', 'questionnaire'));
            $mform->addElement('select', 'precise', get_string('responseformat', 'questionnaire'), $responseformats);
        } else if ($question->type_id == QUESCHECK || $question->type_id == QUESNUMERIC) {
            $question->length = isset($question->length) ? $question->length : $deflength;
            $mform->addElement('text', 'length', get_string($lhelpname, 'questionnaire'), array('size' => '1'));
        }

        $mform->setType('length', PARAM_INT);

        // Precision field.
        if ($question->type_id == QUESYESNO || $question->type_id == QUESDROP || $question->type_id == QUESDATE ||
            $question->type_id == QUESSECTIONTEXT || $question->type_id == QUESRADIO) {
            $mform->addElement('hidden', 'precise', $defprecise);
        } else if ($question->type_id == QUESRATE) {
            $precoptions = array("0" => get_string('normal', 'questionnaire'),
                                 "1" => get_string('notapplicablecolumn', 'questionnaire'),
                                 "2" => get_string('noduplicates', 'questionnaire'),
                                 "3" => get_string('osgood', 'questionnaire'));
            $mform->addElement('select', 'precise', get_string($phelpname, 'questionnaire'), $precoptions);
            $mform->addHelpButton('precise', $phelpname, 'questionnaire');
        } else if ($question->type_id == QUESESSAY) {
            $choices = array();
            for ($lines = 5; $lines <= 40; $lines += 5) {
                $choices[$lines] = get_string('nlines', 'questionnaire', $lines);
            }
            $mform->addElement('select', 'length', get_string('responsefieldlines', 'questionnaire'), $choices);
        } else if ($question->type_id == QUESCHECK || $question->type_id == QUESNUMERIC || $question->type_id == QUESTEXT) {
            $question->precise = isset($question->precise) ? $question->precise : $defprecise;
            $mform->addElement('text', 'precise', get_string($phelpname, 'questionnaire'), array('size' => '1'));
        }

        $mform->setType('precise', PARAM_INT);

        // Dependence fields.

        if ($questionnaire->navigate) {
            $position = isset($question->position) ? $question->position : count($questionnaire->questions) + 1;
            $dependencies = questionnaire_get_dependencies($questionnaire->questions, $position);
            $canchangeparent = true;
            if (count($dependencies) > 1) {
                if (isset($question->ctrid)) {
                    $haschildren = questionnaire_get_descendants ($questionnaire->questions, $question->ctrid);
                    if (count($haschildren) !== 0) {
                        $canchangeparent = false;
                        $parent = questionnaire_get_parent ($question);
                        $fixeddependency = $parent [$question->id]['parent'];
                    }
                }
                if ($canchangeparent) {
                    $question->dependquestion = isset($question->dependquestion) ? $question->dependquestion.','.
                                    $question->dependchoice : '0,0';
                    $group = array($mform->createElement('selectgroups', 'dependquestion', '', $dependencies) );
                    $mform->addGroup($group, 'selectdependency', get_string('dependquestion', 'questionnaire'), '', false);
                    $mform->addHelpButton('selectdependency', 'dependquestion', 'questionnaire');
                } else {
                    $mform->addElement('static', 'selectdependency', get_string('dependquestion', 'questionnaire'),
                                    '<div class="dimmed_text">'.$fixeddependency.'</div>');
                }
                $mform->addHelpButton('selectdependency', 'dependquestion', 'questionnaire');
            }
        }

        // Content field.
        $modcontext    = $this->_customdata['modcontext'];
        $editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'trusttext' => true, 'context' => $modcontext);
        $mform->addElement('editor', 'content', get_string('text', 'questionnaire'), null, $editoroptions);
        $mform->setType('content', PARAM_RAW);
        $mform->addRule('content', null, 'required', null, 'client');

        // Options section:
        // has answer options ... so show that part of the form.
        if ($DB->get_field('questionnaire_question_type', 'has_choices', array('typeid' => $question->type_id)) == 'y' ) {
            if (!empty($question->choices)) {
                $numchoices = count($question->choices);
            } else {
                $numchoices = 0;
            }

            if (!empty($question->choices)) {
                foreach ($question->choices as $choiceid => $choice) {
                    if (!empty($question->allchoices)) {
                        $question->allchoices .= "\n";
                    }
                    $question->allchoices .= $choice->content;
                }
            } else {
                $question->allchoices = '';
            }

            $mform->addElement('html', '<div class="qoptcontainer">');

            $options = array('wrap' => 'virtual', 'class' => 'qopts');
            $mform->addElement('textarea', 'allchoices', get_string('possibleanswers', 'questionnaire'), $options);
            $mform->setType('allchoices', PARAM_RAW);
            $mform->addRule('allchoices', null, 'required', null, 'client');
            $mform->addHelpButton('allchoices', $ohelpname, 'questionnaire');

            $mform->addElement('html', '</div>');

            $mform->addElement('hidden', 'num_choices', $numchoices);
            $mform->setType('num_choices', PARAM_INT);
        }

        // Hidden fields.
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'ctrid', 0);
        $mform->setType('ctrid', PARAM_INT);
        $mform->addElement('hidden', 'sid', 0);
        $mform->setType('sid', PARAM_INT);
        $mform->addElement('hidden', 'type_id', $question->type_id);
        $mform->setType('type_id', PARAM_INT);
        $mform->addElement('hidden', 'action', 'question');
        $mform->setType('action', PARAM_RAW);

        // Buttons.
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));
        if (isset($question->ctrid)) {
            $buttonarray[] = &$mform->createElement('submit', 'makecopy', get_string('saveasnew', 'questionnaire'));
        }
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // If this is a rate question.
        if ($data['type_id'] == QUESRATE) {
            if ($data['length'] < 2) {
                $errors["length"] = get_string('notenoughscaleitems', 'questionnaire');
            }
            // If this is a rate question with no duplicates option.
            if ($data['precise'] == 2 ) {
                $allchoices = $data['allchoices'];
                $allchoices = explode("\n", $allchoices);
                $nbnameddegrees = 0;
                $nbvalues = 0;
                foreach ($allchoices as $choice) {
                    if ($choice && !preg_match("/^[0-9]{1,3}=/", $choice)) {
                            $nbvalues++;
                    }
                }
                if ($nbvalues < 2) {
                    $errors["allchoices"] = get_string('noduplicateschoiceserror', 'questionnaire');
                }
            }
        }

        return $errors;
    }
}
