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

namespace block_dixeo_designer\service;

use block_dixeo_designer\service\remote\dixeo_remote_adapter;

/**
 * Factory for designer_service (allows test double injection).
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class designer_service_factory {
    /** @var designer_service|null Test instance for unit tests. */
    private static ?designer_service $testinstance = null;

    /** @var dixeo_remote_adapter|null Test remote adapter for unit tests. */
    private static ?dixeo_remote_adapter $testremoteapi = null;

    /**
     * Get a designer_service instance.
     *
     * @return designer_service
     */
    public static function get_designer_service(): designer_service {
        if (self::$testinstance !== null) {
            return self::$testinstance;
        }
        $jobservice = \local_dixeo\external\service_factory::get_job_service();
        $filesyncservice = \local_dixeo\external\service_factory::get_file_sync_service();
        return new designer_service(null, null, null, null, self::$testremoteapi, $jobservice, $filesyncservice);
    }

    /**
     * Set a test designer_service instance (for unit tests).
     *
     * @param designer_service|null $instance
     * @return void
     */
    public static function set_test_designer_service(?designer_service $instance): void {
        self::$testinstance = $instance;
    }

    /**
     * Set a test remote adapter used when the test designer_service is not overridden.
     *
     * @param dixeo_remote_adapter|null $adapter
     * @return void
     */
    public static function set_test_remote_adapter(?dixeo_remote_adapter $adapter): void {
        self::$testremoteapi = $adapter;
    }

    /**
     * Reset to default state (call in test tearDown).
     *
     * @return void
     */
    public static function reset(): void {
        self::$testinstance = null;
        self::$testremoteapi = null;
    }
}
