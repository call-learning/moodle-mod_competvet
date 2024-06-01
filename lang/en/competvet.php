<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     mod_competvet
 * @category    string
 * @copyright   2023 - CALL Learning - Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
$string['admincompetvet:role'] = 'Admin CompetVet';
$string['admincompetvet:role:desc'] = 'Admin CompetVet can do anything on any situation of the platform.';
$string['allmysituations'] = 'All my situations';
$string['pluginname'] = 'CompetVet';
$string['modulename'] = 'CompetVet';
$string['modulenameplural'] = 'CompetVet';
$string['appraiser'] = 'Appraiser';
$string['competvetname'] = 'CompetVet Activity Name';
$string['competvetsettings'] = 'CompetVet Settings';
$string['competvetplanning'] = 'Planning';
$string['competvet:addinstance'] = 'Can add a new CompetVet activity';
$string['competvet:canaskobservation'] = 'Can ask for an observation';
$string['competvet:candoeverything'] = 'Can do everything';
$string['competvet:cangrade'] = 'Can grade a student';
$string['competvet:canobserve'] = 'Can observe a student';
$string['competvet:editplanning'] = 'Can edit planning';
$string['competvet:view'] = 'Can view a situation';
$string['competvet:viewother'] = 'Can view other\'s situations and planning';
// Additional capabilities to create.
$string['competvet:managecompetencies'] = 'Manage CompetVet competencies';
$string['competvet:managecompetencyframeworks'] = 'Manage CompetVet competency frameworks';
$string['competvet:managesituationtemplates'] = 'Manage CompetVet situation templates';
$string['competvet:editcriteria'] = 'Edit CompetVet criteria';
$string['criterion:label'] = 'Criterion label';
$string['criterion:sort'] = 'Criterion sort';
$string['criterion:grid'] = 'Criterion evaluation grid';
$string['criterion:idnumber'] = 'Criterion unique ID';
$string['criterion:parentid'] = 'Criterion Parent ID';
$string['criterion:parentlabel'] = 'Criterion Parent Label';
$string['criterion:parentidnumber'] = 'Criterion Parent ID Number';
$string['editplanning'] = 'Edit planning';
$string['grid:selector'] = 'Evaluation grid selector';
$string['grid:name'] = 'Evaluation grid name';
$string['grid:idnumber'] = 'Evaluation grid idnumber';
$string['entity:grid'] = 'Evaluation grid';
$string['pluginadministration'] = 'CompetVet Administration';
$string['situationname'] = 'Name of the situation';
$string['context'] = 'Context';
$string['comments'] = 'Comments';
$string['comment'] = 'Comment';
$string['criterion'] = 'Criterion';
$string['commentfor'] = 'Comment for {$a}';
$string['layout:default'] = 'Click to expand review panel';
$string['layout:collapsegradepanel'] = 'Collapse grade panel';
$string['layout:collapsereviewpanel'] = 'Collapse review panel';
$string['grade'] = 'Grade';
$string['gradefor'] = 'Grade for {$a}';
$string['gradeedit'] = 'Edit grades';
$string['grade_eval_name'] = 'Eval. Observations';
$string['grade_list_name'] = 'Eval. List';
$string['grade_caselog_name'] = 'Eval. Case Log';
$string['startdate'] = 'Start date';
$string['student'] = 'Student';
$string['enddate'] = 'End date';
$string['group'] = 'Group';
$string['grid:default:eval'] = 'Grille d\'évaluation par défaut (EVAL)';
$string['grid:default:list'] = 'Grille d\'évaluation par défaut (LIST)';
$string['grid:default:certif'] = 'Grille d\'évaluation par défaut (CERTIF)';
$string['view'] = 'View';
$string['edit'] = 'Edit';
$string['delete'] = 'Delete';
$string['add'] = 'Add';
$string['gradeitem:list'] = 'List Grade';
$string['gradeitem:caselog'] = 'Case Log Grade';
$string['gradeitem:eval'] = 'Eval Grade';
$string['nofilters'] = 'No filters';
$string['loading'] = 'Loading';
$string['back'] = 'Back';
$string['invaliddatafor'] = 'Invalid data for {$a}';
$string['invaliddata'] = 'Invalid data for {$a}';
$string['observer:role'] = 'Observer';
$string['observer:role:desc'] = 'Observer role: a user with this role can observe a student and create an observation';
$string['observer:fullname'] = 'Observer Fullname';
$string['entity:observation_comment'] = 'Observation comment';
$string['observation_comment:name'] = 'Observation comment name';
$string['observation_comment:comment'] = 'Observation comment content';
$string['evaluator:role'] = 'Evaluator';
$string['evaluator:role:desc'] = 'Evaluator role: a user with this role can observe a student and give him a grade (eval, caselog, list)';
$string['progress_createcompetvets'] = 'Creating Situations ({$a})';
$string['progress_createaccountswithroles'] = 'Creating user accounts with role {$a->rolename} ({$a->from} - {$a->to})';
$string['progress_creategroups'] = 'Creating groups';
$string['progress_createobservations'] = 'Creating observations ({$a->count}) for {$a->situation}';
$string['progress_createplannings'] = 'Creating plannings ({$a->count}) for {$a->situation}';
$string['responsibleucue:role'] = 'Responsible UC/UE';
$string['planning:defaultsession'] = 'Default planning session';
$string['planning:session'] = 'Planning session';
$string['planning:session_help'] = 'Planning session';
$string['responsibleucue:role:desc'] = 'Responsible UC/UE role: a user with this role can observe a student but can also create a new situation and plannings.';
$string['planning:confirmdelete'] = 'Are you sure you want to delete this planning?';
$string['report:plannings'] = 'Plannings report';
$string['report:situations'] = 'Situation report';
$string['report:criteria'] = 'Criteria report';
$string['report:grids'] = 'Evaluation grids report';
$string['report:observations'] = 'Observations report';
$string['report:todos'] = 'TODO report';
$string['entity:planning'] = 'Planning';
$string['entity:situation'] = 'Situation';
$string['entity:criterion'] = 'Criterion';
$string['entity:grid'] = 'Evaluation Grid';
$string['entity:observation_comment'] = 'Observation comment';
$string['observation:status'] = 'Status';
$string['observation:status:archived'] = 'Archived';
$string['observation:status:notstarted'] = 'Not started';
$string['observation:status:inprogress'] = 'In Progress';
$string['observation:status:completed'] = 'Completed';
$string['tab:eval'] = 'Eval ({$a->done}/{$a->required})';
$string['tab:autoeval'] = 'Auto-Eval ({$a->done}/{$a->required})';
$string['tab:list'] = 'List ({$a->cases})';
$string['tab:cert'] = 'Cert ({$a->certdone}/{$a->certopen})';
$string['entity:competvet_observation'] = 'Observation';
$string['situation:def'] = 'Situation definition';
$string['situation:shortnamewithlinks'] = 'Situation short name';
$string['situation:tagnames'] = 'Situation tags';
$string['situation:shortname'] = 'Situation short name';
$string['situation:shortname_help'] = 'Situation short name';
$string['situation:evalnum'] = 'Required evaluations number';
$string['situation:evalnum_help'] = 'Required evaluations number';
$string['situation:autoevalnum'] = 'Required autoevaluation number';
$string['situation:autoevalnum_help'] = 'Required autoevaluation number';
$string['situation:tags:y:1'] = 'First year';
$string['situation:tags:y:2'] = 'Second year';
$string['situation:tags:y:3'] = 'Third year';
$string['situation:selector'] = 'Situation selector';
$string['situation:intro'] = 'Situation Intro';
$string['situation:name'] = 'Situation Name (Full)';
$string['situation:tags'] = 'Tags for situation';
$string['situation:cmid'] = 'Course Module ID for the situation';
$string['situation:evalgrid'] = 'Eval Grid';
$string['situation:listgrid'] = 'List Grid';
$string['situation:certifgrid'] = 'Certif Grid';
$string['status:draft'] = 'Draft';
$string['status:published'] = 'Published';
$string['status:archived'] = 'Archived';
$string['planning:page:students'] = 'Students - {$a}';
$string['planning:page:observers'] = 'Observers';
$string['planning:page:info:eval'] = 'Evaluations';
$string['planning:page:info:autoeval'] = 'Auto-Evaluations';
$string['planning:page:info:list'] = 'List';
$string['planning:page:info:cert'] = 'Certification';
$string['planningcategory:current'] = 'Current';
$string['planningcategory:future'] = 'Future';
$string['planningcategory:observerlate'] = 'Late';
$string['planningcategory:observercompleted'] = 'Finies';
$string['student:fullname'] = 'Student Fullname';
$string['sendstudentnotifications'] = 'Send student notifications';
$string['savingchanges'] = 'Saving changes';
$string['situation:idnumber'] = 'Situation Unique ID';
$string['tagarea_competvet_situation'] = 'Situations';
$string['tagcollection_situations'] = 'Situations';
$string['observation:category:eval:observation'] = 'Observations';
$string['observation:category:eval:autoeval'] = 'Autoevaluations';
$string['observation:comment:context'] = 'Context';
$string['observation:comment:comment'] = 'Comment';
$string['observation:comment:privatecomment'] = 'Private comment';
$string['observation:comment:progress'] = 'Progress';
$string['observation:comment:improvement'] = 'Improvement';
$string['observation:comment:missing'] = 'Missing';
$string['observation:auto'] = 'Add an autoevaluation';
$string['observation:auto:save'] = 'Save autoevaluation';
$string['observation:add'] = 'Add an observation';
$string['observation:ask'] = 'Ask for an observation';
$string['observation:ask:save'] = 'Select observer';
$string['observation:asked'] = 'Observation asked';
$string['observation:asked:body'] = 'Observation asked to {$a}';
$string['observation:edit'] = 'Edit observation';
$string['observation:delete'] = 'Delete the observation';
$string['observation:delete:confirm'] = 'Confirm you want to delete the observation';
$string['observation:add:save'] = 'Save';
$string['observation:edit:save'] = 'Save';
$string['observation:comment:commentno'] = 'Comment {no}';
$string['observation:comment:add'] = 'Add comment';
$string['observation:comment:deleteno'] = 'Delete comment {no}';
$string['todos'] = 'ToDos';
$string['entity:competvet_todo'] = 'ToDo';
$string['todo:status:pending'] = 'Pending';
$string['todo:status'] = 'Status';
$string['todo:user'] = 'User';
$string['todo:targetuser'] = 'Target User';
$string['todo:type'] = 'Type';
$string['todo:planning'] = 'Planning';
$string['todo:user:fullname'] = 'User full name';
$string['todo:action:eval:asked'] = 'Observation Asked';
$string['todo:action:cta:eval:asked'] = 'Create Observation';
$string['todo:action'] = 'Action';
$string['todo:data'] = 'Data';

