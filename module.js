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
* JavaScript library for the quiz module.
*
* @package    mod_groupevaluation
* @copyright  Jose Vilas
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

function setNewGrade(userid, text) {

  var grade = prompt(text);

  if (isFinite(String(grade))) {
    if (grade >= 0 && grade <= 100) {
      var newgrade = document.createElement("input");
      newgrade.setAttribute("type", "hidden");
      newgrade.setAttribute("name", "newgrade");
      newgrade.setAttribute("value", 1);

      var input = document.createElement("input");
      input.setAttribute("type", "hidden");
      input.setAttribute("name", "editgrade[" + userid + "]");
      input.setAttribute("value", grade);
      //append to form
      document.getElementById("groupevaluation_reportform").appendChild(newgrade);
      document.getElementById("groupevaluation_reportform").appendChild(input);
    }
  }
}

function viewSubmit(id, allfields) {

  if (allfields) {
    var input = document.createElement("input");
    input.setAttribute("type", "hidden");
    input.setAttribute("name", "allfields");
    input.setAttribute("value", 1);
    //append to form element that you want .
    document.getElementById(id).appendChild(input);
  }

  document.getElementById(id).submit();
}

M.mod_groupevaluation = M.mod_groupevaluation || {};

M.mod_groupevaluation.init_attempt_form = function(Y) {
   M.core_formchangechecker.init({formid: 'phpesp_response'});
};

M.mod_groupevaluation.init_check = function(Y) {
   Y.on('click', function(e) {
       checked = this.get('checked');
       Y.all('input.removecheckbox').each(function() {
           this.set('checked', checked);
       });
       if (document.getElementById('checknotadded').checked) {
         document.getElementById('checknotadded').checked = false;
         Y.all('input.addcheckbox').each(function() {
             this.set('checked', false);
         });
       }
   }, '#checkadded');

   Y.on('click', function(e) {
       checked = this.get('checked');
       Y.all('input.addcheckbox').each(function() {
           this.set('checked', checked);
       });
       if (document.getElementById('checkadded').checked) {
         document.getElementById('checkadded').checked = false;
         Y.all('input.removecheckbox').each(function() {
             this.set('checked', false);
         });
       }
   }, '#checknotadded');

   Y.on('click', function(e) {
       checked = this.get('checked');
       Y.all('input.addcheckbox').each(function() {
           this.set('checked', checked);
       });
       Y.all('input.removecheckbox').each(function() {
           this.set('checked', checked);
       });
   }, '#selectall');
};

M.mod_groupevaluation.init_sendmessage = function(Y) {
    Y.on('click', function(e) {
        Y.all('input.usercheckbox').each(function() {
            this.set('checked', 'checked');
        });
    }, '#checkall');

    Y.on('click', function(e) {
        Y.all('input.usercheckbox').each(function() {
            this.set('checked', '');
        });
    }, '#checknone');

    Y.on('click', function(e) {
        Y.all('input.usercheckbox').each(function() {
            if (this.get('alt') == 0) {
                this.set('checked', 'checked');
            } else {
                this.set('checked', '');
            }
        });
    }, '#checknotstarted');

    Y.on('click', function(e) {
        Y.all('input.usercheckbox').each(function() {
            if (this.get('alt') == 1) {
                this.set('checked', 'checked');
            } else {
                this.set('checked', '');
            }
        });
    }, '#checkstarted');

};
