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
 * Public course booking catalogue.
 *
 * This page is intentionally accessible to anonymous visitors, even when the
 * site enforces login ($CFG->forcelogin), because it must advertise bookable
 * courses publicly. It therefore does NOT call require_login(); it only ever
 * exposes courses explicitly flagged for public booking.
 *
 * @package    local_coursebooking
 * @copyright  2026 Wellingtone
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_coursebooking\manager;

$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/coursebooking/index.php'));
$PAGE->set_pagelayout('base');
$PAGE->set_pagetype('local-coursebooking-index');
$PAGE->set_title(get_string('catalogue', 'local_coursebooking'));
$PAGE->set_heading(get_string('catalogueheading', 'local_coursebooking'));
$PAGE->set_cacheable(false);

// Make sure the front-end session/theme is initialised for guests.
$PAGE->add_body_class('local-coursebooking-catalogue');

echo $OUTPUT->header();

if (!get_config('local_coursebooking', 'enabled')) {
    echo $OUTPUT->notification(get_string('disabledmessage', 'local_coursebooking'), 'info');
    echo $OUTPUT->footer();
    die;
}

$intro = get_config('local_coursebooking', 'intro');
if ($intro === false || $intro === '') {
    $intro = get_string('catalogueintro', 'local_coursebooking');
}
$currency = get_config('local_coursebooking', 'currencysymbol');
if ($currency === false) {
    $currency = '£';
}

echo html_writer::start_div('local-coursebooking-wrapper');
echo html_writer::tag('p', format_text($intro, FORMAT_HTML), ['class' => 'local-coursebooking-intro lead']);

$bookable = manager::get_bookable_courses();

if (empty($bookable)) {
    echo $OUTPUT->notification(get_string('nocourses', 'local_coursebooking'), 'info');
} else {
    echo html_writer::start_div('local-coursebooking-grid');
    foreach ($bookable as $item) {
        $course = $item->course;
        $fullname = format_string($course->fullname, true, ['context' => $context]);

        // Price label.
        if ($item->cost === null || $item->cost == 0) {
            $pricelabel = get_string('free', 'local_coursebooking');
        } else {
            $pricelabel = $currency . number_format($item->cost, 2) . ' '
                . get_string('perplace', 'local_coursebooking');
        }

        // Start date label.
        if (!empty($course->startdate)) {
            $datelabel = get_string('starts', 'local_coursebooking') . ': '
                . userdate($course->startdate, get_string('strftimedaydatetime', 'core_langconfig'));
        } else {
            $datelabel = get_string('nostartdate', 'local_coursebooking');
        }

        // Availability.
        $isfull = ($item->remaining !== null && $item->remaining <= 0);
        if ($item->remaining === null) {
            $availlabel = get_string('unlimitedplaces', 'local_coursebooking');
            $availclass = 'badge bg-success';
        } else if ($isfull) {
            $availlabel = get_string('fullybooked', 'local_coursebooking');
            $availclass = 'badge bg-danger';
        } else {
            $availlabel = get_string('placesleft', 'local_coursebooking', $item->remaining);
            $availclass = 'badge bg-info';
        }

        echo html_writer::start_div('local-coursebooking-card card h-100');
        echo html_writer::start_div('card-body d-flex flex-column');

        echo html_writer::tag('h3', $fullname, ['class' => 'card-title h5']);
        echo html_writer::tag('div', s($datelabel), ['class' => 'local-coursebooking-date text-muted mb-2']);

        if (!empty($course->summary)) {
            $summary = format_text($course->summary, $course->summaryformat, ['context' => $context]);
            echo html_writer::div(shorten_text(strip_tags($summary), 160), 'local-coursebooking-summary mb-3');
        }

        echo html_writer::start_div('mt-auto');
        echo html_writer::tag('div', s($pricelabel), ['class' => 'local-coursebooking-price h5 mb-2']);
        echo html_writer::tag('span', s($availlabel), ['class' => $availclass . ' mb-3 d-inline-block']);

        if ($isfull) {
            echo html_writer::tag('button', get_string('fullybooked', 'local_coursebooking'),
                ['class' => 'btn btn-secondary disabled w-100 mt-2', 'disabled' => 'disabled']);
        } else {
            $bookurl = new moodle_url('/local/coursebooking/book.php', ['id' => $course->id]);
            echo html_writer::link($bookurl, get_string('booknow', 'local_coursebooking'),
                ['class' => 'btn btn-primary w-100 mt-2']);
        }

        echo html_writer::end_div(); // mt-auto.
        echo html_writer::end_div(); // card-body.
        echo html_writer::end_div(); // card.
    }
    echo html_writer::end_div(); // grid.
}

echo html_writer::end_div(); // wrapper.

echo $OUTPUT->footer();
