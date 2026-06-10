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

namespace local_coursebooking\reportbuilder\local\entities;

use lang_string;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\{date, number, select, text};
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\report\{column, filter};
use local_coursebooking\manager;

defined('MOODLE_INTERNAL') || die();

/**
 * Booking entity for report builder.
 *
 * @package    local_coursebooking
 * @copyright  2026 Wellingtone
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class booking extends base {

    /**
     * Database tables that this entity uses.
     *
     * @return string[]
     */
    protected function get_default_tables(): array {
        return ['local_coursebooking_book'];
    }

    /**
     * The default title for this entity.
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('entity_booking', 'local_coursebooking');
    }

    /**
     * Initialise the entity.
     *
     * @return base
     */
    public function initialise(): base {
        foreach ($this->get_all_columns() as $column) {
            $this->add_column($column);
        }
        foreach ($this->get_all_filters() as $filter) {
            $this->add_filter($filter)->add_condition($filter);
        }
        return $this;
    }

    /**
     * Helper returning the booking status options.
     *
     * @return array
     */
    protected function get_status_options(): array {
        return [
            'confirmed' => get_string('status_confirmed', 'local_coursebooking'),
            'pending' => get_string('status_pending', 'local_coursebooking'),
            'cancelled' => get_string('status_cancelled', 'local_coursebooking'),
        ];
    }

    /**
     * Helper returning the booking type options.
     *
     * @return array
     */
    protected function get_type_options(): array {
        return [
            manager::TYPE_INDIVIDUAL => get_string('bookingtype_individual', 'local_coursebooking'),
            manager::TYPE_GROUP => get_string('bookingtype_group', 'local_coursebooking'),
        ];
    }

    /**
     * Returns list of all available columns.
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        $alias = $this->get_table_alias('local_coursebooking_book');

        $columns = [];

        $columns[] = (new column(
            'reference',
            new lang_string('booking_reference', 'local_coursebooking'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$alias}.reference")
            ->set_is_sortable(true);

        $typeoptions = $this->get_type_options();
        $columns[] = (new column(
            'bookingtype',
            new lang_string('booking_type', 'local_coursebooking'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$alias}.bookingtype")
            ->set_is_sortable(true)
            ->add_callback(static function(?string $value) use ($typeoptions): string {
                return $value === null ? '' : ($typeoptions[$value] ?? $value);
            });

        $statusoptions = $this->get_status_options();
        $columns[] = (new column(
            'status',
            new lang_string('booking_status', 'local_coursebooking'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$alias}.status")
            ->set_is_sortable(true)
            ->add_callback(static function(?string $value) use ($statusoptions): string {
                return $value === null ? '' : ($statusoptions[$value] ?? $value);
            });

        $columns[] = (new column(
            'contactname',
            new lang_string('booking_contactname', 'local_coursebooking'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$alias}.contactname")
            ->set_is_sortable(true);

        $columns[] = (new column(
            'contactemail',
            new lang_string('booking_contactemail', 'local_coursebooking'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$alias}.contactemail")
            ->set_is_sortable(true);

        $columns[] = (new column(
            'places',
            new lang_string('booking_places', 'local_coursebooking'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_fields("{$alias}.places")
            ->set_is_sortable(true);

        $columns[] = (new column(
            'unitprice',
            new lang_string('booking_unitprice', 'local_coursebooking'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_FLOAT)
            ->add_fields("{$alias}.unitprice")
            ->set_is_sortable(true);

        $columns[] = (new column(
            'totalprice',
            new lang_string('booking_totalprice', 'local_coursebooking'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_FLOAT)
            ->add_fields("{$alias}.totalprice")
            ->set_is_sortable(true);

        $columns[] = (new column(
            'timecreated',
            new lang_string('booking_timecreated', 'local_coursebooking'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_fields("{$alias}.timecreated")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate']);

        return $columns;
    }

    /**
     * Return list of all available filters.
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $alias = $this->get_table_alias('local_coursebooking_book');

        $filters = [];

        $filters[] = (new filter(
            text::class,
            'reference',
            new lang_string('booking_reference', 'local_coursebooking'),
            $this->get_entity_name(),
            "{$alias}.reference"
        ))->add_joins($this->get_joins());

        $filters[] = (new filter(
            select::class,
            'bookingtype',
            new lang_string('booking_type', 'local_coursebooking'),
            $this->get_entity_name(),
            "{$alias}.bookingtype"
        ))
            ->add_joins($this->get_joins())
            ->set_options($this->get_type_options());

        $filters[] = (new filter(
            select::class,
            'status',
            new lang_string('booking_status', 'local_coursebooking'),
            $this->get_entity_name(),
            "{$alias}.status"
        ))
            ->add_joins($this->get_joins())
            ->set_options($this->get_status_options());

        $filters[] = (new filter(
            text::class,
            'contactemail',
            new lang_string('booking_contactemail', 'local_coursebooking'),
            $this->get_entity_name(),
            "{$alias}.contactemail"
        ))->add_joins($this->get_joins());

        $filters[] = (new filter(
            number::class,
            'places',
            new lang_string('booking_places', 'local_coursebooking'),
            $this->get_entity_name(),
            "{$alias}.places"
        ))->add_joins($this->get_joins());

        $filters[] = (new filter(
            date::class,
            'timecreated',
            new lang_string('booking_timecreated', 'local_coursebooking'),
            $this->get_entity_name(),
            "{$alias}.timecreated"
        ))->add_joins($this->get_joins());

        return $filters;
    }
}
