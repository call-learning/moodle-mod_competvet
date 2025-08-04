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

$string['acceptgrade'] = 'Accepter la note suggérée';
$string['add'] = 'Ajouter';
$string['addcriterion'] = 'Ajouter un critère';
$string['addgrid'] = 'Ajouter une grille';
$string['addoption'] = 'Ajouter une option';
$string['addplanning'] = 'Ajouter une planification';
$string['addsupervisor'] = 'Ajouter un superviseur pour valider cette certification';
$string['admincompetvet:role'] = 'Admin CompetVet';
$string['admincompetvet:role:desc'] = 'L\'administrateur CompetVet peut tout faire sur n\'importe quelle situation de la plateforme.';
$string['allmysituations'] = 'Toutes mes situations';
$string['allsituations'] = 'Toutes les situations pour le cours {$a}';
$string['animal'] = 'Animal';
$string['animalage'] = 'Âge';
$string['animalageunknown'] = 'Âge inconnu';
$string['animalbreed'] = 'Race';
$string['animalbreedunknown'] = 'Race inconnue';
$string['animalsex'] = 'Sexe';
$string['animalsexunknown'] = 'Sexe inconnu';
$string['animalspecies'] = 'Espèce';
$string['appraiser'] = 'Évaluateur';
$string['average'] = 'Moyenne';
$string['back'] = 'Retour';
$string['caseentry:content'] = 'Contenu du cas';
$string['caseentry:timecreated'] = 'Cas créé le';
$string['caseentry:timemodified'] = 'Cas modifié le';
$string['caseentry:field'] = '{$a}';
$string['cachedef_casestructures'] = 'Cache des structures de cas';
$string['cachedef_usersituations'] = 'Cache des situations des utilisateurs';
$string['calc:certif:list'] = 'Le score suggéré est égal au score du module \'List\'. Le module \'Incontournables\' doit nécessairement être validé pour que ce score ne soit pas 0.';
$string['calc:eval'] = 'Le score suggéré est égal au score du module \'Eval\'.';
$string['calc:eval:certif'] = 'Le score suggéré est égal au score du module \'Eval\'. Le module \'Incontournables\' doit nécessairement être validé pour que ce score ne soit pas 0.';
$string['calc:eval:certif:list'] = 'Le score suggéré est la moyenne des scores \'Eval\' et \'List\' affectés de leur coefficient. Le module \'Incontournables\' doit nécessairement être validé pour que cette moyenne soit calculée';
$string['calc:eval:list'] = 'Le score suggéré est la moyenne des scores \'Eval\' et \'List\' affectés de leur coefficient.';
$string['calc:list'] = 'Le score suggéré est égal au score du module \'List\'.';
$string['case:add'] = 'Ajouter un cas clinique';
$string['case:add:save'] = 'Enregistrer';
$string['case:edit'] = 'Modifier le cas clinique';
$string['case:edit:save'] = 'Enregistrer';
$string['cert:global:notdeclared'] = 'Non déclaré';
$string['cert:global:notseen'] = 'Non vu';
$string['cert:global:validated'] = 'Validé';
$string['cert:global:waiting'] = 'En attente de validation';
$string['certdecl'] = 'Déclaration de certification';
$string['certdecl:save'] = 'Enregistrer';
$string['certif_criteria'] = 'Liste des incontournables';
$string['certifevaluation'] = 'Certification des incontournables';
$string['certify'] = 'Certification des incontournables (Certif)';
$string['changegrade'] = 'Modifier la note';
$string['clear_pending_todos'] = 'Supprimer les tâches en attente';
$string['clear_pending_todos_days'] = 'Supprimer les tâches en attente plus anciennes que les jours';
$string['clear_pending_todos_days_desc'] = 'Spécifiez le nombre de jours avant lequel toutes les tâches en attente doivent être supprimées. 0 signifie que le tâche est désactivée.';
$string['closeevaluation'] = 'Fermer l\'évaluation';
$string['comment'] = 'Commentaire';
$string['commentfor'] = 'Commentaire pour {$a}';
$string['comments'] = 'Commentaires';
$string['commentsaved'] = 'Commentaire enregistré';
$string['commment'] = 'Commentaire';
$string['competgrade:grade'] = 'Recevoir une note';
$string['competgrade:viewallgrades'] = 'Voir toutes les notes des utilisateurs';
$string['competvet:addinstance'] = 'Peut ajouter une nouvelle activité CompetVet';
$string['competvet:canaskobservation'] = 'Peut demander une observation';
$string['competvet:candoeverything'] = 'Peut tout faire';
$string['competvet:caneditother'] = 'Peut modifier les situations et la planification des autres';
$string['competvet:cangrade'] = 'Peut noter un étudiant';
$string['competvet:canobserve'] = 'Peut observer un étudiant';
$string['competvet:editcriteria'] = 'Modifier les critères CompetVet';
$string['competvet:editplanning'] = 'Peut modifier la planification';
$string['competvet:managecompetencies'] = 'Gérer les compétences CompetVet';
$string['competvet:managecompetencyframeworks'] = 'Gérer les grilles de compétences CompetVet';
$string['competvet:managesituationtemplates'] = 'Gérer les modèles de situation CompetVet';
$string['competvet:view'] = 'Peut voir une situation';
$string['competvet:viewother'] = 'Peut voir les situations et la planification des autres';
$string['competvetname'] = 'Nom de l\'activité CompetVet';
$string['competvetplanning'] = 'Planification';
$string['competvetsettings'] = 'Paramètres CompetVet';
$string['confidencelevel'] = 'Niveau de confiance';
$string['confirmed'] = 'Certifié';
$string['confirmplanningdelete'] = 'Êtes-vous sûr de vouloir supprimer cette planification (il y a des données utilisateurs) ?';
$string['context'] = 'Contexte';
$string['controltask'] = 'Contrôler les tâches';
$string['criterion'] = 'Critère';
$string['criterion:evalgrid'] = 'Grille d\'évaluation';
$string['criterion:grid'] = 'Grille d\'évaluation des critères';
$string['criterion:idnumber'] = 'ID unique du critère';
$string['criterion:label'] = 'Libellé du critère';
$string['criterion:parentid'] = 'ID parent du critère';
$string['criterion:parentidnumber'] = 'Numéro d\'ID parent du critère';
$string['criterion:parentlabel'] = 'Libellé parent du critère';
$string['criterion:sort'] = 'Trier les critères';
$string['deactivatepenalty'] = 'Désactiver la pénalité';
$string['decl:notseen'] = 'Je n\'ai pas encore rencontré cette situation.';
$string['decl:seendone'] = 'Je déclare avoir réalisé l\'essentiel clinique ci-dessus le {$a} et je demande aux enseignants ci-dessous qui m\'ont supervisé lors de cette procédure de confirmer cela.';
$string['declareddate'] = 'Déclaré avoir atteint cet essentiel le {$a}';
$string['declaredlevel'] = 'Niveau de confiance déclaré';
$string['declarelevel'] = 'Déclarez votre niveau de confiance';
$string['defaultcriteria'] = 'Critères par défaut';
$string['defaultcriteria_help'] = 'Les critères par défaut sont fixes, seul le texte des critères peut être modifié';
$string['defaultlang_help'] = 'Langue par défaut de l\'activité';
$string['delete'] = 'Supprimer';
$string['deleteexistingroles'] = 'Supprimer les attributions de rôles existantes';
$string['edit'] = 'Modifier';
$string['editplanning'] = 'Modifier la planification';
$string['email:end_of_planning'] = <<<'EOF'