// Grading interface
$string['modulename_help'] = 'The Compet Eval Grading interface.';
$string['modulename_link'] = 'mod/competgrade/view';
$string['privacy:metadata'] = 'The Compet grade plugin does not store any personal data.';
$string['closeevaluation'] = 'Close evaluation';
$string['previoususer'] = 'Previous user';
$string['nextuser'] = 'Next user';
$string['competgrade:grade'] = 'Receive a grade';
$string['competgrade:viewallgrades'] = 'View all user grades';
$string['evaluate'] = 'Global Evaluation';
$string['certify'] = 'Certify';
$string['list'] = 'List';
$string['globalgrade'] = 'Global grade';
$string['globalcomment'] = 'Global comment';
$string['globalcomment_info'] = 'This comment will be shared with the student';
$string['commentsaved'] = 'Comment saved';
$string['enterskillassesment'] = 'enter your skills assessments here';
$string['entercertificationassesment'] = 'enter your certification assessments here';
$string['more'] = 'more';
$string['less'] = 'less';
$string['points'] = '{$a} points';

$string['managecriteria'] = 'Manage criteria';
$string['list_criteria'] = 'List criteria';
$string['certif_criteria'] = 'Certification criteria';
$string['eval_criteria'] = 'Evaluation criteria';

// Eval
$string['evalevaluation'] = 'Eval evaluation';
$string['evalscore'] = 'Average evaluation score';
$string['numberofobservations'] = 'Number of observations';
$string['penalty'] = 'Penalty';
$string['penalty_help'] = 'The number of observations obtained is insufficient. A penalty of -20 points is applied.';
$string['deactivatepenalty'] = 'Deactivate penalty';
$string['selfevaluation'] = 'Self evaluation';
$string['finalscore'] = 'Proposed final score';
$string['scoreevaluator'] = 'Score validated by the evaluator';
$string['scoreevaluator_help'] = 'This grade will be the one taken into account for the calculation of the overall grade.';
$string['freecommenteval'] = 'Free comment for the Eval part';
$string['selfevalnone'] = '+ 0 No relevant self-evaluation';
$string['selfevalbonus'] = '+{$a} realised and relevant';
$string['supervisorchart'] = 'Supervisor chart';
$string['noevaluations'] = 'No evaluations';

