## Why

Un conflit apparent de roles dans CompetVet peut venir non pas d'un conflit entre student et teacher, mais d'un conflit entre student herite du cours et observer affecte explicitement sur l'activite. Si CompetVet lit tous les roles visibles depuis le contexte du module sans prioriser correctement les affectations directes sur l'activite, il peut conclure a tort qu'un utilisateur est a la fois student et observer.

## What Changes

- Verifier et formaliser la regle de resolution du role effectif d'un utilisateur dans CompetVet, avec priorite aux roles affectes directement sur l'activite par rapport aux roles herites du cours, de la categorie ou du systeme.
- Verifier que le conflit cible concerne bien student et observer, et non student et teacher.
- Securiser l'import CSV des roles en supprimant d'abord les roles CompetVet deja presents sur le contexte du module avant de reappliquer les roles du fichier.
- Add or confirm regression coverage for inherited-versus-direct role conflicts.
- Verifier separement ce qui se passe quand un groupe disparait, afin de distinguer un probleme de role d'un probleme de planning orphelin.

## Capabilities

### New Capabilities
- `competvet-role-resolution`: Defines how CompetVet computes a user's effective role when direct and inherited roles coexist, and how role import avoids stale conflicts.

### Modified Capabilities

## Impact

- Resolution des roles dans les APIs et vues CompetVet.
- Import CSV des roles sur le contexte du module.
- Tests de regression autour des roles herites, des roles directs et des conflits observer/student.
- Audit du comportement des plannings quand un groupe disparait, pour verifier que le probleme n'est pas confondu avec un conflit de roles.
