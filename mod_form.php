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
        $mform->setExpanded('timing');

        // Open and close dates.
        $mform->addElement('date_time_selector', 'timeopen', get_string('groupevaluationopen', 'groupevaluation'),
                self::$datefieldoptions);
        $mform->addHelpButton('timeopen', 'quizopenclose', 'quiz');

        $mform->addElement('date_time_selector', 'timeclose', get_string('groupevaluationclose', 'groupevaluation'),
                self::$datefieldoptions);

        // -------------------------------------------------------------------------------
        $mform->addElement('header', 'results', get_string('results', 'groupevaluation'));
        $mform->addHelpButton('results', 'results', 'groupevaluation');
        $mform->setExpanded('results');



        // -------------------- VIEW CHECKBOXES ----------------------------------------

        $mform->addElement('html', '<div class="viewcheckbox" style="float:left;">');

        $mform->addElement('advcheckbox', 'viewaverage', get_string('average', 'groupevaluation'), null, array('group' => 1), array(0, 1));
        $mform->setDefault('viewaverage', 1);

        $mform->addElement('advcheckbox', 'viewselfevaluation', get_string('selfevaluation', 'groupevaluation'), null, array('group' => 1), array(0, 1));
        $mform->setDefault('viewselfevaluation', 1);

        $mform->addElement('advcheckbox', 'viewdeviation', get_string('deviation', 'groupevaluation'), null, array('group' => 1), array(0, 1));
        $mform->setDefault('viewdeviation', 1);

        $mform->addElement('advcheckbox', 'viewweight', get_string('weight', 'groupevaluation'), null, array('group' => 1), array(0, 1));
        $mform->addHelpButton('viewweight', 'weight', 'groupevaluation');
        $mform->setDefault('viewweight', 0);

        $mform->addElement('html', '</div><div class="viewcheckbox" style="float:left;">');

        $mform->addElement('advcheckbox', 'viewmaximum', get_string('maximum', 'groupevaluation'), null, array('group' => 1), array(0, 1));
        $mform->setDefault('viewmaximum', 1);

        $mform->addElement('advcheckbox', 'viewminimum', get_string('minimum', 'groupevaluation'), null, array('group' => 1), array(0, 1));
        $mform->setDefault('viewminimum', 1);

        $mform->addElement('advcheckbox', 'viewgrade', get_string('grade', 'groupevaluation'), null, array('group' => 1), array(0, 1));
        $mform->setDefault('viewgrade', 1);

        $mform->addElement('advcheckbox', 'viewanswers', get_string('viewanswers', 'groupevaluation'), null, array('group' => 1), array(0, 1));
        $mform->addHelpButton('viewanswers', 'viewanswers', 'groupevaluation');
        $mform->setDefault('viewanswers', 0);

        $mform->addElement('html', '</div>');

        // -------------------------------------------------------------------------------
        $mform->addElement('html', '<div style="clear: left;">');

        $addsoftdevgroup = array();
        $srcarrowreddown = new moodle_url($CFG->wwwroot.'/mod/groupevaluation/pix/arrow_red_down.gif');
        $srcarrowyellowdown = new moodle_url($CFG->wwwroot.'/mod/groupevaluation/pix/arrow_yellow_down.gif');
        $srcarrowyellowup = new moodle_url($CFG->wwwroot.'/mod/groupevaluation/pix/arrow_yellow_up.gif');
        $srcarrowgreenup = new moodle_url($CFG->wwwroot.'/mod/groupevaluation/pix/arrow_green_up.gif');
        $arrowreddown = '<img class="iconsmall" src="'.$srcarrowreddown.'">';
        $arrowyellowdown = '<img class="iconsmall" src="'.$srcarrowyellowdown.'">';
        $arrowyellowup = '<img class="iconsmall" src="'.$srcarrowyellowup.'">';
        $arrowgreenup = '<img class="iconsmall" src="'.$srcarrowgreenup.'">';
        $softarrows = $arrowyellowup.' '.$arrowyellowdown;
        $hardarrows = $arrowgreenup.' '.$arrowreddown;

        $addsoftdevgroup[] =& $mform->createElement('text', 'softupperdeviation', '');
        $mform->setType('softupperdeviation', PARAM_INT);
        $mform->setDefault('softupperdeviation', 20);

        $addsoftdevgroup[] =& $mform->createElement('text', 'softlowerdeviation', '');
        $mform->setType('softlowerdeviation', PARAM_INT);
        $mform->setDefault('softlowerdeviation', -20);

        $mform->addGroup($addsoftdevgroup, 'addsoftdevgroup', get_string('softdeviation', 'groupevaluation'), ' '.$softarrows.' ', false);
        $mform->addHelpButton('addsoftdevgroup', 'softdeviation', 'groupevaluation');

        $addharddevgroup = array();

        $addharddevgroup[] =& $mform->createElement('text', 'hardupperdeviation', '');
        $mform->setType('hardupperdeviation', PARAM_INT);
        $mform->setDefault('hardupperdeviation', 40);

        $addharddevgroup[] =& $mform->createElement('text', 'hardlowerdeviation', '');
        $mform->setType('hardlowerdeviation', PARAM_INT);
        $mform->setDefault('hardlowerdeviation', -40);

        $mform->addGroup($addharddevgroup, 'addharddevgroup', get_string('harddeviation', 'groupevaluation'), ' '.$hardarrows.' ', false);
        $mform->addHelpButton('addharddevgroup', 'harddeviation', 'groupevaluation');

        $mform->addElement('html', '</div>');

        // -------------------------------------------------------------------------------

        // Add standard grading elements.
        $this->standard_grading_coursemodule_elements();

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }
}
