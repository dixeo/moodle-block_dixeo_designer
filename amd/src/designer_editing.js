// This file is part of Moodle - http://moodle.org/
//
// @module     block_dixeo_designer/designer_editing
// @copyright  2026 Dixeo
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

/**
 * Inline editing for editable fields in the structure designer.
 *
 * @module block_dixeo_designer/designer_editing
 */
define([
    'jquery',
    'core/notification',
    'block_dixeo_designer/designer_text'
], function($, Notification, TextUtil) {
    'use strict';

    return {
    /**
     * Placeholder label from data-placeholder (set at render time).
     *
     * @param {jQuery} $element Editable element
     * @return {string}
     */
    getEditablePlaceholder: function($element) {
        return String($element.attr('data-placeholder') || '').trim();
    },

    /**
     * Whether the stored value for this field is empty (not the visible placeholder label).
     *
     * @param {jQuery} $element Editable element
     * @return {boolean}
     */
    isEditableEmpty: function($element) {
        if ($element.hasClass('is-showing-placeholder')) {
            return true;
        }
        return $element.text().trim() === '';
    },

    /**
     * Normalize text read from an editable for saving (never persist placeholder label).
     *
     * @param {jQuery} $element Editable element
     * @return {string}
     */
    readEditableValue: function($element) {
        var text = $element.text().trim();
        var placeholder = this.getEditablePlaceholder($element);
        if (placeholder !== '' && text === placeholder) {
            return '';
        }
        return text;
    },

    /**
     * Edit-controls div for an editable (may follow a validation error node).
     *
     * @param {jQuery} $element Editable element
     * @return {jQuery}
     */
    getEditControlsForEditable: function($element) {
        return $element.nextAll('.edit-controls').first();
    },

    /**
     * Show or hide the visible placeholder for an empty editable (UI only; not stored in JSON).
     *
     * @param {jQuery} $element Editable element
     */
    syncEditablePlaceholder: function($element) {
        var placeholder = this.getEditablePlaceholder($element);
        var empty = this.isEditableEmpty($element);

        $element.toggleClass('is-empty', empty);

        // Never inject placeholder label while the user is actively editing (input handler calls sync).
        if (empty && placeholder !== '' && !$element.hasClass('editing')) {
            $element.addClass('is-showing-placeholder');
            if ($element.text().trim() !== placeholder) {
                $element.text(placeholder);
            }
        } else if ($element.hasClass('is-showing-placeholder')) {
            $element.removeClass('is-showing-placeholder');
        }
    },

    /**
     * Apply placeholder state to all structure editables after render or bulk updates.
     */
    syncAllEditablePlaceholders: function() {
        var self = this;
        $('.course-structure-container [data-placeholder].editable').each(function() {
            self.syncEditablePlaceholder($(this));
        });
    },

    /**
     * Read the stored structure value for an editable field path.
     *
     * @param {string} path data-path value
     * @return {string}
     */
    getStructureValueForPath: function(path) {
        return String(this.getValueByPath(this.structure, path) || '').trim();
    },

    /**
     * Update editable DOM from a stored structure value (applies placeholder when empty).
     *
     * @param {jQuery} $element Editable element
     * @param {string} value Stored value
     */
    restoreEditableFromValue: function($element, value) {
        var text = String(value || '').trim();
        $element.removeClass('is-showing-placeholder');
        if (text === '') {
            $element.empty();
        } else {
            $element.text(text);
        }
        this.syncEditablePlaceholder($element);
    },

    /**
     * Update editable DOM text after save attempt (success or validation failure).
     *
     * @param {jQuery} $element Editable element
     * @param {string} value Decoded value
     */
    applyEditableSavedValue: function($element, value) {
        $element.removeClass('is-showing-placeholder');
        if (value.trim() === '') {
            $element.empty();
        } else {
            $element.text(value);
        }
    },

    /**
     * Cancel an in-progress edit and restore the value from when editing started.
     *
     * @param {jQuery} $element Editable element
     * @param {string} originalValue Value from structure at edit start
     * @param {string} path data-path value
     */
    revertEditableEdit: function($element, originalValue, path) {
        this.restoreEditableFromValue($element, originalValue);
        if (path) {
            this.clearStructureFieldValidationError(path);
        }
        this.cancelEdit($element);
        if (this.shouldRefreshStructureValidationDisplay()) {
            this.revalidateStructureAfterRender();
        }
    },

    /**
     * Select all text in a contenteditable element.
     *
     * @param {jQuery} $element Editable element
     */
    selectAllEditableContent: function($element) {
        var range = document.createRange();
        range.selectNodeContents($element[0]);
        var sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(range);
    },

    /**
     * Commit the field currently being edited (if any).
     *
     * @return {Promise<boolean>} Resolves true when saved and valid, false when validation failed.
     */
    commitCurrentEdit: function() {
        if (!this.currentlyEditing) {
            return Promise.resolve(true);
        }
        var $element = this.currentlyEditing;
        var path = $element.data('path');
        var newText = this.readEditableValue($element);
        return this.saveEdit($element, path, newText);
    },

    /**
     * Set up editable field handlers
     */
    setupEditableHandlers: function() {
        var self = this;

        this.syncAllEditablePlaceholders();

        $('.course-structure-container').off('click.dixeoFieldError', '.dixeo-designer-field-error')
            .on('click.dixeoFieldError', '.dixeo-designer-field-error', function(e) {
                if (self.designerEditingLocked) {
                    return;
                }
                var $field = $(this).prevAll('.editable').first();
                if (!$field.length) {
                    return;
                }
                e.preventDefault();
                if (self.currentlyEditing && self.currentlyEditing[0] !== $field[0]) {
                    self.cancelEdit(self.currentlyEditing);
                }
                if (!self.currentlyEditing || self.currentlyEditing[0] !== $field[0]) {
                    self.startEditing($field);
                }
            });

        $('.editable').off('click').on('click', function() {
            if (self.designerEditingLocked) {
                return;
            }
            var $target = $(this);

            if (self.currentlyEditing) {
                if (self.currentlyEditing[0] === $target[0]) {
                    return;
                }
                self.commitCurrentEdit().then(function(ok) {
                    if (ok !== false) {
                        self.startEditing($target);
                    }
                    return null;
                }).catch(Notification.exception);
                return;
            }

            self.startEditing($target);
        });
    },

    /**
     * Start editing a field
     * @param {jQuery} $element Element to edit
     */
    startEditing: function($element) {
        var self = this;
        this.currentlyEditing = $element;

        var path = $element.data('path');
        var originalValue = this.getStructureValueForPath(path);

        $element.addClass('editing');
        $element.attr('contenteditable', 'true');

        // Start from the stored value, never from the visible placeholder label.
        $element.removeClass('is-showing-placeholder');
        if (originalValue === '') {
            $element.empty();
        } else {
            $element.text(originalValue);
        }

        $element.focus();

        $element.off('input.placeholder').on('input.placeholder', function() {
            if ($element.text().trim() !== '') {
                $element.removeClass('is-showing-placeholder');
            }
        });

        if (originalValue !== '') {
            self.selectAllEditableContent($element);
        }

        var $controls = self.getEditControlsForEditable($element);
        $controls.html($('#edit-controls-template').html());
        $controls.show();

        var commitEdit = function() {
            var newText = self.readEditableValue($element);
            self.saveEdit($element, path, newText);
        };

        $controls.find('.save-edit').off('mousedown').on('mousedown', function(e) {
            e.preventDefault();
            commitEdit();
        });

        $controls.find('.cancel-edit').off('mousedown').on('mousedown', function(e) {
            e.preventDefault();
            self.revertEditableEdit($element, originalValue, path);
        });

        if (!$element.hasClass('module-summary') && !$element.hasClass('module-instructions') &&
                !$element.hasClass('course-summary')) {
            $element.off('keydown').on('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    commitEdit();
                } else if (e.key === 'Escape') {
                    self.revertEditableEdit($element, originalValue, path);
                }
            });
        }
    },

    /**
     * Save edited field after scoped server validation.
     *
     * @param {jQuery} $element Element being edited
     * @param {string} path Data path
     * @param {string} value New value
     * @return {Promise<boolean>} Resolves true when saved and valid, false when validation failed.
     */
    saveEdit: function($element, path, value) {
        var self = this;
        var decodedValue = TextUtil.decodeHtml(value);
        var snapshot = JSON.parse(JSON.stringify(this.structure));
        var $controls = this.getEditControlsForEditable($element);

        this.setValueByPath(this.structure, path, decodedValue);
        $controls.find('.save-edit, .cancel-edit').prop('disabled', true);

        return this.validateStructureForDesigner(path).then(function(resp) {
            $controls.find('.save-edit, .cancel-edit').prop('disabled', false);

            if (resp && resp.valid) {
                self.clearStructureFieldValidationError(path);
                self.pushHistory();
                self.applyEditableSavedValue($element, decodedValue);
                self.cancelEdit($element);
                self.syncEditablePlaceholder($element);
                return true;
            }

            self.structure = snapshot;
            var fielderrors = (resp && resp.fielderrors) ? resp.fielderrors : [];
            // Stay in edit mode so Save/Cancel remain visible; DOM keeps the attempted value.
            self.applyEditableSavedValue($element, decodedValue);
            self.showStructureValidationErrors(fielderrors);
            $element.focus();
            return false;
        }).catch(function(failure) {
            self.structure = snapshot;
            $controls.find('.save-edit, .cancel-edit').prop('disabled', false);
            Notification.exception(failure);
            return false;
        });
    },

    /**
     * Cancel editing
     * @param {jQuery} $element Element being edited
     */
    cancelEdit: function($element) {
        $element.removeClass('editing');
        $element.removeAttr('contenteditable');
        $element.off('input.placeholder');
        $element.off('keydown');
        this.getEditControlsForEditable($element).hide().empty();
        this.currentlyEditing = null;
    }
};
});
