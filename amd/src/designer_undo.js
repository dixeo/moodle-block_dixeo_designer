// This file is part of Moodle - http://moodle.org/
//
// @module     block_dixeo_designer/designer_undo
// @copyright  2026 Dixeo
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

/**
 * In-memory undo/redo stack and path updates for the structure designer.
 *
 * @module block_dixeo_designer/designer_undo
 */
define(['jquery'], function($) {
    'use strict';

    return {
    /**
     * Push current structure to in-memory history (after an edit) and update undo/redo
     */
    pushHistory: function() {
        this.history = this.history.slice(0, this.historyIndex + 1);
        this.history.push(JSON.parse(JSON.stringify(this.structure)));
        this.historyIndex = this.history.length - 1;
        this.hasUnsavedChanges = true;
        this.updateUndoRedoButtons();
    },

    /**
     * Set value in structure by path
     * @param {Object} obj Object to modify
     * @param {string} path Path to property
     * @param {string} value New value
     */
    setValueByPath: function(obj, path, value) {
        var parts = path.match(/([^[\].]+)|(\[\d+\])/g);
        var current = obj;

        for (var i = 0; i < parts.length - 1; i++) {
            var key = parts[i].replace(/[[\]]/g, '');
            current = current[key];
        }

        var finalKey = parts[parts.length - 1].replace(/[[\]]/g, '');
        current[finalKey] = value;
    },

    /**
     * Read value from structure by path (matches data-path on editables).
     *
     * @param {Object} obj Structure object
     * @param {string} path Path to property
     * @return {*}
     */
    getValueByPath: function(obj, path) {
        var parts = path.match(/([^[\].]+)|(\[\d+\])/g);
        var current = obj;

        for (var i = 0; i < parts.length; i++) {
            var key = parts[i].replace(/[[\]]/g, '');
            if (current === undefined || current === null) {
                return '';
            }
            current = current[key];
        }

        return current === undefined || current === null ? '' : current;
    },

    /**
     * Update undo/redo button states (in-memory history only)
     */
    updateUndoRedoButtons: function() {
        var canUndo = this.historyIndex > 0;
        var canRedo = this.history.length > 0 && this.historyIndex < this.history.length - 1;

        $('#btn-undo').prop('disabled', !canUndo);
        $('#btn-redo').prop('disabled', !canRedo);
    },

    /**
     * Undo - restore previous state from in-memory history
     */
    undo: function() {
        if (this.historyIndex <= 0) {
            return;
        }
        this.historyIndex--;
        this.structure = JSON.parse(JSON.stringify(this.history[this.historyIndex]));
        this.prepareStructureMutationForRender();
        this.renderStructure();
        this.updateUndoRedoButtons();
    },

    /**
     * Redo - restore next state from in-memory history
     */
    redo: function() {
        if (this.historyIndex >= this.history.length - 1) {
            return;
        }
        this.historyIndex++;
        this.structure = JSON.parse(JSON.stringify(this.history[this.historyIndex]));
        this.prepareStructureMutationForRender();
        this.renderStructure();
        this.updateUndoRedoButtons();
    }
};
});
