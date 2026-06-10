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

namespace local_coursebooking\form;

use local_coursebooking\manager;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Booking form for the public catalogue (individual and group bookings).
 *
 * @package    local_coursebooking
 * @copyright  2026 Wellingtone
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class booking_form extends \moodleform {

    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;
        $courseid = (int) $this->_customdata['courseid'];
        $remaining = $this->_customdata['remaining']; // int|null.
        $maxgroup = (int) $this->_customdata['maxgroupsize'];

        $mform->addElement('hidden', 'id', $courseid);
        $mform->setType('id', PARAM_INT);

        // Honeypot: real users never see/fill this; bots usually do.
        $mform->addElement('text', 'website', get_string('email', 'local_coursebooking'),
            ['autocomplete' => 'off', 'tabindex' => '-1', 'style' => 'display:none', 'aria-hidden' => 'true']);
        $mform->setType('website', PARAM_RAW);

        // Booking type.
        $typeoptions = [
            manager::TYPE_INDIVIDUAL => get_string('bookingtype_individual', 'local_coursebooking'),
            manager::TYPE_GROUP => get_string('bookingtype_group', 'local_coursebooking'),
        ];
        $radioarray = [];
        foreach ($typeoptions as $value => $label) {
            $radioarray[] = $mform->createElement('radio', 'bookingtype', '', $label, $value);
        }
        $mform->addGroup($radioarray, 'bookingtypegroup',
            get_string('bookingtype', 'local_coursebooking'), ['<br>'], false);
        $mform->setDefault('bookingtype', manager::TYPE_INDIVIDUAL);

        // ---- Individual booking ----
        $mform->addElement('static', 'individualheader', '',
            \html_writer::tag('h4', get_string('individualdetails', 'local_coursebooking'),
                ['class' => 'local-coursebooking-subheading']));
        $mform->addElement('text', 'fullname', get_string('yourname', 'local_coursebooking'), ['maxlength' => 200]);
        $mform->setType('fullname', PARAM_TEXT);
        $mform->addElement('text', 'email', get_string('youremail', 'local_coursebooking'), ['maxlength' => 255]);
        $mform->setType('email', PARAM_RAW_TRIMMED);

        $mform->hideIf('individualheader', 'bookingtype', 'neq', manager::TYPE_INDIVIDUAL);
        $mform->hideIf('fullname', 'bookingtype', 'neq', manager::TYPE_INDIVIDUAL);
        $mform->hideIf('email', 'bookingtype', 'neq', manager::TYPE_INDIVIDUAL);

        // ---- Group booking ----
        // Person making the booking.
        $mform->addElement('static', 'groupheader', '',
            \html_writer::tag('h4', get_string('groupbookerdetails', 'local_coursebooking'),
                ['class' => 'local-coursebooking-subheading']));
        $mform->addElement('text', 'leadername', get_string('groupleadername', 'local_coursebooking'), ['maxlength' => 200]);
        $mform->setType('leadername', PARAM_TEXT);
        $mform->addElement('text', 'leaderemail', get_string('groupleaderemail', 'local_coursebooking'), ['maxlength' => 255]);
        $mform->setType('leaderemail', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('leaderemail', 'groupleaderemail', 'local_coursebooking');

        // Delegates.
        $mform->addElement('static', 'delegatesheader', '',
            \html_writer::tag('h4', get_string('groupdelegates', 'local_coursebooking'),
                ['class' => 'local-coursebooking-subheading']));

        // Determine how many delegate rows to allow.
        $hardcap = $maxgroup > 0 ? $maxgroup : 50;
        if ($remaining !== null) {
            $hardcap = min($hardcap, max(1, (int) $remaining));
        }
        // Show 2 delegate rows by default; "Add another delegate" reveals one more at a time.
        $initialrepeat = min(2, $hardcap);

        $repeatel = [
            $mform->createElement('text', 'delname',
                get_string('delegatename', 'local_coursebooking'), ['maxlength' => 200]),
            $mform->createElement('text', 'delemail',
                get_string('delegateemail', 'local_coursebooking'), ['maxlength' => 255]),
        ];
        // hideif ensures every repeated delegate row only appears for group bookings.
        $repeatoptions = [
            'delname' => [
                'type' => PARAM_TEXT,
                'hideif' => ['bookingtype', 'neq', manager::TYPE_GROUP],
            ],
            'delemail' => [
                'type' => PARAM_RAW_TRIMMED,
                'hideif' => ['bookingtype', 'neq', manager::TYPE_GROUP],
            ],
        ];

        $this->repeat_elements($repeatel, $initialrepeat, $repeatoptions, 'delegate_repeats',
            'delegate_add', 1, get_string('adddelegate', 'local_coursebooking'), true);

        $mform->hideIf('groupheader', 'bookingtype', 'neq', manager::TYPE_GROUP);
        $mform->hideIf('leadername', 'bookingtype', 'neq', manager::TYPE_GROUP);
        $mform->hideIf('leaderemail', 'bookingtype', 'neq', manager::TYPE_GROUP);
        $mform->hideIf('delegatesheader', 'bookingtype', 'neq', manager::TYPE_GROUP);
        $mform->hideIf('delegate_add', 'bookingtype', 'neq', manager::TYPE_GROUP);

        $this->add_action_buttons(true, get_string('submitbooking', 'local_coursebooking'));
    }

    /**
     * Server-side validation.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Honeypot must be empty.
        if (!empty($data['website'])) {
            $errors['website'] = get_string('error_required', 'local_coursebooking');
            return $errors;
        }

        $remaining = $this->_customdata['remaining']; // int|null.
        $type = $data['bookingtype'] ?? manager::TYPE_INDIVIDUAL;

        if ($type === manager::TYPE_INDIVIDUAL) {
            if (trim($data['fullname'] ?? '') === '') {
                $errors['fullname'] = get_string('error_required', 'local_coursebooking');
            }
            if (!validate_email($data['email'] ?? '')) {
                $errors['email'] = get_string('error_invalidemail', 'local_coursebooking');
            }
            if ($remaining !== null && $remaining < 1) {
                $errors['fullname'] = get_string('error_fullybooked', 'local_coursebooking');
            }
        } else {
            if (trim($data['leadername'] ?? '') === '') {
                $errors['leadername'] = get_string('error_required', 'local_coursebooking');
            }
            if (!validate_email($data['leaderemail'] ?? '')) {
                $errors['leaderemail'] = get_string('error_invalidemail', 'local_coursebooking');
            }

            $names = $data['delname'] ?? [];
            $emails = $data['delemail'] ?? [];

            $seen = [];
            $count = 0;
            foreach ($emails as $i => $email) {
                $name = trim($names[$i] ?? '');
                $em = trim($email);

                // Skip fully empty rows.
                if ($name === '' && $em === '') {
                    continue;
                }
                $count++;

                if ($name === '') {
                    $errors["delname[$i]"] = get_string('error_required', 'local_coursebooking');
                }
                if (!validate_email($em)) {
                    $errors["delemail[$i]"] = get_string('error_invalidemail', 'local_coursebooking');
                    continue;
                }
                $key = \core_text::strtolower($em);
                if (isset($seen[$key])) {
                    $errors["delemail[$i]"] = get_string('error_duplicateemail', 'local_coursebooking', s($em));
                }
                $seen[$key] = true;
            }

            if ($count === 0) {
                $errors['leaderemail'] = get_string('error_nodelegates', 'local_coursebooking');
            } else if ($remaining !== null && $count > $remaining) {
                $errors['leaderemail'] = get_string('error_capacity', 'local_coursebooking', $remaining);
            }
        }

        return $errors;
    }
}
