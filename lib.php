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
 * Library functions for the Course Booking plugin.
 *
 * @package    local_coursebooking
 * @copyright  2026 Wellingtone
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Add a link to the public catalogue in the global "More" navigation for staff.
 *
 * @param global_navigation $navigation
 */
function local_coursebooking_extend_navigation(global_navigation $navigation) {
    global $PAGE;

    if (!get_config('local_coursebooking', 'enabled')) {
        return;
    }
    if (!isloggedin() || isguestuser()) {
        return;
    }
    if (!has_capability('local/coursebooking:viewbookings', context_system::instance())) {
        return;
    }

    $node = $navigation->add(
        get_string('catalogue', 'local_coursebooking'),
        new moodle_url('/local/coursebooking/index.php'),
        navigation_node::TYPE_CUSTOM,
        null,
        'local_coursebooking_catalogue',
        new pix_icon('i/courseevent', '')
    );
    $node->showinflatnavigation = true;
}
