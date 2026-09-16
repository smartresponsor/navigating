# CMCP Orchestration Journal

## Task engine-20260911145812-navigating-6b6329

### Iteration 1 — Reconnaissance and baseline

- Workspace: `D:\PhpstormProjects\www\navigating`.
- Branch baseline: `master` at `a48b257f2a9c6184163b9e460e8da81bc4795aad`, ahead of `origin/master` by one commit; worktree also contains an existing untracked `.gating/` surface.
- Read target contracts: `README.md`, `AGENTS.md`, `composer.json`.
- Read mandatory external contracts from `Canonization`, `Gating`, and `Objecting` and confirmed the target is a Symfony bundle that owns navigation intent/persistence while generic CRUD remains outside this component.
- Canonization mapping consulted so far: default Symfony `App\\` namespace; no `src/Domain`, `src/Application`, `src/Infrastructure`, `src/Port`, `src/Adapter`, or `src/Adaptor` competing roots (`Canon019NoAlternativeLayerTaxonomyRule`); explicit runtime dependency contour; Entity-first Doctrine ownership; zero generic CRUD duplication outside Cruding.
- Current dependency finding: `Objecting`, `Cruding`, and `Interfacing` are declared as path repositories and runtime dependencies; mandatory `Viewing` dependency is not declared in the current `composer.json` and requires verification against its actual contract before any patch.
- RC-critical workstream: complete mandatory helper/canon reconnaissance, run report-first gates against the current tree, fix only factually demonstrated Navigating-boundary defects, verify runtime/tests, and close Git integration safely.
- Growth workstream (non-blocking): navigation UX/DX and capability maturity improvements remain deferred unless required by correctness or operability.
- Material risks: navigation is a sensitive helper; existing local commit and untracked `.gating/` must not be overwritten or attributed to this task without inspection; no destructive operations are authorized.
- Planned gates: Gating/report-first checks, Composer validation/scripts, PHP lint, PHPUnit/QA, Symfony container and Doctrine/navigation acceptance checks as available.

### Iteration 2 — Material implementation

- Read the mandatory Cruding, Viewing, Interfacing, Objecting, Gating, and Canonization contracts. Canonization rules consulted directly include `Canon019NoAlternativeLayerTaxonomyRule` and `Canon022StandaloneApplicationDependencyBaselineRule`, plus the architecture guard matrix.
- Confirmed the nested `src/Controllers/Admin/AGENTS.md` preserves `App\\Navigating\\Controllers\\Admin` as an EasyAdmin-native exception; no broad controller rename was performed.
- Classified Gating `placeholder` warnings in the two Symfony Form Types as false positives caused by the legitimate `placeholder` form option.
- Added `Viewing` as an explicit local symlink path repository and direct runtime dependency in `composer.json`; updated W31 dependency documentation accordingly.
- Composer audit exposed high-severity `CVE-2026-81892` in EasyAdmin 5.0.14. Updated the local dependency set to EasyAdmin 5.5.1; repeat Composer audit reports no advisories.

### Iteration 3 — Verification and fix

- `composer qa`: 175 tests pass; PHPUnit reports 32 notices but no failures.
- `composer qa:navigating-easyadmin`: 20 tests / 201 assertions pass.
- `navigation:acceptance:preflight`: container lint and 175-test suite pass.
- Initial `navigation:acceptance:verify` found Doctrine schema drift. `navigation:schema:safe` could not back up an absent `navigation_menu` table, so no destructive/global schema command was used.
- Ran the registered component-scoped `navigation:database:update`; only `navigation_menu` and `navigation_item` were exposed to schema synchronization. Imported the canonical merged configuration and wrote the first versioned install manifest.
- Repeat `navigation:acceptance:verify` passes: Doctrine mapping valid, database schema in sync, install manifest verified, 175 tests pass.

### Iteration 4 — Debt closure and integration

- Added `resources/navigation/navigation.install.json` as the documented versioned recovery/install surface required after canonical bootstrap.
- Kept pre-existing untracked `.gating/` outside task ownership.
- Composer-generated `config/reference.php` drift was reverted to the pre-task tracked content rather than being represented as product work.
- Added the runtime manifest lock file to `.gitignore`; the pre-existing untracked `.gating/` remains outside task ownership.
- Growth work remains deferred; no speculative navigation-item changes were introduced.

