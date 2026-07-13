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

namespace block_dixeo_designer\external\draft\dto;

/**
 * DTO for block_dixeo_designer get_filesync_status external response.
 *
 * @package    block_dixeo_designer
 * @copyright  2026 Dixeo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class filesync_status_result {
    /**
     * Constructor.
     *
     * @param string $status Sync status label.
     * @param float|null $progresspercent Remote upload progress percent.
     * @param int|null $filestotal Total remote files.
     * @param int|null $filescompleted Completed remote files.
     * @param int|null $uploadbytes Uploaded bytes so far.
     * @param int|null $uploadbytestotal Total bytes to upload.
     * @param string|null $errormessage Error message when failed.
     * @param int|null $lastsynccompleted Last sync completion timestamp.
     * @param bool $hassubmissionfiles Whether the submission has source files.
     * @param bool $moodleprepareactive Whether Moodle prepare is active.
     * @param float|null $moodlepreparepercent Moodle prepare progress percent.
     */
    public function __construct(
        /** @var string Sync status label. */
        public string $status,
        /** @var float|null Remote upload progress percent. */
        public ?float $progresspercent,
        /** @var int|null Total remote files. */
        public ?int $filestotal,
        /** @var int|null Completed remote files. */
        public ?int $filescompleted,
        /** @var int|null Uploaded bytes so far. */
        public ?int $uploadbytes,
        /** @var int|null Total bytes to upload. */
        public ?int $uploadbytestotal,
        /** @var string|null Error message when failed. */
        public ?string $errormessage,
        /** @var int|null Last sync completion timestamp. */
        public ?int $lastsynccompleted,
        /** @var bool Whether the submission has source files. */
        public bool $hassubmissionfiles,
        /** @var bool Whether Moodle prepare is active. */
        public bool $moodleprepareactive,
        /** @var float|null Moodle prepare progress percent. */
        public ?float $moodlepreparepercent
    ) {
    }

    /**
     * Build a DTO from the designer service status object.
     *
     * @param object $status
     * @return self
     */
    public static function from_service(object $status): self {
        $lastsync = $status->lastsynccompleted ?? null;
        $moodlepct = $status->moodlepreparepercent ?? null;
        $uploadbytes = $status->uploadbytes ?? null;
        $uploadtotal = $status->uploadbytestotal ?? null;
        return new self(
            (string) ($status->status ?? 'none'),
            isset($status->progresspercent) ? (float) $status->progresspercent : null,
            isset($status->filestotal) ? (int) $status->filestotal : null,
            isset($status->filescompleted) ? (int) $status->filescompleted : null,
            isset($uploadbytes) && is_numeric($uploadbytes) ? (int) $uploadbytes : null,
            isset($uploadtotal) && is_numeric($uploadtotal) ? (int) $uploadtotal : null,
            isset($status->errormessage) ? (string) $status->errormessage : null,
            $lastsync !== null && $lastsync !== '' ? (int) $lastsync : null,
            !empty($status->hassubmissionfiles),
            !empty($status->moodleprepareactive),
            isset($moodlepct) && is_numeric($moodlepct) ? (float) $moodlepct : null
        );
    }

    /**
     * Convert to webservice response array.
     *
     * @return array<string, bool|float|int|string|null>
     */
    public function to_array(): array {
        return [
            'status' => $this->status,
            'progresspercent' => $this->progresspercent,
            'filestotal' => $this->filestotal,
            'filescompleted' => $this->filescompleted,
            'uploadbytes' => $this->uploadbytes,
            'uploadbytestotal' => $this->uploadbytestotal,
            'errormessage' => $this->errormessage,
            'lastsynccompleted' => $this->lastsynccompleted,
            'hassubmissionfiles' => $this->hassubmissionfiles,
            'moodleprepareactive' => $this->moodleprepareactive,
            'moodlepreparepercent' => $this->moodlepreparepercent,
        ];
    }
}
