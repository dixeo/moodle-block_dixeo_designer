// This file is part of Moodle - http://moodle.org/
//
// @module     block_dixeo_designer/progress
// @copyright  2026 Dixeo
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

/**
 * Designer generation progress: polling, bar animation, step labels, and shared phase/session helpers.
 * File-sync band mapping: {@link module:block_dixeo_designer/filesync_progress_map}.
 *
 * @module block_dixeo_designer/progress
 */
define([
    'core/ajax',
    'core/str',
    'core/config',
    'core/notification',
    'core/templates',
    'block_dixeo_designer/filesync_progress_map',
    'block_dixeo_designer/content_phase_progress'
], function(Ajax, Str, Config, Notification, Template, filesyncMap, ContentPhaseProgress) {
    'use strict';

    // Phases returned by block_dixeo_designer_get_finalize_progress().
    const PHASE_GENERATING_CONTENT = 'generating_content';
    const PHASE_FINALIZING = 'finalizing';
    const PHASE_DONE = 'done';

    // Remote course structure generation requires instructions >= this length.
    const MIN_INSTRUCTIONS_LEN = 20;

    const SESSION_RETURN_TO_KEY = 'block_dixeo_designer_return_to';
    const SESSION_RETURN_TO_JOBID_KEY = 'block_dixeo_designer_return_to_jobid';

    // API / template keys that must remain snake_case for the server (accessed via consts).
    const KEY_JOB_ID = 'job_id';
    const KEY_FINALIZE_MODE = 'finalize_mode';
    const KEY_GENERATE_ANOTHER_URL = 'generate_another_url';
    const KEY_MODULE_TOTAL = 'module_total';
    const KEY_MODULE_INDEX = 'module_index';
    const KEY_SECTION_TOTAL = 'section_total';
    const KEY_SECTION_INDEX = 'section_index';

    /**
     * Dispatched on document when generation/finalize UI should release backdrop + polls
     * (e.g. generator cancel must unlock designer.js Create Course lock).
     */
    const GLOBAL_UNLOCK_UI_EVENT = 'dixeo_designer_global_unlock_ui';

    /**
     * Dispatched immediately before a programmatic navigation (e.g. reload designer after structure save)
     * so designer.js can clear beforeunload guards without waiting for unload.
     */
    const ALLOW_NAVIGATION_EVENT = 'dixeo_designer_allow_navigation';

    /**
     * Dispatched when structure finalize validation failed with field paths (designer UI).
     * detail: { job_id: string, fielderrors: {path, message}[] }
     */
    const STRUCTURE_FIELD_VALIDATION_EVENT = 'dixeo_designer_structure_field_validation';

    /**
     * Resolve after ms (flattenable alternative to setTimeout + promises).
     *
     * @param {number} ms
     * @returns {Promise}
     */
    function delay(ms) {
        return new Promise(function(resolve) {
            setTimeout(resolve, ms);
        });
    }

    /**
     * Best-effort step label update (swallows string fetch failures).
     *
     * @param {object} self Progress instance
     * @param {number} step Step number
     * @param {string} key Lang string key
     * @param {object} [params] Lang string params
     * @returns {Promise}
     */
    function updateStepLabel(self, step, key, params) {
        return Str.get_string(key, 'block_dixeo_designer', params).then(function(str) {
            self.setStepLabel(step, str);
            return null;
        }).catch(function() {
            return null;
        });
    }

    /**
     * Alert using a lang string as fallback when failure.message is missing.
     *
     * @param {Error|object|null} failure
     * @param {string} stringKey
     * @returns {Promise}
     */
    function alertErrorWithString(failure, stringKey) {
        return Str.get_string(stringKey, 'block_dixeo_designer').then(function(msg) {
            Notification.alert('', (failure && failure.message) ? failure.message : msg);
            return null;
        }).catch(function() {
            return null;
        });
    }

    /**
     * Handle invalid structure validation response (field events + optional alert).
     *
     * @param {object} self Progress instance
     * @param {object|null} vresp Validation response
     * @returns {Promise}
     */
    function handleInvalidStructure(self, vresp) {
        self.resetProgress();
        const fielderrors = (vresp && vresp.fielderrors) ? vresp.fielderrors : [];
        if (fielderrors.length) {
            const detail = {
                fielderrors: fielderrors
            };
            detail[KEY_JOB_ID] = self.getJobId();
            document.dispatchEvent(new CustomEvent(
                STRUCTURE_FIELD_VALIDATION_EVENT,
                {
                    bubbles: true,
                    detail: detail
                }
            ));
        }
        const errs = (vresp && vresp.errors && vresp.errors.length) ?
            vresp.errors :
            ['Validation failed'];
        const body = errs.join('\n\n');
        if (!fielderrors.length) {
            return Str.get_string(
                'designer_structure_validation_failed_title',
                'block_dixeo_designer'
            ).then(function(title) {
                Notification.alert(title, body);
                return null;
            }).catch(function() {
                Notification.alert('', body);
                return null;
            });
        }
        return Promise.resolve(null);
    }

    /**
     * Start quick finalize + begin polling (preserves fire-and-forget finalize call).
     *
     * @param {object} self Progress instance
     * @param {*} runId Generation run id
     * @returns {Promise}
     */
    function startQuickFinalize(self, runId) {
        const finalizeArgs = {
            createcourse: true,
            sesskey: M.cfg.sesskey
        };
        finalizeArgs[KEY_JOB_ID] = self.getJobId();
        finalizeArgs[KEY_FINALIZE_MODE] = 'quick';

        const finalizePromise = Ajax.call([{
            methodname: 'block_dixeo_designer_finalize_course',
            args: finalizeArgs,
        }])[0].catch(function(err) {
            if (runId !== self.generationRunId) {
                return null;
            }
            self.resetProgress();
            return alertErrorWithString(err, 'designer_error_finalize_failed');
        });
        self.pollFinalizeProgress(runId);
        return finalizePromise;
    }

    /**
     * Validate structure then finalize for create-course flow.
     *
     * @param {object} self Progress instance
     * @param {string} structureJson
     * @param {*} runId Generation run id
     * @returns {Promise}
     */
    function finalizeAfterStructure(self, structureJson, runId) {
        const validateArgs = {
            structure: structureJson,
            sesskey: M.cfg.sesskey
        };
        validateArgs[KEY_JOB_ID] = self.getJobId();

        return Ajax.call([{
            methodname: 'block_dixeo_designer_validate_structure_for_finalize',
            args: validateArgs,
        }])[0]
        .then(function(vresp) {
            if (runId !== self.generationRunId) {
                return null;
            }
            if (!vresp || !vresp.valid) {
                return handleInvalidStructure(self, vresp);
            }
            return startQuickFinalize(self, runId);
        })
        .catch(function(err) {
            if (runId !== self.generationRunId) {
                return null;
            }
            self.resetProgress();
            return alertErrorWithString(err, 'designer_error_finalize_failed');
        });
    }

    /**
     * Save structure and navigate to designer for the current job.
     *
     * @param {object} self Progress instance
     * @param {string} structureJson
     * @returns {Promise}
     */
    function saveStructureAndNavigate(self, structureJson) {
        const saveArgs = {
            structure: structureJson,
            sesskey: M.cfg.sesskey
        };
        saveArgs[KEY_JOB_ID] = self.getJobId();

        return Ajax.call([{
            methodname: 'block_dixeo_designer_save_structure',
            args: saveArgs,
        }])[0]
        .then(function() {
            document.dispatchEvent(
                new CustomEvent(ALLOW_NAVIGATION_EVENT, {bubbles: true})
            );
            window.location.href = Config.wwwroot +
                '/blocks/dixeo_designer/designer.php?id=' + self.getJobId();
            return null;
        })
        .catch(function(err) {
            self.resetProgress();
            return alertErrorWithString(err, 'designer_error_save_structure_failed');
        });
    }

    /**
     * Map progress percentage to an active step.
     *
     * Step mapping: 0–20% => 1; >20–40% => 2; >=40–<80% => 3; >=80% => 4.
     *
     * @param {number} progress Progress percentage (0-100)
     * @returns {number} Step number (1-4)
     */
    function getActiveStepFromProgress(progress) {
        const p = Number(progress);
        const safeP = Number.isFinite(p) ? p : 0;

        if (safeP >= 80) {
            return 4;
        }
        if (safeP >= 40) {
            return 3;
        }
        if (safeP > 20) {
            return 2;
        }
        return 1;
    }

    /**
     * Progress UI + polling merged onto the generator instance via Object.assign.
     *
     * @param {object} refs
     * @param {Element|null} refs.generationContainer
     * @param {Element|null} refs.generatorForm
     * @param {Element|null} refs.promptContainer
     * @param {Element|null} refs.filesContainer
     * @returns {object}
     */
    function createGeneratorProgress(refs) {
        const r = refs;
        const contentPhaseAnimator = ContentPhaseProgress.createAnimator();

        return {
            progress: 0,
            filesyncPollIntervalId: null,
            filesyncProgressAnimRafId: null,
            structurePollIntervalId: null,
            step2FakeIntervalId: null,
            step2StartMs: null,
            finalizePollIntervalId: null,

            cancelFileSyncProgressAnimation: function() {
                if (this.filesyncProgressAnimRafId !== null) {
                    cancelAnimationFrame(this.filesyncProgressAnimRafId);
                    this.filesyncProgressAnimRafId = null;
                }
            },

            animateFileSyncProgressTo: function(targetMapped, runId) {
                const self = this;
                const end = Math.min(20, Math.max(0, targetMapped));
                if (runId !== self.generationRunId) {
                    return;
                }
                self.cancelFileSyncProgressAnimation();
                const start = self.progress;
                if (end <= start + 0.02) {
                    if (end > start) {
                        self.setProgress(end);
                    }
                    return;
                }
                const durationMs = 280;
                const t0 = performance.now();
                const tick = function(now) {
                    if (runId !== self.generationRunId) {
                        self.filesyncProgressAnimRafId = null;
                        return;
                    }
                    const t = Math.min(1, (now - t0) / durationMs);
                    const eased = 1 - (1 - t) * (1 - t);
                    const value = start + (end - start) * eased;
                    self.setProgress(value);
                    if (t < 1) {
                        self.filesyncProgressAnimRafId = requestAnimationFrame(tick);
                    } else {
                        self.setProgress(end);
                        self.filesyncProgressAnimRafId = null;
                    }
                };
                this.filesyncProgressAnimRafId = requestAnimationFrame(tick);
            },

            clearAllProgressPolls: function() {
                this.cancelFileSyncProgressAnimation();
                if (this.filesyncPollIntervalId) {
                    clearInterval(this.filesyncPollIntervalId);
                    this.filesyncPollIntervalId = null;
                }
                if (this.structurePollIntervalId) {
                    clearInterval(this.structurePollIntervalId);
                    this.structurePollIntervalId = null;
                }
                if (this.step2FakeIntervalId) {
                    clearInterval(this.step2FakeIntervalId);
                    this.step2FakeIntervalId = null;
                }
                this.step2StartMs = null;
            },

            startStep2Progress: function(createcourse, runId) {
                const self = this;

                /**
                 * Slow fake progress for step 2 (20% to 37%) while the remote job prepares.
                 */
                function startStep2Fake() {
                    self.step2StartMs = Date.now();
                    self.step2FakeIntervalId = setInterval(function() {
                        if (self.step2StartMs === null) {
                            return;
                        }
                        const elapsed = Date.now() - self.step2StartMs;
                        const t = Math.min(1, elapsed / 90000);
                        const fake = 20 + 17 * t;
                        if (self.progress < fake) {
                            self.setProgress(fake);
                        }
                    }, 500);
                }

                let submitted = false;

                const pollFileSync = function() {
                    if (runId !== self.generationRunId) {
                        return;
                    }
                    const syncArgs = {
                        sesskey: M.cfg.sesskey
                    };
                    syncArgs[KEY_JOB_ID] = self.getJobId();
                    Ajax.call([{
                        methodname: 'block_dixeo_designer_get_filesync_status',
                        args: syncArgs,
                    }])[0]
                    .then(function(data) {
                        if (runId !== self.generationRunId) {
                            return null;
                        }
                        if (data && data.errormessage) {
                            self.clearAllProgressPolls();
                            self.resetProgress();
                            Notification.alert('', data.errormessage);
                            return null;
                        }

                        const hasSubmissionFiles = data.hassubmissionfiles === true || data.hassubmissionfiles === 1;
                        const lastsyncNum = Number(data.lastsynccompleted);
                        const noneReady = data.status === 'none'
                            && Number.isFinite(lastsyncNum) && lastsyncNum > 0;

                        const targetMapped = filesyncMap.computeTarget(data, {
                            hasSubmissionFiles: hasSubmissionFiles,
                            structureSubmitDone: self.structureSubmitDone,
                        });

                        if (targetMapped !== null && self.progress < targetMapped) {
                            self.animateFileSyncProgressTo(targetMapped, runId);
                        }

                        const labelSpec = filesyncMap.resolveLabel(data, hasSubmissionFiles);
                        const labelPromise = updateStepLabel(
                            self,
                            1,
                            labelSpec.key,
                            labelSpec.params || {}
                        );

                        if (!submitted && (data.status === 'synchronized' || noneReady)) {
                            submitted = true;
                            self.cancelFileSyncProgressAnimation();
                            self.submitStructureAndPoll(createcourse, runId, startStep2Fake);
                        }
                        return labelPromise;
                    })
                    .catch(function(err) {
                        if (runId !== self.generationRunId) {
                            return null;
                        }
                        self.clearAllProgressPolls();
                        self.resetProgress();
                        return alertErrorWithString(err, 'designer_error_status_check_failed');
                    });
                };

                pollFileSync();
                this.filesyncPollIntervalId = setInterval(pollFileSync, 2000);
            },

            submitStructureAndPoll: function(createcourse, runId, startStep2Fake) {
                const self = this;
                if (runId !== self.generationRunId) {
                    return;
                }

                if (this.filesyncPollIntervalId) {
                    clearInterval(this.filesyncPollIntervalId);
                    this.filesyncPollIntervalId = null;
                }

                updateStepLabel(self, 2, 'step_generating_structure');

                const structureArgs = {
                    sesskey: M.cfg.sesskey
                };
                structureArgs[KEY_JOB_ID] = self.getJobId();
                Ajax.call([{
                    methodname: 'block_dixeo_designer_submit_structure_job',
                    args: structureArgs,
                }])[0]
                .then(function() {
                    if (runId !== self.generationRunId) {
                        return null;
                    }
                    self.structureSubmitDone = true;
                    self.setProgress(20, true);
                    if (typeof startStep2Fake === 'function') {
                        startStep2Fake();
                    }
                    self.pollStructureCompletion(createcourse, runId);
                    return null;
                })
                .catch(function(err) {
                    if (runId !== self.generationRunId) {
                        return null;
                    }
                    self.clearAllProgressPolls();
                    self.resetProgress();
                    return alertErrorWithString(err, 'designer_error_structure_start_failed');
                });
            },

            pollStructureCompletion: function(createcourse, runId) {
                const self = this;

                const poll = function() {
                    if (runId !== self.generationRunId) {
                        return;
                    }
                    let afterStructure = null;
                    const statusArgs = {
                        sesskey: M.cfg.sesskey
                    };
                    statusArgs[KEY_JOB_ID] = self.getJobId();
                    Ajax.call([{
                        methodname: 'block_dixeo_designer_get_structure_status',
                        args: statusArgs,
                    }])[0]
                    .then(function(data) {
                        if (runId !== self.generationRunId) {
                            return null;
                        }
                        if (data.failed) {
                            self.clearAllProgressPolls();
                            self.resetProgress();
                            return alertErrorWithString(
                                {message: data.error},
                                'designer_error_generation_failed_inline'
                            );
                        }

                        if (!data.completed) {
                            return null;
                        }

                        self.clearAllProgressPolls();
                        self.setProgress(40);
                        afterStructure = {
                            structureJson: (typeof data.result === 'string')
                                ? data.result
                                : JSON.stringify(data.result || {}),
                            createcourse: createcourse,
                            runId: runId
                        };
                        return delay(createcourse ? 500 : 1000);
                    })
                    .then(function() {
                        if (!afterStructure) {
                            return null;
                        }
                        const pending = afterStructure;
                        afterStructure = null;
                        if (pending.runId !== self.generationRunId) {
                            return null;
                        }
                        if (pending.createcourse) {
                            return finalizeAfterStructure(self, pending.structureJson, pending.runId);
                        }
                        return saveStructureAndNavigate(self, pending.structureJson);
                    })
                    .catch(function(failure) {
                        if (runId !== self.generationRunId) {
                            return null;
                        }
                        self.clearAllProgressPolls();
                        self.resetProgress();
                        return alertErrorWithString(failure, 'designer_error_status_check_failed');
                    });
                };

                poll();
                this.structurePollIntervalId = setInterval(poll, 3000);
            },

            clearFinalizePoll: function() {
                contentPhaseAnimator.reset();
                if (this.finalizePollIntervalId) {
                    clearInterval(this.finalizePollIntervalId);
                    this.finalizePollIntervalId = null;
                }
            },

            pollFinalizeProgress: function(runId) {
                const self = this;
                // Defensive: avoid orphaned finalize polling loops.
                self.clearFinalizePoll();
                contentPhaseAnimator.reset();
                let pollInFlight = false;
                const poll = function() {
                    if (runId !== undefined && runId !== null && runId !== self.generationRunId) {
                        return;
                    }
                    if (pollInFlight) {
                        return;
                    }
                    pollInFlight = true;
                    const progressArgs = {
                        sesskey: M.cfg.sesskey
                    };
                    progressArgs[KEY_JOB_ID] = self.getJobId();
                    Ajax.call([{
                        methodname: 'block_dixeo_designer_get_finalize_progress',
                        args: progressArgs,
                    }])[0]
                    .then(function(data) {
                        if (runId !== undefined && runId !== null && runId !== self.generationRunId) {
                            return null;
                        }
                        if (data.phase === PHASE_GENERATING_CONTENT) {
                            const parsed = ContentPhaseProgress.parseIndexAndTotal(data);
                            if (parsed && parsed.total > 0) {
                                contentPhaseAnimator.onGeneratingContentPoll(data, function(pct, force) {
                                    self.setProgress(pct, force);
                                });
                                return updateStepLabel(self, 3, 'step_generating_content_count', {
                                    current: parsed.current,
                                    total: parsed.total
                                });
                            }
                            let total = 0;
                            let current = 0;
                            if (Number(data[KEY_MODULE_TOTAL]) > 0) {
                                total = Number(data[KEY_MODULE_TOTAL]) || 0;
                                const moduleIndex = Number(data[KEY_MODULE_INDEX]) || 0;
                                current = Math.min(total, Math.max(1, moduleIndex));
                            } else if (Number(data[KEY_SECTION_TOTAL]) > 0) {
                                total = Number(data[KEY_SECTION_TOTAL]) || 0;
                                const sectionIndex = Number(data[KEY_SECTION_INDEX]) || 0;
                                current = Math.min(total, Math.max(1, sectionIndex));
                            }
                            if (total > 0) {
                                const completed = Math.max(0, current - 1);
                                const pct = 40 + 40 * (completed / total);
                                self.setProgress(pct);
                                return updateStepLabel(self, 3, 'step_generating_content_count', {
                                    current: current,
                                    total: total
                                });
                            }
                            return null;
                        }
                        if (data.phase === PHASE_FINALIZING) {
                            contentPhaseAnimator.reset();
                            self.setProgress(80);
                            return null;
                        }
                        if (data.phase === PHASE_DONE && data.courseid) {
                            self.clearFinalizePoll();
                            self.setProgress(100);
                            self.finishProgress(data.courseid, data.coursename);
                            return null;
                        }
                        return null;
                    })
                    .catch(function() {
                        return null;
                    })
                    .then(function() {
                        pollInFlight = false;
                        return null;
                    })
                    .catch(function() {
                        return null;
                    });
                };
                poll();
                this.finalizePollIntervalId = setInterval(poll, 2000);
            },

            setFileNamesLoading: function(loading, options) {
                if (!r.filesContainer) {
                    return;
                }
                options = options || {};
                const stepText = options.stepText || 'Uploading files (0/1)';
                const mbLine = options.mbLine || '';
                const progressPct = options.progressPct;
                if (loading) {
                    r.filesContainer.classList.remove('d-none');
                    r.filesContainer.classList.add('file-names-loading');
                    let html = '<div class="file-names-loading-state">' +
                        '<div class="file-names-loading-row">' +
                        '<span class="fa fa-spinner fa-spin mr-2" aria-hidden="true"></span>' +
                        '<span class="file-names-loading-text">' + stepText + '</span></div>';
                    if (mbLine) {
                        html += '<div class="file-names-loading-row">' +
                            '<span class="file-names-loading-mb text-muted small">' + mbLine + '</span></div>';
                    }
                    if (progressPct !== undefined && progressPct >= 0) {
                        const pctRound = Math.round(progressPct);
                        const pctStyle = Math.min(100, progressPct) + '%';
                        html += '<div class="file-names-loading-row">' +
                            '<div class="file-upload-progress" role="progressbar" aria-valuemin="0" ' +
                            'aria-valuemax="100" aria-valuenow="' + pctRound + '">' +
                            '<div class="file-upload-progress-bar" style="width: ' + pctStyle + ';"></div></div></div>';
                    }
                    html += '</div>';
                    r.filesContainer.innerHTML = html;
                } else {
                    r.filesContainer.classList.remove('file-names-loading');
                }
            },

            updateFileUploadProgress: function(stepText, mbLine, progressPct) {
                if (!r.filesContainer || !r.filesContainer.classList.contains('file-names-loading')) {
                    return;
                }
                const textEl = r.filesContainer.querySelector('.file-names-loading-text');
                if (textEl) {
                    textEl.textContent = stepText;
                }
                let mbEl = r.filesContainer.querySelector('.file-names-loading-mb');
                if (mbLine !== undefined) {
                    if (!mbEl) {
                        const state = r.filesContainer.querySelector('.file-names-loading-state');
                        if (state) {
                            const mbRow = document.createElement('div');
                            mbRow.className = 'file-names-loading-row';
                            mbEl = document.createElement('span');
                            mbEl.className = 'file-names-loading-mb text-muted small';
                            mbEl.textContent = mbLine;
                            mbRow.appendChild(mbEl);
                            state.appendChild(mbRow);
                        }
                    } else {
                        mbEl.textContent = mbLine;
                    }
                }
                let barWrap = r.filesContainer.querySelector('.file-upload-progress');
                if (progressPct !== undefined && progressPct >= 0) {
                    if (!barWrap) {
                        const state = r.filesContainer.querySelector('.file-names-loading-state');
                        if (state) {
                            const row = document.createElement('div');
                            row.className = 'file-names-loading-row';
                            barWrap = document.createElement('div');
                            barWrap.className = 'file-upload-progress';
                            barWrap.setAttribute('role', 'progressbar');
                            barWrap.setAttribute('aria-valuemin', '0');
                            barWrap.setAttribute('aria-valuemax', '100');
                            barWrap.innerHTML = '<div class="file-upload-progress-bar"></div>';
                            row.appendChild(barWrap);
                            state.appendChild(row);
                        }
                    }
                    barWrap.setAttribute('aria-valuenow', Math.round(progressPct));
                    const bar = barWrap.querySelector('.file-upload-progress-bar');
                    if (bar) {
                        bar.style.width = Math.min(100, progressPct) + '%';
                    }
                }
            },

            startProgress: function() {
                const currentGenerateCourse = r.generatorForm
                    ? r.generatorForm.querySelector('#generate_course')
                    : null;
                const currentGenerateStructure = r.generatorForm
                    ? r.generatorForm.querySelector('#generate_course_structure')
                    : null;

                if (currentGenerateCourse) {
                    currentGenerateCourse.disabled = true;
                }
                if (currentGenerateStructure) {
                    currentGenerateStructure.disabled = true;
                }
                r.promptContainer.classList.replace('d-block', 'd-none');
                r.generationContainer.classList.replace('d-none', 'd-block');
                this.setProgress(0, true);
                this.setActiveStep(1);
            },

            finishProgress: async function(courseid, coursename) {
                this.setProgress(100);
                setTimeout(() => {
                    let context = {
                        courseid: courseid,
                        coursename: coursename,
                        wwwroot: Config.wwwroot
                    };

                    const freshDesignerUrl = Config.wwwroot + '/blocks/dixeo_designer/designer.php';
                    let returnTo = null;
                    let returnToJobId = null;
                    try {
                        returnTo = sessionStorage.getItem(SESSION_RETURN_TO_KEY);
                        returnToJobId = sessionStorage.getItem(SESSION_RETURN_TO_JOBID_KEY);
                    } catch (e) {
                        returnTo = null;
                        returnToJobId = null;
                    }
                    const currentJobId = String(this.getJobId() || '');
                    const hasMatchingStoredJob = returnTo && returnToJobId && returnToJobId === currentJobId;
                    const currentIsDesignerPage = window.location.pathname.indexOf('/blocks/dixeo_designer/designer.php') !== -1;
                    const returnToIsDesigner = returnTo && returnTo.indexOf('/blocks/dixeo_designer/designer.php') !== -1;

                    if (hasMatchingStoredJob) {
                        context[KEY_GENERATE_ANOTHER_URL] = returnToIsDesigner ? freshDesignerUrl : returnTo;
                    } else {
                        context[KEY_GENERATE_ANOTHER_URL] = currentIsDesignerPage ? freshDesignerUrl : (Config.wwwroot + '/my/');
                    }

                    Template.render('block_dixeo_designer/success_message', context)
                        .then((html) => {
                            r.generationContainer.parentElement.insertAdjacentHTML('beforeend', html);
                            r.generationContainer.classList.replace('d-block', 'd-none');
                            return null;
                        })
                        .catch((error) => {
                            Notification.exception(error);
                        });
                }, 3000);
            },

            resetProgress: function() {
                try {
                    document.dispatchEvent(new CustomEvent(GLOBAL_UNLOCK_UI_EVENT, {bubbles: true}));
                } catch (e) {
                    // Ignore if DOM unavailable.
                }
                this.unlockDesignerUI();
                this.clearAllProgressPolls();
                this.clearFinalizePoll();
                this.resetStepLabels();
                if (this.skipRegenerateSyncOnce) {
                    this.skipRegenerateSyncOnce = false;
                    this.enableGenerationButtons();
                } else {
                    const currentGenerateCourse = r.generatorForm
                        ? r.generatorForm.querySelector('#generate_course')
                        : null;
                    if (currentGenerateCourse) {
                        currentGenerateCourse.disabled = false;
                    }
                    this.syncRegenerateButtonState();
                }
                r.promptContainer.classList.replace('d-none', 'd-block');
                r.generationContainer.classList.replace('d-block', 'd-none');

                let successContainer = r.generatorForm.querySelector('#success_message_container');
                if (successContainer) {
                    successContainer.remove();
                }

                this.setProgress(0, true);
            },

            resetStepLabels: function() {
                const self = this;
                updateStepLabel(self, 1, 'step_uploading_files');
                updateStepLabel(self, 2, 'step_generating_structure');
                updateStepLabel(self, 3, 'step_generating_content');
                updateStepLabel(self, 4, 'step_finalizing_details');
            },

            setProgress: function(progress) {
                const force = arguments.length > 1 ? Boolean(arguments[1]) : false;
                const nextProgress = Math.min(100, Math.max(0, progress));

                if (!force && nextProgress < this.progress) {
                    return;
                }

                this.progress = nextProgress;

                const container = r.generationContainer || document.querySelector('.designer-finalize-progress');
                if (!container) {
                    return;
                }
                const progressBar = container.querySelector('.s-progress--bar');
                if (progressBar) {
                    progressBar.style.width = `${this.progress}%`;
                    progressBar.setAttribute('aria-valuenow', Math.round(this.progress));
                    if (this.progress >= 100) {
                        progressBar.classList.add('done');
                    } else {
                        progressBar.classList.remove('done');
                    }
                }

                this.updateActiveStepFromProgress();
            },

            updateActiveStepFromProgress: function() {
                const step = getActiveStepFromProgress(this.progress);
                this.setActiveStep(step);
            },

            setActiveStep: function(step) {
                const container = r.generationContainer || document.querySelector('.designer-finalize-progress');
                if (!container) {
                    return;
                }
                container.querySelectorAll('.generation-step').forEach(function(el) {
                    el.classList.remove('active');
                    if (parseInt(el.getAttribute('data-step'), 10) === step) {
                        el.classList.add('active');
                    }
                });
            },

            setStepLabel: function(step, text) {
                const container = r.generationContainer || document.querySelector('.designer-finalize-progress');
                if (!container) {
                    return;
                }
                const el = container.querySelector('.generation-step[data-step="' + step + '"]');
                if (el) {
                    el.textContent = text || '';
                }
            },
        };
    }

    return {
        createGeneratorProgress: createGeneratorProgress,
        PHASE_GENERATING_CONTENT: PHASE_GENERATING_CONTENT,
        PHASE_FINALIZING: PHASE_FINALIZING,
        PHASE_DONE: PHASE_DONE,
        MIN_INSTRUCTIONS_LEN: MIN_INSTRUCTIONS_LEN,
        SESSION_RETURN_TO_KEY: SESSION_RETURN_TO_KEY,
        SESSION_RETURN_TO_JOBID_KEY: SESSION_RETURN_TO_JOBID_KEY,
        GLOBAL_UNLOCK_UI_EVENT: GLOBAL_UNLOCK_UI_EVENT,
        ALLOW_NAVIGATION_EVENT: ALLOW_NAVIGATION_EVENT,
        STRUCTURE_FIELD_VALIDATION_EVENT: STRUCTURE_FIELD_VALIDATION_EVENT,
        getActiveStepFromProgress: getActiveStepFromProgress,
    };
});
