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
 * Course structure JSON designer (core + mixins: collapse, editing, undo, finalize, dragdrop).
 *
 * @module     block_dixeo_designer/designer
 * @package
 * @author     Josemaria Bolanos <admin@mako.digital>
 * @copyright  2026 Dixeo (contact@dixeo.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'jquery',
    'core/ajax',
    'core/notification',
    'core/str',
    'core/templates',
    'core/config',
    'block_dixeo_designer/progress',
    'block_dixeo_designer/designer_finalize',
    'block_dixeo_designer/designer_dragdrop',
    'block_dixeo_designer/designer_collapse',
    'block_dixeo_designer/designer_editing',
    'block_dixeo_designer/designer_undo'
], function(
    $,
    Ajax,
    Notification,
    Str,
    Templates,
    Config,
    DesignerProgress,
    designerFinalizeMixin,
    designerDragDropMixin,
    designerCollapseMixin,
    designerEditingMixin,
    designerUndoMixin
) {

    /**
     * Infer Moodle component from catalogue type id when the API omits component.
     *
     * @param {string} type Machine type (e.g. page, h5p_quiz)
     * @return {string} Component name e.g. mod_page
     */
    function inferModuleComponentFromType(type) {
        var t = (type || '').toString();
        if (!t) {
            return '';
        }
        if (/^h5p_/i.test(t)) {
            return 'mod_h5pactivity';
        }
        return 'mod_' + t.toLowerCase().replace(/^mod_/, '');
    }

    /**
     * Monologo URL for a module type: installed mod uses plugin monologo; otherwise block fallback pix.
     *
     * @param {string} component Moodle component e.g. mod_page
     * @param {boolean} installed When false, use designer fallback asset (avoids requests to missing plugin pix).
     * @return {string}
     */
    function getModuleMonologoUrl(component, installed) {
        var fallback = Config.wwwroot + '/blocks/dixeo_designer/pix/monologo.svg';
        if (!installed) {
            return fallback;
        }
        if (component && component.indexOf('mod_') === 0) {
            return Config.wwwroot + '/mod/' + component.substring(4) + '/pix/monologo.svg';
        }
        return fallback;
    }

    /**
     * Attach iconurl to each MODULE_TYPE_OPTIONS row (catalogue shape: value, label, component, installed).
     */
    function applyModuleTypeOptionIconurls() {
        MODULE_TYPE_OPTIONS = MODULE_TYPE_OPTIONS.map(function(row) {
            var installed = row.installed !== false;
            var component = row.component || inferModuleComponentFromType(row.value);
            return {
                value: row.value,
                label: row.label,
                component: component,
                installed: installed,
                iconurl: getModuleMonologoUrl(component, installed)
            };
        });
    }

    /**
     * Default catalogue when local_dixeo_get_module_types is unavailable (shape matches API rows).
     */
    var MODULE_TYPE_OPTIONS = [
        {value: 'page', label: 'Page', component: 'mod_page', installed: true},
        {value: 'book', label: 'Book', component: 'mod_book', installed: true},
        {value: 'label', label: 'Text and media area', component: 'mod_label', installed: true},
        {value: 'url', label: 'URL', component: 'mod_url', installed: true},
        {value: 'glossary', label: 'Glossary', component: 'mod_glossary', installed: true},
        {value: 'lesson', label: 'Lesson', component: 'mod_lesson', installed: true},
        {value: 'quiz', label: 'Quiz', component: 'mod_quiz', installed: true},
        {value: 'simplequiz', label: 'Simple quiz', component: 'mod_simplequiz', installed: true},
        {value: 'simplequiz2', label: 'MCQ', component: 'mod_simplequiz2', installed: true},
        {value: 'h5p_quiz', label: 'Quiz', component: 'mod_h5pactivity', installed: true},
        {value: 'h5p_flashcards', label: 'Flashcards', component: 'mod_h5pactivity', installed: true},
        {value: 'h5p_crossword', label: 'Crossword', component: 'mod_h5pactivity', installed: true},
        {value: 'h5p_findthewords', label: 'Find the words', component: 'mod_h5pactivity', installed: true},
        {value: 'slideshow', label: 'Slideshow', component: 'mod_slideshow', installed: true}
    ];
    applyModuleTypeOptionIconurls();

    var Designer = {
        jobid: null,
        structure: null,
        /** In-memory undo history: array of structure snapshots */
        history: [],
        /** Index into history for current state */
        historyIndex: -1,
        currentlyEditing: null,
        hasUnsavedChanges: false,
        pendingCollapseState: null,
        /** Whether inline editor/actions are locked during create-course generation. */
        designerEditingLocked: false,
        /** After renderStructure, run full validation (delete/duplicate changes paths). */
        pendingStructureRevalidation: false,
        /** Cached promise for delete-confirm strings to avoid first-click latency. */
        deleteConfirmStringsPromise: null,
        imageStatusPollIntervalId: null,
        /** @type {boolean} From get_structure: local_dixeo course image generation allowed */
        imageCanGenerate: false,
        /** @type {boolean} From get_structure: course image edit/regenerate allowed */
        imageCanEdit: false,

        /** @type {number} Draft course id for WS language context (0 if not created yet). */
        courseId: 0,

        /** @type {number} Generation bar % during create-course flow (designer_finalize mixin). */
        generationProgress: 0,
        /** @type {boolean} Prevents duplicate success handling when finalize poll overlaps. */
        finalizeProgressCompleted: false,

        /**
         * Initialize the designer
         * @param {string} jobid
         * @param {number} [courseid] Course id for module type strings (optional)
         */
        init: function(jobid, courseid) {
            this.jobid = jobid;
            this.courseId = typeof courseid === 'number' ? courseid : (parseInt(courseid, 10) || 0);
            this.showLoading();
            this.setupEventHandlers();
            this.setupFooterHandlers();
            this.preloadDeleteConfirmDialog();

            var self = this;
            document.addEventListener(DesignerProgress.GLOBAL_UNLOCK_UI_EVENT, function() {
                self.clearFinalizePoll();
                self.clearImageStatusPoll();
                self.unlockDesignerUI();
                self.setDesignerEditingLocked(false);
                $('#btn-create-course').prop('disabled', false);
                self.finalizeProgressCompleted = false;
            });
            document.addEventListener(DesignerProgress.ALLOW_NAVIGATION_EVENT, function() {
                self.hasUnsavedChanges = false;
                self.suppressBeforeUnload = true;
            });
            document.addEventListener(DesignerProgress.STRUCTURE_FIELD_VALIDATION_EVENT, function(ev) {
                var detail = ev && ev.detail ? ev.detail : {};
                if (detail.job_id !== undefined && detail.job_id !== null &&
                        String(detail.job_id) !== String(self.jobid)) {
                    return;
                }
                self.showStructureValidationErrors(detail.fielderrors || []);
            });
            this.loadModuleTypes().then(function() {
                self.loadStructure();
            }).catch(function(err) {
                Notification.exception(err);
                self.showLoading();
            });
        },

        /**
         * Preload modal dependencies and common delete-confirm strings so the first
         * delete click opens immediately instead of paying lazy-load cost.
         */
        preloadDeleteConfirmDialog: function() {
            var self = this;
            if (!this.deleteConfirmStringsPromise) {
                this.deleteConfirmStringsPromise = Str.get_strings([
                    {key: 'designer_confirm_delete', component: 'block_dixeo_designer'},
                    {key: 'designer_delete_module_confirm', component: 'block_dixeo_designer'},
                    {key: 'designer_delete_section_confirm', component: 'block_dixeo_designer'},
                    {key: 'delete', component: 'core'},
                    {key: 'cancel', component: 'core'}
                ]);
            }

            // Warm-up core modal modules used by Notification.confirm.
            try {
                require(['core/modal_factory', 'core/modal_events'], function() {
                    // No-op: this only preloads AMD chunks.
                });
            } catch (e) {
                // Ignore preload failures; regular confirm flow still works.
            }

            return this.deleteConfirmStringsPromise.catch(function() {
                // If preload fails, allow runtime fallback in deleteItem.
                self.deleteConfirmStringsPromise = null;
                return null;
            });
        },

        /**
         * Enable/disable designer editing controls while generation is running.
         *
         * @param {boolean} locked
         */
        setDesignerEditingLocked: function(locked) {
            this.designerEditingLocked = Boolean(locked);
            var isLocked = this.designerEditingLocked;

            var page = document.getElementById('page-blocks-dixeo_designer-designer');
            if (page) {
                page.classList.toggle('dixeo-designer-editing-locked', isLocked);
            }

            // If we are locking while a field is in editing mode, close it immediately.
            if (isLocked && this.currentlyEditing) {
                this.cancelEdit(this.currentlyEditing);
            }

            // Disable native drag start while locked.
            $('.section-item, .module-item').attr('draggable', isLocked ? 'false' : 'true');
            if (isLocked && typeof this.removeDropIndicators === 'function') {
                this.removeDropIndicators();
            }

            // Keep footer controls in sync (undo/redo/create). Create is already disabled by click path.
            $('#btn-undo, #btn-redo').prop('disabled', isLocked);
            if (!isLocked) {
                $('#btn-create-course').prop('disabled', false);
            }
        },

        /**
         * Validate the in-memory structure via the same rules as finalize.
         *
         * @param {string} [scopePath] When set, only validate this data-path (inline field save).
         * @return {Promise<{valid: boolean, fielderrors: Array, errors: Array}>}
         */
        validateStructureForDesigner: function(scopePath) {
            var args = {
                job_id: this.jobid,
                structure: JSON.stringify(this.structure),
                sesskey: M.cfg.sesskey
            };
            if (scopePath) {
                args.scope_path = String(scopePath);
            }
            return Ajax.call([{
                methodname: 'block_dixeo_designer_validate_structure_for_finalize',
                args: args
            }])[0];
        },

        /**
         * Re-fetch validation errors from the server and update the UI.
         *
         * @return {Promise<boolean>} True when structure is valid.
         */
        revalidateStructureAfterRender: function() {
            var self = this;
            return this.validateStructureForDesigner().then(function(resp) {
                if (resp && resp.valid) {
                    self.clearStructureValidationErrors();
                    return true;
                }
                var fielderrors = (resp && resp.fielderrors) ? resp.fielderrors : [];
                self.showStructureValidationErrors(fielderrors);
                return false;
            });
        },

        /**
         * Whether validation messages are visible (paths may be stale after index changes).
         *
         * @return {boolean}
         */
        shouldRefreshStructureValidationDisplay: function() {
            var $container = $('.course-structure-container');
            return $container.find('.dixeo-designer-field-error').length > 0 ||
                $container.find('.dixeo-designer-structure-global-errors').length > 0;
        },

        /**
         * Close inline edit and schedule a full validation refresh after the next renderStructure.
         */
        prepareStructureMutationForRender: function() {
            if (this.currentlyEditing) {
                this.cancelEdit(this.currentlyEditing);
            }
            this.pendingStructureRevalidation = this.shouldRefreshStructureValidationDisplay();
        },

        /**
         * Normalize a field error row from the validation webservice.
         *
         * @param {*} row
         * @return {{path: string, message: string}}
         */
        normalizeFieldError: function(row) {
            return {
                path: (row && row.path !== undefined && row.path !== null) ? String(row.path) : '',
                message: (row && row.message !== undefined && row.message !== null) ? String(row.message) : ''
            };
        },

        /**
         * Load module types from API; fallback to default list on error
         */
        loadModuleTypes: function() {
            return Ajax.call([{
                methodname: 'local_dixeo_get_module_types',
                args: {courseid: this.courseId || 0}
            }])[0].then(function(response) {
                if (response.success && response.types && response.types.length > 0) {
                    MODULE_TYPE_OPTIONS = response.types.map(function(t) {
                        var installed = t.installed !== false;
                        var component = t.component || inferModuleComponentFromType(t.type);
                        return {
                            value: t.type,
                            label: t.label || t.type,
                            component: component,
                            installed: installed,
                            iconurl: getModuleMonologoUrl(component, installed)
                        };
                    });
                }
            }).catch(function() {
                // Keep default MODULE_TYPE_OPTIONS; ensure icon URLs use monologo rules.
                applyModuleTypeOptionIconurls();
            });
        },

        /**
         * Show loading indicator
         */
        showLoading: function() {
            var container = $('.course-structure-container');
            Str.get_string('designer_loading', 'block_dixeo_designer').done(function(str) {
                container.html('<div id="loading-indicator" class="text-center py-5">' +
                    '<i class="fa fa-spinner fa-spin fa-3x"></i>' +
                    '<p class="mt-3">' + str + '</p>' +
                    '</div>');
            });
        },

        /**
         * Load structure from server (single latest version)
         */
        loadStructure: function() {
            var self = this;

            Ajax.call([{
                methodname: 'block_dixeo_designer_get_structure',
                args: {
                    job_id: this.jobid,
                    sesskey: M.cfg.sesskey
                },
                done: function(response) {
                    self.imageCanGenerate = !!response.image_can_generate;
                    self.imageCanEdit = !!response.image_can_edit;
                    var raw = JSON.parse(response.structure);
                    self.structure = raw.course_structure || raw;
                    self.history = [JSON.parse(JSON.stringify(self.structure))];
                    self.historyIndex = 0;
                    self.renderStructure();
                    self.updateUndoRedoButtons();
                    var st = response.image_status ? String(response.image_status) : '';
                    var shouldPoll = self.imageCanGenerate && (
                        !self.structure.image ||
                        st === 'pending' ||
                        st === 'processing'
                    );
                    if (shouldPoll) {
                        self.startImageStatusPoll();
                    }
                },
                fail: function(error) {
                    Notification.exception(error);
                }
            }]);
        },

        /**
         * Save structure to server (used only when user clicks "Create course")
         * @return {Promise}
         */
        saveStructure: function() {
            var self = this;
            this.showSavingIndicator();

            return Ajax.call([{
                methodname: 'block_dixeo_designer_save_structure',
                args: {
                    job_id: this.jobid,
                    structure: JSON.stringify(this.structure),
                    sesskey: M.cfg.sesskey
                },
                done: function() {
                    self.showSavedIndicator();
                },
                fail: function(error) {
                    Notification.exception(error);
                }
            }])[0];
        },

        /**
         * Render the structure as HTML using Mustache templates
         */
        renderStructure: function() {
            var self = this;
            var container = $('.course-structure-container');
            container.empty();

            if (!this.structure) {
                Str.get_string('designer_invalid_data', 'block_dixeo_designer').done(function(str) {
                    container.html('<div class="alert alert-danger">' + str + '</div>');
                });
                return;
            }

            // Prepare template context
            // Note: We don't escape HTML here because Mustache auto-escapes {{}} variables
            var templateContext = {
                title: this.structure.title || '',
                summary: this.structure.summary || '',
                image: this.structure.image || null,
                jobid: this.jobid,
                hasSections: this.structure.sections && this.structure.sections.length > 0,
                sections: [],
                designer_image_show_loading: Boolean(this.imageCanGenerate) && !this.structure.image,
                designer_image_allow_edit: Boolean(this.imageCanEdit) && !!this.structure.image,
                designer_show_course_image_area: Boolean(this.structure.image) || Boolean(this.imageCanGenerate)
            };

            // Process sections
            if (this.structure.sections && this.structure.sections.length > 0) {
                this.structure.sections.forEach(function(section, sectionIdx) {
                    var sectionData = {
                        index: sectionIdx,
                        number: sectionIdx + 1,
                        title: section.title || '',
                        summary: section.summary || '',
                        jobid: self.jobid,
                        hasModules: section.modules && section.modules.length > 0,
                        modules: []
                    };

                    // Process modules
                    if (section.modules && section.modules.length > 0) {
                        section.modules.forEach(function(module, moduleIdx) {
                            var moduleType = module.type || '';
                            var iconurl = self.getModuleIconUrlForModuleType(moduleType);
                            sectionData.modules.push({
                                index: moduleIdx,
                                sectionIndex: sectionIdx,
                                type: moduleType,
                                typeLabel: self.getModuleTypeLabel(moduleType),
                                title: module.title || '',
                                summary: module.summary || '',
                                instructions: module.instructions || '',
                                iconurl: iconurl,
                                jobid: self.jobid,
                                moduleTypeOptions: MODULE_TYPE_OPTIONS
                            });
                        });
                    }

                    templateContext.sections.push(sectionData);
                });
            }

            // Update hasSections after populating (in case structure was empty)
            templateContext.hasSections = templateContext.sections.length > 0;

            // Load language strings and render template
            var stringsPromise = Str.get_strings([
                {key: 'designer_edit', component: 'block_dixeo_designer'},
                {key: 'designer_duplicate', component: 'block_dixeo_designer'},
                {key: 'designer_delete', component: 'block_dixeo_designer'},
                {key: 'designer_add_section', component: 'block_dixeo_designer'},
                {key: 'designer_add_activity', component: 'block_dixeo_designer'},
                {key: 'designer_change_activity_type', component: 'block_dixeo_designer'},
                {key: 'designer_expand_all', component: 'block_dixeo_designer'},
                {key: 'designer_collapse_all', component: 'block_dixeo_designer'},
                {key: 'designer_module_summary_label', component: 'block_dixeo_designer'},
                {key: 'designer_module_instructions_label', component: 'block_dixeo_designer'},
                {key: 'designer_placeholder_course_title', component: 'block_dixeo_designer'},
                {key: 'designer_placeholder_course_summary', component: 'block_dixeo_designer'},
                {key: 'designer_placeholder_section_title', component: 'block_dixeo_designer'},
                {key: 'designer_placeholder_section_summary', component: 'block_dixeo_designer'},
                {key: 'designer_placeholder_module_title', component: 'block_dixeo_designer'},
                {key: 'designer_placeholder_module_summary', component: 'block_dixeo_designer'},
                {key: 'designer_placeholder_module_instructions', component: 'block_dixeo_designer'}
            ]);

            stringsPromise.then(function(strings) {
                templateContext.strings = {
                    edit: strings[0],
                    duplicate: strings[1],
                    delete: strings[2],
                    add_section: strings[3],
                    add_activity: strings[4],
                    change_activity_type: strings[5],
                    expand_all: strings[6],
                    collapse_all: strings[7],
                    module_summary_label: strings[8],
                    module_instructions_label: strings[9],
                    placeholder_course_title: strings[10],
                    placeholder_course_summary: strings[11],
                    placeholder_section_title: strings[12],
                    placeholder_section_summary: strings[13],
                    placeholder_module_title: strings[14],
                    placeholder_module_summary: strings[15],
                    placeholder_module_instructions: strings[16]
                };

                return Templates.render('block_dixeo_designer/course_structure', templateContext);
            }).then(function(html) {
                container.html(html);
                self.setupEventHandlersAfterRender();
                if (self.pendingStructureRevalidation) {
                    self.pendingStructureRevalidation = false;
                    self.revalidateStructureAfterRender();
                } else {
                    self.clearStructureValidationErrors();
                }
            }).catch(function(error) {
                Notification.exception(error);
                Str.get_string('designer_invalid_data', 'block_dixeo_designer').done(function(str) {
                    container.html('<div class="alert alert-danger">' + str + '</div>');
                });
            });
        },

        /**
         * Set up event handlers after rendering
         */
        setupEventHandlersAfterRender: function() {
            var self = this;

            // Set up collapse handlers
            this.setupCollapseHandlers();

            // Collapse all / Expand all (only one link visible at a time; default: Expand all)
            $('#link-expand-all').off('click').on('click', function(e) {
                e.preventDefault();
                $('.section-item').each(function() {
                    var sectionIdx = $(this).data('section-idx');
                    var sectionId = 'section-' + self.jobid + '-' + sectionIdx;
                    var collapseTarget = document.getElementById(sectionId);
                    var toggleBtn = document.querySelector('[data-target="#' + sectionId + '"]');
                    var $toggleBtn = toggleBtn ? $(toggleBtn) : $();
                    if (collapseTarget && !collapseTarget.classList.contains('show')) {
                        collapseTarget.classList.add('show');
                        $toggleBtn.find('i').first().removeClass('fa-chevron-right').addClass('fa-chevron-down');
                        $toggleBtn.attr('aria-expanded', 'true').removeClass('collapsed');
                    }
                });
                $('#link-expand-all').addClass('d-none');
                $('#link-collapse-all').removeClass('d-none');
            });
            $('#link-collapse-all').off('click').on('click', function(e) {
                e.preventDefault();
                $('.section-item').each(function() {
                    var sectionIdx = $(this).data('section-idx');
                    var sectionId = 'section-' + self.jobid + '-' + sectionIdx;
                    var collapseTarget = document.getElementById(sectionId);
                    var toggleBtn = document.querySelector('[data-target="#' + sectionId + '"]');
                    var $toggleBtn = toggleBtn ? $(toggleBtn) : $();
                    if (collapseTarget && collapseTarget.classList.contains('show')) {
                        collapseTarget.classList.remove('show');
                        $toggleBtn.find('i').first().removeClass('fa-chevron-down').addClass('fa-chevron-right');
                        $toggleBtn.attr('aria-expanded', 'false').addClass('collapsed');
                    }
                });
                $('#link-collapse-all').addClass('d-none');
                $('#link-expand-all').removeClass('d-none');
            });

            // Set up editable handlers
            this.setupEditableHandlers();

            // Set up action button handlers
            this.setupActionHandlers();

            // Set up module type select (icon → dropdown)
            this.setupModuleTypeSelectHandlers();

            // Set up drag and drop
            this.setupDragAndDrop();
            this.bindImageInteractions();

            // Restore collapse state if pending (e.g., after drag-and-drop)
            if (this.pendingCollapseState) {
                this.restoreCollapseState(this.pendingCollapseState);
                this.pendingCollapseState = null;
            }
        },

        clearImageStatusPoll: function() {
            if (this.imageStatusPollIntervalId) {
                clearInterval(this.imageStatusPollIntervalId);
                this.imageStatusPollIntervalId = null;
            }
        },

        startImageStatusPoll: function() {
            var self = this;
            this.clearImageStatusPoll();
            if (!this.imageCanGenerate) {
                this.setImageLoadingState(false);
                return;
            }

            var poll = function() {
                Ajax.call([{
                    methodname: 'block_dixeo_designer_get_image_status',
                    args: {
                        job_id: self.jobid,
                        sesskey: M.cfg.sesskey
                    }
                }])[0].then(function(resp) {
                    if (resp.image && self.structure) {
                        self.structure.image = resp.image;
                    }
                    if (resp.status === 'processing' || resp.status === 'pending') {
                        self.setImageLoadingState(true);
                        return;
                    }
                    if (resp.completed) {
                        self.setImageLoadingState(false);
                        self.clearImageStatusPoll();
                        self.renderStructure();
                        return;
                    }
                    if (resp.failed) {
                        self.setImageLoadingState(false);
                        self.clearImageStatusPoll();
                        if (resp.error) {
                            Notification.alert('', resp.error);
                        }
                    }
                }).catch(function() {
                    // Keep polling in case transient backend errors happen.
                });
            };

            poll();
            this.imageStatusPollIntervalId = setInterval(poll, 2500);
        },

        setImageLoadingState: function(active) {
            var root = document.querySelector('[data-designer-course-image-root]');
            if (!root) {
                return;
            }
            root.classList.toggle('is-loading', Boolean(active));
            var img = root.querySelector('[data-designer-course-image]');
            var placeholder = root.querySelector('[data-designer-course-image-placeholder]');
            if (placeholder) {
                // With a course image, show the placeholder only while a job is in flight (hide stale image via CSS).
                // With no image yet, keep the placeholder visible whenever we are not in a transient "loading off" tick.
                if (img) {
                    placeholder.classList.toggle('d-none', !active);
                } else {
                    placeholder.classList.remove('d-none');
                }
            }
        },

        bindImageInteractions: function() {
            var self = this;
            var imageRoot = document.querySelector('[data-designer-course-image-root]');
            if (!imageRoot) {
                return;
            }

            var image = imageRoot.querySelector('[data-designer-course-image]');
            var openRegenerate = imageRoot.querySelector('[data-designer-image-regenerate-open]');
            var previewModal = document.querySelector('[data-designer-image-preview-modal]');
            var previewImage = previewModal ? previewModal.querySelector('[data-designer-image-preview-img]') : null;
            var regenerateModal = document.querySelector('[data-designer-image-regenerate-modal]');
            var regeneratePrompt = regenerateModal
                ? regenerateModal.querySelector('[data-designer-image-regenerate-prompt]')
                : null;
            var regenerateMsg = regenerateModal
                ? regenerateModal.querySelector('[data-designer-image-regenerate-message]')
                : null;
            var regenerateLoading = regenerateModal
                ? regenerateModal.querySelector('[data-designer-image-regenerate-loading]')
                : null;
            var regenerateSubmit = regenerateModal
                ? regenerateModal.querySelector('[data-designer-image-regenerate-submit]')
                : null;

            if (image && previewModal && previewImage) {
                image.addEventListener('click', function() {
                    previewImage.src = image.src;
                    previewModal.classList.remove('d-none');
                    previewModal.setAttribute('aria-hidden', 'false');
                });
            }
            document.querySelectorAll('[data-designer-image-preview-close]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    if (!previewModal) {
                        return;
                    }
                    previewModal.classList.add('d-none');
                    previewModal.setAttribute('aria-hidden', 'true');
                });
            });

            if (openRegenerate && regenerateModal) {
                openRegenerate.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    regenerateModal.classList.remove('d-none');
                    regenerateModal.setAttribute('aria-hidden', 'false');
                    if (regeneratePrompt) {
                        regeneratePrompt.focus();
                    }
                });
            }
            document.querySelectorAll('[data-designer-image-regenerate-close]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    if (!regenerateModal) {
                        return;
                    }
                    regenerateModal.classList.add('d-none');
                    regenerateModal.setAttribute('aria-hidden', 'true');
                });
            });

            if (regenerateSubmit) {
                regenerateSubmit.addEventListener('click', function() {
                    var prompt = regeneratePrompt ? regeneratePrompt.value.trim() : '';
                    if (!prompt) {
                        Str.get_string('designer_image_generate_prompt_required', 'block_dixeo_designer')
                            .done(function(str) {
                                if (regenerateMsg) {
                                    regenerateMsg.textContent = str;
                                    regenerateMsg.classList.remove('d-none');
                                }
                            });
                        if (regeneratePrompt) {
                            regeneratePrompt.focus();
                        }
                        return;
                    }

                    if (regenerateMsg) {
                        regenerateMsg.textContent = '';
                        regenerateMsg.classList.add('d-none');
                    }
                    if (regenerateLoading) {
                        regenerateLoading.classList.remove('d-none');
                    }
                    regenerateSubmit.disabled = true;
                    if (regeneratePrompt) {
                        regeneratePrompt.disabled = true;
                    }

                    Ajax.call([{
                        methodname: 'block_dixeo_designer_start_image_edit',
                        args: {
                            job_id: self.jobid,
                            instructions: prompt,
                            sesskey: M.cfg.sesskey
                        }
                    }])[0].then(function() {
                        if (regenerateModal) {
                            regenerateModal.classList.add('d-none');
                            regenerateModal.setAttribute('aria-hidden', 'true');
                        }
                        if (regeneratePrompt) {
                            regeneratePrompt.value = '';
                            regeneratePrompt.disabled = false;
                        }
                        if (regenerateLoading) {
                            regenerateLoading.classList.add('d-none');
                        }
                        regenerateSubmit.disabled = false;
                        self.setImageLoadingState(true);
                        self.startImageStatusPoll();
                    }).catch(function(error) {
                        self.setImageLoadingState(false);
                        Str.get_string('designer_image_generate_unavailable', 'block_dixeo_designer')
                            .done(function(fallback) {
                                if (regenerateMsg) {
                                    regenerateMsg.textContent =
                                        (error && error.message) ? error.message : fallback;
                                    regenerateMsg.classList.remove('d-none');
                                }
                            });
                        if (regenerateLoading) {
                            regenerateLoading.classList.add('d-none');
                        }
                        regenerateSubmit.disabled = false;
                        if (regeneratePrompt) {
                            regeneratePrompt.disabled = false;
                        }
                    });
                });
            }
        },

        /**
         * Monologo URL for a module type (uses catalogue options and monologo URL rules).
         *
         * @param {string} type Machine type id
         * @return {string}
         */
        getModuleIconUrlForModuleType: function(type) {
            if (!type) {
                return getModuleMonologoUrl('', false);
            }
            var tl = type.toString().toLowerCase();
            var i;
            for (i = 0; i < MODULE_TYPE_OPTIONS.length; i++) {
                if (MODULE_TYPE_OPTIONS[i].value.toLowerCase() === tl) {
                    return MODULE_TYPE_OPTIONS[i].iconurl;
                }
            }
            var comp = inferModuleComponentFromType(type);
            return getModuleMonologoUrl(comp, true);
        },

        /**
         * Update the toggle button main icon (monologo, primary-tinted via CSS mask) to match a catalogue option.
         *
         * @param {JQuery} $toggle The .module-type-select-toggle element
         * @param {{iconurl: string}} opt Selected option
         */
        setModuleTypeToggleMainIcon: function($toggle, opt) {
            var $main = $toggle.find('.module-type-select-toggle-main');
            if (!$main.length || !opt || !opt.iconurl) {
                return;
            }
            var safe = String(opt.iconurl).replace(/'/g, '%27');
            $main.empty();
            $('<span>', {
                'class': 'dixeo-designer-module-type-icon dixeo-designer-module-type-icon--toggle',
                role: 'presentation',
                'aria-hidden': 'true',
                css: {
                    '--dixeo-activity-icon': 'url(\'' + safe + '\')'
                }
            }).appendTo($main);
        },

        /**
         * Return human-readable label for a module type (same as dropdown).
         * @param {string} type Module type value
         * @return {string} Human-readable label
         */
        getModuleTypeLabel: function(type) {
            if (!type) {
                return '';
            }
            var t = type.toString();
            var i;
            for (i = 0; i < MODULE_TYPE_OPTIONS.length; i++) {
                if (MODULE_TYPE_OPTIONS[i].value === t) {
                    return MODULE_TYPE_OPTIONS[i].label;
                }
            }
            var tl = t.toLowerCase();
            for (i = 0; i < MODULE_TYPE_OPTIONS.length; i++) {
                if (MODULE_TYPE_OPTIONS[i].value.toLowerCase() === tl) {
                    return MODULE_TYPE_OPTIONS[i].label;
                }
            }
            return t;
        },

        /**
         * Set up action button handlers
         */
        setupActionHandlers: function() {
            var self = this;

            // Copy button
            $('.btn-copy-item').off('click').on('click', function(e) {
                if (self.designerEditingLocked) {
                    return;
                }
                e.stopPropagation();
                self.duplicateItem($(this));
            });

            // Delete button
            $('.btn-delete-item').off('click').on('click', function(e) {
                if (self.designerEditingLocked) {
                    return;
                }
                e.stopPropagation();
                self.deleteItem($(this));
            });

            // Add section button
            $('.btn-add-section').off('click').on('click', function(e) {
                if (self.designerEditingLocked) {
                    return;
                }
                e.stopPropagation();
                var sectionIndex = parseInt($(this).data('section-index'));
                self.addSection(sectionIndex);
            });

            // Add module/activity button
            $('.btn-add-module').off('click').on('click', function(e) {
                if (self.designerEditingLocked) {
                    return;
                }
                e.stopPropagation();
                var sectionIndex = parseInt($(this).data('section-index'));
                var moduleIndex = parseInt($(this).data('module-index'));
                self.addModule(sectionIndex, moduleIndex);
            });
        },

        /**
         * Set up module type select: toggle dropdown, option select, click outside to close
         */
        setupModuleTypeSelectHandlers: function() {
            var self = this;

            /** Close all open module-type dropdowns. */
            function closeAllDropdowns() {
                $('.module-type-select-dropdown').addClass('d-none').attr('aria-hidden', 'true');
                $('.module-type-select-toggle').attr('aria-expanded', 'false');
            }

            /**
             * Open the dropdown for a given wrapper
             * @param {jQuery} $wrapper The wrapper element containing the dropdown
             */
            function openDropdown($wrapper) {
                var $dropdown = $wrapper.find('.module-type-select-dropdown');
                var $toggle = $wrapper.find('.module-type-select-toggle');
                var isOpen = !$dropdown.hasClass('d-none');

                // Close all dropdowns first
                closeAllDropdowns();

                // If this one wasn't open, open it now
                if (!isOpen) {
                    $dropdown.removeClass('d-none').attr('aria-hidden', 'false');
                    $toggle.attr('aria-expanded', 'true');
                }
            }

            // Use event delegation for dynamically added elements
            $(document).off('click', '.module-type-select-toggle').on('click', '.module-type-select-toggle', function(e) {
                if (self.designerEditingLocked) {
                    return;
                }
                e.stopPropagation();
                e.preventDefault();
                var $wrapper = $(this).closest('.module-type-select-wrapper');
                openDropdown($wrapper);
            });

            // Also open dropdown when clicking module-type div
            $(document).off('click', '.module-type').on('click', '.module-type', function(e) {
                if (self.designerEditingLocked) {
                    return;
                }
                e.stopPropagation();
                var $moduleItem = $(this).closest('.module-item');
                var $wrapper = $moduleItem.find('.module-type-select-wrapper');
                if ($wrapper.length) {
                    openDropdown($wrapper);
                }
            });

            // Use event delegation for dynamically added elements
            $(document).off('click', '.module-type-option').on('click', '.module-type-option', function(e) {
                if (self.designerEditingLocked) {
                    return;
                }
                e.stopPropagation();
                var value = $(this).data('value');
                var $wrapper = $(this).closest('.module-type-select-wrapper');
                var sectionIdx = parseInt($wrapper.data('section-index'), 10);
                var moduleIdx = parseInt($wrapper.data('module-index'), 10);

                // Find the option (use for loop for compatibility)
                var opt = null;
                var i;
                for (i = 0; i < MODULE_TYPE_OPTIONS.length; i++) {
                    if (MODULE_TYPE_OPTIONS[i].value === value) {
                        opt = MODULE_TYPE_OPTIONS[i];
                        break;
                    }
                }

                if (!opt) {
                    return;
                }

                // Update structure
                self.structure.sections[sectionIdx].modules[moduleIdx].type = value;

                // Update UI: monologo icon and type text – use human-readable label
                self.setModuleTypeToggleMainIcon($wrapper.find('.module-type-select-toggle'), opt);
                var $moduleType = $wrapper.closest('.module-item').find('.module-type');
                if ($moduleType.length) {
                    $moduleType.text(opt.label);
                }

                closeAllDropdowns();
                self.pushHistory();
                self.clearStructureFieldValidationError(
                    'sections[' + sectionIdx + '].modules[' + moduleIdx + '].type'
                );
            });

            $(document).off('click.module-type-select').on('click.module-type-select', function(e) {
                if (!$(e.target).closest('.module-type-select-wrapper').length) {
                    closeAllDropdowns();
                }
            });

            // Highlight toggle and module-type when hovering over toggle
            $(document).off('mouseenter mouseleave', '.module-type-select-toggle')
                .on('mouseenter mouseleave', '.module-type-select-toggle', function(e) {
                    var $moduleItem = $(this).closest('.module-item');
                    var $moduleType = $moduleItem.find('.module-type');
                    if (e.type === 'mouseenter') {
                        $(this).addClass('highlighted');
                        $moduleType.addClass('highlighted');
                    } else {
                        // Only remove highlight if module-type is not being hovered
                        if (!$moduleType.is(':hover')) {
                            $(this).removeClass('highlighted');
                            $moduleType.removeClass('highlighted');
                        }
                    }
                });

            // Also highlight toggle when hovering over module-type (if not already highlighted)
            $(document).off('mouseenter mouseleave', '.module-type').on('mouseenter mouseleave', '.module-type', function(e) {
                var $moduleItem = $(this).closest('.module-item');
                var $toggle = $moduleItem.find('.module-type-select-toggle');
                if (e.type === 'mouseenter') {
                    $toggle.addClass('highlighted');
                    $(this).addClass('highlighted');
                } else {
                    // Only remove highlight if toggle is not being hovered
                    if (!$toggle.is(':hover')) {
                        $toggle.removeClass('highlighted');
                        $(this).removeClass('highlighted');
                    }
                }
            });
        },

        /**
         * Add a new section
         * @param {number} index Index where to insert the section
         */
        addSection: function(index) {
            var self = this;
            // Capture collapse state before re-rendering
            var expandedSections = this.captureCollapseState();

            self.prepareStructureMutationForRender();

            if (!Array.isArray(this.structure.sections)) {
                this.structure.sections = [];
            }

            var newSection = {
                title: '',
                summary: '',
                modules: []
            };

            // Insert at the specified index
            self.structure.sections.splice(index, 0, newSection);
            self.pushHistory();

            // Store expanded state to restore after render
            self.pendingCollapseState = expandedSections;

            self.renderStructure();
        },

        /**
         * Add a new module/activity
         * @param {number} sectionIndex Index of the section
         * @param {number} moduleIndex Index where to insert the module
         */
        addModule: function(sectionIndex, moduleIndex) {
            var self = this;
            // Capture collapse state before re-rendering
            var expandedSections = this.captureCollapseState();

            // Ensure section has modules array
            if (!this.structure.sections[sectionIndex].modules) {
                this.structure.sections[sectionIndex].modules = [];
            }

            var defaultType = MODULE_TYPE_OPTIONS.length ? MODULE_TYPE_OPTIONS[0].value : 'page';
            var newModule = {
                type: defaultType,
                title: '',
                summary: '',
                instructions: ''
            };

            // Insert at the specified index
            self.structure.sections[sectionIndex].modules.splice(moduleIndex, 0, newModule);
            self.pushHistory();

            // Store expanded state to restore after render (and ensure section is expanded)
            expandedSections[sectionIndex] = true;
            self.pendingCollapseState = expandedSections;

            self.prepareStructureMutationForRender();
            self.renderStructure();
        },

        /**
         * Find a structure field node by its data-path (matches server validation paths).
         *
         * @param {string} path
         * @returns {JQuery}
         */
        findFieldElementByPath: function(path) {
            var want = String(path);
            return $('.course-structure-container [data-path]').filter(function() {
                return String($(this).attr('data-path') || '') === want;
            });
        },

        clearStructureValidationErrors: function() {
            var $container = $('.course-structure-container');
            $container.find('.dixeo-designer-field-error').remove();
            $container.find('.dixeo-designer-structure-global-errors').remove();
            $container.find('.is-invalid').removeClass('is-invalid');
        },

        clearStructureFieldValidationError: function(path) {
            if (!path) {
                return;
            }
            var $field = this.findFieldElementByPath(String(path));
            if (!$field.length) {
                return;
            }
            $field.removeClass('is-invalid');
            $field.nextAll('.dixeo-designer-field-error').remove();
        },

        /**
         * Show finalize validation messages next to matching fields (Moodle invalid-feedback style).
         *
         * @param {Array<{path: string, message: string}>} fielderrors
         */
        showStructureValidationErrors: function(fielderrors) {
            if (!fielderrors || !fielderrors.length) {
                this.clearStructureValidationErrors();
                return;
            }

            var $container = $('.course-structure-container');
            $container.find('.dixeo-designer-field-error').remove();
            $container.find('.dixeo-designer-structure-global-errors').remove();
            $container.find('.is-invalid').removeClass('is-invalid');

            var self = this;
            var globalMsgs = [];
            fielderrors.forEach(function(row) {
                var normalized = self.normalizeFieldError(row);
                var path = normalized.path;
                var msg = normalized.message;
                if (!msg) {
                    return;
                }
                if (!path) {
                    globalMsgs.push(msg);
                    return;
                }
                var $field = self.findFieldElementByPath(path);
                if (!$field.length) {
                    globalMsgs.push(msg);
                    return;
                }
                $field.addClass('is-invalid');
                var $fb = $('<div class="dixeo-designer-field-error invalid-feedback d-block"></div>').text(msg);
                var $controls = self.getEditControlsForEditable($field);
                if ($controls.length) {
                    $controls.after($fb);
                } else {
                    $field.after($fb);
                }
            });
            if (globalMsgs.length) {
                var $alert = $('<div class="alert alert-danger dixeo-designer-structure-global-errors" role="alert"></div>');
                globalMsgs.forEach(function(m) {
                    $alert.append($('<p class="mb-1"></p>').text(m));
                });
                $container.prepend($alert);
            }

            var firstInvalid = $container.find('.is-invalid').get(0);
            if (firstInvalid && typeof firstInvalid.scrollIntoView === 'function') {
                firstInvalid.scrollIntoView({block: 'nearest', behavior: 'smooth'});
            }
        },

        /**
         * Duplicate section or module
         * @param {jQuery} $button Button that was clicked
         */
        duplicateItem: function($button) {
            // Capture collapse state before re-rendering
            var expandedSections = this.captureCollapseState();

            var $sectionItem = $button.closest('.section-item');
            var $moduleItem = $button.closest('.module-item');

            var self = this;
            // Load language string for copy suffix
            Str.get_string('designer_copy_suffix', 'block_dixeo_designer').done(function(copySuffix) {
                if ($moduleItem.length > 0) {
                    // Duplicate module
                    var sectionIdx = $sectionItem.data('section-idx');
                    var moduleIdx = $moduleItem.data('module-idx');
                    var module = JSON.parse(JSON.stringify(self.structure.sections[sectionIdx].modules[moduleIdx]));
                    module.title = module.title + copySuffix;
                    self.structure.sections[sectionIdx].modules.splice(moduleIdx + 1, 0, module);
                } else if ($sectionItem.length > 0) {
                    // Duplicate section
                    var sectionIdx = $sectionItem.data('section-idx');
                    var section = JSON.parse(JSON.stringify(self.structure.sections[sectionIdx]));
                    section.title = section.title + copySuffix;
                    self.structure.sections.splice(sectionIdx + 1, 0, section);
                }
                self.pushHistory();

                // Store expanded state to restore after render
                self.pendingCollapseState = expandedSections;

                self.prepareStructureMutationForRender();
                self.renderStructure();
            });
        },

        /**
         * Delete section or module
         * @param {jQuery} $button Button that was clicked
         */
        deleteItem: function($button) {
            var self = this;
            var $sectionItem = $button.closest('.section-item');
            var $moduleItem = $button.closest('.module-item');

            var messageKey = $moduleItem.length > 0 ? 'designer_delete_module_confirm' : 'designer_delete_section_confirm';
            var stringsPromise = this.deleteConfirmStringsPromise || this.preloadDeleteConfirmDialog();
            Promise.resolve(stringsPromise).then(function(strings) {
                if (!strings || strings.length < 5) {
                    return Str.get_strings([
                        {key: 'designer_confirm_delete', component: 'block_dixeo_designer'},
                        {key: messageKey, component: 'block_dixeo_designer'},
                        {key: 'delete', component: 'core'},
                        {key: 'cancel', component: 'core'}
                    ]);
                }
                var message = messageKey === 'designer_delete_module_confirm' ? strings[1] : strings[2];
                return [strings[0], message, strings[3], strings[4]];
            }).then(function(strings) {
                Notification.confirm(
                    strings[0],
                    strings[1],
                    strings[2],
                    strings[3],
                        function() {
                            // Capture collapse state before re-rendering
                            var expandedSections = self.captureCollapseState();

                            if ($moduleItem.length > 0) {
                                // Delete module
                                var sectionIdx = $sectionItem.data('section-idx');
                                var moduleIdx = $moduleItem.data('module-idx');
                                self.structure.sections[sectionIdx].modules.splice(moduleIdx, 1);
                            } else if ($sectionItem.length > 0) {
                                // Delete section
                                var sectionIdx = $sectionItem.data('section-idx');
                                self.structure.sections.splice(sectionIdx, 1);
                            }
                            self.pushHistory();

                            // Store expanded state to restore after render
                            self.pendingCollapseState = expandedSections;

                            self.prepareStructureMutationForRender();
                            self.renderStructure();
                        }
                    );
                });
        },

        /**
         * Set up footer button handlers: Undo, Redo, Create course
         */
        setupFooterHandlers: function() {
            var self = this;

            $('#btn-undo').on('click', function() {
                self.undo();
            });

            $('#btn-redo').on('click', function() {
                self.redo();
            });

            $('#btn-create-course').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var $btn = $(this);
                $btn.prop('disabled', true);
                self.startCreateCourseProgress();
            });
        },


        /**
         * Set up event handlers
         */
        setupEventHandlers: function() {
            var self = this;

            // Cleanup on page unload
            $(window).on('beforeunload', function() {
                self.clearImageStatusPoll();
                if (self.suppressBeforeUnload) {
                    return;
                }
                if (self.hasUnsavedChanges) {
                    // Note: beforeunload message is browser-controlled, but we set it anyway
                    return self.unsavedChangesMessage || 'You have unsaved changes. Are you sure you want to leave?';
                }
            });

            // Load unsaved changes message
            Str.get_string('designer_unsaved_changes', 'block_dixeo_designer').done(function(str) {
                self.unsavedChangesMessage = str;
            });

        },


        /**
         * Show saving indicator
         */
        showSavingIndicator: function() {
            // Remove any existing indicators first
            $('.saving-indicator').remove();

            Str.get_string('designer_saving', 'block_dixeo_designer').done(function(str) {
                var $indicator = $('<div class="saving-indicator"><i class="fa fa-spinner fa-spin"></i> ' + str + '</div>');
                $('body').append($indicator);

                setTimeout(function() {
                    $indicator.remove();
                }, 3000);
            });
        },

        /**
         * Show saved indicator
         */
        showSavedIndicator: function() {
            // Remove any existing indicators first
            $('.saving-indicator').remove();

            Str.get_string('designer_saved', 'block_dixeo_designer').done(function(str) {
                var $indicator = $('<div class="saving-indicator"><i class="fa fa-check"></i> ' + str + '</div>');
                $('body').append($indicator);

                setTimeout(function() {
                    $indicator.fadeOut(function() {
                        $(this).remove();
                    });
                }, 2000);
            });
        }
    };

    $.extend(
        Designer,
        designerFinalizeMixin,
        designerDragDropMixin,
        designerCollapseMixin,
        designerEditingMixin,
        designerUndoMixin
    );

    return Designer;
});
