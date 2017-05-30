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
 * English strings for groupevaluation
 *
 *
 *
 * @package    mod_groupevaluation
 * @copyright  Jose Vilas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

//$languages = array ();
$defaultcriterions = array('crtteamwork', 'crtinteraction', 'crtkeepinggroup', 'crtquality', 'crtabilities');
$languages =  array('en' => 'english', 'es' => 'spanish', 'gl' => 'galician'); //Change in db/instal.php to

$crtstring = array ();
// --------- Default criterions ENGLISH ----------------------
$crtstring['en_crtteamwork'] = 'Contribution to Teamwork';
$crtstring['en_crtteamwork_desc'] = 'Contribution to Teamwork';
$crtstring['en_crtteamwork_ans5'] = 'Makes more work or higher quality than expected. Make important contributions that improve the work of the team. It helps teammates who are having difficulty completing their work.';
$crtstring['en_crtteamwork_ans4'] = 'Demonstrates behaviors described in the immediate higher or lower  level.';
$crtstring['en_crtteamwork_ans3'] = 'Completes reasonable participation in the work of the team with acceptable quality. Maintains the commitments and fulfills its tasks in time. It helps your teammates who are having difficulty when it is easy or important.';
$crtstring['en_crtteamwork_ans2'] = 'Demonstrates behaviors described in the next higher or lower level.';
$crtstring['en_crtteamwork_ans1'] = 'Does not make a reasonable participation in the work of the team. Deliver a sloppy or incomplete job. Does not meet deadlines. He is late, does not prepare the topics, or does not attend team meetings. It does not help teammates. Leave the job if it becomes difficult.';

$crtstring['en_crtinteraction'] = 'Interaction with groupmates';
$crtstring['en_crtinteraction_desc'] = 'Interaction with groupmates';
$crtstring['en_crtinteraction_ans5'] = 'Ask your teammates for feedback and use their suggestions for improvement. Provides encouragement or enthusiasm to the team. He makes sure that his teammates stay informed and understand each other. Ask and show interest in the ideas and contributions of teammates.';
$crtstring['en_crtinteraction_ans4'] = 'Demonstrates behaviors described in the immediate higher or lower  level.';
$crtstring['en_crtinteraction_ans3'] = 'Respect and respond to comments from your teammates. Fully participate in team activities. Communicate clearly. Share information with your peers. Listen to your teammates and respect their contributions.';
$crtstring['en_crtinteraction_ans2'] = 'Demonstrates behaviors described in the next higher or lower level.';
$crtstring['en_crtinteraction_ans1'] = 'Usually defensive. He does not accept the help or advice of his teammates. He complains, gives excuses, or does not interact with his teammates. He does actions that affect his teammates without evaluating his opinion. Does not share information. He interrupts, ignores, or mocks his teammates.';

$crtstring['en_crtkeepinggroup'] = 'Keep the group running';
$crtstring['en_crtkeepinggroup_desc'] = 'Keep the group running';
$crtstring['en_crtkeepinggroup_ans5'] = 'Controls the conditions affecting the equipment and monitors the progress of the equipment. Make sure your teammates are making the appropriate progress. Provides specific information to teammates, in a timely and constructive manner.';
$crtstring['en_crtkeepinggroup_ans4'] = 'Demonstrates behaviors described in the immediate higher or lower  level.';
$crtstring['en_crtkeepinggroup_ans3'] = 'Notices of changes that influence the success of the team. He knows what everyone on the team should be doing and realizes the problems. Alerts your teammates or suggests solutions when team success is threatened.';
$crtstring['en_crtkeepinggroup_ans2'] = 'Demonstrates behaviors described in the next higher or lower level.';
$crtstring['en_crtkeepinggroup_ans1'] = 'Not aware of whether the team is meeting its objectives. Does not pay attention to the progress of teammates. Avoid discussing team issues, even when they are obvious.';

