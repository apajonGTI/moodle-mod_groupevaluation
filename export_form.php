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
require_once($CFG->dirroot.'/mod/groupevaluation/defaultcriterions.php');



class criterion_export_form extends moodleform {

    protected function definition() {
        global $OUTPUT;
        global $languages; // From defaultcriterions.php

        $mform = $this->_form;

        // Select criteria
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $arrayOfOptions = array('groupevaluation' => get_string('thisgroupevaluationcriterions', 'groupevaluation'),
                                'saved' => get_string('savedcriterions', 'groupevaluation'),
                                'default' => get_string('defaultcriterions', 'groupevaluation'),
                                'all' => get_string('allcriterions', 'groupevaluation'));
        $mform->addElement('select', 'type', get_string('exportcriterions', 'groupevaluation'), $arrayOfOptions);
        //$mform->addHelpButton('type', 'weight', 'groupevaluation');


        // Default language
        $currentlanguaje = current_language();
        if (in_array($currentlanguaje, $languages)) { // $languages from defaultcriterions.php
          $langaux = $currentlanguaje;
        } else {
          $langaux = 'en';
        }

        // Select languages
        $options =  array();
        foreach ($languages as $language => $value) {
          $options[$language] = get_string($value,'groupevaluation');
        }

        $mform->addElement('select', 'language', get_string('defaultcriterionslanguage', 'groupevaluation'), $options);
        $mform->setDefault('language', $langaux);

        // Set a template for the format select elements
        $renderer = $mform->defaultRenderer();
        $template = "{help} {element}\n";
        $renderer->setGroupElementTemplate($template, 'format');

        // Submit buttons.
        $this->add_action_buttons(false, get_string('exportquestions', 'question'));
    }
}