<p>Bonjour,</p>

<p>Des étudiants attendent leur note pour la rotation {$a->situation}. Cette rotation s'est terminée le {$a->enddate}</p>

<p>Plus le feedback est proche de la fin de l'activité, plus il est efficace. Aussi nous vous remercions de bien vouloir noter ces étudiants dès que possible.</p>

<p>Vous pouvez accéder à l'interface de notation en cliquant ici : <a href="{$a->competvetlink}" style="color: #1a73e8; text-decoration: none;">LIEN</a></p>

<p>Merci pour votre implication dans ce processus essentiel.</p>

<p>Pour information, la liste des étudiants concernés est ci-dessous :

<ul>
    {$a->students}
</ul>
EOF;
$string['email:end_of_planning:subject'] = '[CompetVet] Vous avez des étudiants à noter dans la rotation {$a->competvetname}';
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
                Enseignement, recherche, et soin vétérinaire depuis 1766
            </p>
            <p style="margin: 5px 0; padding: 0;">
                Adresse: 7 Avenue du Général de Gaulle, 94700 Maisons-Alfort, France
            </p>
            <p style="margin: 5px 0; padding: 0;">
                Site web: <a href="http://www.vet-alfort.fr" style="color: #1a73e8; text-decoration: none;">www.vet-alfort.fr</a>
            </p>
        </td>
    </tr>
