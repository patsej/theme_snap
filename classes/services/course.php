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

namespace theme_snap\services;

use theme_snap\renderables\course_card;
use theme_snap\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Course service class.
 * @author    gthomas2
 * @copyright Copyright (c) 2016 Moodlerooms Inc. (http://www.moodlerooms.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course {

    private function __construct() {}

    /**
     * Return singleton.
     *
     * @return course service
     */
    public static function service() {
        static $instance = null;
        if ($instance === null) {
            $instance = new course();
        }
        return $instance;
    }

    /**
     * @param string $courseshortname
     * @param string $data
     * @param string $filename
     * @return array
     * @throws \file_exception
     * @throws \stored_file_creation_exception
     */
    public function setcoverimage($courseshortname, $data, $filename) {

        global $CFG;

        $course = $this->coursebyshortname($courseshortname);
        if ($course->id != SITEID) {
            // Course cover images.
            $context = \context_course::instance($course->id);
        } else {
            // Site cover images.
            $context = \context_system::instance();
        }

        require_capability('moodle/course:manageactivities', $context);

        $fs = get_file_storage();
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $ext = $ext === 'jpeg' ? 'jpg' : $ext;
        $newfilename = 'rawcoverimage.'.$ext;

        if ($course->id != SITEID) {
            // Course cover images.
            $context = \context_course::instance($course->id);
            $fileinfo = array(
                'contextid' => $context->id,
                'component' => 'course',
                'filearea' => 'overviewfiles',
                'itemid' => 0,
                'filepath' => '/',
                'filename' => $newfilename);

                $extensions = ['jpg', 'jpeg', 'png', 'gif', 'svg'];

                // Remove any old cover image files.
                foreach ($extensions as $ext) {
                    $chkfilename = 'rawcoverimage.'.$ext;
                    if ($fs->file_exists($context->id, $fileinfo['component'], $fileinfo['filearea'], 0, '/', $chkfilename)) {
                        $storedfile = $fs->get_file($context->id, $fileinfo['component'], $fileinfo['filearea'], 0, '/', $chkfilename);
                        $storedfile->delete();
                    }
                }

        } else {
            // Site cover images.
            $context = \context_system::instance();
            $fileinfo = array(
                'contextid' => $context->id,
                'component' => 'theme_snap',
                'filearea' => 'poster',
                'itemid' => 0,
                'filepath' => '/',
                'filename' => $newfilename);

            // Remove everything from poster area.
            $fs->delete_area_files($context->id, 'theme_snap', 'poster');
        }

        $binary =  base64_decode($data);
        if (strlen($binary) > get_max_upload_file_size($CFG->maxbytes)) {
            throw new \moodle_exception('error:coverimageexceedsmaxbytes', 'theme_snap');
        }

        // Create new cover image file and process it.
        $storedfile = $fs->create_file_from_string($fileinfo, $binary);
        $success = $storedfile instanceof \stored_file;
        if ($course->id != SITEID) {
            local::process_coverimage($context, $storedfile);
        } else {
            local::process_coverimage($context);
        }
        return ['success' => $success];
    }

    /**
     * Is a specific course favorited or not for the specified or current user.
     *
     * @param int $courseid
     * @param null | int $userid
     * @param bool $fromcache
     * @return bool
     */
    public function favorited($courseid, $userid = null, $fromcache = true) {
        global $USER;

        $userid = $userid !== null ? $userid : $USER->id;

        $favorites = $this->favorites($userid, false, $fromcache);
        return !empty($favorites) && !empty($favorites[$courseid]);
    }

    /**
     * Get course favorites for specific userid.
     * @param null $userid
     * @param bool $fromcache
     * @return array
     */
    public function favorites($userid = null, $fromcache = true) {
        global $USER, $DB;

        $userid = $userid !== null ? $userid : $USER->id;

        static $favorites = [];

        if (!$fromcache) {
            unset($favorites[$userid]);
        }

        if (!isset($favorites[$userid])) {
            $favorites[$userid] = $DB->get_records('theme_snap_course_favorites',
                ['userid' => $userid],
                'courseid ASC',
                'courseid'
            );
        }

        return $favorites[$userid];
    }

    /**
     * Get courses for current user split by favorite status.
     *
     * @return array
     * @throws \coding_exception
     */
    public function my_courses_split_by_favorites() {
        $courses = enrol_get_my_courses(null, 'fullname ASC, id DESC');
        $favorites = $this->favorites();
        $favorited = [];
        $notfavorited = [];
        foreach ($courses as $course) {
            if (isset($favorites[$course->id])) {
                $favorited[$course->id] = $course;
            } else {
                $notfavorited[$course->id] = $course;
            }
        }
        return [$favorited, $notfavorited];
    }

    /**
     * Set favorite status on or off.
     *
     * @param string $courseshortname
     * @param bool $on
     * @param null | int $userid
     * @return bool
     */
    public function setfavorite($courseshortname, $on = true, $userid = null) {
        global $USER, $DB;

        $course = $this->coursebyshortname($courseshortname);
        $userid = $userid !== null ? $userid : $USER->id;

        $favorited = $this->favorited($course->id, $userid);
        if ($on) {
            if (!$favorited) {
                $data = (object) [
                    'courseid' => $course->id,
                    'userid' => $userid,
                    'timefavorited' => time()
                ];
                $DB->insert_record('theme_snap_course_favorites', $data);
            }
        } else {
            if ($favorited) {
                $select = [
                    'courseid' => $course->id,
                    'userid' => $userid
                ];
                $DB->delete_records('theme_snap_course_favorites', $select);
            }
        }
        // Kill favorited cache and return if favorited.
        return $this->favorited($course->id, $userid, false);
    }

    /**
     * Get course by shortname.
     * @param string $shortname
     * @return mixed
     */
    public function coursebyshortname($shortname, $fields = '*') {
        global $DB;
        $course = $DB->get_record('course', ['shortname' => $shortname], $fields, MUST_EXIST);
        return $course;
    }

    /**
     * Get a card renderable by course shortname.
     * @param string $shortname
     * @return course_card (renderable)
     */
    public function cardbyshortname($shortname) {
        $course = $this->coursebyshortname($shortname, 'id');
        return new course_card($course->id);
    }
}
