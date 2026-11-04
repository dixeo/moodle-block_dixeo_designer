# `block_dixeo_designer` PHP layout

Top-level under `classes/`:

| Area | Path | Role |
|------|------|------|
| **Web services** | `external/draft/`, `external/course/` (+ `dto/` under each) | AJAX externals only. `start_generation` calls `designer_service::prepare_generation()`, which creates or reuses the draft course and starts file sync; `submit_structure_job` then submits the remote structure job. |
| **Application layer** | `service/` | Orchestration, remote adapter, submission persistence, structure versions, template UI helper, prepare-progress cache. |
| **Shared constants** | `workflow_constants.php` | Workflow/submission status strings (root namespace `block_dixeo_designer`). |
| **Moodle plumbing** | `privacy/`, `task/` | Privacy provider, scheduled task. |

### `service/` in more detail

| Subpath | Contents |
|---------|----------|
| `service/` | `designer_service`, `designer_submission_ui_service`, `designer_course_creation_service`, `designer_service_factory`, `course_template_helper` |
| `service/remote/` | Dixeo API adapter |
| `service/cache/` | `prepare_progress_cache` (Moodle prepare phase for file-sync UI) |
| `service/structure/` | `repository` — saved structure JSON versions |
| `service/submission/` | `service`, `repository`, `file_service`, `render_helper` — job rows + files + Mustache context |

### AJAX scripts (not under `classes/`)

| Path | Role |
|------|------|
| `ajax/upload_files.php` | Multipart upload for designer source files |
| `ajax/delete_file.php` | Delete one uploaded file |

See `ajax/README.md`. These are distinct from `classes/service/submission/` (PHP classes for persistence).
