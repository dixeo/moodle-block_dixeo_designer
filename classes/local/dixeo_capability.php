<?php
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace block_dixeo_designer\local;

/**
 * Server-side gates for designer entry points and Dixeo operations (aligns with local_dixeo externals).
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class dixeo_capability {
    /**
     * Require local/dixeo:generate in the given course (CONTEXT_COURSE capability).
     *
     * @param int $courseid
     * @return void
     */
    public static function require_generate_for_course(int $courseid): void {
        $context = \context_course::instance($courseid);
        require_capability('local/dixeo:generate', $context);
    }

    /**
     * Require Dixeo generate + ability to manage activities (module materialization).
     *
     * @param int $courseid
     * @return void
     */
    public static function require_generate_and_manage_activities(int $courseid): void {
        $context = \context_course::instance($courseid);
        require_capability('local/dixeo:generate', $context);
        require_capability('moodle/course:manageactivities', $context);
    }

    /**
     * Stop when Dixeo is turned off for the designer.
     *
     * The designer is a site-level page, so the Dixeo layer always applies. When the job
     * already has a draft course, that course must also have Dixeo turned on.
     *
     * @param int|null $courseid Draft course id, when one exists.
     * @return void
     * @throws \moodle_exception error:dixeo_disabled (local_dixeo) when Dixeo is off.
     */
    public static function require_designer_enabled(?int $courseid = null): void {
        $config = \local_dixeo\external\service_factory::get_settings_config();
        $config->require_enabled(\context_system::instance());
        if ($courseid === null || $courseid <= SITEID) {
            return;
        }
        $coursecontext = \context_course::instance($courseid, IGNORE_MISSING);
        if ($coursecontext instanceof \context_course) {
            $config->require_enabled($coursecontext);
        }
    }

    /**
     * Stop when Dixeo is turned off for a designer job and its draft course.
     *
     * @param string $jobid Designer job id.
     * @return void
     * @throws \moodle_exception error:dixeo_disabled (local_dixeo) when Dixeo is off.
     */
    public static function require_designer_enabled_for_job(string $jobid): void {
        global $DB;

        $courseid = $DB->get_field('block_dixeo_designer_submission', 'courseid', ['jobid' => $jobid]);
        self::require_designer_enabled(!empty($courseid) ? (int) $courseid : null);
    }

    /**
     * Context whose course-image policy applies to a designer job.
     *
     * The job's draft course when it exists, otherwise the Dixeo layer.
     *
     * @param string $jobid Designer job id.
     * @return \context
     */
    public static function image_policy_context_for_job(string $jobid): \context {
        global $DB;

        $courseid = (int) $DB->get_field('block_dixeo_designer_submission', 'courseid', ['jobid' => $jobid]);
        if ($courseid > SITEID) {
            $coursecontext = \context_course::instance($courseid, IGNORE_MISSING);
            if ($coursecontext instanceof \context_course) {
                return $coursecontext;
            }
        }
        return \context_system::instance();
    }
}
