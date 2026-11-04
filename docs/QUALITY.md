# Code quality — continued improvement

Internal checklist for `block_dixeo_designer` (architecture already aligned: `external/` + `service/`, `ajax/` scripts, Dixeo rules in `.cursor/rules`).

## Done in-tree

- PHPUnit for externals (mocked), `designer_service`, factory, course creation, submission, structure repo, template helper, cleanup task, **`render_helper`**, **`prepare_progress_cache`**.
- `classes/README.md`, `amd/README.md`, `ajax/README.md` describe layout.
- **i18n:** Module “Summary” / “Instructions” labels use `block_dixeo_designer` strings; client-side error fallbacks (upload/cancel/delete/status/generation/finalize/save) use lang strings. Success banner logo `alt` is hardcoded (`Dixeo`) in the template to avoid an extra string fetch. Mustache templates use `{{#str}}` or context from PHP/JS — no remaining user-facing literals in templates except dynamic data (titles, file names). **Remaining English-only UI:** `MODULE_TYPE_OPTIONS` fallback in `amd/src/designer.js` when `local_dixeo_get_module_types` fails or returns empty (labels are normally supplied by the API).
- Cancel/finalize workflow now distinguishes progress-preserving block cancel vs footer hard reset, and self-heal finalize recreates draft prerequisites (resource copy + sync ready + vector-store sync) before module fill.

## Actionable next steps (prioritised)

1. **Shrink orchestration files**  
   - Split `classes/service/designer_service.php` and `designer_course_creation_service.php` by workflow phase (start/sync vs cancel vs finalize), keeping business rules in `local_dixeo` where applicable.

2. **Large AMD modules**  
   - Break up `amd/src/progress.js` and `amd/src/designer.js` into smaller modules (same public `define` entry points if needed), run `grunt amd`.

3. **Static analysis in CI**  
   - `phpcs` (Moodle standard) + **PHPStan** (or Moodle plugin checker) on this component; fix reported issues.

4. **Coverage threshold**  
   - PHPUnit with coverage report; aim for high coverage on `designer_service` cancel/finalize branches and `service/remote/dixeo_remote_adapter.php` (with mocks).

5. **Developer-only strings**  
   - Optional: move prefixes in `debugging()` messages in `designer_course_creation_service` to lang strings (`lang/en`) for consistency (low priority).

6. **JS tests (optional)**  
   - Jest or Moodle AMD test pattern for `filesync_progress_map.js` pure functions.

Review this file when planning refactors; bump `version.php` after behavioural changes.
