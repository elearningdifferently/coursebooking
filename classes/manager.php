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

namespace local_coursebooking;

use core_course\customfield\course_handler;
use core_customfield\category_controller;
use core_customfield\field_controller;
use core_text;
use moodle_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Core logic for the Course Booking plugin.
 *
 * @package    local_coursebooking
 * @copyright  2026 Wellingtone
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {

    /** @var string Custom course field: cost per place. */
    const FIELD_COST = 'cb_cost';

    /** @var string Custom course field: maximum registrations. */
    const FIELD_MAXREG = 'cb_maxreg';

    /** @var string Custom course field: expose to public catalogue. */
    const FIELD_EXPOSE = 'cb_expose';

    /** @var string Booking type: individual. */
    const TYPE_INDIVIDUAL = 'individual';

    /** @var string Booking type: group. */
    const TYPE_GROUP = 'group';

    /** @var string Confirmed booking status. */
    const STATUS_CONFIRMED = 'confirmed';

    /**
     * Create the custom course fields used by this plugin, if they do not already exist.
     *
     * Safe to call repeatedly (idempotent).
     */
    public static function ensure_custom_fields(): void {
        $handler = course_handler::create();

        // Find or create our category.
        $categories = $handler->get_categories_with_fields();
        $categoryname = get_string('fieldcategory', 'local_coursebooking');
        $category = null;
        foreach ($categories as $cat) {
            if ($cat->get('name') === $categoryname) {
                $category = $cat;
                break;
            }
        }
        if ($category === null) {
            $categoryid = $handler->create_category($categoryname);
            $category = category_controller::create($categoryid);
        }

        // Collect existing field shortnames to avoid duplicates.
        $existing = [];
        foreach ($handler->get_fields() as $field) {
            $existing[$field->get('shortname')] = true;
        }

        $fields = [
            self::FIELD_COST => [
                'name' => get_string('field_cost', 'local_coursebooking'),
                'type' => 'text',
            ],
            self::FIELD_MAXREG => [
                'name' => get_string('field_maxreg', 'local_coursebooking'),
                'type' => 'text',
            ],
            self::FIELD_EXPOSE => [
                'name' => get_string('field_expose', 'local_coursebooking'),
                'type' => 'checkbox',
            ],
        ];

        foreach ($fields as $shortname => $meta) {
            if (!empty($existing[$shortname])) {
                continue;
            }
            self::create_field($handler, $category, $shortname, $meta['name'], $meta['type']);
        }
    }

    /**
     * Create a single custom course field.
     *
     * @param course_handler $handler
     * @param category_controller $category
     * @param string $shortname
     * @param string $name
     * @param string $type text|checkbox
     */
    protected static function create_field(course_handler $handler, category_controller $category,
            string $shortname, string $name, string $type): void {
        $configdata = [
            'required' => 0,
            'uniquevalues' => 0,
            'locked' => 0,
            'visibility' => 2,
            'defaultvalue' => '',
            'defaultvalueformat' => FORMAT_MOODLE,
            'displaysize' => 0,
            'maxlength' => 0,
            'ispassword' => 0,
            'link' => '',
            'linktarget' => '',
            'checkbydefault' => 0,
        ];

        $record = (object) [
            'name' => $name,
            'shortname' => $shortname,
            'description' => '',
            'descriptionformat' => FORMAT_HTML,
            'type' => $type,
            'sortorder' => 0,
            'configdata' => json_encode($configdata),
        ];

        $field = field_controller::create(0, (object) ['type' => $type], $category);
        $handler->save_field_configuration($field, $record);
    }

    /**
     * Return a map of our custom field shortnames to their field id.
     *
     * @return array [shortname => fieldid]
     */
    public static function get_field_ids(): array {
        global $DB;

        $shortnames = [self::FIELD_COST, self::FIELD_MAXREG, self::FIELD_EXPOSE];
        [$insql, $params] = $DB->get_in_or_equal($shortnames, SQL_PARAMS_NAMED, 'sn');

        $sql = "SELECT f.shortname, f.id
                  FROM {customfield_field} f
                  JOIN {customfield_category} c ON c.id = f.categoryid
                 WHERE c.component = :component
                   AND c.area = :area
                   AND f.shortname $insql";
        $params['component'] = 'core_course';
        $params['area'] = 'course';

        return $DB->get_records_sql_menu($sql, $params);
    }

    /**
     * Get the booking configuration for a single course.
     *
     * @param int $courseid
     * @return stdClass {cost: float|null, maxreg: int, exposed: bool}
     */
    public static function get_course_config(int $courseid): stdClass {
        $handler = course_handler::create();
        $datas = $handler->get_instance_data($courseid, true);

        $config = (object) [
            'cost' => null,
            'maxreg' => 0,
            'exposed' => false,
        ];

        foreach ($datas as $data) {
            $shortname = $data->get_field()->get('shortname');
            $value = $data->get_value();
            switch ($shortname) {
                case self::FIELD_COST:
                    $config->cost = ($value === null || $value === '') ? null : (float) $value;
                    break;
                case self::FIELD_MAXREG:
                    $config->maxreg = (int) $value;
                    break;
                case self::FIELD_EXPOSE:
                    $config->exposed = (bool) $value;
                    break;
            }
        }

        return $config;
    }

    /**
     * Return the list of courses exposed for public booking, with computed availability.
     *
     * @return array of objects: {course, cost, maxreg, booked, remaining}
     */
    public static function get_bookable_courses(): array {
        global $DB, $SITE;

        $fieldids = self::get_field_ids();
        if (empty($fieldids[self::FIELD_EXPOSE])) {
            return [];
        }

        $params = [
            'exposefieldid' => $fieldids[self::FIELD_EXPOSE],
            'costfieldid' => $fieldids[self::FIELD_COST] ?? 0,
            'maxregfieldid' => $fieldids[self::FIELD_MAXREG] ?? 0,
            'siteid' => $SITE->id,
            'confirmed' => self::STATUS_CONFIRMED,
        ];

        $sql = "SELECT c.id, c.fullname, c.shortname, c.summary, c.summaryformat,
                       c.startdate, c.enddate, c.visible,
                       cost.charvalue AS cost,
                       maxreg.charvalue AS maxreg,
                       COALESCE(booked.places, 0) AS booked
                  FROM {course} c
                  JOIN {customfield_data} expose
                       ON expose.instanceid = c.id AND expose.fieldid = :exposefieldid AND expose.intvalue = 1
             LEFT JOIN {customfield_data} cost
                       ON cost.instanceid = c.id AND cost.fieldid = :costfieldid
             LEFT JOIN {customfield_data} maxreg
                       ON maxreg.instanceid = c.id AND maxreg.fieldid = :maxregfieldid
             LEFT JOIN (
                       SELECT d.courseid, COUNT(d.id) AS places
                         FROM {local_coursebooking_deleg} d
                         JOIN {local_coursebooking_book} b ON b.id = d.bookingid
                        WHERE b.status = :confirmed
                     GROUP BY d.courseid
                  ) booked ON booked.courseid = c.id
                 WHERE c.visible = 1
                   AND c.id <> :siteid
              ORDER BY c.startdate ASC, c.fullname ASC";

        $records = $DB->get_records_sql($sql, $params);

        $result = [];
        foreach ($records as $record) {
            $maxreg = (int) $record->maxreg;
            $booked = (int) $record->booked;
            $remaining = $maxreg > 0 ? max(0, $maxreg - $booked) : null;

            $result[] = (object) [
                'course' => $record,
                'cost' => ($record->cost === null || $record->cost === '') ? null : (float) $record->cost,
                'maxreg' => $maxreg,
                'booked' => $booked,
                'remaining' => $remaining,
            ];
        }

        return $result;
    }

    /**
     * Count confirmed delegate places already booked on a course.
     *
     * @param int $courseid
     * @return int
     */
    public static function count_booked_places(int $courseid): int {
        global $DB;

        $sql = "SELECT COUNT(d.id)
                  FROM {local_coursebooking_deleg} d
                  JOIN {local_coursebooking_book} b ON b.id = d.bookingid
                 WHERE d.courseid = :courseid
                   AND b.status = :status";

        return (int) $DB->count_records_sql($sql, [
            'courseid' => $courseid,
            'status' => self::STATUS_CONFIRMED,
        ]);
    }

    /**
     * Get remaining capacity for a course, or null when unlimited.
     *
     * @param int $courseid
     * @param int $maxreg
     * @return int|null
     */
    public static function get_remaining_places(int $courseid, int $maxreg): ?int {
        if ($maxreg <= 0) {
            return null;
        }
        return max(0, $maxreg - self::count_booked_places($courseid));
    }

    /**
     * Determine whether a course may currently be booked by the public.
     *
     * @param int $courseid
     * @return bool
     */
    public static function is_bookable(int $courseid): bool {
        global $DB, $SITE;

        if ($courseid == $SITE->id) {
            return false;
        }
        $course = $DB->get_record('course', ['id' => $courseid], 'id, visible', IGNORE_MISSING);
        if (!$course || !$course->visible) {
            return false;
        }
        return self::get_course_config($courseid)->exposed;
    }

    /**
     * Create a booking, accounts and enrolments inside a course-level lock and DB transaction.
     *
     * @param stdClass $course The course record.
     * @param string $bookingtype One of TYPE_INDIVIDUAL / TYPE_GROUP.
     * @param string $contactname Booking contact / group leader name.
     * @param string $contactemail Booking contact / group leader email.
     * @param array $delegates List of [firstname, lastname, email, isleader] arrays.
     * @return stdClass The created booking record.
     * @throws moodle_exception When capacity is exceeded or input is invalid.
     */
    public static function create_booking(stdClass $course, string $bookingtype, string $contactname,
            string $contactemail, array $delegates): stdClass {
        global $DB;

        if (empty($delegates)) {
            throw new moodle_exception('error_nodelegates', 'local_coursebooking');
        }

        $config = self::get_course_config($course->id);
        if (!$config->exposed) {
            throw new moodle_exception('error_notbookable', 'local_coursebooking');
        }

        // Acquire a per-course lock to make the capacity check race-safe.
        $lockfactory = \core\lock\lock_config::get_lock_factory('local_coursebooking');
        $lock = $lockfactory->get_lock('course_' . $course->id, 10);
        if (!$lock) {
            throw new moodle_exception('error_capacity', 'local_coursebooking');
        }

        try {
            $requested = count($delegates);
            $remaining = self::get_remaining_places($course->id, $config->maxreg);
            if ($remaining !== null) {
                if ($remaining <= 0) {
                    throw new moodle_exception('error_fullybooked', 'local_coursebooking');
                }
                if ($requested > $remaining) {
                    throw new moodle_exception('error_capacity', 'local_coursebooking', '', $remaining);
                }
            }

            $transaction = $DB->start_delegated_transaction();

            $now = time();
            $unitprice = $config->cost;
            $totalprice = $unitprice === null ? null : $unitprice * $requested;

            $booking = (object) [
                'courseid' => $course->id,
                'bookingtype' => $bookingtype,
                'contactname' => $contactname,
                'contactemail' => $contactemail,
                'status' => self::STATUS_CONFIRMED,
                'places' => $requested,
                'unitprice' => $unitprice,
                'totalprice' => $totalprice,
                'reference' => self::generate_reference(),
                'timecreated' => $now,
                'timemodified' => $now,
            ];
            $booking->id = $DB->insert_record('local_coursebooking_book', $booking);

            foreach ($delegates as $delegate) {
                [$userid, $created] = self::create_or_get_user(
                    $delegate['firstname'],
                    $delegate['lastname'],
                    $delegate['email']
                );
                self::enrol_delegate($userid, $course->id);

                $DB->insert_record('local_coursebooking_deleg', (object) [
                    'bookingid' => $booking->id,
                    'courseid' => $course->id,
                    'userid' => $userid,
                    'firstname' => $delegate['firstname'],
                    'lastname' => $delegate['lastname'],
                    'email' => $delegate['email'],
                    'isleader' => !empty($delegate['isleader']) ? 1 : 0,
                    'newaccount' => $created ? 1 : 0,
                    'timecreated' => $now,
                ]);
            }

            $transaction->allow_commit();
        } finally {
            $lock->release();
        }

        return $booking;
    }

    /**
     * Create a Moodle account for the delegate, or return the existing account for the email.
     *
     * New accounts use manual auth, are forced to change password on first login, and receive
     * an email with their generated password via standard Moodle functionality.
     *
     * @param string $firstname
     * @param string $lastname
     * @param string $email
     * @return array [int userid, bool created]
     */
    public static function create_or_get_user(string $firstname, string $lastname, string $email): array {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/user/lib.php');

        $email = core_text::strtolower(trim($email));

        // Reuse an existing, non-deleted account with this email on the local host.
        $existing = $DB->get_records('user', [
            'email' => $email,
            'mnethostid' => $CFG->mnet_localhost_id,
            'deleted' => 0,
        ], 'id ASC', 'id', 0, 1);
        if ($existing) {
            $existing = reset($existing);
            return [(int) $existing->id, false];
        }

        $newuser = new stdClass();
        $newuser->auth = 'manual';
        $newuser->mnethostid = $CFG->mnet_localhost_id;
        $newuser->confirmed = 1;
        $newuser->username = self::generate_username($email);
        $newuser->email = $email;
        $newuser->firstname = $firstname;
        $newuser->lastname = $lastname;
        $newuser->lang = $CFG->lang ?? 'en';
        $newuser->timecreated = time();
        $newuser->timemodified = $newuser->timecreated;

        // Do not let user_create_user attempt to set a password (we mail one below).
        $userid = user_create_user($newuser, false, true);
        $newuser->id = $userid;

        // Force password change on first login, then email the generated password.
        set_user_preference('auth_forcepasswordchange', 1, $newuser);
        $userrecord = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
        setnew_password_and_mail($userrecord);

        return [(int) $userid, true];
    }

    /**
     * Enrol a delegate into a course using the manual enrolment plugin.
     *
     * @param int $userid
     * @param int $courseid
     */
    public static function enrol_delegate(int $userid, int $courseid): void {
        global $DB;

        $enrol = enrol_get_plugin('manual');
        if (!$enrol) {
            throw new moodle_exception('error_notbookable', 'local_coursebooking');
        }

        $instance = $DB->get_record('enrol', [
            'courseid' => $courseid,
            'enrol' => 'manual',
        ], '*', IGNORE_MULTIPLE);

        if (!$instance) {
            $course = get_course($courseid);
            $instanceid = $enrol->add_default_instance($course);
            if (!$instanceid) {
                $instanceid = $enrol->add_instance($course);
            }
            $instance = $DB->get_record('enrol', ['id' => $instanceid], '*', MUST_EXIST);
        }

        $roleid = self::get_enrol_roleid();
        $enrol->enrol_user($instance, $userid, $roleid);
    }

    /**
     * Get the role id used when enrolling delegates.
     *
     * @return int
     */
    public static function get_enrol_roleid(): int {
        $configured = (int) get_config('local_coursebooking', 'enrolrole');
        if ($configured > 0) {
            return $configured;
        }
        $studentroles = get_archetype_roles('student');
        if ($studentroles) {
            $role = reset($studentroles);
            return (int) $role->id;
        }
        return 0;
    }

    /**
     * Split a single full-name string into first and last name parts.
     *
     * The final word becomes the last name and everything before it the first
     * name. When only one word is supplied it is used for both parts so that
     * required Moodle account fields are never empty.
     *
     * @param string $fullname
     * @return array [string firstname, string lastname]
     */
    public static function split_name(string $fullname): array {
        $fullname = trim(preg_replace('/\s+/', ' ', $fullname));
        if ($fullname === '') {
            return ['', ''];
        }
        $parts = explode(' ', $fullname);
        if (count($parts) === 1) {
            return [$parts[0], $parts[0]];
        }
        $lastname = array_pop($parts);
        $firstname = implode(' ', $parts);
        return [$firstname, $lastname];
    }

    /**
     * Generate a unique, valid username derived from an email address.
     *
     * @param string $email
     * @return string
     */
    protected static function generate_username(string $email): string {
        global $DB, $CFG;

        $base = core_text::strtolower($email);
        $base = clean_param($base, PARAM_USERNAME);
        if ($base === '') {
            $base = 'delegate';
        }
        // Keep room for a numeric suffix.
        $base = core_text::substr($base, 0, 90);

        $username = $base;
        $suffix = 1;
        while ($DB->record_exists('user', ['username' => $username, 'mnethostid' => $CFG->mnet_localhost_id])) {
            $username = $base . $suffix;
            $suffix++;
        }
        return $username;
    }

    /**
     * Generate a unique booking reference.
     *
     * @return string
     */
    protected static function generate_reference(): string {
        global $DB;

        do {
            $reference = 'CB-' . strtoupper(substr(md5(uniqid((string) random_int(0, PHP_INT_MAX), true)), 0, 8));
        } while ($DB->record_exists('local_coursebooking_book', ['reference' => $reference]));

        return $reference;
    }
}
