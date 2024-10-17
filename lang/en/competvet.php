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
$string['competvet:caneditother'] = 'Can edit other\'s observations and certifications';
$string['criterion:label'] = 'Criterion label';
$string['criterion:sort'] = 'Criterion sort';
$string['criterion:grid'] = 'Criterion evaluation grid';
$string['criterion:idnumber'] = 'Criterion unique ID';
$string['criterion:parentid'] = 'Criterion Parent ID';
$string['criterion:parentlabel'] = 'Criterion Parent Label';
$string['criterion:parentidnumber'] = 'Criterion Parent ID Number';
$string['criterion:evalgrid'] = 'Evaluation grid';

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
$string['gradepercent'] = 'Grade / 100';
$string['gradestudent'] = 'Grade student';
$string['changegrade'] = 'Change grade';
$string['viewgrade'] = 'View grade';
$string['notgradedyet'] = 'Not yet graded';
$string['grader'] = 'Graded by';
$string['timegraded'] = 'Time graded';
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
$string['observation_comment:comment'] = 'Observation comment content {$a}';
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
$string['entity:criteria'] = 'Criteria';
$string['entity:roles'] = 'Assign roles';
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
$string['situation:category'] = 'Situation category';
$string['situation:category_help'] = 'Situation category: First year,... Format is [shortname]|fr:[French label]|en:[English label]';
$string['situation:def'] = 'Situation definition';
$string['situation:shortnamewithlinks'] = 'Situation short name';
$string['situation:shortname'] = 'Situation short name';
$string['situation:shortname_help'] = 'Situation short name';
$string['situation:evalnum'] = 'Required evaluations number';
$string['situation:evalnum_help'] = 'Required evaluations number';
$string['situation:autoevalnum'] = 'Required autoevaluation number';
$string['situation:autoevalnum_help'] = 'Required autoevaluation number';
$string['situation:certifpnum'] = 'Required certification percentage';
$string['situation:certifpnum_help'] = 'Required certification percentage';
$string['situation:casenum'] = 'Required case number';
$string['situation:casenum_help'] = 'Required case number';
$string['situation:haseval'] = 'Is Eval module enabled?';
$string['situation:haseval_help'] = 'Is Eval module enabled?';
$string['situation:hascertif'] = 'Is Certif module enabled?';
$string['situation:hascertif_help'] = 'Is Certif module enabled?';
$string['situation:hascase'] = 'Is List module enabled?';
$string['situation:hascase_help'] = 'Is List module enabled?';
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
$string['planning:page:badge:eval'] = 'Number of evaluations received / required';
$string['planning:page:badge:autoeval'] = 'Number of autoevaluations received / required';
$string['planning:page:badge:list'] = 'Number of cases entered / required';
$string['planning:page:badge:cert'] = 'Number of certifications received / required';
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
$string['observation:comment:comment'] = 'Comment for the student';
$string['observation:comment:privatecomment'] = 'Private comment for observer';
$string['observation:comment:progress'] = 'What I have progressed on';
$string['observation:comment:improvement'] = 'What I need to improve';
$string['observation:comment:missing'] = 'What I missed';
$string['observation:auto'] = 'Add an autoevaluation';
$string['observation:auto:save'] = 'Save autoevaluation';
$string['observation:add'] = 'Add an observation';
$string['observation:ask'] = 'Ask for an observation';
$string['observation:ask:save'] = 'Select observer';
$string['observation:asked'] = 'Observation asked';
$string['observation:asked:body'] = 'Observation asked to {$a}';
$string['observation:created'] = 'Created an observation';
$string['observation:delete'] = 'Delete the observation';
$string['observation:delete:confirm'] = 'Confirm you want to delete the observation';
$string['observation:add:save'] = 'Save';
$string['observation:edit'] = 'Edit the observation';
$string['observation:edit:save'] = 'Save';
$string['observation:comment:commentno'] = 'Comment {no}';
$string['observation:comment:add'] = 'Add comment';
$string['observation:comment:deleteno'] = 'Delete comment {no}';
$string['observation_comment:type'] = 'Type de commentaire {$a}';
$string['observation:comment:observercomment'] = 'Observer comment';
$string['todos'] = 'ToDos';
$string['entity:competvet_todo'] = 'ToDo';
$string['todo:action:certif:valid:asked'] = 'Validation asked';
$string['todo:status:pending'] = 'Pending';
$string['todo:status:done'] = 'Done';
$string['todo:status:deleted'] = 'Deleted';
$string['todo:status'] = 'Status';
$string['todo:action:format:observationasked'] = 'Observation asked by {$a->student} for situation {$a->situationlabel}
 to observer {$a->observer}';
