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


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot.'/mod/groupevaluation/locallib.php');
$lang = get_language();
require_once($CFG->dirroot.'/mod/groupevaluation/lang/'.$lang.'/groupevaluation.php');



class criterion_export_form extends moodleform {

    protected function definition() {
        global $OUTPUT;
        global $languages; // From defaultcriterions.php

        $mform = $this->_form;

        // Select criteria
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $strfileformat = get_string('fileformat', 'groupevaluation');
        $strmoodlexml = get_string('moodlexmlformat', 'groupevaluation');
        $mform->addElement('static', 'fileformat', $strfileformat, $strmoodlexml);
        $mform->addHelpButton('fileformat', 'moodlexmlformat', 'groupevaluation');
        
        $arrayOfOptions = array('groupevaluation' => get_string('thisgroupevaluationcriterions', 'groupevaluation'),
                                'saved' => get_string('savedcriterions', 'groupevaluation'),
                                'default' => get_string('defaultcriterions', 'groupevaluation'),
                                'all' => get_string('allcriterions', 'groupevaluation'));
        $attributes = array('onchange' => 'selectTypeChanged();','id' => 'id_type');
        $mform->addElement('select', 'type', get_string('exportcriterions', 'groupevaluation'), $arrayOfOptions, $attributes);
        //$mform->addHelpButton('type', 'weight', 'groupevaluation');


        // Default language
        $currentlanguaje = current_language();
        if (in_array($currentlanguaje, $languages)) { // $languages from defaultcriterions.php
          $langaux = $currentlanguaje;
        } else {
          $langaux = 'en';
        }

        // Select languages
        //$options =  array();
        $html = '<div id="id_select_language" style="visibility:hidden">';
        $html .= '<div id="fitem_id_languaje" class="fitem fitem_fselect ">';
        $html .= '<div class="fitemtitle">';
        $html .= '<label for="id_languaje">'.get_string('exportlanguage', 'groupevaluation').' </label>';
        $html .= '</div>';
        $html .= '<div class="felement fselect">';
        $html .= '<select id="id_languaje" name="language">';
        foreach ($languages as $language => $value) {
          //$options[$language] = get_string($value,'groupevaluation');
          $selected = '';
          if ($language == $langaux) {
            $selected = 'selected="selected"';
          }
          $html .= '<option value="'.$language.'" '.$selected.'>'.get_string($value,'groupevaluation').'</option>';
        }
        $html .= '</select>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        $mform->addElement('html', $html);

        //$mform->addElement('select', 'language', get_string('exportlanguage', 'groupevaluation'), $options, $attributes);
        //$mform->setDefault('language', $langaux);

        // Set a template for the format select elements
        $renderer = $mform->defaultRenderer();
        $template = "{help} {element}\n";
        $renderer->setGroupElementTemplate($template, 'format');

        // Submit buttons.
        $this->add_action_buttons(false, get_string('exportquestions', 'question'));
    }
}
