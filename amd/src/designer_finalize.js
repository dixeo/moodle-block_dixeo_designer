// This file is part of Moodle - http://moodle.org/
//
// @module     block_dixeo_designer/designer_finalize
// @copyright  2026 Dixeo
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

/**
 * Create-course finalize flow: polling, progress bar, UI lock, success template.
 * Merged onto {@link module:block_dixeo_designer/designer} via jQuery.extend.
 *
 * @module block_dixeo_designer/designer_finalize
 */
define([
    'jquery',
    'core/ajax',
    'core/notification',
    'core/str',
    'core/templates',
    'core/config',
    'block_dixeo_designer/progress',
    'block_dixeo_designer/content_phase_progress'
], function($, Ajax, Notification, Str, Templates, Config, DesignerProgress, ContentPhaseProgress) {
    'use strict';

    const contentPhaseAnimator = ContentPhaseProgress.createAnimator();

    return {
    finalizePollIntervalId: null,
    clearFinalizePoll: function() {
        contentPhaseAnimator.reset();
        if (this.finalizePollIntervalId) {
            clearInterval(this.finalizePollIntervalId);
            this.finalizePollIntervalId = null;
        }
    },

    /**
     * Smooth-scroll the window to the top; resolve when scrolling settles.
     * Uses scrollend when available, with a timeout fallback.
     *
     * @returns {Promise<void>}
     */
    scrollPageToTopSmooth: function() {
        return new Promise(function(resolve) {
            var y = window.scrollY || window.pageYOffset || 0;
            if (y < 2) {
                resolve();
                return;
            }
            var done = false;
            var finish = function() {
                if (done) {
                    return;
                }
                done = true;
                window.removeEventListener('scrollend', onScrollEnd);
                clearInterval(poller);
                clearTimeout(maxTimer);
                resolve();
            };
            var onScrollEnd = function() {
                finish();
            };
            window.addEventListener('scrollend', onScrollEnd, {passive: true});
            var poller = setInterval(function() {
                if ((window.scrollY || window.pageYOffset || 0) < 2) {
                    finish();
                }
            }, 40);
            var maxTimer = setTimeout(finish, 1200);
            try {
                window.scrollTo({top: 0, left: 0, behavior: 'smooth'});
            } catch (e) {
                window.scrollTo(0, 0);
                finish();
            }
        });
    },

    /**
     * If the designer block was collapsed via the toggle, expand it and wait for layout.
     *
     * @returns {Promise<void>}
     */
    ensureDesignerBlockExpanded: function() {
        return new Promise(function(resolve) {
            var blockContainer = document.querySelector(
                '.dixeo-designer-block-wrapper .block_dixeo_designer.block-container'
            );
            var toggleBtn = document.querySelector('.dixeo-designer-block-toggle');
            if (blockContainer && blockContainer.classList.contains('d-none')) {
                if (toggleBtn) {
                    toggleBtn.click();
                } else {
                    blockContainer.classList.remove('d-none');
                }
            }
            requestAnimationFrame(function() {
                requestAnimationFrame(function() {
                    setTimeout(resolve, 80);
                });
            });
        });
    },

    /**
     * @returns {Promise<string>} Resolves to "wait" or "background".
     */
    showImageFinalizeBackgroundModal: function() {
        return new Promise(function(resolve) {
            var host = document.body;
            var existing = host.querySelector('[data-designer-image-finalize-notice-modal]');

            /**
             * Wire click handlers and display the notice modal.
             *
             * @param {HTMLElement} modal
             */
            function wireAndOpen(modal) {
                var finish = function(choice) {
                    modal.classList.add('d-none');
                    modal.setAttribute('aria-hidden', 'true');
                    resolve(choice);
                };
                var waitBtn = modal.querySelector('[data-designer-image-finalize-notice-wait]');
                var goBtn = modal.querySelector('[data-designer-image-finalize-notice-background]');
                if (waitBtn) {
                    waitBtn.onclick = function() {
                        finish('wait');
                    };
                }
                if (goBtn) {
                    goBtn.onclick = function() {
                        finish('background');
                    };
                }
                modal.querySelectorAll('[data-designer-image-finalize-notice-dismiss]').forEach(function(el) {
                    el.onclick = function() {
                        finish('wait');
                    };
                });
                modal.classList.remove('d-none');
                modal.setAttribute('aria-hidden', 'false');
            }

            if (existing) {
                wireAndOpen(existing);
                return;
            }

            Templates.render('block_dixeo_designer/image_finalize_notice', {}).then(function(html) {
                host.insertAdjacentHTML('beforeend', html);
                var modal = host.querySelector('[data-designer-image-finalize-notice-modal]');
                if (!modal) {
                    resolve('background');
                    return;
                }
                wireAndOpen(modal);
            }).catch(function() {
                resolve('background');
            });
        });
    },

    /**
     * When "Create course" is clicked: scroll top smoothly, expand block if needed,
     * show progress UI, then lock with backdrop (measured after layout).
     */
    startCreateCourseProgress: function() {
        var self = this;
        var $btn = $('#btn-create-course');

        self.hasUnsavedChanges = false;
        self.suppressBeforeUnload = true;

        // Read sessionStorage "return to" state so "Generate new course"
        // redirects to the correct destination for this job.
        try {
            var storedReturnTo = sessionStorage.getItem(DesignerProgress.SESSION_RETURN_TO_KEY);
            var storedJobId = sessionStorage.getItem(DesignerProgress.SESSION_RETURN_TO_JOBID_KEY);
            var currentJobId = String(self.jobid || '');

            // Only overwrite the stored redirect if it doesn't match the current job.
            // This keeps the "return to dashboard" behavior when we navigated to
            // designer.php from the dashboard, but still fixes stale values
            // when the user starts directly on designer.php.
            if (!storedReturnTo || storedJobId !== currentJobId) {
                sessionStorage.setItem(DesignerProgress.SESSION_RETURN_TO_KEY, window.location.href);
                sessionStorage.setItem(DesignerProgress.SESSION_RETURN_TO_JOBID_KEY, currentJobId);
            }
        } catch (e) {
            // Ignore storage failures.
        }

        self.finalizeProgressCompleted = false;
        self.clearFinalizePoll();

        var generatorForm = document.getElementById('edai_course_designer_form');
        var promptContainer = generatorForm ? generatorForm.querySelector('.prompt-container') : null;
        var generationContainer = generatorForm ? generatorForm.querySelector('.generation-container') : null;

        /**
         * Run server-side finalize validation before any UI lock, save, or finalize (read-only).
         *
         * @returns {void}
         */
        function runValidateThenMaybeProceed() {
            self.clearStructureValidationErrors();
            Ajax.call([{
                methodname: 'block_dixeo_designer_validate_structure_for_finalize',
                args: {
                    job_id: self.jobid,
                    structure: JSON.stringify(self.structure),
                    sesskey: M.cfg.sesskey
                }
            }])[0].then(function(resp) {
                if (!resp || !resp.valid) {
                    var fielderrors = (resp && resp.fielderrors) ? resp.fielderrors : [];
                    if (fielderrors.length) {
                        self.showStructureValidationErrors(fielderrors);
                    }
                    var errs = (resp && resp.errors && resp.errors.length) ?
                        resp.errors :
                        ['Validation failed'];
                    var body = errs.join('\n\n');
                    var reenableBtn = function() {
                        $btn.prop('disabled', false);
                    };
                    if (!fielderrors.length) {
                        Str.get_string('designer_structure_validation_failed_title', 'block_dixeo_designer')
                            .then(function(title) {
                                Notification.alert(title, body);
                                reenableBtn();
                            })
                            .catch(function() {
                                Notification.alert('', body);
                                reenableBtn();
                            });
                    } else {
                        reenableBtn();
                    }
                    return;
                }
                maybeWarnPendingImageThenProceed();
            }).catch(function(err) {
                $btn.prop('disabled', false);
                Notification.exception(err);
            });
        }

        /**
         * Reveal generation UI, position lock backdrop, then save structure and finalize course.
         */
        function runFinalizeFlow() {
            self.setDesignerEditingLocked(true);
            if (promptContainer && generationContainer) {
                promptContainer.classList.replace('d-block', 'd-none');
                generationContainer.classList.remove('d-none');
                generationContainer.classList.add('d-block');
            }

            self.lockDesignerUI();

            var fileNamesEl = generatorForm ? generatorForm.querySelector('#file_names') : null;
            var hasFiles = Boolean(
                fileNamesEl &&
                !fileNamesEl.classList.contains('d-none') &&
                fileNamesEl.querySelector('.file-item')
            );
            if (!hasFiles) {
                Str.get_string('step_processing_prompt', 'block_dixeo_designer').then(function(label) {
                    self.setGenerationStepLabel(1, label);
                });
            }

            self.setGenerationProgress(40);
            self.updateGenerationActiveStepFromProgress();

            self.pollDesignerFinalizeProgress();

            Ajax.call([{
                methodname: 'block_dixeo_designer_save_structure',
                args: {
                    job_id: self.jobid,
                    structure: JSON.stringify(self.structure),
                    sesskey: M.cfg.sesskey
                }
            }])[0].then(function() {
                Ajax.call([{
                    methodname: 'block_dixeo_designer_finalize_course',
                    args: {
                        job_id: self.jobid,
                        createcourse: true,
                        sesskey: M.cfg.sesskey,
                        finalize_mode: 'twostep'
                    }
                }])[0].catch(function(err) {
                    self.clearFinalizePoll();
                    self.unlockDesignerUI();
                    self.setDesignerEditingLocked(false);

                    if (promptContainer && generationContainer) {
                        promptContainer.classList.replace('d-none', 'd-block');
                        generationContainer.classList.replace('d-block', 'd-none');
                    }
                    $('#btn-create-course').prop('disabled', false);
                    Notification.exception(err);
                });
            }).catch(function(err) {
                self.clearFinalizePoll();
                self.unlockDesignerUI();
                self.setDesignerEditingLocked(false);

                if (promptContainer && generationContainer) {
                    promptContainer.classList.replace('d-none', 'd-block');
                    generationContainer.classList.replace('d-block', 'd-none');
                }
                $('#btn-create-course').prop('disabled', false);
                Notification.exception(err);
            });
        }

        /**
         * Offer optional wait when course image is still generating, then start finalize flow.
         *
         * @returns {void}
         */
        function maybeWarnPendingImageThenProceed() {
            Ajax.call([{
                methodname: 'block_dixeo_designer_get_image_status',
                args: {
                    job_id: self.jobid,
                    sesskey: M.cfg.sesskey
                }
            }])[0].then(function(resp) {
                var st = resp && resp.status ? String(resp.status) : '';
                if (st === 'pending' || st === 'processing') {
                    return self.showImageFinalizeBackgroundModal().then(function(choice) {
                        if (choice === 'wait') {
                            self.setDesignerEditingLocked(false);
                            $btn.prop('disabled', false);
                            return null;
                        }
                        return 'go';
                    });
                }
                return 'go';
            }).catch(function() {
                return 'go';
            }).then(function(go) {
                if (go !== 'go') {
                    return;
                }
                self.scrollPageToTopSmooth()
                    .then(function() {
                        return self.ensureDesignerBlockExpanded();
                    })
                    .then(function() {
                        requestAnimationFrame(function() {
                            requestAnimationFrame(runFinalizeFlow);
                        });
                    });
            });
        }

        runValidateThenMaybeProceed();
    },

    pollDesignerFinalizeProgress: function() {
        var self = this;
        contentPhaseAnimator.reset();
        var pollInFlight = false;
        var poll = function() {
            if (pollInFlight) {
                return;
            }
            pollInFlight = true;
            Ajax.call([{
                methodname: 'block_dixeo_designer_get_finalize_progress',
                args: {
                    job_id: self.jobid,
                    sesskey: M.cfg.sesskey
                }
            }])[0].then(function(data) {
                if (data.phase === DesignerProgress.PHASE_GENERATING_CONTENT) {
                    var parsed = ContentPhaseProgress.parseIndexAndTotal(data);
                    if (parsed && parsed.total > 0) {
                        contentPhaseAnimator.onGeneratingContentPoll(data, function(pct) {
                            self.setGenerationProgress(pct);
                        });
                        self.updateGenerationActiveStepFromProgress();
                        Str.get_string('step_generating_content_count', 'block_dixeo_designer', {
                            current: parsed.current,
                            total: parsed.total
                        }).then(function(str) {
                            self.setGenerationStepLabel(3, str);
                        });
                    } else {
                        var total = 0;
                        var current = 0;
                        if (Number(data.module_total) > 0) {
                            total = Number(data.module_total) || 0;
                            var moduleIndex = Number(data.module_index) || 0;
                            current = Math.min(total, Math.max(1, moduleIndex));
                        } else if (Number(data.section_total) > 0) {
                            total = Number(data.section_total) || 0;
                            var sectionIndex = Number(data.section_index) || 0;
                            current = Math.min(total, Math.max(1, sectionIndex));
                        }
                        if (total > 0) {
                            var completed = Math.max(0, current - 1);
                            var pct = 40 + 40 * (completed / total);
                            self.setGenerationProgress(pct);
                            self.updateGenerationActiveStepFromProgress();
                            Str.get_string('step_generating_content_count', 'block_dixeo_designer', {
                                current: current,
                                total: total
                            }).then(function(str) {
                                self.setGenerationStepLabel(3, str);
                            });
                        }
                    }
                } else if (data.phase === DesignerProgress.PHASE_FINALIZING) {
                    contentPhaseAnimator.reset();
                    self.setGenerationProgress(80);
                    self.updateGenerationActiveStepFromProgress();
                } else if (data.phase === DesignerProgress.PHASE_DONE && data.courseid) {
                    // Avoid rendering/locking twice if multiple poll loops are active.
                    if (self.finalizeProgressCompleted) {
                        return;
                    }
                    self.finalizeProgressCompleted = true;
                    self.clearFinalizePoll();
                    self.setGenerationProgress(100);
                    self.updateGenerationActiveStepFromProgress();
                    self.finishProgress(data.courseid, data.coursename);
                    self.unlockDesignerUI();
                }
            }).catch(function() {}).then(function() {
                pollInFlight = false;
            });
        };
        poll();
        this.finalizePollIntervalId = setInterval(poll, 2000);
    },

    /**
     * Shared generation progress helpers (use the prompt block markup).
     *
     * @param {number} progress Progress percentage (0-100).
     */
    setGenerationProgress: function(progress) {
        var generatorForm = document.getElementById('edai_course_designer_form');
        var generationContainer = generatorForm ? generatorForm.querySelector('.generation-container') : null;
        if (!generationContainer) {
            return;
        }
        var p = Math.min(100, Math.max(0, progress));
        var $bar = $(generationContainer).find('.s-progress--bar');
        if ($bar.length) {
            $bar.css('width', p + '%').attr('aria-valuenow', p);
            $bar.toggleClass('done', p >= 100);
        }
        this.generationProgress = p;
    },
    updateGenerationActiveStepFromProgress: function() {
        var p = this.generationProgress ?? 0;
        var step = DesignerProgress.getActiveStepFromProgress(p);

        var generatorForm = document.getElementById('edai_course_designer_form');
        var generationContainer = generatorForm ? generatorForm.querySelector('.generation-container') : null;
        if (!generationContainer) {
            return;
        }

        $(generationContainer).find('.generation-step').removeClass('active')
            .filter('[data-step="' + step + '"]').addClass('active');
    },
    setGenerationStepLabel: function(step, text) {
        var generatorForm = document.getElementById('edai_course_designer_form');
        var generationContainer = generatorForm ? generatorForm.querySelector('.generation-container') : null;
        if (!generationContainer) {
            return;
        }
        $(generationContainer).find('.generation-step[data-step="' + step + '"]').text(text || '');
    },

    /**
     * Lock/unlock the UI with the same backdrop used by Regenerate.
     */
    designerUiLockEl: null,
    designerUiLockUpdateHandler: null,
    designerUiResizeObserver: null,
    lockDesignerUI: function() {
        if (this.designerUiLockEl) {
            return;
        }

        var wrapper = document.querySelector('.dixeo-designer-block-wrapper');
        var blockContainer = document.querySelector('.dixeo-designer-block-wrapper .block_dixeo_designer.block-container');
        var editorFooter = document.querySelector('#page-blocks-dixeo_designer-designer .editor-toolbar-footer');
        if (!wrapper || !editorFooter) {
            return;
        }

        var anchor = blockContainer || wrapper;
        var rectAnchor = anchor.getBoundingClientRect();

        var el = document.createElement('div');
        el.className = 'dixeo-designer-ui-lock-backdrop';
        el.setAttribute('aria-hidden', 'true');
        el.style.top = rectAnchor.bottom + 'px';
        document.body.appendChild(el);
        this.designerUiLockEl = el;

        var self = this;
        var updateTop = function() {
            if (!self.designerUiLockEl) {
                return;
            }
            var r = anchor.getBoundingClientRect();
            self.designerUiLockEl.style.top = r.bottom + 'px';
        };

        var ticking = false;
        this.designerUiLockUpdateHandler = function() {
            if (ticking) {
                return;
            }
            ticking = true;
            requestAnimationFrame(function() {
                ticking = false;
                updateTop();
            });
        };

        window.addEventListener('resize', this.designerUiLockUpdateHandler);
        window.addEventListener('scroll', this.designerUiLockUpdateHandler, true);

        if (typeof ResizeObserver !== 'undefined') {
            this.designerUiResizeObserver = new ResizeObserver(function() {
                updateTop();
            });
            this.designerUiResizeObserver.observe(anchor);
        }

        var burstFrames = 0;
        var burst = function() {
            updateTop();
            burstFrames++;
            if (burstFrames < 6) {
                requestAnimationFrame(burst);
            }
        };
        requestAnimationFrame(burst);
    },
    unlockDesignerUI: function() {
        if (!this.designerUiLockEl) {
            return;
        }
        if (this.designerUiResizeObserver) {
            try {
                this.designerUiResizeObserver.disconnect();
            } catch (e) {
                // Ignore observer teardown issues.
            }
            this.designerUiResizeObserver = null;
        }
        if (this.designerUiLockUpdateHandler) {
            window.removeEventListener('resize', this.designerUiLockUpdateHandler);
            window.removeEventListener('scroll', this.designerUiLockUpdateHandler, true);
        }
        this.designerUiLockEl.remove();
        this.designerUiLockEl = null;
        this.designerUiLockUpdateHandler = null;
    },

    setDesignerProgress: function(progress) {
        var p = Math.min(100, Math.max(0, progress));
        var $bar = $('#designer-finalize-progress .s-progress--bar');
        if ($bar.length) {
            $bar.css('width', p + '%').attr('aria-valuenow', p);
            $bar.toggleClass('done', p >= 100);
        }
    },
    setDesignerActiveStep: function(step) {
        $('#designer-finalize-progress .generation-step').removeClass('active')
            .filter('[data-step="' + step + '"]').addClass('active');
    },
    setDesignerStepLabel: function(step, text) {
        $('#designer-finalize-progress .generation-step[data-step="' + step + '"]').text(text || '');
    },

    /**
     * Show success message after course creation and hide editor
     * @param {number} courseid Created course id
     * @param {string} coursename Created course name
     */
    finishProgress: function(courseid, coursename) {
        var self = this;
        var generatorForm = document.getElementById('edai_course_designer_form');
        var generationContainer = generatorForm ? generatorForm.querySelector('.generation-container') : null;
        if (!generationContainer) {
            return;
        }

        var successHost = generationContainer.parentElement;
        if (!successHost) {
            return;
        }

        // Clear old success message if present.
        var existing = successHost.querySelector('#success_message_container');
        if (existing) {
            existing.remove();
        }

        // Compute redirect for "Generate new course".
        // Prefer the original page where generation was initiated (stored in sessionStorage).
        // If that original page was the designer, go to a fresh designer.php (no id).
        var returnTo = null;
        var returnToJobId = null;
        try {
            returnTo = sessionStorage.getItem(DesignerProgress.SESSION_RETURN_TO_KEY);
            returnToJobId = sessionStorage.getItem(DesignerProgress.SESSION_RETURN_TO_JOBID_KEY);
        } catch (e) {
            returnTo = null;
            returnToJobId = null;
        }

        var freshDesignerUrl = Config.wwwroot + '/blocks/dixeo_designer/designer.php';
        var currentIsDesignerPage = window.location.pathname.indexOf('/blocks/dixeo_designer/designer.php') !== -1;
        var returnToIsDesigner = returnTo && returnTo.indexOf('/blocks/dixeo_designer/designer.php') !== -1;
        var currentJobId = String(self.jobid || '');
        var hasMatchingStoredJob = returnTo && returnToJobId && returnToJobId === currentJobId;

        var generateAnotherUrl;
        if (hasMatchingStoredJob) {
            generateAnotherUrl = returnToIsDesigner ? freshDesignerUrl : returnTo;
        } else {
            generateAnotherUrl = currentIsDesignerPage ? freshDesignerUrl : (Config.wwwroot + '/my/');
        }

        var context = {
            courseid: courseid,
            coursename: coursename,
            wwwroot: Config.wwwroot,
            generate_another_url: generateAnotherUrl
        };

        Templates.render('block_dixeo_designer/success_message', context)
            .then(function(html) {
                successHost.insertAdjacentHTML('beforeend', html);
                generationContainer.classList.replace('d-block', 'd-none');
                $('.editor-toolbar-footer').addClass('d-none');

                // Prevent "unsaved changes" prompt on navigation to the next page.
                var anotherBtn = successHost.querySelector('.button_generate_another');
                if (anotherBtn) {
                    anotherBtn.addEventListener('click', function() {
                        self.hasUnsavedChanges = false;
                        self.suppressBeforeUnload = true;
                    });
                }
            })
            .catch(function(error) {
                Notification.exception(error);
            });
    }
};
});
