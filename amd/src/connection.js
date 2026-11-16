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
 * Connection messages of the course designer, bound to its component. The strings and the
 * template needed to report a failure are loaded while the connection works.
 *
 * @module     block_dixeo_designer/connection
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {forComponent} from 'local_dixeo/network';
import Prefetch from 'core/prefetch';

Prefetch.prefetchTemplates(['block_dixeo_designer/success_message']);

export const {
    component,
    connectionMessage,
    message,
    alertString,
    notifyConnection,
    notifyError,
    exception,
    handle,
} = forComponent({
    component: 'block_dixeo_designer',
    connectionKey: 'designer_error_connection',
    keys: [
        'designer_error_connection_finalize',
        'designer_error_finalize_failed',
        'designer_error_generation_failed_inline',
        'designer_error_save_structure_failed',
        'designer_error_status_check_failed',
        'designer_error_structure_start_failed',
        'designer_invalid_data',
        'designer_error_delete_failed',
        'course_generated',
        'generate_another',
        'view_course',
    ],
});
