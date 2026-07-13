// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * AMD module for course designer block.
 *
 * @module     block_dixeo_designer/generator
 * @author     Josemaria Bolanos <admin@mako.digital>
 * @copyright  2025 Dixeo (contact@dixeo.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
    'core/ajax',
    'jquery',
    'core/templates',
    'core/notification',
    'core/str',
    'core/config',
    'block_dixeo_designer/progress'
], function(Ajax, $, Template, Notification, Str, Config, Progress) {
    const generatorForm = document.getElementById('edai_course_designer_form');
    const promptContainer = generatorForm.querySelector('.prompt-container');
    const promptForm = generatorForm.querySelector('#prompt-form');
    const generationContainer = generatorForm.querySelector('.generation-container');
    const courseDescription = generatorForm.querySelector('#course_description');
    const templateSelect = generatorForm.querySelector('#templateid');
    const generateCourse = generatorForm.querySelector('#generate_course');
    const generateStructure = generatorForm.querySelector('#generate_course_structure');
    const tempCourseFiles = generatorForm.querySelector('#temp_course_files');
    const filesContainer = generatorForm.querySelector('#file_names');

    return Object.assign({
        generationRunId: 0,
        /** True after block_dixeo_designer_submit_structure_job succeeds; unlocks 20% top of step 1. */
        structureSubmitDone: false,
        init: function() {
            this.progress = 0;
            this.adjustDescriptionHeight();
            this.handleDragAndDrop();
            this.bindDeleteHandlers();

            courseDescription.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' && !event.shiftKey && generateStructure) {
                    event.preventDefault();
                    generateStructure.click();
                }
            });

            if (generateCourse) {
                generateCourse.addEventListener('click', (event) => this.generateCourse(event, false));
            }
            if (generateStructure) {
                generateStructure.addEventListener('click', (event) => this.generateCourse(event, true));
            }

            // Prompt/template changes affect whether generation is allowed (prompt or files required).
            if (courseDescription) {
                courseDescription.addEventListener('input', () => {
                    this.syncGenerationInputAvailability();
                });
            }
            if (templateSelect) {
                templateSelect.addEventListener('change', () => {
                    this.syncGenerationInputAvailability();
                });
            }
            if (filesContainer) {
                const inputObserver = new MutationObserver(() => {
                    this.syncGenerationInputAvailability();
                });
                inputObserver.observe(filesContainer, {
                    subtree: true,
                    childList: true,
                    attributes: true,
                    attributeFilter: ['class']
                });
                this.generationInputFilesObserver = inputObserver;
            }

            // Regenerate fast-path UX:
            // When editing an existing job, disable the Regenerate button until the
            // prompt/template/files actually change.
            this.initRegenerateChangeTracking();

            document.querySelectorAll('.btn-cancel-draft').forEach((cancelBtn) => {
                cancelBtn.addEventListener('click', (event) => this.cancelDraft(event));
            });

            this.syncGenerationInputAvailability();

            const toggleBtn = document.querySelector('.dixeo-designer-block-toggle');
            const blockContainer = document.querySelector('.block_dixeo_designer.block-container');
            if (toggleBtn && blockContainer) {
                toggleBtn.addEventListener('click', function() {
                    const isHidden = blockContainer.classList.toggle('d-none');
                    toggleBtn.setAttribute('aria-expanded', isHidden ? 'false' : 'true');
                    toggleBtn.setAttribute('title', isHidden
                        ? toggleBtn.getAttribute('data-title-show')
                        : toggleBtn.getAttribute('data-title-hide'));
                    const icon = toggleBtn.querySelector('i.fa');
                    if (icon) {
                        icon.classList.remove('fa-chevron-up', 'fa-chevron-down');
                        icon.classList.add(isHidden ? 'fa-chevron-down' : 'fa-chevron-up');
                    }
                });
            }
        },
        skipRegenerateSyncOnce: false,
        /**
         * True when user has entered a prompt or has uploaded files (server-side list).
         * Matches server-side check in generateCourse (description or files).
         *
         * @returns {boolean}
         */
        hasMinimumGenerationInput: function() {
            const promptVal = courseDescription ? courseDescription.value.trim() : '';
            if (promptVal !== '') {
                return true;
            }
            return this.hasServerFiles();
        },
        /**
         * Disables both generation buttons when there is no prompt and no files; otherwise applies
         * regenerate (change-detection) rules for existing jobs.
         */
        syncGenerationInputAvailability: function() {
            const currentGenerateCourse = generatorForm
                ? generatorForm.querySelector('#generate_course')
                : null;
            const currentGenerateStructure = generatorForm
                ? generatorForm.querySelector('#generate_course_structure')
                : null;

            if (!this.hasMinimumGenerationInput()) {
                if (currentGenerateCourse) {
                    currentGenerateCourse.disabled = true;
                }
                if (currentGenerateStructure) {
                    currentGenerateStructure.disabled = true;
                }
                return;
            }

            if (this.regenChangeTrackingEnabled) {
                const currentSig = this.getSubmissionSignature();
                const changed = currentSig !== this.regenInitialSignature;
                if (currentGenerateStructure) {
                    currentGenerateStructure.disabled = !changed;
                }
                if (currentGenerateCourse) {
                    currentGenerateCourse.disabled = false;
                }
                return;
            }

            if (currentGenerateCourse) {
                currentGenerateCourse.disabled = false;
            }
            if (currentGenerateStructure) {
                currentGenerateStructure.disabled = false;
            }
        },
        enableGenerationButtons: function() {
            this.syncGenerationInputAvailability();
        },
        regenChangeTrackingEnabled: false,
        regenInitialSignature: null,
        initRegenerateChangeTracking: function() {
            if (!generateStructure) {
                return;
            }

            const existingJobAttr = generateStructure.dataset.existingJob;
            const isExistingJob = existingJobAttr === 'true' || existingJobAttr === '1';
            if (!isExistingJob) {
                return;
            }

            this.regenChangeTrackingEnabled = true;
            this.regenInitialSignature = this.getSubmissionSignature();

            // Disable until changes are detected (and require prompt or files via syncGenerationInputAvailability).
            this.syncGenerationInputAvailability();
        },
        getSubmissionSignature: function() {
            const promptVal = courseDescription ? courseDescription.value.trim() : '';
            const templateVal = templateSelect ? (templateSelect.value || '') : '';

            let filePart = '';
            if (filesContainer && !filesContainer.classList.contains('d-none')) {
                const items = Array.from(filesContainer.querySelectorAll('.file-item'));
                const fileIds = items.map((el) => el.dataset.fileId || '').filter(Boolean).sort();
                // Also include the displayed text to detect unusual id-less cases.
                const fileText = items.map((el) => el.textContent.trim()).sort();
                filePart = JSON.stringify({fileIds: fileIds, fileText: fileText});
            }

            // Signature must be deterministic.
            return JSON.stringify({
                prompt: promptVal,
                template: templateVal,
                files: filePart
            });
        },
        syncRegenerateButtonState: function() {
            this.syncGenerationInputAvailability();
        },
        /**
         * While cancel WS runs: block interactions on the form, show spinner + "Cancelling..." in the button, disable toggle.
         *
         * @param {boolean} active
         */
        setCancelPending: function(active) {
            const form = generatorForm;
            if (!form) {
                return;
            }

            const cancelButtons = Array.from(document.querySelectorAll('.btn-cancel-draft'));

            if (active) {
                form.classList.add('dixeo-designer-cancel-pending');
                form.setAttribute('aria-busy', 'true');

                form.querySelectorAll('button, input, select, textarea').forEach(function(el) {
                    if (!el.disabled) {
                        el.setAttribute('data-dixeo-cancel-unlock', '1');
                        el.disabled = true;
                    }
                });

                cancelButtons.forEach(function(btn) {
                    const idleEl = btn.querySelector('.btn-cancel-draft-idle');
                    const loadingEl = btn.querySelector('.btn-cancel-draft-loading');
                    if (!btn.disabled) {
                        btn.setAttribute('data-dixeo-cancel-unlock', '1');
                        btn.disabled = true;
                    }
                    if (idleEl) {
                        idleEl.classList.add('d-none');
                    }
                    if (loadingEl) {
                        loadingEl.classList.remove('d-none');
                    }
                });

                const wrapper = form.closest('.dixeo-designer-block-wrapper');
                const toggle = wrapper ? wrapper.querySelector('.dixeo-designer-block-toggle') : null;
                if (toggle && !toggle.disabled) {
                    toggle.setAttribute('data-dixeo-cancel-unlock', '1');
                    toggle.disabled = true;
                }
            } else {
                form.classList.remove('dixeo-designer-cancel-pending');
                form.removeAttribute('aria-busy');

                form.querySelectorAll('[data-dixeo-cancel-unlock="1"]').forEach(function(el) {
                    el.disabled = false;
                    el.removeAttribute('data-dixeo-cancel-unlock');
                });

                cancelButtons.forEach(function(btn) {
                    const idleEl = btn.querySelector('.btn-cancel-draft-idle');
                    const loadingEl = btn.querySelector('.btn-cancel-draft-loading');
                    if (idleEl) {
                        idleEl.classList.remove('d-none');
                    }
                    if (loadingEl) {
                        loadingEl.classList.add('d-none');
                    }
                });
            }
        },
        cancelDraft: function(event) {
            event.preventDefault();
            const self = this;
            const isFooterCancel = Boolean(
                event &&
                event.currentTarget &&
                event.currentTarget.closest &&
                event.currentTarget.closest('.editor-toolbar-footer')
            );
            const isDesignerPage = window.location.pathname.indexOf('/blocks/dixeo_designer/designer.php') !== -1;
            const isHardReset = isFooterCancel || !isDesignerPage;
            if (!generatorForm || generatorForm.classList.contains('dixeo-designer-cancel-pending')) {
                return;
            }

            // Invalidate all in-flight async callbacks from the previous run.
            self.generationRunId++;
            self.clearAllProgressPolls();
            self.setCancelPending(true);

            const finishCancel = function() {
                self.clearAllProgressPolls();
                self.skipRegenerateSyncOnce = true;
                self.setCancelPending(false);
                self.resetProgress();
            };

            // Quick generation happens outside designer.php, so a cancel must hard reset.
            var cancelErr = null;
            Ajax.call([{
                methodname: 'block_dixeo_designer_cancel_draft',
                args: {
                    sesskey: M.cfg.sesskey,
                    ['job_id']: this.getJobId(),
                    ['delete_structure']: isHardReset
                },
            }])[0]
            .then(function() {
                finishCancel();
                if (isFooterCancel) {
                    document.dispatchEvent(
                        new CustomEvent(Progress.ALLOW_NAVIGATION_EVENT, {bubbles: true})
                    );
                    window.location.href = Config.wwwroot + '/blocks/dixeo_designer/designer.php';
                }
                return null;
            })
            .catch(function(err) {
                finishCancel();
                cancelErr = err;
                return Str.get_string('designer_error_cancel_failed', 'block_dixeo_designer');
            })
            .then(function(msg) {
                if (!cancelErr) {
                    return null;
                }
                Notification.alert('', cancelErr.message || msg);
                return null;
            })
            .catch(function() {
                return null;
            });
        },
        getJobId: function() {
            return generationContainer.dataset.job_id;
        },
        hasServerFiles: function() {
            return Boolean(filesContainer && filesContainer.querySelector('.file-item'));
        },
        generateCourse: function(event, reviewStructure) {
            event.preventDefault();
            const runId = ++this.generationRunId;
            this.structureSubmitDone = false;
            // New run: always stop any stale pollers from previous attempts.
            this.clearAllProgressPolls();
            this.clearFinalizePoll();

            // Remember where the user initiated generation so "Generate new course"
            // can redirect back correctly after completion.
            try {
                sessionStorage.setItem(
                    Progress.SESSION_RETURN_TO_KEY,
                    window.location.href
                );
                // Also store the job so the designer page can decide
                // whether the redirect value is still relevant.
                sessionStorage.setItem(
                    Progress.SESSION_RETURN_TO_JOBID_KEY,
                    String(this.getJobId())
                );
            } catch (e) {
                // Ignore storage failures.
            }

            const courseDescriptionValue = courseDescription.value.trim();
            if (courseDescriptionValue === '' && !this.hasServerFiles()) {
                this.notify('invalidinput', 'descriptionorfilesrequired');
                return;
            }

            // Remote API requires instructions >= 20 characters.
            // If the user provided a non-empty description, block early in the client.
            const minInstructionLen = Progress.MIN_INSTRUCTIONS_LEN;
            if (courseDescriptionValue !== '' && courseDescriptionValue.length < minInstructionLen) {
                Str.get_string('designer_instructions_too_short', 'block_dixeo_designer', {min: minInstructionLen})
                    .then(function(msg) {
                        Notification.alert('', msg);
                        return null;
                    })
                    .catch(function() {
                        return null;
                    });
                return;
            }

            // Ensure progress UI is visible even if stale progress leaked from an old run.
            const promptVisible = promptContainer && !promptContainer.classList.contains('d-none');
            if (this.progress === 0 || promptVisible) {
                this.startProgress();
            }

            // On designer.php, regeneration runs while the editor/footer stay visible.
            // Lock the editor/footer with a backdrop so users can't click around.
            if (reviewStructure) {
                this.lockDesignerUI();
            }

            // ReviewStructure true = design only (no course), false = create full course. skip=1 means create course.
            const createcourse = !reviewStructure;

            const isLocalUploading = Boolean(
                filesContainer && filesContainer.classList.contains('file-names-loading')
            );
            if (!isLocalUploading) {
                const localFileCount = filesContainer
                    ? filesContainer.querySelectorAll('.file-item').length
                    : 0;
                if (localFileCount > 0) {
                    const self = this;
                    Str.get_string('step_preparing_files', 'block_dixeo_designer').then(function(label) {
                        self.setStepLabel(1, label);
                        return null;
                    }).catch(function() {
                        return null;
                    });
                } else {
                    // If there are no files, the first step should show we are processing only the prompt.
                    const self = this;
                    Str.get_string('step_processing_prompt', 'block_dixeo_designer').then(function(label) {
                        self.setStepLabel(1, label);
                        return null;
                    }).catch(function() {
                        return null;
                    });
                }
            }

            // 0–20%: Processing files.
            // The local file upload stage runs independently (step 1 shows x/y progress),
            // and once that stage is considered done and the backend call returns,
            // the overall progress bar advances into the 20% step-2 band.
            // Start at 0; step 1 is driven by file-sync polling in parallel with prepare_generation.
            this.setProgress(0, true);

            const startPromise = Ajax.call([{
                methodname: 'block_dixeo_designer_start_generation',
                args: {
                    description: courseDescriptionValue,
                    templateid: (templateSelect && templateSelect.value !== '') ? templateSelect.value : null,
                    sesskey: M.cfg.sesskey,
                    ['job_id']: this.getJobId()
                },
            }])[0];

            startPromise.then((startResp) => {
                if (runId !== this.generationRunId) {
                    return null;
                }
                // Regenerate no-op fast-path:
                // If backend determined prompt/template/files are identical and the
                // latest structure is already saved, reload the designer immediately
                // without polling file sync or submitting remote generation.
                if (reviewStructure && startResp && startResp.noop) {
                    this.clearAllProgressPolls();
                    this.unlockDesignerUI();
                    document.dispatchEvent(
                        new CustomEvent(Progress.ALLOW_NAVIGATION_EVENT, {bubbles: true})
                    );
                    window.location.href = Config.wwwroot + '/blocks/dixeo_designer/designer.php?id=' + this.getJobId();
                    return null;
                }
                // Poll only after prepare_generation has bound the draft course for this run.
                // Polling in parallel allowed stale course sync state to trigger an early submit.
                this.startStep2Progress(createcourse, runId);
                return null;
            })
            .catch(async error => {
                if (runId !== this.generationRunId) {
                    return null;
                }
                this.resetProgress();
                this.clearAllProgressPolls();
                const errorTitle = await Str.get_string('error_title', 'block_dixeo_designer');
                Notification.alert(errorTitle, error.message);
                return null;
            });
        },
        designerUiLockEl: null,
        designerUiLockUpdateHandler: null,
        adjustDescriptionHeight: function() {
            courseDescription.addEventListener('input', function() {
                this.style.height = 'auto';
                const maxHeight = parseFloat(getComputedStyle(this).lineHeight) * 9;
                this.style.overflowY = 'hidden';

                if (this.scrollHeight > maxHeight) {
                    this.style.height = maxHeight + 'px';
                    this.style.overflowY = 'scroll';
                } else {
                    this.style.height = this.scrollHeight + 'px';
                }
            });
            courseDescription.dispatchEvent(new Event('input'));
        },
        transferFiles: async function(newFiles) {
            if (!newFiles || newFiles.length === 0) {
                return;
            }

            const files = Array.from(newFiles);
            const totalFiles = files.length;
            const totalBytes = files.reduce((sum, f) => sum + (f.size || 0), 0);
            const totalMB = (totalBytes / (1024 * 1024)).toFixed(2);
            const self = this;

            const uploadFailedMsg = await new Promise(function(resolve, reject) {
                Str.get_string('designer_error_upload_failed', 'block_dixeo_designer').done(resolve).fail(reject);
            });

            const formatMB = function(bytes) {
                return (bytes / (1024 * 1024)).toFixed(2);
            };

            const stepStr = await new Promise(function(resolve, reject) {
                Str.get_string('step_uploading_files_count', 'block_dixeo_designer', {
                    current: 1,
                    total: totalFiles
                }).done(resolve).fail(reject);
            });
            self.setFileNamesLoading(true, {
                stepText: stepStr,
                mbLine: '0 MB / ' + totalMB + ' MB',
                progressPct: 0
            });
            self.setStepLabel(1, stepStr);

            let bytesUploaded = 0;
            let lastContext = null;

            /**
             * Upload a single file via XHR and return the response context (or null).
             * @param {File} file The file to upload
             * @param {number} fileNum 1-based file index for progress text
             * @param {number} bytesSoFar Bytes already uploaded (for progress)
             * @param {number} totalBytesVal Total bytes to upload
             * @param {number} totalFilesVal Total number of files
             * @param {string} totalMBVal Total size in MB string for display
             * @returns {Promise<object|null>} Resolves with file context from response or null
             */
            function doUploadOneFile(file, fileNum, bytesSoFar, totalBytesVal, totalFilesVal, totalMBVal) {
                return new Promise(function(resolve, reject) {
                    const formData = new FormData();
                    formData.append('sesskey', M.cfg.sesskey);
                    formData.append('jobid', self.getJobId());
                    formData.append('files[]', file);

                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', Config.wwwroot + '/blocks/dixeo_designer/ajax/upload_files.php');

                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            const totalSoFar = bytesSoFar + e.loaded;
                            const pct = totalBytesVal > 0 ? (totalSoFar / totalBytesVal) * 100 : 0;
                            const uploadedMB = formatMB(totalSoFar);
                            Str.get_string('step_uploading_files_count', 'block_dixeo_designer', {
                                current: fileNum,
                                total: totalFilesVal
                            }).then(function(stepStr) {
                                self.setStepLabel(1, stepStr);
                                self.updateFileUploadProgress(stepStr, uploadedMB + ' MB / ' + totalMBVal + ' MB', pct);
                                return null;
                            }).catch(function() {
                                return null;
                            });
                        }
                    });

                    xhr.onload = function() {
                        if (xhr.status >= 200 && xhr.status < 300) {
                            try {
                                const data = JSON.parse(xhr.responseText);
                                if (data.success && data.context) {
                                    resolve(data.context);
                                } else {
                                    resolve(null);
                                }
                            } catch (err) {
                                reject(new Error(uploadFailedMsg));
                            }
                        } else {
                            try {
                                const data = JSON.parse(xhr.responseText);
                                reject(new Error(data.message || uploadFailedMsg));
                            } catch (err) {
                                reject(new Error(uploadFailedMsg));
                            }
                        }
                    };
                    xhr.onerror = function() {
                        reject(new Error(uploadFailedMsg));
                    };
                    xhr.send(formData);
                });
            }

            try {
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    const fileNum = i + 1;
                    const context = await doUploadOneFile(file, fileNum, bytesUploaded, totalBytes, totalFiles, totalMB);
                    if (context) {
                        lastContext = context;
                    }
                    bytesUploaded += file.size || 0;
                }

                self.setFileNamesLoading(false);
                if (lastContext) {
                    self.displayFileNames(lastContext);
                }
            } catch (error) {
                self.setFileNamesLoading(false);
                filesContainer.innerHTML = '';
                filesContainer.classList.add('d-none');
                Str.get_string('designer_error_upload_failed', 'block_dixeo_designer').then(function(msg) {
                    Notification.alert('', error.message || msg);
                    return null;
                }).catch(function() {
                    return null;
                });
            } finally {
                tempCourseFiles.value = '';
            }
        },
        handleDragAndDrop: function() {
            let dragEnterCounter = 0;
            $('#prompt-form').bind({
                dragenter: function(event) {
                    event.preventDefault();
                    event.stopPropagation();
                    dragEnterCounter++;
                    promptContainer.classList.add('drag-over');
                },
                dragleave: function(event) {
                    event.preventDefault();
                    event.stopPropagation();
                    dragEnterCounter--;
                    if (dragEnterCounter === 0) {
                        promptContainer.classList.remove('drag-over');
                    }
                },
            });

            this.dropOnChildElements(promptForm);
            tempCourseFiles.addEventListener('change', () => this.transferFiles(tempCourseFiles.files));
        },
        dropOnChildElements: function(node) {
            node.childNodes.forEach(child => {
                if (child.nodeType !== Node.ELEMENT_NODE) {
                    return;
                }

                this.dropOnChildElements(child);

                child.addEventListener('dragover', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                });

                child.addEventListener('drop', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    promptContainer.classList.remove('drag-over');

                    if (event.dataTransfer.files.length > 0) {
                        this.transferFiles(event.dataTransfer.files);
                    }
                });
            });
        },
        lockDesignerUI: function() {
            if (this.designerUiLockEl) {
                return;
            }

            const wrapper = document.querySelector('.dixeo-designer-block-wrapper');
            // Use the inner block bottom (not the wrapper toggle) so the overlay
            // starts below the designer UI (progress/debug content remains visible).
            const blockContainer = document.querySelector(
                '.dixeo-designer-block-wrapper .block_dixeo_designer.block-container'
            );
            // Only lock when the fixed editor/footer exist (designer.php).
            const editorFooter = document.querySelector('#page-blocks-dixeo_designer-designer .editor-toolbar-footer');
            if (!wrapper || !editorFooter) {
                return;
            }

            const el = document.createElement('div');
            el.className = 'dixeo-designer-ui-lock-backdrop';
            el.setAttribute('aria-hidden', 'true');

            // Position the overlay so it starts below the block (so progress UI remains visible).
            const rectAnchor = blockContainer || wrapper;
            const initialTop = rectAnchor.getBoundingClientRect().bottom;
            el.style.top = initialTop + 'px';

            document.body.appendChild(el);
            this.designerUiLockEl = el;

            let ticking = false;
            const self = this;

            const updateTop = function() {
                if (!self.designerUiLockEl) {
                    return;
                }
                const rect = rectAnchor.getBoundingClientRect();
                self.designerUiLockEl.style.top = rect.bottom + 'px';
            };

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

            // Ensure the correct top is set even if layout changes immediately.
            updateTop();
        },
        unlockDesignerUI: function() {
            if (!this.designerUiLockEl) {
                return;
            }

            if (this.designerUiLockUpdateHandler) {
                window.removeEventListener('resize', this.designerUiLockUpdateHandler);
                window.removeEventListener('scroll', this.designerUiLockUpdateHandler, true);
            }

            this.designerUiLockEl.remove();
            this.designerUiLockEl = null;
            this.designerUiLockUpdateHandler = null;
        },
        displayFileNames: function(context) {
            if (filesContainer) {
                // Dispose any Bootstrap tooltips on current content to prevent stuck tooltips after DOM replace.
                $(filesContainer).find('[data-toggle="tooltip"], [data-bs-toggle="tooltip"]').tooltip('dispose');
                Template.render('block_dixeo_designer/filenames', context).then((html) => {
                    filesContainer.classList.remove('file-names-loading');
                    filesContainer.innerHTML = html;
                    if (context.hasFiles) {
                        filesContainer.classList.remove('d-none');
                    } else {
                        filesContainer.classList.add('d-none');
                    }
                    this.bindDeleteHandlers();
                    return null;
                }).catch((error) => {
                    Notification.exception(error);
                    return null;
                });
            }
        },
        bindDeleteHandlers: function() {
            if (!filesContainer) {
                return;
            }

            filesContainer.querySelectorAll('.delete-icon').forEach((deleteIcon) => {
                deleteIcon.addEventListener('click', async() => {
                    try {
                        const response = await fetch(
                            Config.wwwroot + '/blocks/dixeo_designer/ajax/delete_file.php',
                            {
                                method: 'POST',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                                body: new URLSearchParams({
                                    sesskey: M.cfg.sesskey,
                                    jobid: this.getJobId(),
                                    fileid: deleteIcon.dataset.fileId
                                })
                            }
                        );
                        const data = await response.json();
                        if (!response.ok || !data.success) {
                            const fallback = await Str.get_string('designer_error_delete_failed', 'block_dixeo_designer');
                            throw new Error(data.message || fallback);
                        }

                        this.displayFileNames(data.context);
                    } catch (error) {
                        Notification.exception(error);
                    }
                });
            });
        },
        notify: async function() {
            let strings = [];
            let component = 'block_dixeo_designer';

            for (let i = 0; i < arguments.length; i++) {
                if (Array.isArray(arguments[i])) {
                    strings.push({
                        key: arguments[i][0],
                        component: component,
                        param: arguments[i][1]
                    });
                } else if (i === 1 && arguments[i]) {
                    Notification.alert('', arguments[i]);
                    return;
                } else {
                    strings.push({
                        key: arguments[i],
                        component: component
                    });
                }
            }

            Str.get_strings(strings)
            .done((s) => {
                if (s.length > 1) {
                    Notification.alert(s[0], s[1]);
                } else {
                    Notification.alert('', s[0]);
                }
            })
            .fail(Notification.exception);
        }
    }, Progress.createGeneratorProgress({
        generationContainer: generationContainer,
        generatorForm: generatorForm,
        promptContainer: promptContainer,
        filesContainer: filesContainer
    }));
});
