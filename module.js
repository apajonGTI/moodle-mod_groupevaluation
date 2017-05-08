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

/*
 function addAllGroups(source) {
   checkboxes = document.getElementsByName('addgroup');
   for(var i=0, n=checkboxes.length;i<n;i++) {
     checkboxes[i].checked = source.checked;
   }
 }

 function removeAllGroups(source) {
   checkboxes = document.getElementsByName('removegroup');
   for(var i=0, n=checkboxes.length;i<n;i++) {
     checkboxes[i].checked = source.checked;
   }
 }*/

 M.mod_groupevaluation = M.mod_groupevaluation || {};


 M.mod_groupevaluation.init_sendmessage = function(Y) {
     Y.on('click', function(e) {
         checked = this.get('checked');
         Y.all('input.addcheckbox').each(function() {
             this.set('checked', checked);
         });
     }, '#addall');

     Y.on('click', function(e) {
         checked = this.get('checked');
         Y.all('input.removecheckbox').each(function() {
             this.set('checked', checked);
         });
     }, '#removeall');
 };