$string['todo:action:format:certificationasked'] = 'Certification asked by {$a->student} for situation {$a->situationlabel}
 to observer {$a->observer}';
$string['todo:user'] = 'User';
$string['todo:targetuser'] = 'Target User';
$string['todo:type'] = 'Type';
$string['todo:planning'] = 'Planning';
$string['todo:user:fullname'] = 'User full name';
$string['todo:action:eval:asked'] = 'Observation Asked';
$string['todo:action:cta:eval:asked'] = 'Create Observation';
$string['todo:action'] = 'Action';
$string['todo:data'] = 'Data';
$string['todo:timecreated'] = 'Time created';
$string['todo:timemodified'] = 'Time modified';

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
$string['tabletitle:autoevalstudent'] = 'Student\' autoevaluations';
$string['tabletitle:evals'] = 'Observations';
// Planning interface
$string['stats'] = 'Stats';
$string['studentprogress'] = 'access to the emulator to perform actions identical to those of the mobile application';

// Eval
$string['evalevaluation'] = 'Eval evaluation';
$string['evalscore'] = 'Average evaluation score';
$string['average'] = 'Average';
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
$string['numcertifvalidated'] = 'Number of certified essentials';
$string['statusproposed'] = 'Status proposed by CompetVet';
$string['minpercentcertif'] = 'The minimum threshold to validate is {$a}% of certified essentials';
$string['evaluatordecision'] = 'Evaluator decision';
$string['evaluatordecision_help'] = 'Non-validation being a deal breaker, you can manually change this grade. If you force the validation, please comment.';
$string['evalcomment'] = 'Free commentary on the essential clinical aspects';
$string['confidencelevel'] = 'Confidence level';
$string['seendone'] = 'Seen and done';
$string['confirmed'] = 'Confirmed';
$string['notseen'] = 'Not seen';
$string['notreached'] = 'Level not reached';
$string['validated'] = 'Validated';
$string['notvalidated'] = 'Not validated';
$string['nocertifications'] = 'No certifications';
$string['observationrequest'] = 'Observation request';
$string['observationwaiting'] = '{$a->targetfullname} Requested observation on {$a->timecreated}';
$string['observationrequested'] = '{$a->targetfullname} Requested observation on {$a->timecreated} <br> {$a->userfullname} Completed observation on {$a->timemodified}';

$string['freecommentlist'] = 'Free comment for the List part';

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
$string['cert:global:notdeclared'] = 'Not declared';
$string['cert:global:notseen'] = 'Not seen';
$string['cert:global:validated'] = 'Validated';
$string['cert:global:waiting'] = 'Waiting for validation';

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
$string['groupnotfound'] = 'The group with name {$a} was not found';

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
$string['certdecl:save'] = 'Save';
$string['addsupervisor'] = 'Add a supervisor to validate this certification';
$string['supervisorsection'] = 'Supervisors';
$string['valid:confirmed'] = 'I confirm that this essential has been achieved';
$string['valid:notseen'] = 'I do not validate because I did not see this essential';
$string['valid:levelnotreached'] = 'I do not validate because the level is not reached';
$string['valid:observernotseen'] = 'The observer did not see this essential';

// Upload planning form
$string['uploadplanning'] = 'Upload planning';