$crtstring['en_crtquality'] = 'Expected quality';
$crtstring['en_crtquality_desc'] = 'Expected quality';
$crtstring['en_crtquality_ans5'] = 'Motivates the team to do an excellent job. He cares that the team does an extraordinary job, even if there is no additional reward. He thinks the team can do an excellent job.';
$crtstring['en_crtquality_ans4'] = 'Demonstrates behaviors described in the immediate higher or lower  level.';
$crtstring['en_crtquality_ans3'] = 'Encourage the team to do a good job that meets all the requirements. He wants the team to work well enough to get all the prizes available. It considers that the team can fully fulfill its responsibilities.';
$crtstring['en_crtquality_ans2'] = 'Demonstrates behaviors described in the next higher or lower level.';
$crtstring['en_crtquality_ans1'] = 'Satisfied even if the equipment does not meet the stipulated standards. He wants the team to work less, even if it affects the team. Doubt that the team can fulfill its objectives.';

$crtstring['en_crtabilities'] = 'Knowledge, skills and abilities';
$crtstring['en_crtabilities_desc'] = 'Knowledge, skills and abilities';
$crtstring['en_crtabilities_ans5'] = 'Demonstrates the knowledge, skills and abilities to do an excellent job. Acquire new knowledge or skills to improve team performance. Able to play the role of any team member if necessary.';
$crtstring['en_crtabilities_ans4'] = 'Demonstrates behaviors described in the immediate higher or lower  level.';
$crtstring['en_crtabilities_ans3'] = 'Demonstrates sufficient knowledge, skills, and abilities to contribute to the team\'s work. Acquire the knowledge or skills necessary to meet the requirements. Able to perform some of the tasks normally performed by other team members.';
$crtstring['en_crtabilities_ans2'] = 'Demonstrates behaviors described in the next higher or lower level.';
$crtstring['en_crtabilities_ans1'] = 'Lack of basic training necessary to be a member of the team. He can not or does not want to develop the knowledge or skills necessary to contribute to the team. You can not do any of the roles of other team members.';
// --------- end: Default criterions ENGLISH -----------------

// --------- Default criterions SPANISH ----------------------
$crtstring['es_crtteamwork'] = 'Contribución al trabajo en equipo';
$crtstring['es_crtteamwork_desc'] = 'Contribución al trabajo en equipo';
$crtstring['es_crtteamwork_ans5'] = 'Hace más trabajo o de mayor calidad que lo esperado. Realiza importantes aportaciones que mejoran el trabajo del equipo. Ayuda a los compañeros de equipo que están teniendo dificultades para completar su trabajo.';
$crtstring['es_crtteamwork_ans4'] = 'Demuestra comportamientos descritos en el nivel inmediatamente superior o inferior.';
$crtstring['es_crtteamwork_ans3'] = 'Completa una participación razonable en el trabajo del equipo con una calidad acceptable. Mantiene los compromisos y cumple con sus tareas a tiempo. Ayuda a sus compañeros de equipo que están teniendo dificultades cuando es fácil o importante.';
$crtstring['es_crtteamwork_ans2'] = 'Demuestra comportamientos descritos en el nivel inmediatamente superior o inferior.';
$crtstring['es_crtteamwork_ans1'] = 'No hace una participación razonable en el trabajo del equipo. Entrega un trabajo descuidado o incompleto. No cumple plazos. Llega tarde, sin preparar los temas, o no asiste a las reuniones del equipo. No ayuda a los compañeros de equipo. Deja el trabajo si se hace difícil.';

