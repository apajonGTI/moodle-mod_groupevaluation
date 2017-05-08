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
 * The main groupevaluation configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_groupevaluation
 * @copyright  2017 Jose Vilas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form
 *
 * @package    mod_groupevaluation
 * @copyright  Jose Vilas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_groupevaluation_mod_form extends moodleform_mod {
    /** @var array options to be used with date_time_selector fields in the groupevaluation. */
    public static $datefieldoptions = array('optional' => true, 'step' => 1);
    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('groupevaluationname', 'groupevaluation'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'groupevaluationname', 'groupevaluation');

        // Introduction.
        $this->standard_intro_elements(get_string('introduction', 'groupevaluation'));

        // -------------------------------------------------------------------------------
        $mform->addElement('header', 'timing', get_string('timing', 'form'));
        // Open and close dates.
        $mform->addElement('date_time_selector', 'timeopen', get_string('groupevaluationopen', 'groupevaluation'),
                self::$datefieldoptions);
        $mform->addHelpButton('timeopen', 'quizopenclose', 'quiz');

        $mform->addElement('date_time_selector', 'timeclose', get_string('groupevaluationclose', 'groupevaluation'),
                self::$datefieldoptions);

        // -------------------------------------------------------------------------------
        $mform->addElement('header', 'results', get_string('results', 'groupevaluation'));
        $mform->addHelpButton('results', 'results', 'groupevaluation');

        /*$addqgroup = array();

        $addqgroup[] =& $mform->createElement('text', 'softupperdeviation', get_string('softupperdeviation', 'groupevaluation'));
        $mform->setType('softupperdeviation', PARAM_INT);
        $mform->addHelpButton('softupperdeviation', 'softupperdeviation', 'groupevaluation');
        $mform->setDefault('softupperdeviation', 20);

        $addqgroup[] =& $mform->createElement('text', 'softlowerdeviation', get_string('softlowerdeviation', 'groupevaluation'));
        $mform->setType('softlowerdeviation', PARAM_INT);
        $mform->addHelpButton('softlowerdeviation', 'softlowerdeviation', 'groupevaluation');
        $mform->setDefault('softlowerdeviation', -20);

        $addqgroup[] =& $mform->createElement('text', 'hardupperdeviation', get_string('hardupperdeviation', 'groupevaluation'));
        $mform->setType('hardupperdeviation', PARAM_INT);
        $mform->addHelpButton('hardupperdeviation', 'hardupperdeviation', 'groupevaluation');
        $mform->setDefault('hardupperdeviation', 40);

        $addqgroup[] =& $mform->createElement('text', 'hardlowerdeviation', get_string('hardlowerdeviation', 'groupevaluation'));
        $mform->setType('hardlowerdeviation', PARAM_INT);
        $mform->addHelpButton('hardlowerdeviation', 'hardlowerdeviation', 'groupevaluation');
        $mform->setDefault('hardlowerdeviation', -40);

        $addqgroup[] =& $mform->createElement('advcheckbox', 'fieldmaxassessment', get_string('fieldmaxassessment', 'groupevaluation'), null, array('group' => 1, ), array(0, 1));
        $addqgroup[] =& $mform->createElement('advcheckbox', 'fieldminassessment', get_string('fieldminassessment', 'groupevaluation'), null, array('group' => 1, ), array(0, 1));
        $addqgroup[] =& $mform->createElement('advcheckbox', 'fieldselfassessment', get_string('fieldselfassessment', 'groupevaluation'), null, array('group' => 1, ), array(0, 1));
        $addqgroup[] =& $mform->createElement('advcheckbox', 'fielddeviation', get_string('fielddeviation', 'groupevaluation'), null, array('group' => 1, ), array(0, 1));
        $addqgroup[] =& $mform->createElement('advcheckbox', 'fieldassessment', get_string('fieldassessment', 'groupevaluation'), null, array('group' => 1, ), array(0, 1));

        $mform->addGroup($addqgroup, 'addcrtgroup', '', ' ', false);*/

        // -------------------------------------------------------------------------------

        $mform->addElement('advcheckbox', 'fieldmaxassessment', get_string('fieldmaxassessment', 'groupevaluation'), null, array('group' => 1, ), array(0, 1));
        $mform->setDefault('fieldmaxassessment', 1);
        $mform->addElement('advcheckbox', 'fieldminassessment', get_string('fieldminassessment', 'groupevaluation'), null, array('group' => 1, ), array(0, 1));
        $mform->setDefault('fieldminassessment', 1);
        $mform->addElement('advcheckbox', 'fieldselfassessment', get_string('fieldselfassessment', 'groupevaluation'), null, array('group' => 1, ), array(0, 1));
        $mform->setDefault('fieldselfassessment', 1);
        $mform->addElement('advcheckbox', 'fielddeviation', get_string('fielddeviation', 'groupevaluation'), null, array('group' => 1, ), array(0, 1));
        $mform->setDefault('fielddeviation', 1);
        $mform->addElement('advcheckbox', 'fieldassessment', get_string('fieldassessment', 'groupevaluation'), null, array('group' => 1, ), array(0, 1));
        $mform->setDefault('fieldassessment', 1);

        // -------------------------------------------------------------------------------

        $addsoftdevgroup = array();

        $addsoftdevgroup[] =& $mform->createElement('text', 'softupperdeviation', '');
        $mform->setType('softupperdeviation', PARAM_INT);
        $mform->setDefault('softupperdeviation', 20);

        $addsoftdevgroup[] =& $mform->createElement('text', 'softlowerdeviation', '');
        $mform->setType('softlowerdeviation', PARAM_INT);
        $mform->setDefault('softlowerdeviation', -20);

        $mform->addGroup($addsoftdevgroup, 'addsoftdevgroup', get_string('softdeviation', 'groupevaluation'), ' ', false);
        $mform->addHelpButton('addsoftdevgroup', 'softdeviation', 'groupevaluation');

        $addharddevgroup = array();

        $addharddevgroup[] =& $mform->createElement('text', 'hardupperdeviation', '');
        $mform->setType('hardupperdeviation', PARAM_INT);
        $mform->setDefault('hardupperdeviation', 40);

        $addharddevgroup[] =& $mform->createElement('text', 'hardlowerdeviation', '');
        $mform->setType('hardlowerdeviation', PARAM_INT);
        $mform->setDefault('hardlowerdeviation', -40);

        $mform->addGroup($addharddevgroup, 'addharddevgroup', get_string('harddeviation', 'groupevaluation'), ' ', false);
        $mform->addHelpButton('addharddevgroup', 'harddeviation', 'groupevaluation');

        // -------------------------------------------------------------------------------

        // Add standard grading elements.
        $this->standard_grading_coursemodule_elements();

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }
}
