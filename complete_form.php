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

class groupevaluation_complete_form extends moodleform {

    public function __construct($action, $preview=false) {
        $this->preview = $preview;
        return parent::__construct($action);
    }

    public function definition() {
        global $DB, $CFG, $groupevaluation, $SESSION, $OUTPUT, $cm, $context;
        global $savedanswers, $incomplete;

        $mform    =& $this->_form;
        //TODO Actualizar la variable $SESSION en criterions.php con los datos de este formulario

        //$this->add_action_buttons();
        //$mform->addElement('submit', 'addsurvey', get_string('addsurvey', 'groupevaluation'));

        $stryes = get_string('yes');
        $strno = get_string('no');
        $strweight = get_string('weight', 'groupevaluation');
        $strposition = get_string('position', 'groupevaluation');

        $table = 'groupevaluation_criterions';
        $select = "groupevaluationid = $groupevaluation->id"; //is put into the where clause
        $criterions = $DB->get_records_select($table, $select, null, 'position ASC');

        if ($incomplete) {
          $mform->addElement('html', '<div class="alert alert-error">'.get_string('completerequiredcrt', 'groupevaluation').'</div>');
        }

        $pos = 0;
        $crtnum = 0;

        // CRITERIONS //
        $mform->addElement('header', 'criterionshdr', get_string('criterions', 'groupevaluation'));
        $mform->setExpanded('criterionshdr', true);

        $mform->addElement('html', '<div class="qcontainer">');

        foreach ($criterions as $criterion) {
            $managecrtgroup = array();

            $crtid = $criterion->id;
            $special = $criterion->special;
            $required = $criterion->required;
            $pos = $criterion->position;
            $weight = round($criterion->weight);

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

            $spacer = $OUTPUT->pix_url('spacer');

            $mform->addElement('html', '<div class="qn-container">'); // Begin div qn-container.

            if ($special == 1) {
              $srcspecial = $OUTPUT->pix_url('i/marked');
              $strspecial = get_string('special', 'groupevaluation');

            } else {
              $srcspecial = $OUTPUT->pix_url('i/marker');
              $strspecial = get_string('nospecial', 'groupevaluation');
            }
            $imgspecial = ' <img src="'.$srcspecial.'" title="'.$strspecial.'" alt="'.$strspecial.'">';

            if ($criterion->name) {
                $crtname = $criterion->name;
            } else {
                $crtname = '';
            }

            if ($required) {
              $strrequired = get_string('requiredcrt', 'groupevaluation');
              $srcrequired = $OUTPUT->pix_url('req');
              $imgrequired = '<img class="req" title="'.$strrequired.'" alt="'.$strrequired.'" src="'.$srcrequired.'"/>';
              $textrequired = '<div class="crtrequired">('.get_string('required', 'groupevaluation').')</div>';

            } else {
              $imgrequired = '';
              $textrequired = '';
            }

            $textweight = '';
            if ($this->preview) {
              $strnotvisible = 'title="'.get_string('fieldnotvisible', 'groupevaluation').'"';
              $textweight = '<div class="crtrequired" '.$strnotvisible.'>['.$strweight.': '.$weight.'%]</div>';
            }

            $managecrtgroup[] =& $mform->createElement('static', 'opentag_'.$crtid, '', '');
            $managecrtgroup[] =& $mform->createElement('static', 'special', '', $imgspecial);
            $managecrtgroup[] =& $mform->createElement('static', 'crtname_'.$crtid, '',
                            '<div class="crtname">'.$imgrequired.$crtname.'</div> '.$textrequired.$textweight);
            $managecrtgroup[] =& $mform->createElement('static', 'closetag_'.$crtid, '', '');

            $mform->addGroup($managecrtgroup, 'manageqgroup', '', '&nbsp;', false);



            $ctrnumber = '<div class="qn-info"><h2 class="qn-number">'.$pos.'</h2></div>';
            $mform->addElement('static', 'qcontent_'.$crtid, '',$ctrnumber.'<div class="qn-question">'.$text.'</div>');

            // Answers
            $tags = $DB->get_records_select('groupevaluation_tags', 'criterionid = '.$crtid, null, 'position');

            $mform->addElement('html', '<div class="crt-answers">'); // Begin div qn-container.
            foreach ($tags as $tag) {
              $checked = '';
              $name = 'answer_'.$crtid;
              if (isset($savedanswers->$name)) {
                if ($tag->value == $savedanswers->$name) {
                  $checked = 'checked="checked"';
                }
              }

              $disabled = '';
              if ($this->preview) {
                $disabled = 'disabled';
              }

              $mform->addElement('html', '<p><input name="'.$name.'" value="'.$tag->value.'" id="'.
                                  $name.'" type="radio" '.$checked.' '.$disabled.'> '.$tag->text.'</p>');
            }
            $mform->addElement('html', '</div>');
            $mform->addElement('html', '</div>'); // End div qn-container.
        }

        $strsave = get_string('save', 'groupevaluation');
        $strnotsend = get_string('savenotsend', 'groupevaluation');
        $strcancel = get_string('cancel', 'groupevaluation');
        $strsend = get_string('sendevaluation', 'groupevaluation');
        $confirmtext = get_string('confirmsubmit', 'groupevaluation');
        $href = $CFG->wwwroot.htmlspecialchars('/mod/groupevaluation/view.php?id='.$cm->id);
        $cancelhtml = '<a href="'.$href.'" class="btn btn-default btn-lg" style="margin: 0px 0px 10px 5px;" '.
                'role="button" title="'.$strcancel.'">'.$strcancel.'</a>';

        if (!($this->preview)) {
          $mform->addElement('html', '<div id="fgroup_id_buttonar" class="fitem fitem_actionbuttons fitem_fgroup">');
          $mform->addElement('html', '<div id="yui_3_17_2_1_1494530448840_1068" class="felement fgroup">');

          $mform->addElement('html', $cancelhtml);
          $mform->addElement('html', '<input type="submit" id="savebutton" name="savebutton" value="'.
                            $strsave.'" title="'.$strnotsend.'"/>');
          $mform->addElement('html', '<input type="submit" id="sendbutton" name="sendbutton" value="'.
                            $strsend.'" title="'.$strsend.'" onclick="return confirm(\''.$confirmtext.'\')" />');

          $mform->addElement('html', '</div>');
          $mform->addElement('html', '</div>');
        }

        // Hidden fields.
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'sid', 0);
        $mform->setType('sid', PARAM_INT);
        $mform->addElement('hidden', 'numcriterions', count($criterions));
        $mform->setType('numcriterions', PARAM_INT);
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }
}
