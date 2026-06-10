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

declare(strict_types=1);

namespace local_coursebooking\reportbuilder\datasource;

use core_reportbuilder\datasource;
use core_reportbuilder\local\entities\{course, user};
use local_coursebooking\reportbuilder\local\entities\{booking, delegate};

/**
 * Course bookings datasource.
 *
 * Each row represents a single delegate within a booking, allowing purchase
 * orders to be generated with full per-delegate and per-booking detail.
 *
 * @package    local_coursebooking
 * @copyright  2026 Wellingtone
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bookings extends datasource {

    /**
     * Return user friendly name of the datasource.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('datasource_bookings', 'local_coursebooking');
    }

    /**
     * Initialise report.
     */
    protected function initialise(): void {
        $bookingentity = new booking();
        $bookingalias = $bookingentity->get_table_alias('local_coursebooking_book');

        $this->set_main_table('local_coursebooking_book', $bookingalias);
        $this->add_entity($bookingentity);

        // Join the delegate entity (one booking has one or more delegates).
        $delegateentity = new delegate();
        $delegatealias = $delegateentity->get_table_alias('local_coursebooking_deleg');
        $this->add_entity($delegateentity
            ->add_join("LEFT JOIN {local_coursebooking_deleg} {$delegatealias}
                ON {$delegatealias}.bookingid = {$bookingalias}.id"));

        // Join the course entity.
        $courseentity = new course();
        $coursealias = $courseentity->get_table_alias('course');
        $this->add_entity($courseentity
            ->add_join("LEFT JOIN {course} {$coursealias}
                ON {$coursealias}.id = {$bookingalias}.courseid"));

        // Join the user entity (the account created/linked for the delegate).
        $userentity = new user();
        $useralias = $userentity->get_table_alias('user');
        $this->add_entity($userentity
            ->add_joins($delegateentity->get_joins())
            ->add_join("LEFT JOIN {user} {$useralias}
                ON {$useralias}.id = {$delegatealias}.userid"));

        $this->add_all_from_entities();
    }

    /**
     * Return the columns that will be added to the report upon creation.
     *
     * @return string[]
     */
    public function get_default_columns(): array {
        return [
            'booking:reference',
            'course:fullname',
            'booking:bookingtype',
            'delegate:firstname',
            'delegate:lastname',
            'delegate:email',
            'booking:unitprice',
            'booking:timecreated',
        ];
    }

    /**
     * Return the filters that will be added to the report upon creation.
     *
     * @return string[]
     */
    public function get_default_filters(): array {
        return [
            'booking:status',
            'booking:timecreated',
            'course:fullname',
        ];
    }

    /**
     * Return the conditions that will be added to the report upon creation.
     *
     * @return string[]
     */
    public function get_default_conditions(): array {
        return [
            'booking:status',
        ];
    }

    /**
     * Return the default sorting that will be added to the report upon creation.
     *
     * @return array
     */
    public function get_default_column_sorting(): array {
        return [
            'booking:timecreated' => SORT_DESC,
        ];
    }
}