$crtstring['es_crtinteraction'] = 'Interacción con los compañeros de equipo';
$crtstring['es_crtinteraction_desc'] = 'Interacción con los compañeros de equipo';
$crtstring['es_crtinteraction_ans5'] = 'Pregunta a sus compañeros de equipo para conseguir realimentación y utiliza sus sugerencias para mejorar. Proporciona estímulo o entusiasmo al equipo. Se asegura de que sus compañeros de equipo se mantengan informados y se entiendan entre ellos. Pide y muestra interés por las ideas y contribuciones de los compañeros de equipo.';
$crtstring['es_crtinteraction_ans4'] = 'Demuestra comportamientos descritos en el nivel inmediatamente superior o inferior.';
$crtstring['es_crtinteraction_ans3'] = 'Respeta y responde a los comentarios de sus compañeros de equipo. Participa plenamente en las actividades del equipo. Comunica con claridad. Comparte información con sus compañeros. Escucha a sus compañeros de equipo y respeta sus contribuciones.';
$crtstring['es_crtinteraction_ans2'] = 'Demuestra comportamientos descritos en el nivel inmediatamente superior o inferior.';
$crtstring['es_crtinteraction_ans1'] = 'Suele estar a la defensiva. No accepta la ayuda o el consejo de sus compañeros de equipo. Se queja, da excusas, o no interactúa con sus compañeros de equipo. Realiza acciones que afectan a sus compañeros de equipo sin valorar su opinión. No comparte la información. Interrumpe, ignora, o se burla de sus compañeros de equipo.';

$crtstring['es_crtkeepinggroup'] = 'Mantener al equipo en funcionamiento';
$crtstring['es_crtkeepinggroup_desc'] = 'Mantener al equipo en funcionamiento';
$crtstring['es_crtkeepinggroup_ans5'] = 'Controla las condiciones que afectan al equipo y monitoriza el progreso del equipo. Se asegura de que sus compañeros de equipo estén haciendo los progresos apropiados. Proporciona información específica a los compañeros de equipo, de forma oportuna y constructiva.';
$crtstring['es_crtkeepinggroup_ans4'] = 'Demuestra comportamientos descritos en el nivel inmediatamente superior o inferior.';
$crtstring['es_crtkeepinggroup_ans3'] = 'Se da cuenta de cambios que influyen en el éxito del equipo. Sabe lo que todos en el equipo deberían estar haciendo y se da cuenta de los problemas. Alertas a sus compañeros de equipo o sugiere soluciones cuando el éxito del equipo se ve amenazado.';
$crtstring['es_crtkeepinggroup_ans2'] = 'Demuestra comportamientos descritos en el nivel inmediatamente superior o inferior.';
$crtstring['es_crtkeepinggroup_ans1'] = 'No es consciente de si el equipo está cumpliendo con sus objetivos. No presta atención a los progresos de los compañeros de equipo. Evita discutir los problemas del equipo, incluso cuando son evidentes.';

$crtstring['es_crtquality'] = 'Calidad esperada';
$crtstring['es_crtquality_desc'] = 'Calidad esperada';
$crtstring['es_crtquality_ans5'] = 'Motiva al equipo para hacer un trabajo excelente. Le importa que el equipo haga un trabajo extraordinario, incluso si no hay una recompensa adicional. Cree que el equipo puede hacer un trabajo excelente.';
$crtstring['es_crtquality_ans4'] = 'Demuestra comportamientos descritos en el nivel inmediatamente superior o inferior.';
$crtstring['es_crtquality_ans3'] = 'Alienta al equipo para hacer un buen trabajo que reúna todos los requisitos. Quiere que el equipo trabaje lo suficientemente bien como para conseguir todos los premios disponibles. Considera que el equipo pueda cumplir completamente con sus responsabilidades.';
$crtstring['es_crtquality_ans2'] = 'Demuestra comportamientos descritos en el nivel inmediatamente superior o inferior.';
$crtstring['es_crtquality_ans1'] = 'Satisfecho incluso si el equipo no cumple las normas estipuladas. Quiere que el equipo trabaje menos, incluso si afecta al equipo. Duda que el equipo pueda cumplir sus objetivos.';

