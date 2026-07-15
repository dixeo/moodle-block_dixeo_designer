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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace block_dixeo_designer;

use advanced_testcase;
use block_dixeo_designer\service\designer_service;
use block_dixeo_designer\service\designer_service_factory;
use block_dixeo_designer\service\remote\dixeo_remote_adapter;
use ReflectionClass;

/**
 * Tests for designer_service_factory.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_dixeo_designer\service\designer_service_factory
 */
final class designer_service_factory_test extends advanced_testcase {
    protected function tearDown(): void {
        designer_service_factory::reset();
        parent::tearDown();
    }

    public function test_factory_injects_test_remote_adapter(): void {
        $adapter = $this->createMock(dixeo_remote_adapter::class);
        designer_service_factory::set_test_remote_adapter($adapter);

        $service = designer_service_factory::get_designer_service();

        $ref = new ReflectionClass(designer_service::class);
        $prop = $ref->getProperty('remoteapi');
        $prop->setAccessible(true);

        $this->assertSame($adapter, $prop->getValue($service));
    }
}
