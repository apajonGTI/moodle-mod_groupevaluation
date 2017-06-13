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
 * Define all the backup steps that will be used by the backup_groupevaluation_activity_task
 *
 * @package   mod_groupevaluation
 * @category  backup
 * @copyright Jose Vilas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Define the complete groupevaluation structure for backup, with file and id annotations
 *
 * @package   mod_groupevaluation
 * @category  backup
 * @copyright Jose Vilas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_groupevaluation_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the backup structure of the module
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        global $DB;

        // Get know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define the root element describing the groupevaluation instance.
        $groupevaluation = new backup_nested_element('groupevaluation', array('id'), array(
            'course', 'name', 'intro', 'introformat', 'timecreated', 'timemodified', 'timeopen',
            'timeclose', 'grade', 'hardlowerdeviation', 'hardupperdeviation', 'softlowerdeviation',
            'softupperdeviation', 'viewaverage', 'viewselfevaluation', 'viewdeviation', 'viewmaximum',
            'viewminimum', 'viewgrade', 'viewweight', 'viewanswers', 'viewresults'));


        // Define each element separated.
        $surveys = new backup_nested_element('surveys');
        $survey = new backup_nested_element('survey', array('id'), array(
            'authorid', 'userid', 'groupid', 'groupevaluationid', 'submitted', 'timemodified', 'status', 'mailed'));

        $criterions = new backup_nested_element('criterions');
        $criterion = new backup_nested_element('criterion', array('id'), array(
            'groupevaluationid', 'name', 'text', 'textformat', 'weight', 'saved', 'defaultcriterion', 'languagecode',
            'timemodified', 'createdby', 'modifiedby', 'special', 'position', 'required'));

        $tags = new backup_nested_element('tags');
        $tag = new backup_nested_element('tag', array('id'), array(
            'criterionid', 'text', 'value', 'timemodified', 'position'));

        // Build the tree.
        $groupevaluation->add_child($surveys);
        $surveys->add_child($survey);

        $groupevaluation->add_child($criterions);
        $criterions->add_child($criterion);

        $criterion->add_child($tags);
        $tags->add_child($tag);

        // Define data sources.
        $groupevaluation->set_source_table('groupevaluation', array('id' => backup::VAR_ACTIVITYID));

        $survey->set_source_table('groupevaluation_surveys', array('groupevaluationid' => '../../id'));
        $criterion->set_source_table('groupevaluation_criterions', array('groupevaluationid' => '../../id'));
        $tag->set_source_table('groupevaluation_tags', array('criterionid' => '../../id'));

        // If we were referring to other tables, we would annotate the relation
        // with the element's annotate_ids() method.

        // Define file annotations (we do not use itemid in this example).
        $groupevaluation->annotate_files('mod_groupevaluation', 'intro', null);

        // Return the root element (groupevaluation), wrapped into standard activity structure.
        return $this->prepare_activity_structure($groupevaluation);
    }
}