$crtstring['es_crtabilities'] = 'Conocimiento, destrezas y habilidades';
$crtstring['es_crtabilities_desc'] = 'Conocimiento, destrezas y habilidades';
$crtstring['es_crtabilities_ans5'] = 'Demuestra el conocimiento, destrezas y habilidades para hacer un trabajo excelente. Adquiere nuevos conocimientos o habilidades para mejorar el rendimiento del equipo. Capaz de desempeñar el papel de cualquier miembro del equipo si es necesario.';
$crtstring['es_crtabilities_ans4'] = 'Demuestra comportamientos descritos en el nivel inmediatamente superior o inferior.';
$crtstring['es_crtabilities_ans3'] = 'Demuestra suficientes conocimientos, destrezas y habilidades para contribuir al trabajo del equipo. Adquiere los conocimientos o habilidades necesarias para cumplir con los requisitos. Capaz de realizar algunas de las tareas normalmente realizadas por otros miembros del equipo.';
$crtstring['es_crtabilities_ans2'] = 'Demuestra comportamientos descritos en el nivel inmediatamente superior o inferior.';
$crtstring['es_crtabilities_ans1'] = 'Falta de formación de base necesaria para ser un miembro del equipo. No puede o no quiere desarrollar los conocimientos o las habilidades necesarias para contribuir al equipo. No puede hacer ninguna de las funciones de otros miembros del equipo.';
// --------- end: Default criterions SPANISH -----------------

// --------- Default criterions GALICIAN ----------------------
$crtstring['gl_crtteamwork'] = 'Contribución ao traballo en equipo';
$crtstring['gl_crtteamwork_desc'] = 'Contribución ao traballo en equipo';
$crtstring['gl_crtteamwork_ans5'] = 'Fai máis traballo ou de maior calidade do esperado. Fai importantes contribucións para mellorar o traballo do equipo. Axudando compañeiros que están tendo dificultades para completar o seu traballo.';
$crtstring['gl_crtteamwork_ans4'] = 'Mostra comportamentos descritos no nivel inmediatamente superior ou inferior.';
$crtstring['gl_crtteamwork_ans3'] = 'Completa unha parte razoable do traballo en equipo con calidade acceptable. Mantén compromisos e cumpre as súas tarefas no tempo. Axuda a seus compañeiros que están tendo dificultade cando é fácil ou importante.';
$crtstring['gl_crtteamwork_ans2'] = 'Mostra comportamentos descritos no nivel inmediatamente superior ou inferior.';
$crtstring['gl_crtteamwork_ans1'] = 'Non ten unha participación razoable no traballo do equipo. Entrega un traballo descuidado ou incompleto. Non cumpre prazos. Chega tarde, sen preparar os temas, ou non comparece ás reunións do equipo. Non axuda aos compañeiros de equipo. Deixa o traballo se se fai difícil.';

$crtstring['gl_crtinteraction'] = 'Interacción con compañeiros';
$crtstring['gl_crtinteraction_desc'] = 'Interacción con compañeiros';
$crtstring['gl_crtinteraction_ans5'] = 'Pregunta aos seus compañeiros de equipo para obter feedback e utilizar as súas suxestións de mellora. Ofrece impulso ou entusiasmo para o equipo. El asegura que os seus compañeiros son mantidos informados e entender uns a outros. Chamadas e mostra interese nas ideas e contribucións dos compañeiros de equipo.';
$crtstring['gl_crtinteraction_ans4'] = 'Mostra comportamentos descritos no nivel inmediatamente superior ou inferior.';
$crtstring['gl_crtinteraction_ans3'] = 'Respecta e responde aos comentarios dos seus compañeiros de equipo. participar plenamente nas actividades do equipo. Comunica claramente. Compartir información con compañeiros. Escoita os seus compañeiros de equipo e respectar as súas contribucións.';
$crtstring['gl_crtinteraction_ans2'] = 'Mostra comportamentos descritos no nivel inmediatamente superior ou inferior.';
$crtstring['gl_crtinteraction_ans1'] = 'Normalmente na defensiva. non acceptar a axuda ou consellos dos seus compañeiros. El reclama, fai desculpas, ou non interactúa cos seus compañeiros. Realiza accións que afectan os seus compañeiros sen avaliar a súa opinión. non compartir a información. Interrupcións ignora ou mofa dos seus compañeiros de equipo.';