</table>
EOF;
$string['email:items_todo'] = <<<'EOF'

<p>Bonjour,</p>

<p>Nous constatons que vous avez des actions en attente sur l'appli CompetVet. Ceci signifie que des étudiants vous ont sollicité pour réaliser une observation (Eval) ou pour certifier la réalisation d'un incontournable clinique (Certif).</p>

<p>Ils ont besoin de vous ! Votre action est fondamentale pour que les étudiants puissent valider leur rotation.</p>

<p>Pour cela, quelques clics suffisent : <strong>ouvrez l'application CompetVet</strong> sur votre téléphone et <strong>rendez vous dans l'onglet : "Liste des tâches".</strong></p>

<p>Tout ce que vous avez à faire est détaillé dans cette liste.</p>

<p>Vous ne recevrez plus ce message dès que cette liste de tâches sera vide.</p>

<p>Merci pour votre implication !</p>

<p>Bien cordialement</p>
EOF;
$string['email:items_todo:subject'] = '[CompetVet] Vous avez des actions en attente dans votre liste de tâches CompetVet {$a->competvetname}';
$string['email:student_graded'] = <<<'EOF'

<p>Bonjour {$a->fullname},</p>

<p>Votre note finale pour la situation <strong>{$a->competvetname}</strong> a été attribuée par votre évaluateur.</p>

<p>Vous pouvez maintenant consulter votre note et les commentaires associés en suivant le lien ci-dessous :</p>

<p><a href="{$a->competvetlink}" style="color: #1a73e8; text-decoration: none;">Accéder à votre note et aux commentaires</a></p>

<p>Veuillez noter que cette situation est désormais clôturée et qu'aucune modification supplémentaire n'est possible.</p>

<p>Bien cordialement</p>
EOF;
$string['email:student_graded:subject'] = '[CompetVet] Votre note pour {$a->competvetname} a été mise à jour';
$string['email:student_target:autoeval'] = <<<'EOF'

<p>Bonjour,</p>

<p>Nous constatons que vous n'avez pas encore réalisé votre auto-évaluation pour la rotation {$a->competvetname}.</p>

<p>Il vous reste encore un peu de temps pour le faire mais il faut y penser !</p>

<p>Bien cordialement</p>
EOF;
$string['email:student_target:autoeval:subject'] = '[CompetVet] Vous n\'avez pas encore réalisé votre auto-évaluation dans la rotation {$a->competvetname}';
$string['email:student_target:cert'] = <<<'EOF'

<p>Bonjour,</p>

<p>Nous constatons que vous n'avez pas encore reçu une certification de tous vos incontournables pour la rotation {$a->competvetname}.</p>

<p>Si vous avez déjà déclaré ces incontournables mais que vos encadrants ne les ont pas encore certifiés, il faut le leur rappeler afin que vous remplissiez bien l'objectif visé.</p>

<p>Bien cordialement</p>
EOF;
$string['email:student_target:cert:subject'] = '[CompetVet] Vous n\'avez pas encore fait certifier tous vos incontournables dans la rotation {$a->competvetname}';
$string['email:student_target:eval'] = <<<'EOF'

<p>Bonjour,</p>

<p>Nous constatons qu'il vous manque encore des observations par des encadrants pour la rotation {$a->competvetname}.
Si vous avez déjà sollicité des encadrants pour le faire, il faut le leur rappeler afin que vous remplissiez bien l'objectif visé. </p>

<p>Bien cordialement</p>
EOF;
$string['email:student_target:eval:subject'] = '[CompetVet]Vous n\'avez pas encore obtenu le nombre requis d\'évaluations dans la rotation {$a->competvetname}';
$string['email:student_target:list'] = <<<'EOF'

<p>Bonjour,

<p>Nous constatons que vous n'avez pas encore saisi le nombre de cas cliniques conseillé dans votre carnet de cas pour la rotation {$a->competvetname}.</p>

<p>Il vous reste encore un peu de temps pour le faire mais il faut y penser !</p>