// Certif
$string['certifevaluation'] = 'Certif evaluation';
$string['numcertifvalidated'] = 'Number of essentials validated';
$string['statusproposed'] = 'Status proposed by CompetVet';
$string['evaluatordecision'] = 'Evaluator decision';
$string['evaluatordecision_help'] = 'Non-validation being a deal breaker, you can manually change this grade. If you force the validation, please comment.';
$string['evalcomment'] = 'Free commentary on the essential clinical aspects';
$string['confidencelevel'] = 'Confidence level';
$string['seendone'] = 'Seen and done';
$string['confirmed'] = 'Confirmed';
$string['notseen'] = 'Not seen';
$string['validated'] = 'Validated';
$string['nocertifications'] = 'No certifications';

$string['evaluation'] = 'Global Evaluation';
$string['suggestedgrade'] = 'Suggested grade';
$string['suggestedgrade_help'] = 'Suggested grade';
$string['finalgrade'] = 'Final grade';
$string['commment'] = 'Comment';
$string['submit'] = 'Submit';
$string['processing'] = 'Processing';

$string['inuse'] = 'In use';
$string['move'] = 'Move';
$string['saving'] = 'Saving';
$string['planning'] = 'Planning';
$string['addplanning'] = 'Add a planning';

$string['addgrid'] = 'Add a grid';
$string['addcriterion'] = 'Add a criterion';
$string['grading'] = 'Grading';
$string['addoption'] = 'Add an option';
$string['newcriterion'] = 'New criterion';
$string['newoption'] = 'New option';
$string['newgrid'] = 'New grid';