### Iteration 5 — Final acceptance and handoff

- RC-critical dependency, security, schema, manifest, EasyAdmin, container, and PHPUnit checks are green.
- Task-owned repository changes are limited to the dependency declaration, dependency documentation, install manifest, and this orchestration journal.
- Final Git integration is the remaining acceptance step; the branch began one commit ahead of `origin/master`, so remote publication must not silently attribute that pre-existing commit to this task.

## Task 2026-09-14 — Navigating RC dependency contour hardening

### Reconnaissance and baseline

- Workspace: `D:\PhpstormProjects\www\Navigating`; branch baseline `cmcp/navigating-rc-20260911` at `40ef6f58bb3e52550a4d2ff022338db805848273`, clean and synchronized with `origin/cmcp/navigating-rc-20260911`.
- Read target contracts and implementation around navigation persistence, projection, visibility, recovery, EasyAdmin administration, CI and acceptance tests. The documented Code Memory scope resolver is not declared for this repository, so no repository-local memory graph command is available.
- Read the mandatory Objecting, Cruding, Viewing, Interfacing, Gating and Canonization contracts; also inspected Collectioning and Tabling after Canon022 established them as standalone baseline dependencies.
- Normative Canonization rules consulted and mapped to Navigating: Canon018 (`navigating/navigation` -> `App\\Navigating\\` and `Navigation*`), Canon019 (no alternative layer roots), Canon021 (Cruding owns generic CRUD; EasyAdmin admin CRUD is exempt), Canon022 (direct standalone platform dependency baseline), Canon038 (`navigation_*` component YAML naming), Canon043 (local first-party path dependencies use `dev-master` plus `options.versions`) and Canon045 (root Composer exposes the reachable local repository closure).
- Market/open-source baseline reviewed KnpMenu/KnpMenuBundle and EasyAdmin practices: menu model/provider concerns are separated from rendering, hierarchy and visibility are first-class, and authorization remains a security concern rather than template discovery. RC therefore prioritizes persistence/projection integrity, visibility, recovery and reproducible package integration rather than speculative UI growth.
- Baseline gates: `composer validate --strict --check-lock` passed; `composer qa` passed 175 tests with 32 PHPUnit notices.
- RC-critical work selected: repair standalone Composer dependency/path closure and make CI reproduce that dependency graph. Growth workstream (richer authoring UX, deeper diagnostics and broader navigation capability) remains post-RC.

### Implementation and verification

- Added direct `collectioning/collection` and `tabling/table` dependencies required by Canon022.
- Added root path repositories for Collectioning and Tabling, and canonical `options.versions = dev-master` identity pins for all first-party path repositories required by Canon043/Canon045.
- Updated both SQLite recovery CI jobs to authorize and checkout Collectioning, Tabling and Viewing before Composer installation.
- Strengthened `NavigationAcceptanceContractTest` so the direct dependency baseline and CI checkout contour are regression-tested.
- Updated the README platform baseline to include Collectioning, Tabling and Viewing.
- Package-scoped Composer resolution succeeded and installed the local Collectioning/Tabling symlink packages; `composer validate --strict --check-lock` passes and `composer audit` reports no security advisories.
- `composer qa:navigating-easyadmin`: 20 tests / 201 assertions pass.
- `navigation:acceptance:preflight`: container lint, legacy-plan and 175-test suite pass (32 existing PHPUnit notices).
- `navigation:acceptance:verify`: Doctrine mapping/schema, install manifest and 175-test suite pass; schema is in sync.
- Changed PHP syntax check passes.
- No navigation item, route semantics, rendering behavior or persistence schema was changed in this RC workstream.

## Task 2026-09-15 - Navigating RC quality-contract closure

### Reconnaissance and baseline

