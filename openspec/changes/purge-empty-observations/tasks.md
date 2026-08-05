## 1. Audit current empty-observation behavior

- [ ] 1.1 Review how observation creation, criterion levels, and empty-grade semantics currently produce persisted empty observations
- [ ] 1.2 Identify every counter, summary flag, or planning indicator that currently counts raw observations instead of effective graded observations
- [ ] 1.3 Identify the appropriate privileged execution path for running an empty-observation purge in CompetVet

## 2. Define and apply the shared emptiness rule

- [ ] 2.1 Introduce a shared observation-level rule that determines whether an observation is empty from its effective grades
- [ ] 2.2 Update observation counters and summary APIs so empty observations are excluded from displayed counts
- [ ] 2.3 Update planning or user-data presence checks so empty observations alone do not make a planning look populated when that is no longer intended

## 3. Implement the purge process

- [ ] 3.1 Add the purge mechanism for observations classified as empty
- [ ] 3.2 Ensure purge uses full observation deletion so dependent comments and criterion records are removed consistently
- [ ] 3.3 Protect the purge mechanism so observations with at least one usable grade are never deleted
- [ ] 3.4 Restrict purge execution to the intended administrative or maintenance permissions

## 4. Protect the behavior with tests

- [ ] 4.1 Add regression coverage for observation emptiness detection across null grades, no-grade sentinel values, and real grades
- [ ] 4.2 Add regression coverage proving empty observations no longer contribute to observation counters and summary flags
- [ ] 4.3 Add regression coverage proving purge deletes empty observations and their dependencies but preserves graded observations
- [ ] 4.4 Add regression coverage for purge authorization and mixed historical datasets
