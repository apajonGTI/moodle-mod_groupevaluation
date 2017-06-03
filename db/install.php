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
 * Provides code to be executed during the module installation
 *
 * This file replaces the legacy STATEMENTS section in db/install.xml,
 * lib.php/modulename_install() post installation hook and partially defaults.php.
 *
 * @package    mod_groupevaluation
 * @copyright  Jose Vilas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Post installation procedure
 *
 * @see upgrade_plugins_modules()
 */
function xmldb_groupevaluation_install() {
  global $DB, $CFG;
  require_once($CFG->dirroot.'/mod/groupevaluation/defaultcriterions.php');

  foreach ($languages as $language => $value) {
    $code = $language.'_';

    foreach ($defaultcriterions as $defaultcriterion) {
      $criterionrecord = new stdClass();
      $criterionrecord->name = $crtstring[$code.$defaultcriterion];
      $criterionrecord->text = $crtstring[$code.$defaultcriterion.'_desc'];
      $criterionrecord->saved = 1;
      $criterionrecord->timecreated = time();
      $criterionrecord->defaultcriterion = 1;
      $criterionrecord->languagecode = $language;
      $criterionid = $DB->insert_record('groupevaluation_criterions', $criterionrecord);

      // Create tags for this criterion
      for ($i = 1; $i <= 5; $i++) {
        $tagrecord = new stdClass();
        $tagrecord->criterionid = $criterionid;
        $tagrecord->text = $crtstring[$code.$defaultcriterion.'_ans'.$i];
        $tagrecord->value = $i * 20;
        $tagrecord->position = 6 - $i;
        $tagrecord->timemodified = $criterionrecord->timecreated;

        $resulttag = $DB->insert_record('groupevaluation_tags', $tagrecord);
      }
    }
  }
}

/**
 * Post installation recovery procedure
 *
 * @see upgrade_plugins_modules()
 */
function xmldb_groupevaluation_install_recovery() {
}