// Suggested grade
$string['acceptgrade'] = 'Accept the suggested grade';
$string['gradeK1'] = 'Grade calculation constant K1';
$string['gradeK1_help'] = 'The weight of the evaluation grade, default is 5';
$string['gradeK2'] = 'Grade calculation constant K2';
$string['gradeK2_help'] = 'The weight of the list grade, default is 2';
$string['notenoughgrades'] = 'Not enough data to calculate the suggested grade';
$string['calc:eval:certif:list'] = 'The suggested score is the average of the \'Eval\' and \'List\' scores weighted by their coefficient. The \'Incontournables\' module must be validated for this average to be calculated.';
$string['calc:certif:list'] = 'The suggested score is equal to the \'List\' module score. The \'Incontournables\' module must be validated for this score not to be 0.';
$string['calc:eval:list'] = 'The suggested score is the average of the \'Eval\' and \'List\' scores weighted by their coefficient.';
$string['calc:eval:certif'] = 'The suggested score is equal to the \'Eval\' module score. The \'Incontournables\' module must be validated for this score not to be 0.';
$string['calc:eval'] = 'The suggested score is equal to the \'Eval\' module score.';
$string['calc:list'] = 'The suggested score is equal to the \'List\' module score.';

$string['search:activity'] = 'Rechercher activités CompetVet';
$string['search:student'] = 'Search Student';
$string['search:group'] = 'Search Group';
$string['search:ungraded'] = 'Ungraded only';
$string['search:startdate'] = 'Start date';
$string['search:clearstartdate'] = 'Clear start date';
$string['exportxls'] = 'Export to Excel';
$string['showastable'] = 'Show as table';
$string['invaliddate'] = 'Invalid date {$a}, it should be in the format dd/mm/yyyy';

$string['atleastone'] = 'At least one of these fields must be checked';
$string['confirmplanningdelete'] = 'Are you sure you want to delete this planning (there are user data)?';
$string['noaccess'] = 'You don\'t have access to this page';
$string['cachedef_usersituations'] = 'Cache des situations des utilisateurs';
$string['cachedef_casestructures'] = 'Cache des structures de cas';


// Task
$string['task:cleanup'] = 'Clean up CompetVet data';
$string['clear_pending_todos'] = 'Remove pending todos';
$string['clear_pending_todos_days'] = 'Remove pending todos older than days';
$string['clear_pending_todos_days_desc'] = 'Specify the number of days ago before which all pending todos should be removed, 0 to disable';
// Notifications
$string['defaultlang_help'] = 'Default language for the emails, Current setting <strong>{$a}</strong>';
$string['controltask'] = 'Control this task';
$string['notification_subject'] = 'Notification from CompetVet: {$a}';
$string['notification:end_of_planning'] = 'End of planning';
$string['notification:student_graded'] = 'Student graded';
$string['notification:student_graded:enabled'] = 'Student graded is an ad-hoc notification, enable or disable it here';
$string['entity:notifications'] = 'Notifications';
$string['notification:notification'] = 'Notification';
$string['notification:timecreated'] = 'Time created';
$string['notification:message'] = 'Message';
$string['notification:items_todo'] = 'Pending actions';
$string['notification:student_target'] = 'Student objectives';
$string['notification:student_target:eval'] = 'Student objectives: Evaluations';
$string['notification:student_target:autoeval'] = 'Student objectives: Self-evaluations';
$string['notification:student_target:cert'] = 'Student objectives: Certifications';
$string['notification:student_target:list'] = 'Student objectives: Case log';

$string['catchall_email'] = 'Catchall Email Address';
$string['catchall_email_desc'] = 'Enter the email address to which all notifications should be redirected when enabled.';
$string['redirect_to_catchall'] = 'Redirect to Catchall Email';
$string['redirect_to_catchall_desc'] = 'Enable this option to redirect all notifications to the catchall email address.';

// Emails
$string['email:end_of_planning:subject'] = '[CompetVet] You have students to grade in the rotation {$a->competvetname}';
$string['email:end_of_planning'] = <<<'EOF'

<p>Hello,</p>

<p>Students are waiting for their grades for the rotation {$a->situation}. This rotation ended on {$a->enddate}</p>

<p>The closer the feedback is to the end of the activity, the more effective it is. Therefore, we kindly ask you to grade these students as soon as possible.</p>

<p>You can access the grading interface by clicking here: <a href="{$a->competvetlink}" style="color: #1a73e8; text-decoration: none;">LINK</a></p>

<p>Thank you for your involvement in this essential process.</p>

<p>For your information, the list of concerned students is below:</p>

<ul>
    {$a->students}
</ul>
EOF;

$string['email:items_todo:subject'] = '[CompetVet] You have pending actions in your CompetVet task list {$a->competvetname}';
$string['email:items_todo'] = <<<'EOF'