<p>Bien cordialement</p>
EOF;
$string['email:student_target:list:subject'] = '[CompetVet] Vous n\'avez pas encore finalisé votre carnet de cas pour la rotation {$a->competvetname}';
$string['enddate'] = 'Date de fin';
$string['entercertificationassesment'] = 'entrez vos évaluations d\'incontournables ici';
$string['enterskillassesment'] = 'entrez vos évaluations de compétences ici';
$string['entity:competvet_observation'] = 'Observation';
$string['entity:competvet_todo'] = 'À faire';
$string['entity:criteria'] = 'Critères';
$string['entity:criterion'] = 'Critère';
$string['entity:grid'] = 'Grille d\'évaluation';
$string['entity:notifications'] = 'Notifications';
$string['entity:observation_comment'] = 'Commentaire d\'observation';
$string['entity:planning'] = 'Planification';
$string['entity:roles'] = 'Utilisateurs';
$string['entity:situation'] = 'Situation';
$string['entity:competvet_case_entry'] = 'Carnet de cas';
$string['error:accessdenied'] = 'Accès refusé';
$string['eval_criteria'] = 'Critères d\'évaluation';
$string['evalcomment'] = 'Commentaire libre sur la certification des incontournables';
$string['evalevaluation'] = 'Évaluation des compétences (Eval)';
$string['evalscore'] = 'Note moyenne de l\'évaluation';
$string['evaluate'] = 'Évaluation globale des compétences';
$string['evaluation'] = 'Évaluation globale';
$string['evaluator:role'] = 'Évaluateur';
$string['evaluator:role:desc'] = 'Rôle d\'évaluateur : un utilisateur avec ce rôle peut observer un étudiant et lui donner une note (compétences, incontournables, journal des cas)';
$string['evaluatordecision'] = 'Décision de l\'évaluateur';
$string['evaluatordecision_help'] = 'L\'absence de validation des incontournables étant rédhibitoire, vous pouvez changer manuellement cette note. Si vous forcez la validation, veuillez commenter.';
$string['exportxls'] = 'Exporter en Excel';
$string['finalgrade'] = 'Score final';
$string['finalscore'] = 'Note finale suggérée';
$string['footer'] = 'Footer';
$string['footer_help'] = 'Default email footer';
$string['freecommenteval'] = 'Commentaire libre pour la partie évaluation des compétences';
$string['freecommentlist'] = 'Commentaire libre pour la partie journal de cas';
$string['globalcomment'] = 'Commentaire global';
$string['globalcomment_info'] = 'Ce commentaire sera partagé avec l\'étudiant';
$string['globalgrade'] = 'Note globale';
$string['grade'] = 'Note';
$string['gradeK1'] = 'Constante de calcul de la note K1';
$string['gradeK1_help'] = 'Le poids de la note d\'évaluation, par défaut est de 5';
$string['gradeK2'] = 'Constante de calcul de la note K2';
$string['gradeK2_help'] = 'Le poids de la note de la liste, par défaut est de 2';
$string['grade_caselog_name'] = 'Journal de cas Éval.';
$string['grade_eval_name'] = 'Obs. Éval.';
$string['grade_list_name'] = 'Liste Éval.';
$string['gradeedit'] = 'Modifier les notes';
$string['gradefor'] = 'Note pour {$a}';
$string['gradeitem:caselog'] = 'Note du carnet de cas';
$string['gradeitem:eval'] = 'Note Éval.';
$string['gradeitem:list'] = 'Note de la liste';
$string['gradepercent'] = 'Note / 100';
$string['grader'] = 'Noté par';
$string['gradestudent'] = 'Noter l\'étudiant';
$string['grading'] = 'Notation';
$string['grid:default:certif'] = 'Liste des incountournables cliniques par défaut (CERTIF)';
$string['grid:default:eval'] = 'Grille d\'évaluation des compétences par défaut (EVAL)';
$string['grid:default:list'] = 'Grille d\'évaluation du carnet de cas par défaut (LIST)';
$string['grid:idnumber'] = 'Numéro d\'identification de la grille d\'évaluation';
$string['grid:name'] = 'Nom de la grille d\'évaluation';
$string['grid:selector'] = 'Sélecteur de grille d\'évaluation';
$string['gridnotfound'] = 'La grille avec l\'id {$a} n\'a pas été trouvée';
$string['group'] = 'Groupe';
$string['groupnotfound'] = 'Le groupe avec le nom {$a} n\'a pas été trouvé';
$string['inuse'] = 'En cours d\'utilisation';
$string['invaliddata'] = 'Données invalides pour {$a}';
$string['invaliddatafor'] = 'Données invalides pour {$a}';
$string['invaliddate'] = 'Date invalide {$a}, elle doit être au format jj/mm/aaaa';
$string['immediate_email'] = 'Envoi immédiat des emails';
$string['immediate_email_desc'] = 'Si désactivé, utilisez l\'interface de notification pour envoyer des emails en lot.';
$string['layout:collapsegradepanel'] = 'Réduire le panneau de note';
$string['layout:collapsereviewpanel'] = 'Réduire le panneau de révision';
$string['layout:default'] = 'Cliquez pour agrandir le panneau de révision';
$string['less'] = 'moins';
$string['level'] = 'Votre niveau de confiance';
$string['level_help'] = 'Indiquez ici avec quel niveau de confiance (de 0 à 100%) vous vous sentiriez capable de reproduire ce geste ou cette procédure à l\'avenir.';
$string['list'] = 'Evaluation du journal de cas (List)';
$string['list_criteria'] = 'Liste des critères';
$string['loading'] = 'Chargement';
$string['managecriteria'] = 'Gérer les critères';
$string['minpercentcertif'] = 'Le seuil minimal pour valider est de {$a}% d\'incontournables certifiés';
$string['modulename'] = 'CompetVet';
$string['modulename_help'] = 'L\'interface de notation des évaluations de compétences.';
$string['modulename_link'] = 'mod/competgrade/view';
$string['modulenameplural'] = 'CompetVet';
$string['more'] = 'plus';
$string['move'] = 'Déplacer';
$string['mytodos'] = 'Mes tâches';
$string['newcriterion'] = 'Nouveau critère';
$string['newgrid'] = 'Nouvelle grille';
$string['newoption'] = 'Nouvelle option';
$string['nextuser'] = 'Utilisateur suivant';
$string['noaccess'] = 'Vous n\'avez pas accès à cette ressource';
$string['nocases'] = 'Aucun cas';
$string['nocertifications'] = 'Non certifié';
$string['noevaluations'] = 'Aucune évaluation';
$string['nofilters'] = 'Pas de filtres';
$string['nolistgrade'] = 'Je ne veux pas noter le journal de bord';
$string['nolistgrade_desc'] = 'Si vous décidez de ne pas noter le journal de bord, le coefficient de ce bloc sera réduit à 0 dans le calcul de la note globale.';
$string['notenoughgrades'] = 'Pas assez de données pour calculer la note suggérée';
$string['notgradedyet'] = 'Pas encore noté';
$string['notification:deleteall'] = 'Supprimer toutes ({$a})';
$string['notification:end_of_planning'] = 'Fin de planification';
$string['notification:items_todo'] = 'Actions en attente';
$string['notification:message'] = 'Message';
$string['notification:notification'] = 'Notification';
$string['notification:recipient'] = 'Destinataire';
$string['notification:sendall'] = 'Envoyer à tous ({$a})';
$string['notification:status:pending'] = 'En attente';
$string['notification:status:send'] = 'Envoyé';
$string['notification:student_graded'] = 'Étudiant noté';
$string['notification:student_graded:enabled'] = 'Activer les notifications de notation';
$string['notification:student_target'] = 'Objectifs étudiants';
$string['notification:student_target:alltasks'] = '(Une tâche pour tous les objectifs étudiants)';
$string['notification:student_target:autoeval'] = 'Objectifs étudiants : Auto-évaluations';
$string['notification:student_target:cert'] = 'Objectifs étudiants : Certifications';
$string['notification:student_target:eval'] = 'Objectifs étudiants : Évaluations';
$string['notification:student_target:list'] = 'Objectifs étudiants : Carnet de cas';
$string['notification:timecreated'] = 'Heure de création';
$string['notification_subject'] = 'Notification de CompetVet : {$a}';
$string['notreached'] = 'Niveau non atteint';
$string['notseen'] = 'Non vu';
$string['notvalidated'] = 'Non validé';
$string['numberofobservations'] = 'Nombre d\'observations';
$string['numcertifvalidated'] = 'Nombre d\'incontournables certifiés';
$string['observation:add'] = 'Ajouter une observation';
$string['observation:add:save'] = 'Enregistrer';
$string['observation:ask'] = 'Demander une observation';
$string['observation:ask:save'] = 'Sélectionner l\'observateur';
$string['observation:asked'] = 'Observation demandée';
$string['observation:asked:body'] = 'Observation demandée à {$a}';
$string['observation:auto'] = 'Ajouter une autoévaluation';
$string['observation:auto:save'] = 'Enregistrer l\'autoévaluation';
$string['observation:category:eval:autoeval'] = 'Autoévaluations';
$string['observation:category:eval:observation'] = 'Observations';
$string['observation:comment:add'] = 'Ajouter un commentaire';
$string['observation:comment:comment'] = 'Commentaire pour l\'étudiant';
$string['observation:comment:commentno'] = 'Commentaire {no}';
$string['observation:comment:context'] = 'Contexte';
$string['observation:comment:deleteno'] = 'Supprimer le commentaire {no}';
$string['observation:comment:improvement'] = 'Ce sur quoi je dois m\'améliorer';
$string['observation:comment:missing'] = 'Ce qui m\'a manqué';
$string['observation:comment:observercomment'] = 'Commentaire observateur';
$string['observation:comment:privatecomment'] = 'Commentaire privé pour l\'évaluateur';
$string['observation:comment:progress'] = 'Ce sur quoi j\'ai progressé';
$string['observation:created'] = 'Observation créée';
$string['observation:delete'] = 'Supprimer l\'observation';
$string['observation:delete:confirm'] = 'Confirmez que vous souhaitez supprimer l\'observation';
$string['observation:edit'] = 'Modifier l\'observation';
$string['observation:edit:save'] = 'Enregistrer';
$string['observation:status'] = 'Statut';
$string['observation:status:archived'] = 'Archivé';
$string['observation:status:completed'] = 'Terminé';
$string['observation:status:inprogress'] = 'En cours';
$string['observation:status:notstarted'] = 'Non commencé';
$string['observation_comment:comment'] = 'Contenu du commentaire d\'observation {$a}';
$string['observation_comment:name'] = 'Nom du commentaire d\'observation';
$string['observation_comment:type'] = 'Type de commentaire  {$a}';
$string['observationrequest'] = 'Demande d\'observation';
$string['observationrequested'] = '{$a->targetfullname} a demandé une observation le {$a->timecreated} <br> {$a->userfullname} a complété l\'observation le {$a->timemodified}';
$string['observationwaiting'] = '{$a->targetfullname} a demandé une observation le {$a->timecreated}';
$string['observer:fullname'] = 'Nom complet de l\'observateur';
$string['observer:role'] = 'Observateur';
$string['observer:role:desc'] = 'Rôle d\'observateur : un utilisateur avec ce rôle peut observer un étudiant et créer une observation';
$string['pauseplanningadd'] = 'Ajouter une pause de planification';
$string['penalty'] = 'Pénalité';
$string['penalty_help'] = 'Le nombre d\'observations obtenues est insuffisant. Une pénalité de -20 points est appliquée.';
$string['planning'] = 'Planification';
$string['planning:confirmdelete'] = 'Êtes-vous sûr de vouloir supprimer cette planification ?';
$string['planning:defaultsession'] = 'Session de planification par défaut';
$string['planning:page:badge:autoeval'] = 'Nombre d\'auto-évaluations réalisées / nombre conseillé';
$string['planning:page:badge:cert'] = 'Nombre d\'incontournables certifiés / nombre conseillé';
$string['planning:page:badge:eval'] = 'Nombre d\'observations reçues / nombre conseillé';
$string['planning:page:badge:list'] = 'Nombre de cas remplis dans le case-log / nombre conseillé';
$string['planning:page:info:autoeval'] = 'Auto-Évaluations';
$string['planning:page:info:cert'] = 'Certification';
$string['planning:page:info:eval'] = 'Évaluations';
$string['planning:page:info:list'] = 'List';
$string['planning:page:observers'] = 'Observateurs';
$string['planning:page:students'] = 'Étudiants - {$a}';
$string['planning:session'] = 'Session de planification';
$string['planning:session_help'] = 'Session de planification';
$string['planningcategory:current'] = 'En cours';
$string['planningcategory:future'] = 'A venir';
$string['planningcategory:observercompleted'] = 'Terminées';
$string['planningcategory:observerlate'] = 'Passées';
$string['planningcategory:paused'] = 'En pause';
$string['pluginadministration'] = 'Administration CompetVet';
$string['pluginname'] = 'CompetVet';
$string['points'] = '{$a} points';
$string['previoususer'] = 'Utilisateur précédent';
$string['privacy:metadata'] = 'Le plugin de notation des compétences ne stocke aucune donnée personnelle.';
$string['processing'] = 'Traitement';
$string['progress_createaccountswithroles'] = 'Création de comptes utilisateurs avec le rôle {$a->rolename} ({$a->from} - {$a->to})';
$string['progress_createcompetvets'] = 'Création de situations ({$a})';
$string['progress_creategroups'] = 'Création de groupes';
$string['progress_createobservations'] = 'Création d\'observations ({$a->count}) pour {$a->situation}';
$string['progress_createplannings'] = 'Création de planifications ({$a->count}) pour {$a->situation}';
$string['report:criteria'] = 'Rapport d\'incontournables';
$string['report:grids'] = 'Rapport des grilles d\'évaluation';
$string['report:observations'] = 'Rapport des observations';
$string['report:plannings'] = 'Rapport de planifications';
$string['report:situations'] = 'Rapport de situations';
$string['report:todos'] = 'Rapport des tâches';
$string['report:case_entries'] = 'Rapport des cas cliniques';
$string['responsibleucue:role'] = 'Responsable UC/UE';
$string['responsibleucue:role:desc'] = 'Rôle de responsable UC/UE : un utilisateur avec ce rôle peut observer un étudiant mais peut également créer une nouvelle situation et des planifications.';
$string['saving'] = 'Enregistrement';
$string['savingchanges'] = 'Enregistrement des modifications';
$string['scoreevaluator'] = 'Note validée par l\'évaluateur';
$string['scoreevaluator_help'] = 'Cette note sera celle prise en compte pour le calcul de la note globale.';
$string['search:activity'] = 'Rechercher activités CompetVet';
$string['search:clearstartdate'] = 'Effacer la date de début';
$string['search:group'] = 'Rechercher groupes';
$string['search:startdate'] = 'Date de début';
$string['search:student'] = 'Rechercher étudiants';
$string['search:ungraded'] = 'Rechercher non notés';
$string['seendone'] = 'Vu et fait';
$string['selectcompetvet'] = 'Sélectionner une situation';
$string['selecttype'] = 'Sélectionner le type';
$string['selectstatus'] = 'Sélectionner le statut';
$string['selfevalbonus'] = '+{$a} réalisée et pertinente';
$string['selfevalnone'] = '+ 0 Aucune autoévaluation pertinente';
$string['selfevaluation'] = 'Autoévaluation';
$string['sendstudentnotifications'] = 'Envoyer des notifications aux étudiants';
$string['showastable'] = 'Montrer sous forme de tableau';
$string['situation:autoevalnum'] = 'Nombre d\'autoévaluations conseillées';
$string['situation:autoevalnum_help'] = 'Nombre d\'autoévaluations conseillées';
$string['situation:casenum'] = 'Nombre de cas requis';
$string['situation:casenum_help'] = 'Nombre de cas requis';
$string['situation:category'] = 'Catégorie de situation';
$string['situation:category_help'] = 'Catégorie de situation : Première année,... Le format est [shortname]|fr:[Libellé en français]|en:[Libellé en anglais]';
$string['situation:certifgrid'] = 'Liste des incontournables à certifier';
$string['situation:certifpnum'] = 'Pourcentage de certification conseillé';
$string['situation:certifpnum_help'] = 'Pourcentage de certification conseillé';
$string['situation:cmid'] = 'ID du module de cours pour la situation';
$string['situation:def'] = 'Définition de la situation';
$string['situation:evalgrid'] = 'Grille d\'évaluation des compétences (Eval)';
$string['situation:evalnum'] = 'Nombre d\'évaluations conseillées';
$string['situation:evalnum_help'] = 'Nombre d\'évaluations conseillées';
$string['situation:hascase'] = 'Activer le module "List" pour générer un carnet de cas';
$string['situation:hascase_help'] = 'En sélectionnant cette case vous activez le module "List" dans cette activité.
Ce module peut être sélectionné seul pour servir de carnet de cas en stage';
$string['situation:hascertif'] = 'Activer le module "Certif" pour la certification des incontournables cliniques
 (compétences spécifiques)';
