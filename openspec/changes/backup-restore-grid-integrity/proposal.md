## Why

Le backup/restore de CompetVet fonctionne deja avec une couverture unitaire de base, mais il reste un risque important de duplication ou de remappage incoherent des grilles, criteres et donnees associees lors de restaurations repetees ou dans des environnements qui contiennent deja des grilles existantes. Ce point est sensible car une duplication silencieuse peut casser les references de criteres, rendre les activites ambigues et complexifier durablement l'administration.

## What Changes

- Verifier et durcir le comportement de backup/restore de CompetVet pour les grilles, criteres et references associees.
- Specifier clairement dans quels cas une grille doit etre reutilisee lors du restore et dans quels cas une nouvelle grille doit etre creee.
- S'assurer qu'un restore ne duplique pas inutilement les grilles ni les criteres quand des equivalences existent deja.
- S'assurer que les situations, observations, certifications et autres donnees restaurees pointent bien vers les bonnes grilles et bons criteres apres remapping.
- Elargir la couverture de tests au-dela du simple restore nominal, notamment sur les restores repetes, les restores dans un site qui contient deja des grilles, et l'absence de duplication des criteres.

## Capabilities

### New Capabilities
- `backup-restore-grid-integrity`: Garantit qu'un backup/restore CompetVet remappe de maniere coherente les grilles et criteres sans duplication indue et sans casser les references metier.

### Modified Capabilities

## Impact

- Pipeline Moodle de backup/restore du module CompetVet.
- Mapping des entites `grid`, `criterion`, `situation`, `planning`, `observation`, `certification` et donnees de cas.
- Strategie de reutilisation des grilles existantes selon leur identite fonctionnelle.
- Tests PHPUnit de backup/restore, avec scenarios de restore multiples et verifications d'integrite des references.