// English list
$string['animal'] = 'Animal';
$string['animalspecies'] = 'Species';
$string['animalbreed'] = 'Breed';
$string['animalbreedunknown'] = 'Unknown Breed';
$string['animalsex'] = 'Sex';
$string['animalsexunknown'] = 'Unknown Sex';
$string['animalage'] = 'Age';
$string['animalageunknown'] = 'Unknown Age';
$string['nocases'] = 'No cases';

// Webservice warnings
$string['gridnotfound'] = 'The grid with id {$a} was not found';

// Case form
$string['case:add'] = 'Add a clinical case';
$string['case:add:save'] = 'Save';
$string['error:accessdenied'] = 'Access denied';
$string['nolistgrade'] = 'I do not want to grade the logbook';
$string['nolistgrade_desc'] = 'If you decide not to grade the logbook, the coefficient for this block will be reduced to 0 in the calculation of the overall grade.';

// Entry form
$string['declarelevel'] = 'Declare your confidence level';
$string['validate'] = 'Validate this essential';
$string['level'] = 'Your confidence level';
$string['declaredlevel'] = 'Stated confidence level';
$string['declareddate'] = 'Declared having achieved this essential on {$a}';
$string['level_help'] = 'Indicate here with what level of confidence (from 0 to 100%) you would feel able to reproduce this gesture or procedure in the future.';
$string['status'] = 'Status';
$string['decl:seendone'] = 'I declare that I have carried out the essential clinic above on {$a} and ask the teachers below who supervised me on this procedure to confirm this.';
$string['decl:notseen'] = 'I have not encountered this situation yet.';
$string['certdecl'] = 'Certification declaration';
$string['addsupervisor'] = 'Add a supervisor to validate this certification';
$string['supervisorsection'] = 'Supervisors';
$string['valid:confirmed'] = 'I confirm that this essential has been achieved';
$string['valid:notseen'] = 'I do not validate because I did not see this essential';
$string['valid:levelnotreached'] = 'I do not validate because the level is not reached';
$string['valid:observernotseen'] = 'The observer did not see this essential';

// Suggested grade
$string['gradeK1'] = 'Grade calculation constant K1';
$string['gradeK1_help'] = 'The weight of the evaluation grade, default is 5';
$string['gradeK2'] = 'Grade calculation constant K2';
$string['gradeK2_help'] = 'The weight of the list grade, default is 2';