$string['situation:hascertif_help'] = 'En sélectionnant cette case vous activez le module "Certif"
(incontournables cliniques (compétences spécifiques)) dans cette activité.
Il faudra vérifier que la liste des incontournables cliniques est bien remplie dans l\'onglet critères et sélectionnée ci-dessous';
$string['situation:haseval'] = 'Activer le module "Eval" pour l\'évaluation des compétences générales';
$string['situation:haseval_help'] = 'En sélectionnant cette case vous activez le module "Eval" dans cette activité';
$string['situation:haslettergrades'] = 'Activer les notes sous forme de lettres';
$string['situation:haslettergrades_help'] = 'En sélectionnant un barème lettré, les étudiants n\'ont pas accès à leur score sur 100 mais à des lettres utilisant le barème suivant : F : entre 0 et 40; FX : entre 41 et 50; E : entre 51 et 60; D : entre 61 et 70; C : entre 71 et 80; B : entre 81 et 90; A : entre 91 et 100';
$string['situation:idnumber'] = 'ID unique de la situation';
$string['situation:intro'] = 'Introduction de la situation';
$string['situation:listgrid'] = 'Grille  d\'évaluation du journal de cas (List)';
$string['situation:name'] = 'Nom de la situation (complet)';
$string['situation:selector'] = 'Sélecteur de situation';
$string['situation:shortname'] = 'Nom court de la situation';
$string['situation:shortname_help'] = 'Nom court de la situation';
$string['situation:shortnamewithlinks'] = 'Nom court de la situation';
$string['situation:tags'] = 'Étiquettes pour la situation';
$string['situationname'] = 'Nom de la situation';
$string['startdate'] = 'Date de début';
$string['status'] = 'Statut';
$string['status:archived'] = 'Archivé';
$string['status:draft'] = 'Brouillon';
$string['status:published'] = 'Publié';
$string['statusproposed'] = 'Statut proposé par CompetVet';
$string['student'] = 'Étudiant';
$string['student:fullname'] = 'Nom complet de l\'étudiant';
$string['studentprogress'] = 'accès à l\'émulateur pour effectuer des actions identiques à celles de l\'application mobile';
$string['submit'] = 'Soumettre';
$string['suggestedgrade'] = 'Note suggérée';
$string['suggestedgrade_help'] = 'Note suggérée';
$string['supervisorchart'] = 'Tableau de supervision';
$string['supervisorsection'] = 'Superviseurs';
$string['tab:autoeval'] = 'Auto-Éval ({$a->done}/{$a->required})';
$string['tab:cert'] = 'Cert ({$a->certdone}/{$a->certopen})';
$string['tab:eval'] = 'Éval ({$a->done}/{$a->required})';
$string['tab:list'] = 'List ({$a->cases})';
$string['tabletitle:autoevalstudent'] = 'Auto-évaluations de l\'étudiant';
$string['tabletitle:evals'] = 'Observations de l\'étudiant';
$string['tagarea_competvet_situation'] = 'Situations';
$string['tagcollection_situations'] = 'Situations';
$string['timegraded'] = 'Noté le';
$string['todo:action'] = 'Action';
$string['todo:action:certif:valid:asked'] = 'Validation demandée';
$string['todo:action:cta:eval:asked'] = 'Créer une observation';
$string['todo:action:eval:asked'] = 'Observation demandée';
$string['todo:action:format:certificationasked'] = 'Certification demandée par {$a->student} pour la situation {$a->situationlabel}
 à l\'observateur {$a->observer}';
