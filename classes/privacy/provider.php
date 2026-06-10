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

namespace local_coursebooking\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use context_system;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for the Course Booking plugin.
 *
 * @package    local_coursebooking
 * @copyright  2026 Wellingtone
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\core_userlist_provider,
        \core_privacy\local\request\plugin\provider {

    /**
     * Describe the personal data stored by this plugin.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_coursebooking_book', [
            'contactname' => 'privacy:metadata:local_coursebooking_book:contactname',
            'contactemail' => 'privacy:metadata:local_coursebooking_book:contactemail',
        ], 'privacy:metadata:local_coursebooking_book');

        $collection->add_database_table('local_coursebooking_deleg', [
            'firstname' => 'privacy:metadata:local_coursebooking_deleg:firstname',
            'lastname' => 'privacy:metadata:local_coursebooking_deleg:lastname',
            'email' => 'privacy:metadata:local_coursebooking_deleg:email',
            'userid' => 'privacy:metadata:local_coursebooking_deleg:userid',
        ], 'privacy:metadata:local_coursebooking_deleg');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $contextlist = new contextlist();
        if ($DB->record_exists('local_coursebooking_deleg', ['userid' => $userid])) {
            $contextlist->add_system_context();
        }
        return $contextlist;
    }

    /**
     * Get the list of users within a specific context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof context_system) {
            return;
        }
        $sql = "SELECT userid FROM {local_coursebooking_deleg} WHERE userid > 0";
        $userlist->add_from_sql('userid', $sql, []);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $user = $contextlist->get_user();
        $hassystem = false;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof context_system) {
                $hassystem = true;
                break;
            }
        }
        if (!$hassystem) {
            return;
        }

        $records = $DB->get_records('local_coursebooking_deleg', ['userid' => $user->id]);
        $data = [];
        foreach ($records as $record) {
            $data[] = (object) [
                'firstname' => $record->firstname,
                'lastname' => $record->lastname,
                'email' => $record->email,
                'isleader' => $record->isleader ? get_string('yes') : get_string('no'),
                'timecreated' => userdate($record->timecreated),
            ];
        }
        if ($data) {
            writer::with_context(context_system::instance())->export_data(
                [get_string('pluginname', 'local_coursebooking')],
                (object) ['bookings' => $data]
            );
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if (!$context instanceof context_system) {
            return;
        }
        // Detach the user link without destroying booking/PO records.
        $DB->set_field('local_coursebooking_deleg', 'userid', 0);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $user = $contextlist->get_user();
        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof context_system) {
                $DB->set_field('local_coursebooking_deleg', 'userid', 0, ['userid' => $user->id]);
            }
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof context_system) {
            return;
        }
        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $DB->set_field_select('local_coursebooking_deleg', 'userid', 0, "userid $insql", $params);
    }
}
