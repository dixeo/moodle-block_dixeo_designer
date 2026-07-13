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

namespace block_dixeo_designer\service\submission;

/**
 * Stores and formats designer submission files.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class file_service {
    /** @var string File area for submission files. */
    public const FILEAREA = 'submissionfiles';

    /**
     * Marks course module rows for submission file resources so they can be moved after finalize.
     *
     * @var string
     */
    public const CM_IDNUMBER_DESIGNER_UPLOAD = 'dixeo_designer_upload';

    /** @var int Maximum size per file. */
    private const MAX_FILE_SIZE = 20971520;

    /** @var int Maximum total size for a submission. */
    private const MAX_TOTAL_SIZE = 52428800;

    /** @var string[] Allowed extensions. */
    private const ALLOWED_EXTENSIONS = ['pdf', 'txt', 'docx', 'pptx'];

    /**
     * Get all stored files for a submission.
     *
     * @param int $submissionid
     * @return \stored_file[]
     */
    public function get_files(int $submissionid): array {
        $fs = get_file_storage();

        return array_values(array_filter(
            $fs->get_area_files(
                \context_system::instance()->id,
                'block_dixeo_designer',
                self::FILEAREA,
                $submissionid,
                'filename',
                false
            ),
            static fn(\stored_file $file): bool => $file->get_filesize() > 0
        ));
    }

    /**
     * Build the Mustache context for the file list.
     *
     * @param int $submissionid
     * @return array
     */
    public function get_template_context(int $submissionid): array {
        return $this->build_template_context($this->get_files($submissionid));
    }

    /**
     * Store uploaded files for a submission.
     *
     * @param int $submissionid
     * @param int $userid
     * @param array $uploadedfiles
     * @return array
     */
    public function store_uploaded_files(int $submissionid, int $userid, array $uploadedfiles): array {
        $existingfiles = $this->get_files($submissionid);
        $totalsize = array_reduce($existingfiles, static function (int $carry, \stored_file $file): int {
            return $carry + $file->get_filesize();
        }, 0);

        foreach ($uploadedfiles as $upload) {
            $filename = clean_param($upload['name'] ?? '', PARAM_FILE);
            $tmpname = $upload['tmp_name'] ?? '';
            $error = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);
            $filesize = (int) ($upload['size'] ?? 0);

            if ($error !== UPLOAD_ERR_OK || $filename === '' || !is_uploaded_file($tmpname)) {
                throw new \moodle_exception('uploaderror', 'block_dixeo_designer');
            }

            $extension = \core_text::strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                throw new \moodle_exception('filetypeinvalid', 'block_dixeo_designer', '', $filename);
            }

            if ($filesize > self::MAX_FILE_SIZE) {
                throw new \moodle_exception('filetoolarge', 'block_dixeo_designer', '', $filename);
            }

            $replacement = $this->find_by_filename($existingfiles, $filename);
            if ($replacement !== null) {
                $totalsize -= $replacement->get_filesize();
            }

            $totalsize += $filesize;
            if ($totalsize > self::MAX_TOTAL_SIZE) {
                throw new \moodle_exception('totaltoolarge', 'block_dixeo_designer');
            }

            if ($replacement !== null) {
                $replacement->delete();
                $existingfiles = array_values(array_filter(
                    $existingfiles,
                    static fn(\stored_file $file): bool => (int) $file->get_id() !== (int) $replacement->get_id()
                ));
            }

            $filerecord = [
                'contextid' => \context_system::instance()->id,
                'component' => 'block_dixeo_designer',
                'filearea' => self::FILEAREA,
                'itemid' => $submissionid,
                'filepath' => '/',
                'filename' => $filename,
                'userid' => $userid,
            ];

            $storedfile = get_file_storage()->create_file_from_pathname($filerecord, $tmpname);
            $existingfiles[] = $storedfile;
        }

        return $this->build_template_context($existingfiles);
    }

    /**
     * Delete a stored file from a submission.
     *
     * @param int $submissionid
     * @param int $storedfileid
     * @return array
     */
    public function delete_file(int $submissionid, int $storedfileid): array {
        foreach ($this->get_files($submissionid) as $file) {
            if ((int) $file->get_id() === $storedfileid) {
                $file->delete();
                break;
            }
        }

        return $this->get_template_context($submissionid);
    }

    /**
     * Copy submission files into the target course as visible resources.
     *
     * @param int $submissionid
     * @param int $courseid
     * @param int $userid
     * @param \Closure|null $aftereachfile Called as ($copiedcount, $filestotal) after each file is stored (1-based count).
     * @return void
     */
    public function copy_files_to_course_resources(
        int $submissionid,
        int $courseid,
        int $userid,
        ?\Closure $aftereachfile = null
    ): void {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/course/lib.php');

        $fs = get_file_storage();
        $files = $this->get_files($submissionid);
        if (empty($files)) {
            return;
        }

        $sectionnumber = $this->ensure_resources_section($courseid);
        $total = count($files);
        $copied = 0;

        foreach ($files as $file) {
            $resource = (object) [
                'course' => $courseid,
                'name' => $file->get_filename(),
                'intro' => '',
                'introformat' => FORMAT_HTML,
                'displayoptions' => 'a:1:{s:10:"printintro";i:1;}',
                'revision' => 1,
                'timemodified' => time(),
            ];
            $resource->id = $DB->insert_record('resource', $resource);

            $moduleid = $DB->get_field('modules', 'id', ['name' => 'resource'], MUST_EXIST);
            $cm = (object) [
                'course' => $courseid,
                'module' => $moduleid,
                'instance' => $resource->id,
                'section' => $DB->get_field(
                    'course_sections',
                    'id',
                    ['course' => $courseid, 'section' => $sectionnumber],
                    MUST_EXIST
                ),
                'idnumber' => self::CM_IDNUMBER_DESIGNER_UPLOAD,
                'visible' => 1,
                'visibleoncoursepage' => 1,
            ];
            $cm->id = $DB->insert_record('course_modules', $cm);
            course_add_cm_to_section(get_course($courseid), $cm->id, $sectionnumber);

            $cmcontext = \context_module::instance($cm->id);
            $filerecord = [
                'contextid' => $cmcontext->id,
                'component' => 'mod_resource',
                'filearea' => 'content',
                'itemid' => 0,
                'filepath' => '/',
                'filename' => $file->get_filename(),
                'userid' => $userid,
            ];

            $fs->create_file_from_storedfile($filerecord, $file);
            $copied++;
            if ($aftereachfile !== null) {
                $aftereachfile($copied, $total);
            }
        }
    }

    /**
     * After structure finalization, move all designer submission file resources to a trailing
     * "Resources" section. Default target index is structure section count + 1; pass an explicit
     * target when a certificate section was inserted after content (target = count + 2).
     *
     * @param int $courseid Draft/final course id.
     * @param int $structuresectioncount Number of content sections from the generated structure (excludes resources/certificate).
     * @param int|null $targetsectionnum If set, section index for Resources; else structuresectioncount + 1.
     * @return bool True when a Resources section was created/updated and modules were moved; false if there was nothing to do.
     */
    public function relocate_designer_upload_resources_after_finalize(
        int $courseid,
        int $structuresectioncount,
        ?int $targetsectionnum = null
    ): bool {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/course/lib.php');

        $cmrecords = $DB->get_records_select(
            'course_modules',
            'course = :courseid AND idnumber = :idn',
            ['courseid' => $courseid, 'idn' => self::CM_IDNUMBER_DESIGNER_UPLOAD],
            'id ASC'
        );
        if (empty($cmrecords)) {
            return false;
        }

        if ($targetsectionnum === null) {
            $targetsectionnum = $structuresectioncount + 1;
        }
        course_create_sections_if_missing($courseid, [$targetsectionnum]);

        $section = $DB->get_record(
            'course_sections',
            ['course' => $courseid, 'section' => $targetsectionnum],
            '*',
            MUST_EXIST
        );
        $section->name = get_string('resources', 'block_dixeo_designer');
        $section->summary = '';
        $section->summaryformat = FORMAT_HTML;
        $DB->update_record('course_sections', $section);

        rebuild_course_cache($courseid, true);
        $format = course_get_format($courseid);
        $lastsection = $format->get_last_section_number();
        $needed = max($lastsection, $targetsectionnum);
        $format->update_course_format_options(['numsections' => $needed]);

        foreach ($cmrecords as $cmrec) {
            rebuild_course_cache($courseid, true);
            $modinfo = get_fast_modinfo($courseid);
            $cm = $modinfo->get_cm((int) $cmrec->id);
            $targetsectioninfo = $modinfo->get_section_info($targetsectionnum);
            moveto_module($cm, $targetsectioninfo);
        }

        rebuild_course_cache($courseid, true);

        return true;
    }

    /**
     * Build list context from stored files.
     *
     * @param \stored_file[] $files
     * @return array
     */
    private function build_template_context(array $files): array {
        $totalsize = 0;
        $items = [];

        foreach ($files as $file) {
            $totalsize += $file->get_filesize();
            $items[] = [
                'id' => (int) $file->get_id(),
                'name' => $file->get_filename(),
                'size' => display_size($file->get_filesize()),
            ];
        }

        return [
            'hasFiles' => !empty($items),
            'files' => $items,
            'totalSize' => display_size($totalsize),
            'maxTotalSize' => display_size(self::MAX_TOTAL_SIZE),
        ];
    }

    /**
     * Find an existing file by filename.
     *
     * @param \stored_file[] $files
     * @param string $filename
     * @return \stored_file|null
     */
    private function find_by_filename(array $files, string $filename): ?\stored_file {
        foreach ($files as $file) {
            if ($file->get_filename() === $filename) {
                return $file;
            }
        }

        return null;
    }

    /**
     * Ensure the course has a visible resources section.
     *
     * @param int $courseid
     * @return int
     */
    private function ensure_resources_section(int $courseid): int {
        global $DB;

        $sectionname = get_string('resources', 'block_dixeo_designer');
        $section = $DB->get_record('course_sections', [
            'course' => $courseid,
            'name' => $sectionname,
        ]);
        if ($section) {
            return (int) $section->section;
        }

        $sectionnumber = (int) $DB->get_field_sql(
            'SELECT COALESCE(MAX(section), 0) + 1 FROM {course_sections} WHERE course = ?',
            [$courseid]
        );

        course_create_sections_if_missing($courseid, [$sectionnumber]);
        $section = $DB->get_record('course_sections', ['course' => $courseid, 'section' => $sectionnumber], '*', MUST_EXIST);
        $section->name = $sectionname;
        $section->summary = '';
        $section->summaryformat = FORMAT_HTML;
        $DB->update_record('course_sections', $section);

        return $sectionnumber;
    }
}
