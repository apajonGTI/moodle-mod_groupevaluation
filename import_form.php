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
  * Defines the import criterions form.
  *
  * @package   mod_groupevaluation
  * @category  grade
  * @copyright Jose Vilas
  * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
  */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');


class criterion_import_form extends moodleform {

    protected function definition() {
        global $OUTPUT;

        $mform = $this->_form;

        // The file to import
        $mform->addElement('header', 'importfileupload', get_string('importcriterionsfile', 'groupevaluation'));

        $strfileformat = get_string('fileformat', 'groupevaluation');
        $strmoodlexml = get_string('moodlexmlformat', 'groupevaluation');
        $mform->addElement('static', 'fileformat', $strfileformat, $strmoodlexml);
        $mform->addHelpButton('fileformat', 'moodlexmlformat', 'groupevaluation');


        $mform->addElement('filepicker', 'newfile', get_string('import'));
        $mform->addRule('newfile', null, 'required', null, 'client');

        // Submit button.
        $mform->addElement('submit', 'submitbutton', get_string('import'));

        // Set a template for the format select elements
        $renderer = $mform->defaultRenderer();
        $template = "{help} {element}\n";
        $renderer->setGroupElementTemplate($template, 'format');
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }
}