<p>Hello,</p>

<p>We have noticed that you have pending actions on the CompetVet app. This means that students have requested you to perform an observation (Eval) or to certify the completion of a clinical essential (Certif).</p>

<p>They need you! Your action is crucial for students to validate their rotation.</p>

<p>Just a few clicks are enough: <strong>open the CompetVet application</strong> on your phone and <strong>go to the "Task List" tab.</strong></p>

<p>Everything you need to do is detailed in this list.</p>

<p>You will no longer receive this message once this task list is empty.</p>

<p>Thank you for your involvement!</p>

<p>Best regards,</p>
EOF;

$string['email:student_graded:subject'] = '[CompetVet] Your grade for {$a->competvetname} has been updated';
$string['email:student_graded'] = <<<'EOF'

<p>Hello {$a->fullname},</p>

<p>Your final grade for the situation <strong>{$a->competvetname}</strong> has been assigned by your evaluator.</p>

<p>You can now view your grade and the associated comments by following the link below:</p>

<p><a href="{$a->competvetlink}" style="color: #1a73e8; text-decoration: none;">Access your grade and comments</a></p>

<p>Please note that this situation is now closed and no further modifications are possible.</p>

<p>Best regards,</p>
EOF;

$string['email:student_target:eval:subject'] = '[CompetVet] You have students to grade in the rotation {$a->competvetname}';
$string['email:student_target:eval'] = <<<'EOF'

<p>Hello,</p>

<p>We have noticed that you are still missing observations by supervisors for the rotation {$a->competvetname}. If you have already requested supervisors to do so, please remind them to ensure you meet the targeted objective.</p>

<p>Best regards,</p>
EOF;

$string['email:student_target:autoeval:subject'] = '[CompetVet] You have not yet completed your self-evaluation in the rotation {$a->competvetname}';
$string['email:student_target:autoeval'] = <<<'EOF'

<p>Hello,</p>

<p>We have noticed that you have not yet completed your self-evaluation for the rotation {$a->competvetname}.</p>

<p>You still have some time to do it, but don't forget!</p>

<p>Best regards,</p>
EOF;

$string['email:student_target:cert:subject'] = '[CompetVet] You have not yet had all your essentials certified in the rotation {$a->competvetname}';
$string['email:student_target:cert'] = <<<'EOF'

<p>Hello,</p>

<p>We have noticed that you have not yet received certification for all your essentials in the rotation {$a->competvetname}.</p>

<p>If you have already declared these essentials but your supervisors have not yet certified them, please remind them to ensure you meet the targeted objective.</p>

<p>Best regards,</p>
EOF;

$string['email:student_target:list:subject'] = '[CompetVet] You have not yet finalized your case log for the rotation {$a->competvetname}';
$string['email:student_target:list'] = <<<'EOF'

<p>Hello,</p>

<p>We have noticed that you have not yet entered the recommended number of clinical cases in your case log for the rotation {$a->competvetname}.</p>

<p>You still have some time to do it, but don't forget!</p>

<p>Best regards,</p>
EOF;

$string['footer'] = 'Footer';
$string['footer_help'] = 'Default email footer';

$string['email:footer'] = <<<'EOF'

<table width="100%" border="0" cellpadding="0" cellspacing="0" style="font-family: Arial, sans-serif; font-size: 13px; line-height: 20px; color: #555555;">
    <tr>
        <td style="width: 100px;">
            <img src="{$a}" alt="Competvet Logo" width="100" height="auto">
        </td>
        <td style="padding: 20px;">
            <p style="margin: 0; padding: 0; color: #333333; font-size: 16px; font-weight: bold;">
                École nationale vétérinaire d'Alfort (EnvA)
            </p>
            <p style="margin: 5px 0; padding: 0;">
                Teaching, research, and veterinary care since 1766
            </p>
            <p style="margin: 5px 0; padding: 0;">
                Address: 7 Avenue du Général de Gaulle, 94700 Maisons-Alfort, France
            </p>
            <p style="margin: 5px 0; padding: 0;">
                Website: <a href="http://www.vet-alfort.fr" style="color: #1a73e8; text-decoration: none;">www.vet-alfort.fr</a>
            </p>
        </td>
    </tr>
</table>
EOF;