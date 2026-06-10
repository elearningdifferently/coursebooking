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

/**
 * Public booking form page.
 *
 * Like index.php this page is intentionally available to anonymous visitors so
 * that they can register for a publicly exposed course. It does not call
 * require_login(); access is limited to courses explicitly flagged as bookable.
 *
 * @package    local_coursebooking
 * @copyright  2026 Wellingtone
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_coursebooking\manager;
use local_coursebooking\form\booking_form;

$courseid = required_param('id', PARAM_INT);

$context = context_system::instance();
$catalogueurl = new moodle_url('/local/coursebooking/index.php');
$pageurl = new moodle_url('/local/coursebooking/book.php', ['id' => $courseid]);

$PAGE->set_context($context);
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('base');
$PAGE->set_pagetype('local-coursebooking-book');
$PAGE->set_cacheable(false);

// Plugin must be enabled.
if (!get_config('local_coursebooking', 'enabled')) {
    $PAGE->set_title(get_string('catalogue', 'local_coursebooking'));
    $PAGE->set_heading(get_string('catalogueheading', 'local_coursebooking'));
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('disabledmessage', 'local_coursebooking'), 'info');
    echo $OUTPUT->footer();
    die;
}

// Course must be publicly bookable.
if (!manager::is_bookable($courseid)) {
    throw new moodle_exception('error_notbookable', 'local_coursebooking', $catalogueurl);
}

$course = get_course($courseid);
$config = manager::get_course_config($courseid);
$remaining = manager::get_remaining_places($courseid, $config->maxreg);

$coursename = format_string($course->fullname, true, ['context' => $context]);
$PAGE->set_title(get_string('bookcourse', 'local_coursebooking', $coursename));
$PAGE->set_heading(get_string('bookcourse', 'local_coursebooking', $coursename));

$maxgroupsize = (int) get_config('local_coursebooking', 'maxgroupsize');

$mform = new booking_form($pageurl->out(false), [
    'courseid' => $courseid,
    'remaining' => $remaining,
    'maxgroupsize' => $maxgroupsize,
]);

if ($mform->is_cancelled()) {
    redirect($catalogueurl);
}

// Fully booked guard.
if ($remaining !== null && $remaining <= 0) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('error_fullybooked', 'local_coursebooking'), 'error');
    echo html_writer::link($catalogueurl, get_string('backtocatalogue', 'local_coursebooking'),
        ['class' => 'btn btn-secondary']);
    echo $OUTPUT->footer();
    die;
}

if ($data = $mform->get_data()) {
    // Build the contact and delegate list from the submitted data.
    if ($data->bookingtype === manager::TYPE_GROUP) {
        $contactname = trim($data->leadername);
        $contactemail = core_text::strtolower(trim($data->leaderemail));
        $delegates = [];
        foreach ($data->delemail as $i => $email) {
            $name = trim($data->delname[$i] ?? '');
            $em = core_text::strtolower(trim($email));
            if ($name === '' && $em === '') {
                continue;
            }
            [$firstname, $lastname] = manager::split_name($name);
            $delegates[] = [
                'firstname' => $firstname,
                'lastname' => $lastname,
                'email' => $em,
                'isleader' => 0,
            ];
        }
    } else {
        $contactname = trim($data->fullname);
        $contactemail = core_text::strtolower(trim($data->email));
        [$firstname, $lastname] = manager::split_name($contactname);
        $delegates = [[
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $contactemail,
            'isleader' => 1,
        ]];
    }

    try {
        $booking = manager::create_booking($course, $data->bookingtype, $contactname, $contactemail, $delegates);

        echo $OUTPUT->header();
        echo $OUTPUT->notification(get_string('bookingsuccess', 'local_coursebooking'), 'success');
        echo html_writer::tag('p',
            get_string('bookingsuccessdetail', 'local_coursebooking', s($contactemail)));
        echo html_writer::tag('p', get_string('booking_reference', 'local_coursebooking')
            . ': ' . s($booking->reference));
        echo html_writer::link($catalogueurl, get_string('backtocatalogue', 'local_coursebooking'),
            ['class' => 'btn btn-primary']);
        echo $OUTPUT->footer();
        die;
    } catch (moodle_exception $e) {
        // Capacity changed between page load and submission, or other booking error.
        echo $OUTPUT->header();
        echo $OUTPUT->notification($e->getMessage(), 'error');
        $mform->display();
        echo $OUTPUT->footer();
        die;
    }
}

echo $OUTPUT->header();
echo html_writer::tag('p', s(get_string('bookcourse', 'local_coursebooking', $coursename)), ['class' => 'sr-only']);
$mform->display();
echo $OUTPUT->footer();