$crtstring['gl_crtkeepinggroup'] = 'Manter o equipo funcionando';
$crtstring['gl_crtkeepinggroup_desc'] = 'Manter o equipo funcionando';
$crtstring['gl_crtkeepinggroup_ans5'] = 'Controla as condicións que afectan o equipo e monitor o progreso do equipo. El asegura que os seus compañeiros de equipo están a facer avances axeitado. Ofrece compañeiros específicos á información en tempo hábil e construtiva.';
$crtstring['gl_crtkeepinggroup_ans4'] = 'Mostra comportamentos descritos no nivel inmediatamente superior ou inferior.';
$crtstring['gl_crtkeepinggroup_ans3'] = 'El nota de cambios que afectan o éxito do equipo. Sabe o que todos no equipo debería estar facendo e entende os problemas. Alertas de seus compañeiros de equipo ou suxerir solucións cando o éxito do equipo está ameazada.';
$crtstring['gl_crtkeepinggroup_ans2'] = 'Mostra comportamentos descritos no nivel inmediatamente superior ou inferior.';
$crtstring['gl_crtkeepinggroup_ans1'] = 'El descoñece se o equipo está acadando os seus obxectivos. Ningunha atención ao progreso dos compañeiros. Evita discutir problemas do equipo, aínda que son evidentes.';

$crtstring['gl_crtquality'] = 'Calidade esperada';
$crtstring['gl_crtquality_desc'] = 'Calidade esperada';
$crtstring['gl_crtquality_ans5'] = 'Motiva o equipo a facer un excelente traballo. Lembre que o equipo fai un excelente traballo, aínda que non haxa unha recompensa adicional. El cre que o equipo pode facer un excelente traballo.';
$crtstring['gl_crtquality_ans4'] = 'Mostra comportamentos descritos no nivel inmediatamente superior ou inferior.';
$crtstring['gl_crtquality_ans3'] = 'Anima o equipo a facer un bo traballo que atenda a todos os requisitos. El quere o equipo funciona ben o suficiente para obter todos os premios dispoñibles. El cre que o equipo pode atender plenamente as súas responsabilidades.';
$crtstring['gl_crtquality_ans2'] = 'Mostra comportamentos descritos no nivel inmediatamente superior ou inferior.';
$crtstring['gl_crtquality_ans1'] = 'Satisfeito aínda que o ordenador non atende aos estándares acordados. El quere o equipo traballando menos, aínda que iso afecte o equipo. Dubido que o equipo poida alcanzar os seus obxectivos.';

$crtstring['gl_crtabilities'] = 'Coñecementos, competencias e habilidades';
$crtstring['gl_crtabilities_desc'] = 'Coñecementos, competencias e habilidades';
$crtstring['gl_crtabilities_ans5'] = 'Demostra coñecementos, competencias e habilidades para facer un excelente traballo. Adquirir novos coñecementos ou habilidades para mellorar o rendemento do ordenador. Capaz de desempeñar o papel de calquera membro do equipo, se é necesario.';
$crtstring['gl_crtabilities_ans4'] = 'Mostra comportamentos descritos no nivel inmediatamente superior ou inferior.';
$crtstring['gl_crtabilities_ans3'] = 'Mostra coñecemento suficiente, competencias e habilidades para contribuír ao traballo do equipo. Adquire o coñecemento ou habilidades necesarias para cumprir os requisitos. Capaz de realizar algunhas das tarefas normalmente desempeñadas por outros membros do equipo.';
$crtstring['gl_crtabilities_ans2'] = 'Mostra comportamentos descritos no nivel inmediatamente superior ou inferior.';
$crtstring['gl_crtabilities_ans1'] = 'Falta de adestramento básico necesario para ser un membro do equipo. Non pode ou non vai desenvolver o coñecemento ou habilidades para contribuír co equipo. Non pode facer calquera das funcións de outros membros do equipo.';
// --------- end: Default criterions GALICIAN -----------------
