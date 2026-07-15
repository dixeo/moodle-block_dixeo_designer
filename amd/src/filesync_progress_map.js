// This file is part of Moodle - http://moodle.org/
//
// @module     block_dixeo_designer/filesync_progress_map
// @copyright  2026 Dixeo
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

/**
 * Maps file-sync / pre-structure progress (0–20% bar band before submit_structure_job).
 * Moodle prepare → HTTP upload → indexing; driven by get_filesync_status payloads.
 * Pure functions only — no DOM or Moodle globals.
 *
 * Other generation phases can use sibling modules (e.g. content-phase mapping) with the same pattern.
 *
 * @module block_dixeo_designer/filesync_progress_map
 */
define([], function() {
    'use strict';

    const MOODLE_CAP = 5;
    const UPLOAD_SPAN = 10;
    const INDEXING_SPAN = 5;
    /** Top of the file-sync band (20%) before structure submit. */
    const FILESYNC_BAND_END = MOODLE_CAP + UPLOAD_SPAN + INDEXING_SPAN;
    const CAP_BEFORE_STRUCTURE_SUBMIT = 19.95;
    const UPLOAD_BAND_END = MOODLE_CAP + UPLOAD_SPAN;

    /**
     * @param {object} row AJAX row
     * @param {string} key
     * @returns {number|null}
     */
    function num(row, key) {
        const raw = row[key];
        if (raw === null || raw === undefined || raw === '') {
            return null;
        }
        const v = Number(raw);
        return Number.isFinite(v) ? v : null;
    }

    /**
     * @param {number} r
     * @returns {number}
     */
    function clamp01(r) {
        return Math.min(1, Math.max(0, r));
    }

    /**
     * @param {number} p
     * @returns {number}
     */
    function clampPct(p) {
        return Math.min(100, Math.max(0, p));
    }

    /**
     * @param {object} data
     * @returns {boolean}
     */
    function isMoodlePrepareActive(data) {
        return data.moodleprepareactive === true
            || data.moodleprepareactive === 1
            || data.moodleprepareactive === '1';
    }

    /**
     * @param {object} data
     * @param {number|null} uploadTotal
     * @param {number|null} uploadNow
     * @param {number|null} fileTotal
     * @param {boolean} noneReady
     * @returns {boolean}
     */
    function remoteSyncStarted(data, uploadTotal, uploadNow, fileTotal, noneReady) {
        if (uploadTotal > 0 && uploadNow !== null) {
            return true;
        }
        if (fileTotal !== null && fileTotal > 0) {
            return true;
        }
        const st = data.status;
        if (st === 'syncing' || st === 'synchronized') {
            return true;
        }
        return Boolean(noneReady);
    }

    /**
     * @param {number|null} pct
     * @param {boolean} remoteStarted
     * @param {string} status
     * @param {boolean} noneReady
     * @returns {number|null}
     */
    function normalizePctForMap(pct, remoteStarted, status, noneReady) {
        let p = pct;
        if (remoteStarted && p === null && (status === 'synchronized' || noneReady)) {
            p = 100;
        }
        if (noneReady && (p === null || p === 0)) {
            p = 100;
        }
        return p;
    }

    /**
     * Progress while status is none (local/upload starting).
     *
     * @param {number} cap
     * @param {number|null} uploadTotal
     * @param {number|null} uploadNow
     * @returns {number}
     */
    function targetForNoneStatus(cap, uploadTotal, uploadNow) {
        const bytesStarted = uploadTotal > 0 && uploadNow !== null && uploadNow > 0;
        if (!bytesStarted) {
            return Math.min(cap, MOODLE_CAP);
        }
        if (uploadNow < uploadTotal) {
            return Math.min(cap, MOODLE_CAP + UPLOAD_SPAN * clamp01(uploadNow / uploadTotal));
        }
        return Math.min(cap, UPLOAD_BAND_END);
    }

    /**
     * Progress while upload bytes are complete and indexing may be running.
     *
     * @param {number} cap
     * @param {number|null} fileTotal
     * @param {number|null} fileDone
     * @param {number|null} pctForMap
     * @returns {number}
     */
    function targetAfterUploadBytes(cap, fileTotal, fileDone, pctForMap) {
        if (fileTotal > 0 && fileDone !== null) {
            if (fileDone < fileTotal) {
                return Math.min(cap, UPLOAD_BAND_END + INDEXING_SPAN * clamp01(fileDone / fileTotal));
            }
            if (pctForMap !== null) {
                const slicePct = pctForMap >= 99 ? 100 : clampPct(pctForMap);
                return Math.min(cap, UPLOAD_BAND_END + INDEXING_SPAN * (slicePct / 100));
            }
        }
        return Math.min(cap, UPLOAD_BAND_END);
    }

    /**
     * Progress while status is syncing.
     *
     * @param {number} cap
     * @param {number|null} uploadTotal
     * @param {number|null} uploadNow
     * @param {number|null} fileTotal
     * @param {number|null} fileDone
     * @param {number|null} pctForMap
     * @returns {number}
     */
    function targetForSyncing(cap, uploadTotal, uploadNow, fileTotal, fileDone, pctForMap) {
        if (uploadTotal > 0 && uploadNow !== null && uploadNow < uploadTotal) {
            return Math.min(cap, MOODLE_CAP + UPLOAD_SPAN * clamp01(uploadNow / uploadTotal));
        }

        const uploadBytesDone = uploadTotal <= 0
            || (uploadNow !== null && uploadNow >= uploadTotal);

        if (uploadBytesDone) {
            return targetAfterUploadBytes(cap, fileTotal, fileDone, pctForMap);
        }

        if (fileTotal > 0 && fileDone !== null) {
            return Math.min(cap, MOODLE_CAP + UPLOAD_SPAN * clamp01(fileDone / fileTotal));
        }
        return Math.min(cap, MOODLE_CAP);
    }

    /**
     * @param {object} data get_filesync_status payload
     * @param {{ hasSubmissionFiles: boolean, structureSubmitDone: boolean }} opts
     * @returns {number}
     */
    function computeTarget(data, opts) {
        const hasFiles = opts.hasSubmissionFiles === true || opts.hasSubmissionFiles === 1;
        const structureDone = Boolean(opts.structureSubmitDone);
        const cap = structureDone ? 100 : CAP_BEFORE_STRUCTURE_SUBMIT;

        const pct = num(data, 'progresspercent');
        const fileTotal = num(data, 'filestotal');
        const fileDone = num(data, 'filescompleted');
        const uploadTotal = num(data, 'uploadbytestotal');
        const uploadNow = num(data, 'uploadbytes');
        const status = data.status;
        const lastsyncNum = Number(data.lastsynccompleted);
        const noneReady = status === 'none' && Number.isFinite(lastsyncNum) && lastsyncNum > 0;

        const remoteStarted = remoteSyncStarted(data, uploadTotal, uploadNow, fileTotal, noneReady);

        if (!hasFiles && !remoteStarted) {
            return 0;
        }

        const pctForMap = normalizePctForMap(pct, remoteStarted, status, noneReady);

        if (status === 'synchronized' || noneReady) {
            return Math.min(cap, FILESYNC_BAND_END);
        }
        if (status === 'preparing' || isMoodlePrepareActive(data)) {
            return Math.min(cap, MOODLE_CAP);
        }
        if (status === 'none' && !noneReady) {
            return targetForNoneStatus(cap, uploadTotal, uploadNow);
        }
        if (status === 'syncing') {
            return targetForSyncing(cap, uploadTotal, uploadNow, fileTotal, fileDone, pctForMap);
        }

        return Math.min(cap, MOODLE_CAP);
    }

    /**
     * Label while per-file counts are available.
     *
     * @param {object} data
     * @param {number} fileTotal
     * @param {number} fileDone
     * @returns {{ key: string, params?: object }|null}
     */
    function labelFromFileCounts(data, fileTotal, fileDone) {
        if (fileTotal === null || fileTotal <= 0 || fileDone === null) {
            return null;
        }
        let currentIndex = fileDone;
        if (data.status === 'syncing' && fileDone < fileTotal) {
            currentIndex = fileDone + 1;
        }
        if (currentIndex < 1) {
            currentIndex = 1;
        }
        return {
            key: 'step_uploading_files_count',
            params: {current: currentIndex, total: fileTotal},
        };
    }

    /**
     * Label while only byte progress is known.
     *
     * @param {object} data
     * @param {number|null} uploadTotal
     * @param {number|null} uploadNow
     * @param {number|null} fileTotal
     * @param {number|null} fileDone
     * @returns {{ key: string, params?: object }}
     */
    function labelFromUploadBytes(data, uploadTotal, uploadNow, fileTotal, fileDone) {
        if (uploadTotal > 0 && uploadNow !== null && uploadNow > 0
            && (data.status === 'syncing' || data.status === 'none')) {
            return {
                key: 'step_uploading_files_count',
                params: {current: 1, total: 1},
            };
        }

        if (data.status === 'syncing' && uploadTotal > 0 && uploadNow !== null
            && uploadNow >= uploadTotal
            && (fileTotal === null || fileTotal <= 0 || fileDone === null || fileDone < fileTotal)) {
            return {key: 'step_preparing_files'};
        }

        return {key: 'step_preparing_files'};
    }

    /**
     * @param {object} data
     * @param {boolean} hasSubmissionFiles
     * @returns {{ key: string, params?: object }}
     */
    function resolveLabel(data, hasSubmissionFiles) {
        if (!hasSubmissionFiles) {
            return {key: 'step_processing_prompt'};
        }
        if (isMoodlePrepareActive(data)) {
            return {key: 'step_preparing_files'};
        }

        const fileTotal = num(data, 'filestotal');
        const fileDone = num(data, 'filescompleted');
        const fromFiles = labelFromFileCounts(data, fileTotal, fileDone);
        if (fromFiles) {
            return fromFiles;
        }

        return labelFromUploadBytes(
            data,
            num(data, 'uploadbytestotal'),
            num(data, 'uploadbytes'),
            fileTotal,
            fileDone
        );
    }

    return {
        CAP_BEFORE_STRUCTURE_SUBMIT: CAP_BEFORE_STRUCTURE_SUBMIT,
        computeTarget: computeTarget,
        resolveLabel: resolveLabel,
    };
});