$string['todo:action:format:observationasked'] = 'Observation demandée par {$a->student} pour la situation {$a->situationlabel}
 à l\'observateur {$a->observer}';
$string['todo:data'] = 'Données';
$string['todo:date'] = 'Date';
$string['todo:delete'] = 'Supprimer';
$string['todo:deleteselected'] = 'Supprimer la sélection';
$string['todo:notodos'] = 'Aucune tâche à afficher';
$string['todo:planning'] = 'Planification';
$string['todo:status'] = 'Statut';
$string['todo:status:deleted'] = 'Effacé';
$string['todo:status:done'] = 'Done';
$string['todo:status:pending'] = 'En attente';
$string['todo:targetuser'] = 'Utilisateur cible';
$string['todo:type'] = 'Type';
$string['todo:user'] = 'Utilisateur';
$string['todo:user:fullname'] = 'Nom complet de l\'utilisateur';
$string['todos'] = 'À faire';
$string['valid:confirmed'] = 'Je confirme que cet essentiel a été réalisé';
$string['valid:levelnotreached'] = 'Je ne valide pas car le niveau n\'est pas atteint';
$string['valid:notseen'] = 'Je ne valide pas car je n\'ai pas vu cet essentiel';
$string['valid:observernotseen'] = 'L\'observateur n\'a pas vu cet essentiel';
$string['validate'] = 'Valider cet essentiel';
$string['validated'] = 'Validé';
$string['view'] = 'Voir';
$string['viewgrade'] = 'Voir l\'évaluation';
$string['report:caselogentries'] = 'Rapport des entrées de cas cliniques';
