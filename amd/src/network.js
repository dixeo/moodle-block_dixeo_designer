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
 * Connection loss handling for the designer: readable error messages and polling that
 * survives short network interruptions (mobile connections).
 *
 * @module     block_dixeo_designer/network
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/str', 'core/notification', 'core/templates'], function(Ajax, Str, Notification, Templates) {
    'use strict';

    const COMPONENT = 'block_dixeo_designer';

    // A polling request that gets no answer within this delay counts as a connection failure.
    const REQUEST_TIMEOUT_MS = 20000;

    // Polling keeps retrying through connection failures for this long before giving up.
    const RETRY_GRACE_MS = 90000;

    // Once the finalize request is lost, give up when the server shows no progress for this long.
    const FINALIZE_START_MS = 60000;
    const FINALIZE_STALL_MS = 600000;

    // Strings and templates needed to report a failure are loaded while the connection works.
    Str.get_strings([
        'designer_error_connection',
        'designer_error_connection_finalize',
        'designer_error_finalize_failed',
        'designer_error_generation_failed_inline',
        'designer_error_save_structure_failed',
        'designer_error_status_check_failed',
        'designer_error_structure_start_failed',
        'designer_invalid_data',
        'course_generated',
        'generate_another',
        'view_course'
    ].map(key => ({key: key, component: COMPONENT}))).catch(() => null);
    if (typeof Templates.prefetchTemplates === 'function') {
        Templates.prefetchTemplates(['block_dixeo_designer/success_message']);
    }

    /**
     * Whether a failure comes from the connection rather than from the server.
     *
     * A Moodle exception carries an errorcode. A lost connection makes core/ajax reject with an
     * empty string, "timeout" or a parse error (proxy error page), and fetch with a TypeError.
     *
     * @param {*} err Rejection value
     * @returns {boolean}
     */
    const isConnectionError = function(err) {
        if (err && typeof err === 'object' && err.errorcode) {
            return false;
        }
        if (err instanceof SyntaxError || err instanceof TypeError) {
            return true;
        }
        return !err || typeof err !== 'object' || !err.message;
    };

    /**
     * Show a lang string of the plugin in an alert.
     *
     * @param {string} key
     * @returns {Promise}
     */
    const alertString = function(key) {
        return Str.get_string(key, COMPONENT).then(message => Notification.alert('', message)).catch(() => null);
    };

    /**
     * Report a failure: the server's message, the connection message, or the fallback string.
     *
     * @param {*} err Rejection value
     * @param {string} [fallbackKey] Lang string for a server failure without message
     * @returns {Promise}
     */
    const notifyError = function(err, fallbackKey) {
        if (isConnectionError(err)) {
            return alertString('designer_error_connection');
        }
        if (err.message) {
            return Promise.resolve(Notification.alert('', String(err.message)));
        }
        return alertString(fallbackKey || 'designer_error_connection');
    };

    /**
     * Call one web service with a timeout, so a request lost on the network does not hang.
     *
     * @param {object} request {methodname, args}
     * @returns {Promise}
     */
    const call = function(request) {
        return Ajax.call([request], true, true, false, REQUEST_TIMEOUT_MS)[0];
    };

    /**
     * Track consecutive connection failures of a polling loop.
     *
     * @returns {{succeeded: Function, shouldRetry: Function}}
     */
    const createRetryWindow = function() {
        let firstFailureAt = null;
        return {
            succeeded: function() {
                firstFailureAt = null;
            },
            shouldRetry: function(err) {
                if (!isConnectionError(err)) {
                    return false;
                }
                firstFailureAt = firstFailureAt || Date.now();
                return Date.now() - firstFailureAt < RETRY_GRACE_MS;
            }
        };
    };

    /**
     * Watch the finalize progress to detect a course creation that no longer advances.
     *
     * @returns {{observe: Function, stalled: Function}}
     */
    const createFinalizeWatch = function() {
        let signature = null;
        let phase = '';
        let changedAt = Date.now();
        return {
            observe: function(data) {
                const next = [data.phase, data.section_index, data.section_total,
                    data.module_index, data.module_total, data.courseid].join('|');
                if (next !== signature) {
                    signature = next;
                    phase = data.phase || '';
                    changedAt = Date.now();
                }
            },
            stalled: function() {
                return Date.now() - changedAt >= (phase ? FINALIZE_STALL_MS : FINALIZE_START_MS);
            }
        };
    };

    return {
        isConnectionError: isConnectionError,
        alertString: alertString,
        notifyError: notifyError,
        call: call,
        createRetryWindow: createRetryWindow,
        createFinalizeWatch: createFinalizeWatch
    };
});
