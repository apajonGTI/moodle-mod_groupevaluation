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
 * Define all the restore steps that will be used by the restore_groupevaluation_activity_task
 *
 * @package   mod_groupevaluation
 * @category  backup
 * @copyright Jose Vilas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Structure step to restore one groupevaluation activity
 *
 * @package   mod_groupevaluation
 * @category  backup
 * @copyright Jose Vilas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_groupevaluation_activity_structure_step extends restore_activity_structure_step {

    /**
     * Defines structure of path elements to be processed during the restore
     *
     * @return array of {@link restore_path_element}
     */
    protected function define_structure() {

        $paths = array();
        $paths[] = new restore_path_element('groupevaluation', '/activity/groupevaluation');

        $paths[] = new restore_path_element('groupevaluation_surveys', '/activity/groupevaluation/surveys/survey');
        $paths[] = new restore_path_element('groupevaluation_criterions', '/activity/groupevaluation/criterions/criterion');
        $paths[] = new restore_path_element('groupevaluation_tags', '/activity/groupevaluation/criterions/criterion/tags/tag');

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process the given restore path element data
     *
     * @param array $data parsed element data
     */
    protected function process_groupevaluation($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->timeopen = 0;
        $data->timeclose = 0;

        if (empty($data->timecreated)) {
            $data->timecreated = time();
        }

        if (empty($data->timemodified)) {
            $data->timemodified = time();
        }

        if ($data->grade < 0) {
            // Scale found, get mapping.
            $data->grade = -($this->get_mappingid('scale', abs($data->grade)));
        }

        // Create the groupevaluation instance.
        $newitemid = $DB->insert_record('groupevaluation', $data);
        $this->apply_activity_instance($newitemid);
    }

    protected function process_groupevaluation_surveys($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->groupevaluationid = $this->get_new_parentid('groupevaluation');
        $data->status = 0;

        if (empty($data->timecreated)) {
            $data->timecreated = time();
        }

        // Insert the groupevaluation_survey record.
        $newitemid = $DB->insert_record('groupevaluation_surveys', $data);
        $this->set_mapping('groupevaluation_surveys', $oldid, $newitemid, true);

    }

    protected function process_groupevaluation_criterions($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->groupevaluationid = $this->get_new_parentid('groupevaluation');

        if (empty($data->timecreated)) {
            $data->timecreated = time();
        }

        // Insert the groupevaluation_question record.
        $newitemid = $DB->insert_record('groupevaluation_criterions', $data);
        $this->set_mapping('groupevaluation_criterions', $oldid, $newitemid, true);
    }

    protected function process_groupevaluation_tags($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->criterionid = $this->get_new_parentid('groupevaluation_criterions');

        // Insert the groupevaluation_tags record.
        $newitemid = $DB->insert_record('groupevaluation_tags', $data);
        $this->set_mapping('groupevaluation_tags', $oldid, $newitemid, true);
    }

    /**
     * Post-execution actions
     */
    protected function after_execute() {
        // Add groupevaluation related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_groupevaluation', 'intro', null);
    }
}
