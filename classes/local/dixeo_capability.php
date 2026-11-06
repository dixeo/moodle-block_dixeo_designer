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
     * Stop when Dixeo is turned off for the designer, or the user has not accepted the current Terms of Use.
     *
     * The designer is a site-level page, so the Dixeo layer always applies. When the job
     * already has a draft course, that course must also have Dixeo turned on.
     *
     * Terms of Use gate: an entry point that starts or advances Dixeo processing, or stores
     * input that will be sent, keeps $requireconsent = true. A read that can start a course
     * image passes false. Stop, cancel, delete and pure status reads do not call this method,
     * so a user can still stop processing, check it and remove data after Dixeo is turned off
     * or acceptance is withdrawn. Acceptance is per user: the draft course is checked when it
     * exists, otherwise the site.
     *
     * @param int|null $courseid Draft course id, when one exists.
     * @param bool $requireconsent Whether the acting user must have accepted the current Terms of Use.
     * @return void
     * @throws \moodle_exception error:dixeo_disabled (local_dixeo) when Dixeo is off,
     *     error:consentrequired (local_dixeo) when the current Terms of Use are not accepted.
     */
    public static function require_designer_enabled(?int $courseid = null, bool $requireconsent = true): void {
        global $USER;

        $config = \local_dixeo\external\service_factory::get_settings_config();
        $config->require_enabled(\context_system::instance());
        $config->require_enabled_for_course($courseid ?? 0);

        if ($requireconsent) {
            (new \local_dixeo\service\consent_service())->require_accepted_for_course((int) $USER->id, $courseid ?? 0);
        }
    }

    /**
     * Stop when Dixeo is turned off for a designer job and its draft course, or the terms are not accepted.
     *
     * @param string $jobid Designer job id.
     * @param bool $requireconsent Whether the acting user must have accepted the current Terms of Use.
     * @return void
     * @throws \moodle_exception error:dixeo_disabled (local_dixeo) when Dixeo is off,
     *     error:consentrequired (local_dixeo) when the current Terms of Use are not accepted.
     */
    public static function require_designer_enabled_for_job(string $jobid, bool $requireconsent = true): void {
        $courseid = self::job_courseid($jobid);
        self::require_designer_enabled($courseid > 0 ? $courseid : null, $requireconsent);
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
        return \local_dixeo\external\service_factory::get_settings_config()->context_for_course(self::job_courseid($jobid));
    }

    /**
     * Draft course id of a designer job, read once per request.
     *
     * Only a found course id is kept: a job gets its draft course during the workflow, and a
     * later read in the same request must see it.
     *
     * @param string $jobid Designer job id.
     * @return int Course id, or 0 when the job has no draft course.
     */
    private static function job_courseid(string $jobid): int {
        global $DB;

        $cache = \cache::make_from_params(\cache_store::MODE_REQUEST, 'block_dixeo_designer', 'jobcourseid');
        $courseid = $cache->get($jobid);
        if ($courseid !== false) {
            return (int) $courseid;
        }

        $courseid = (int) $DB->get_field('block_dixeo_designer_submission', 'courseid', ['jobid' => $jobid]);
        if ($courseid > 0) {
            $cache->set($jobid, $courseid);
        }
        return $courseid;
    }
}