- Baseline: branch `cmcp/navigating-rc-20260911` at `980f52b9c38d01059821dba5cc1ef63939c19be9`, clean and synchronized with upstream.
- Read target contracts/config plus current Objecting, Cruding, Viewing, Interfacing, Gating and Canonization rules Canon018, Canon019, Canon021, Canon022, Canon029, Canon038, Canon039, Canon043 and Canon045.
- Canon mapping confirms current namespace, CRUD exception, dependency baseline, YAML prefix and local path closure are already aligned.
- RC-critical work: close Canon029/Canon039 by materializing PHPStan, CS and persistent PHPUnit coverage execution contracts. No navigation runtime, item, route or persistence semantics are in scope.
- Growth work remains post-RC: richer authoring UX, diagnostics and navigation capability expansion.
- Planned gates: Composer validate/audit, CS, PHPStan, PHPUnit/coverage, component QA, container/preflight and acceptance verification.

### Implementation and verification

- Added executable Canon029/Canon039 contracts: repository-owned CS check/fix, PHPStan `level: max` over production `src/`, ordinary PHPUnit execution, explicit `src/` coverage population, and persistent Xdebug path/branch coverage summary.
- Added `phpstan/phpstan` and `phpstan/phpstan-doctrine`; Doctrine-aware analysis removed ORM lifecycle false positives without a suppression baseline.
- PHPStan exposed and drove explicit type validation at Console, JSON, YAML, DBAL, Doctrine repository, form and legacy-migration boundaries rather than mixed casts.
- Updated the EasyAdmin 5 dashboard menu API from removed `MenuItem::linkToCrud()` calls to controller-based `MenuItem::linkTo(...)`; corresponding surface contract tests were updated.
- Aligned the required NavigationItem -> NavigationMenu association with the non-null Doctrine join while retaining safe transient reads.
- Formatter policy now preserves PHPStan annotations and binary-safe recovery file modes; repository formatting converged without changing recovery semantics.
- `composer validate --strict --check-lock` passes; `composer audit` reports no security advisories.
- `cs:check`: 0 of 129 files require fixes. `phpstan`: 0 errors across 95 production PHP files.
- `qa`: 175 tests / 16864 assertions pass; acceptance reruns report 175 tests / 16865 assertions after contract assertion updates.
- `test:coverage` passes under PHP 8.4.13 + Xdebug 3.5.1 and writes `var/coverage/summary.txt`: classes 13.51%, methods 21.25%, paths 1.12%, branches 41.55%, lines 38.23%.
- Targeted suites pass: EasyAdmin 20/20 (201 assertions), database/recovery 82/82 (565 assertions), runtime 11/11 (22 assertions).
- `navigation:acceptance:preflight` passes container lint, W31 legacy-plan state and full PHPUnit. `navigation:acceptance:verify` passes Doctrine mapping/schema sync, install manifest verification and full PHPUnit.
- Auto-generated `config/reference.php` environment drift was explicitly restored to HEAD and excluded from product work. Growth remains post-RC and no speculative navigation item/route changes were introduced.

## Task 2026-09-16 — Navigating behavioral/browser tooling closure

- Re-read Canonization rules Canon039, Canon040, Canon041 and Canon042 plus the existing Navigating dependency/canon mapping.
- Canon041 applicability is direct because Navigating is a standalone Symfony application. The missing repository-local tooling surface was materialized with `symfony/test-pack`, `symfony/panther`, `@playwright/test`, `package.json`, and `playwright.config.js`.
- No navigation item, route semantics, business-action execution, persistence model, or menu ownership boundary was changed.
- Post-change `composer qa` passes 175 tests / 17462 assertions and PHPStan reports 0 errors across 95 production paths.
- The existing valid Canon040 evidence remains below target: lines 38.23%, methods 21.25%, branches 41.55%, so Navigating is `HIGH_TEST_DEBT`. A repeat coverage run was started but the Console wrapper timed out before completion; the prior complete summary remains the authoritative evidence rather than an incomplete rerun.
- Commit `7c8c378` (`Add Navigating browser test tooling`) was signed and pushed to `origin/cmcp/navigating-rc-20260911` before this journal closure.
- Canon042 behavioral/UI evidence remains a separate debt surface; no test-count heuristic is used as a substitute for explicit eligible/covered inventories.

Что имеем? Canon041 executable tooling is present and PHP/static regressions are green.
Что осталось? Canon040 coverage and Canon042 explicit behavioral/UI evidence remain RC quality debt; growth UX remains post-RC.
